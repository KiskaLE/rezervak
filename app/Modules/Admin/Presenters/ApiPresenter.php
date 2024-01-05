<?php

namespace App\Modules\Admin\Presenters;

use Nette;
use App\Modules\Mailer;
use App\Modules\AvailableDates;
use App\Modules\Payments;
use GuzzleHttp\Client;

class ApiPresenter extends BasePresenter
{

    private $client;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Mailer         $mailer,
        private AvailableDates $availableDates,
        private Payments       $payments

    )
    {
        $this->client = new Client();
    }

    public function renderDefault()
    {
    }

    public function actionVerifyPayments()
    {

        die("verify");
    }

    public function actionClean()
    {
        $database = $this->database;
        $admins = $database->table("users")
        ->where("role=?", "ADMIN")
        ->fetchAll();
        foreach ($admins as $admin) {
            $settings = $admin->related("settings")->fetch();
            $database->transaction(function ($database) use ($admin, $settings) {
                $yesterday = date("Y-m-d H:i:s", strtotime("-" . $settings->time_to_pay . " hours"));
                //$yesterday = date("Y-m-d H:i:s", strtotime("-2" . " minutes"));
                $database->query("DELETE FROM reservations_canceled WHERE 1;");
                $database->query("INSERT INTO reservations_canceled SELECT reservations.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.updated_at < '$yesterday' AND reservations.user_id = '$admin->id' AND reservations.status = 'VERIFIED';");
                $database->query("UPDATE reservations JOIN payments ON reservations.id=payments.reservation_id SET reservations.status = 'CANCELED' WHERE payments.status=0 AND reservations.updated_at < '$yesterday' AND reservations.user_id = '$admin->id' AND reservations.status = 'VERIFIED';");
                //get all canceled reservations
                $canceledReservations = $database->table("reservations_canceled")->fetchAll();
                foreach ($canceledReservations as $reservation) {
                    $this->mailer->sendCancelationMail($reservation->email, $reservation, "Nezaplacení rezervace v určeném čase.");
                    dump("zrušeno");
                    
                }
            });
            // check if any backup reservation can be booked
            $backups = $database
                ->table("reservations")
                ->where("type=? AND status=? AND user_id =?", [1, "VERIFIED", $admin->id])
                ->order("created_at ASC")
                ->fetchAll();
            foreach ($backups as $backup) {
                $duration = $backup->ref("services", "service_id")->duration;
                $email = $backup->email;
                $uuid = $backup->uuid;
                if ($this->availableDates->isTimeAvailable($admin->uuid, $backup->start, $duration, intval($backup->service_id)) && $this->availableDates->isTimeToPay($backup->start, $settings->time_to_pay)) {
                    $database->transaction(function ($database) use ($backup) {
                        $database->table("reservations")->where("id=?", $backup->id)->update(["type" => 0]);
                        //update reservation
                        $database->table("reservations")->where("id=?", $backup->id)->update(["updated_at" => date("Y-m-d H:i:s")]);
                        $this->payments->updateTime($backup->id);
                        $this->mailer->sendConfirmationMail($backup->email, "/payment/?uuid=" . $backup->uuid, $backup);
                        dump("odeslano");
                    });

                }
            }
        }


        die("OK");
    }

}