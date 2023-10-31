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

// clean unverified reservations older than 1 day from database
$yesterday = date("Y-m-d H:i:s", strtotime("-2 minutes"));
$database->query("DELETE FROM reservations WHERE status='UNVERIFIED' AND created_at < '$yesterday'");
//delete unpaid reservations older than 24 hours
$database->query("
    DELETE FROM reservations_delated WHERE 1;

    INSERT INTO reservations_delated SELECT reservations.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday';

    ");
#  DELETE reservations.*, payments.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.created_at < '$yesterday';
//TODO Send mail to customer that reservation was canceled do to unpaid reservation
//get all canceled reservations
$reservations = $database->query("SELECT * FROM reservations_delated WHERE 1");

foreach ($reservations as $reservation) {
    #$mailer->sendCancelationMail($reservation->email);
    //TODO Send mail to backup reservation
    //copy backup reservation into reservations table
    $service_id = $reservation->service_id;
    $date = strval($reservation->date);
    $start = $reservation->start;

    $database->query("INSERT INTO reservations (`uuid`,`date`,`service_id`,`start`,`firstname`,`lastname`,`email`,`phone`,`address`,`code`,`city`,`status`,`payment_id`) SELECT `uuid`, `date`, `service_id`, `start`, `firstname`, `lastname`, `email`, `phone`, `address`, `code`, `city`, `status`, `payment_id` FROM backup_reservations WHERE date='$date' AND start='$start';");
    $id = $database->getInsertId();
    if ($id != 0) {
        $reservation = $database->query("SELECT * FROM reservations WHERE id=$id")->fetch();
        if ($reservation) {
            //create payment
            $database->fetch("INSERT INTO payments (`reservation_id`,`price`) VALUES ($id,0)");
            $mailer->sendConfirmationMail("vojtech.kylar@securitynet.cz", "/payment/?uuid=".$reservation->uuid);
        }
    }



    //send mail  to customer
}


