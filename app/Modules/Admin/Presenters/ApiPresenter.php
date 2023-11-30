<?php

namespace App\Modules\Admin\Presenters;

use Nette;
use Nette\Application\UI\Presenter;
use App\Modules\Mailer;
use App\Modules\AvailableDates;
use App\Modules\Payments;

class ApiPresenter extends BasePresenter
{


    public function __construct(
        private Nette\Database\Explorer $database,
        private Mailer     $mailer,
        private AvailableDates $availableDates,
        private Payments $payments

    )
    {
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
        $admins = $this->database->table("users")
            ->where("role=?", "ADMIN")
            ->fetchAll();
        foreach ($admins as $admin) {
            $database->transaction(function ($database) use ($admin) {
                $settings = $admin->related("settings")->fetch();
                $yesterday = date("Y-m-d H:i:s", strtotime("-" . $settings->time_to_pay . " hours"));
                //$yesterday = date("Y-m-d H:i:s", strtotime("-2" . " minutes"));
                $database->query("DELETE FROM reservations_delated WHERE 1;");
                $database->query("INSERT INTO reservations_delated SELECT reservations.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.type = 0 AND reservations.updated_at < '$yesterday' AND reservations.user_id = '$admin->id';");
                $database->query("DELETE reservations.*, payments.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.type = 0 AND reservations.updated_at < '$yesterday' AND reservations.user_id = '$admin->id';");
                //get all canceled reservations
                $canceledReservations = $database->table("reservations_delated")->fetchAll();
                foreach ($canceledReservations as $reservation) {
                    $this->mailer->sendCancelationMail($reservation->email);

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
                if ($this->availableDates->isTimeAvailable($admin->uuid,$backup->start, $duration, intval($backup->service_id))) {
                    dump("tst");
                    $database->transaction(function ($database) use ($backup) {
                        $database->table("reservations")->where("id=?", $backup->id)->update(["type" => 0]);
                        //update reservation
                        $database->table("reservations")->where("id=?", $backup->id)->update(["updated_at" => date("Y-m-d H:i:s")]);
                        $this->payments->updateTime($backup->id);
                        $this->mailer->sendConfirmationMail($backup->email, "/payment/?uuid=" . $backup->uuid);
                        dump("odeslano");
                    });

                }
            }
        }


        die("OK");
    }

}