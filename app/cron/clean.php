#!/usr/bin/env php
<?php

namespace App\Cron;

use App;

require __DIR__ . '/../../vendor/autoload.php';

# Necháme konfigurátor, aby nám sestavil DI kontejner
$container = App\Bootstrap::bootForCron()->createContainer();

# Z kontejneru si nyní můžeme vytahat služby, které potřebujeme
$database = $container->getByType(\Nette\Database\Connection::class);
$mailer = $container->getByType(\App\Modules\Mailer::class);
# Kousek kódu, který chceme vykonat
$database->connect("mysql:host=127.0.0.1;dbname=rezervak", "root", "");

//get all admins
$admins = $database->query("SELECT users.* , settings.id as id_settings, settings.sample_rate, settings.payment_info, settings.verification_time, settings.number_of_days, settings.time_to_pay FROM users LEFT JOIN settings ON users.settings_id = settings.id WHERE role='ADMIN'")->fetchAll();
dump($admins);
foreach ($admins as $admin) {
    $yesterday = date("Y-m-d H:i:s", strtotime("-" . $admin->time_to_pay . " hours"));
    //delete unpaid reservations older than 24 hours
    $database->query("
    DELETE FROM reservations_delated WHERE 1;
    INSERT INTO reservations_delated SELECT reservations.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday' AND reservations.user.id = '$admin->id';
    DELETE reservations.*, payments.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday' AND reservations.user.id = '$admin->id';
    ");

    #  DELETE reservations.*, payments.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday';
//Send mail to customer that reservation was canceled do to unpaid reservation
//get all canceled reservations
    $reservations = $database->query("SELECT * FROM reservations_delated WHERE user_id = '$admin->id'")->fetchAll();

    foreach ($reservations as $reservation) {
        $mailer->sendCancelationMail($reservation->email);
    }

    // check if any backup reservation can be booked into reservations table
    $backups = $database->query("SELECT * FROM reservations WHERE status='VERIFIED' AND user_id = '$admin->id' AND status=1 ORDER BY created_at ASC");


    $verificationTime = date("Y-m-d H:i:s", strtotime("-" . $admin->verification_time . " minutes"));
    foreach ($backups as $backup) {
        if (($database->query("SELECT * FROM reservations WHERE date='$backup->date' AND start='$backup->start' AND created_at > '$verificationTime' AND user_id='$admin->id'")->fetch())) {
            continue;
        };
        $status = $database->transaction(function ($database) use ($backup) {
            // insert into table
            $database->query("
                INSERT INTO reservations (`uuid`,`user_id` ,`date`,`service_id`,`start`,`firstname`,`lastname`,`email`,`phone`,`address`,`code`,`city`,`status`,`payment_id`) SELECT `uuid`,`user_id`, `date`, `service_id`, `start`, `firstname`, `lastname`, `email`, `phone`, `address`, `code`, `city`, `status`, `payment_id` FROM backup_reservations WHERE id=?;
            ", $backup->id);
            $id = $database->getInsertId();
            $database->query("DELETE FROM backup_reservations WHERE id=?;", $backup->id);
            $reservation = $database->query("SELECT * FROM reservations WHERE id=?", $id)->fetch();
            $service = $database->query("SELECT * FROM services WHERE id=?", $reservation->service_id)->fetch();
            //create payment
            $database->query("INSERT INTO payments (`reservation_id`,`price`) VALUES ($id,$service->price)");
            if ($database->query("SELECT * FROM payments WHERE reservation_id=?", $id)) {
                return true;
            }

            return false;

        });
        if ($status) {
            //send mail  to customer
            $mailer->sendConfirmationMail($reservation->email, "/payment/?uuid=" . $reservation->uuid);
        }


    }
}



