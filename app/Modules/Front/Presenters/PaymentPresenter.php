<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use App\Modules\Payments;



final class PaymentPresenter extends BasePresenter
{

    private $user;
    public function __construct(
        private Nette\Database\Explorer $database,
        private Payments $payments
    )
    {
        parent::__construct();
    }

    public function actionDefault($uuid)
    {
        $reservation = $this->database->table("reservations")->where("uuid=?", $uuid)->fetch();
        $this->user = $reservation->ref("users", "user_id");
        if ($reservation) {
            $this->verify($reservation, $uuid, "reservations");
            if ($reservation->status != "VERIFIED") {
                $this->redirect("Payment:notFound");
            }
            $payment = $this->database->table("payments")->where("reservation_id=?", $reservation->id)->fetch();
            $this->template->service = $reservation;
            $this->template->payment = $payment;
            $qrCode = $this->payments->generatePaymentCode($payment, $this->user->id);
            $user = $reservation->ref("users", "user_id");
            $this->template->userSettings = $user->related("settings")->fetch();
            $this->template->qrCode = $qrCode;

        } else {
            $this->redirect("Payment:notFound");
        }

    }

    public function actionBackup($uuid)
    {
        $reservation = $this->database->table("reservations")->where("uuid=?", $uuid)->fetch();
        $this->user = $reservation->ref("users", "user_id");
        $this->verify($reservation, $uuid, "reservations");
        if ($reservation->status != "VERIFIED") {
            $this->redirect("Payment:notFound");
        }
        $this->template->service = $reservation;

    }

    private function confirm($uuid, $table): void
    {
        try {
            $this->database->table($table)->where("uuid=?", $uuid)->update([
                "status" => "VERIFIED"
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function verify($reservation, $uuid, $table)
    {
        $user_settings = $this->user->related("settings")->fetch();
        $time = $user_settings->verification_time;
        $isLate = strtotime(strval($reservation->created_at)) < strtotime(date("Y-m-d H:i:s") . ' -' . $time . ' minutes');
        //confirm reservation
        if ($reservation->status == "UNVERIFIED" && !$isLate) {
            $this->confirm($uuid, $table);
            $this->redirect("this");
        }
    }

}