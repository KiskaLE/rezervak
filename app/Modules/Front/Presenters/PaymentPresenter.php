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

    public function actionDefault($id) {
        $service = $this->database->table("registereddates")->where("id=?", $id)->fetch();
        $this->template->service = $service;
        bdump($service);
        //confirm reservation
        if ($service->status == "UNVERIFIED") {
            $this->database->table("registereddates")->where("id=?", $id)->update([
                "status" => "VERIFIED"
            ]);
        }
    }

}