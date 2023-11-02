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

//TODO change to 24 hours
$yesterday = date("Y-m-d H:i:s", strtotime("-1 minutes"));
//delete unpaid reservations older than 24 hours
$database->query("
    DELETE FROM reservations_delated WHERE 1;
    INSERT INTO reservations_delated SELECT reservations.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday';
    DELETE reservations.*, payments.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday';
    ");
#  DELETE reservations.*, payments.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday';
//Send mail to customer that reservation was canceled do to unpaid reservation
//get all canceled reservations
$reservations = $database->query("SELECT * FROM reservations_delated WHERE 1");

foreach ($reservations as $reservation) {
    $mailer->sendCancelationMail($reservation->email);
}
// check if any backup reservation can be booked into reservations table
$backups = $database->query("SELECT * FROM backup_reservations WHERE status='VERIFIED' ORDER BY created_at ASC");
//TODO set by user settings
$verificationTime = date("Y-m-d H:i:s", strtotime("-1 minutes"));
foreach ($backups as $backup) {
    //get reservations that is pending to be verified
    if (($database->query("SELECT * FROM reservations WHERE date='$backup->date' AND start='$backup->start' AND created_at > '$verificationTime'")->fetch())) {
        continue;
    };
    if ($database->query("SELECT * FROM reservations WHERE date='$backup->date' AND start='$backup->start' AND status='VERIFIED'")->fetch()) {
        continue;
    }
    //get reservation that is verified
    // insert into table
    $database->query("
            INSERT INTO reservations (`uuid`,`date`,`service_id`,`start`,`firstname`,`lastname`,`email`,`phone`,`address`,`code`,`city`,`status`,`payment_id`) SELECT `uuid`, `date`, `service_id`, `start`, `firstname`, `lastname`, `email`, `phone`, `address`, `code`, `city`, `status`, `payment_id` FROM backup_reservations WHERE id=$backup->id;
        ");
    $id = $database->getInsertId();
    $database->query("DELETE FROM backup_reservations WHERE id=$backup->id;");
    //create payment
    $reservation = $database->query("SELECT * FROM reservations WHERE id=$id")->fetch();
    $service = $database->query("SELECT * FROM services WHERE id=$reservation->service_id")->fetch();
    $database->query("INSERT INTO payments (`reservation_id`,`price`) VALUES ($id,$service->price)");
    //send mail  to customer
    $mailer->sendConfirmationMail($reservation->email, "/payment/?uuid=" . $reservation->uuid);
}
