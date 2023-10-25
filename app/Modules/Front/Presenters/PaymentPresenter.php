<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;


final class PaymentPresenter extends BasePresenter
{
    public function __construct(
        private Nette\Database\Explorer $database,
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

        bdump($isLate);
        //confirm reservation
        if ($service->status == "UNVERIFIED" && !$isLate) {
            $this->database->table("registereddates")->where("uuid=?", $uuid)->update([
                "status" => "VERIFIED"
            ]);
        }

        bdump($service->created_at);
    }

}