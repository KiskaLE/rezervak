<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;


final class HomePresenter extends SecurePresenter
{

    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    public function beforeRender()
    {
        parent::beforeRender();
        bdump($this->user->identity->getData()["name"]);

    }

    public function actionDefault()
    {
        $numberOfReservationsToday = $this->database->table("reservations")->where("date=?", date("Y-m-d"))->count();
        $this->template->numberOfReservationsToday = $numberOfReservationsToday;

        $numberOfReservations = $this->database->table("reservations")->where("date>=? AND status='VERIFIED'", date("Y-m-d"))->count();
        $this->template->numberOfReservations = $numberOfReservations;
    }


}
