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
        $service = $this->database->table("registereddates")->where("uuid=?", $uuid)->fetch();
        $this->template->service = $service;
        //TODO set time in admin settings
        $time = 15;
        $isLate = strtotime(strval($service->created_at)) < strtotime(date("Y-m-d H:i:s"). ' -'.$time.' minutes');
        //confirm reservation
        if ($service->status == "UNVERIFIED" && !$isLate) {
            $this->confirm($uuid, $service);
            $this->redirect("this");
        }

        $payments = $this->database->table("payments")->where("registereddate_id=?", $service->id)->fetchAll();
        foreach ($payments as $payment) {
            $this->paymentsHelper->test();
        }
        $this->template->payments = $payments;
    }

    private function confirm($uuid, $service) {
        $status = $this->database->table("registereddates")->where("uuid=?", $uuid)->update([
            "status" => "VERIFIED"
        ]);
        //create payment table row
        $this->database->table("payments")->insert([
            "price" => "123",
            "registereddate_id" => $service->id
        ]);
    }

}