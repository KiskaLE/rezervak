<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use App\Modules\Payments;
use App\Modules\Mailer;
use Nette\DI\Attributes\Inject;



final class PaymentPresenter extends BasePresenter
{

    #[Inject] public Nette\Database\Explorer $database;

    private $user;
    public function __construct(
        private Payments $payments,
        private Mailer $mailer
    )
    {
    }

    public function actionDefault($id)
    {
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->template->reservation = $reservation;
        $service = $reservation->ref("services", "service_id");
        $this->template->service = $service;
        $this->user = $this->database->table("users")->order("created_at ASC")->order("created_at ASC")->fetch();
        $this->template->user = $this->user;
        if ($reservation) {
            $this->verify($reservation, $id, "reservations");
            if ($reservation->status != "VERIFIED") {
                $this->redirect("Payment:notFound");
            }
            $payment = $this->database->table("payments")->where("reservation_id=?", $reservation->id)->fetch();
            $this->template->reservation = $reservation;
            $this->template->payment = $payment;
            $qrCode = $this->payments->generatePaymentCode($payment, $this->user->id);
            $this->template->userSettings = $this->database->table("settings")->fetch();
            $this->template->qrCode = $qrCode;

        } else {
            $this->redirect("Payment:notFound");
        }

    }

    public function actionBackup($id)
    {
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->user = $this->database->table("users")->order("created_at ASC")->fetch();
        $this->verify($reservation, $id, "reservations");
        if ($reservation->status != "VERIFIED") {
            $this->redirect("Payment:notFound");
        }
        $this->template->service = $reservation;

    }

    private function confirm($uuid, $table): void
    {
        $reservation = $this->database->table($table)->where("uuid=?", $uuid)->fetch();
        $user = $this->database->table("users")->order("created_at ASC")->fetch();
        try {
            $reservation->update([
                "status" => "VERIFIED"
            ]);

        } catch (\Throwable $e) {
        }
        $this->mailer->sendNewReservationMail($user->email, $reservation);
    }

    private function verify($reservation, $uuid, $table)
    {
        $user_settings = $this->database->table("settings")->fetch();
        $time = $user_settings->verification_time;
        $isLate = strtotime(strval($reservation->created_at)) < strtotime(date("Y-m-d H:i:s") . ' -' . $time . ' minutes');
        //confirm reservation
        if ($reservation->status == "UNVERIFIED" && !$isLate) {
            $this->confirm($uuid, $table);
            $this->redirect("this");
        }
    }

}