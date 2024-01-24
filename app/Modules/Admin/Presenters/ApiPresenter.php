<?php

namespace App\Modules\Admin\Presenters;

use Nette;
use App\Modules\Mailer;
use App\Modules\AvailableDates;
use App\Modules\Payments;
use App\Modules\Constants;
use GuzzleHttp\Client;
use Nette\DI\Attributes\Inject;

class ApiPresenter extends BasePresenter
{

    private $client;
    #[Inject] public Nette\Database\Explorer $database;

    #[Inject] public Constants $constants;

    public function __construct(
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
            $settings = $database->table("settings")->fetch();
            $database->transaction(function ($database) use ($settings) {
                $yesterday = date("Y-m-d H:i:s", strtotime("-" . $settings->time_to_pay . " hours"));
                //$yesterday = date("Y-m-d H:i:s", strtotime("-2" . " minutes"));
                $database->query("DELETE FROM reservations_canceled WHERE 1;");
                $database->query("INSERT INTO reservations_canceled SELECT reservations.* FROM reservations LEFT JOIN payments ON reservations.id=payments.reservation_id WHERE payments.status=0 AND reservations.updated_at < '$yesterday'  AND reservations.status = 'VERIFIED';");
                $database->query("UPDATE reservations JOIN payments ON reservations.id=payments.reservation_id SET reservations.status = 'CANCELED' WHERE payments.status=0 AND reservations.updated_at < '$yesterday' AND reservations.status = 'VERIFIED';");
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
                ->where("type=? AND status=?", [1, "VERIFIED"])
                ->order("created_at ASC")
                ->fetchAll();
            foreach ($backups as $backup) {
                $service = $backup->ref("services", "service_id");
                if ($this->availableDates->isTimeAvailable($backup, $service) && $this->availableDates->isTimeToPay($backup->start, $settings->time_to_pay)) {
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


        die("OK");
    }

    public function actionNotify() {
        $userSettings = $this->database->table("settings")->fetch();
        if ($userSettings->notify_time > 0) {
        $notifyTime = date("Y-m-d H:i:s", strtotime("+" . $userSettings->notify_time . " minutes"));
        $reservationsToNotify = $this->database->table("reservations")
        ->select("reservations.*")
        ->where("reservations.status='VERIFIED' AND reservations.notified=0 AND reservations.type=0 AND :payments.status=1 AND reservations.start BETWEEN NOW() AND ?" , $notifyTime)
        ->fetchAll();
        if ($reservationsToNotify) {
            foreach ($reservationsToNotify as $reservation) {
                $this->mailer->sendNotifyMail($reservation->email, $reservation);
                $reservation->update(["updated_at" => date("Y-m-d H:i:s"), "notified" => 1]);
                dump("odeslano");
            }
        }
        }

        $this->database->table("crons")->where("name LIKE ?", "notify")->update(["run_at" => date("Y-m-d H:i:s")]);


        die("end");
    }

    public function actionPaymentsCheck() {
        
        function getValueByName($transaction, $name) {
            foreach ($transaction as $column) {
                $attributes = $column->attributes();
                if ((string)$attributes['name'] === $name) {
                    return (string)$column;
                }
            }
            return "";
        }
        function getElementByName($transactions, $name, $value) {
            foreach ($transactions as $column) {
                foreach ($column as $item) {
                    $attributes = $item->attributes();
                    if ((string)$attributes['name'] === $name && (string)$item == $value) {
                        return $column;
                    }
                }
            }
            return [];
        }

         $token = $this->constants->constants["FIO_TOKEN"];
         $cron = $this->database->table("crons")->where("name LIKE ?", "payments_check")->fetch();
         $from = date("Y-m-d", strtotime($cron->run_at));
         $now = date("Y-m-d");
         $url = "https://www.fio.cz/ib_api/rest/periods/{token}/".$from."/".$now."/transactions.xml";
         //TODO mockup
         $filePath = "./../temp/test.xml";
         $xml = simplexml_load_file($filePath) or die("Error: Cannot create object");
         $transactions = $xml->TransactionList->Transaction;
 
         $payments = $this->database->table("payments")->where("status=0")->fetchAll();
         foreach ($payments as $payment) {
            $paymentVs = $payment->id_transaction;
            if ($transaction = getElementByName($transactions, "VS", $paymentVs)) {
                $transactionValue = getValueByName($transaction, "Objem");
                $transactionCurrency = getValueByName($transaction, "Měna");
                if ($transactionCurrency != "CZK") {
                    continue;
                }
                if ($transactionValue >= $payment->price) {
                    $this->database->table("payments")->where("id=?", $payment->id)->update(["status" => 1, "updated_at" => date("Y-m-d H:i:s")]);
                }
            }
         }
        
        $cron->update(["run_at" => date("Y-m-d H:i:s")]);
        die("check");
    }

}