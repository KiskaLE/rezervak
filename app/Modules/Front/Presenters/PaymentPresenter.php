<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use App\Modules\Payments;


final class PaymentPresenter extends BasePresenter
{
    public function __construct(
        private Nette\Database\Explorer $database,
        private Payments $paymentsHelper
    )
    {
        parent::__construct();

    }

    public function actionDefault($uuid) {
        $reservation = $this->database->table("registereddates")->where("uuid=?", $uuid)->fetch();
        $this->template->service = $reservation;
        //TODO set time in admin settings
        $time = 15;
        $isLate = strtotime(strval($reservation->created_at)) < strtotime(date("Y-m-d H:i:s"). ' -'.$time.' minutes');
        //confirm reservation
        if ($reservation->status == "UNVERIFIED" && !$isLate) {
            $this->confirm($uuid, $reservation, "registereddates");
            $this->redirect("this");
        }

        $payments = $this->database->table("payments")->where("registereddate_id=?", $reservation->id)->fetchAll();
        foreach ($payments as $payment) {
            $this->paymentsHelper->test();
        }
        $this->template->payments = $payments;
    }

    public function actionBackup($uuid) {
        $reservation = $this->database->table("backup_reservations")->where("uuid=?", $uuid)->fetch();
        $this->template->service = $reservation;
        //TODO set time in admin settings
        $time = 15;
        $isLate = strtotime(strval($reservation->created_at)) < strtotime(date("Y-m-d H:i:s"). ' -'.$time.' minutes');
        if ($reservation->status == "UNVERIFIED" && !$isLate) {
            $this->confirm($uuid, $reservation, "backup_reservations");
            $this->redirect("this");
        }

    }

    private function confirm($uuid, $service, $table) {
        $status = $this->database->table($table)->where("uuid=?", $uuid)->update([
            "status" => "VERIFIED"
        ]);
        //create payment table row
        $this->database->table("payments")->insert([
            "price" => "123",
            "registereddate_id" => $service->id
        ]);
    }

}