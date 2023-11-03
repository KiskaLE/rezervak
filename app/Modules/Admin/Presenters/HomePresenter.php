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

    }

    public function renderDefault() {
        $numberOfReservationsToday = $this->database->table("reservations")->where("date=?", date("Y-m-d"))->count();
        $this->template->numberOfReservationsToday = $numberOfReservationsToday;

        $numberOfReservations = $this->database->table("reservations")->where("date>=? AND status='VERIFIED'", date("Y-m-d"))->count();
        $this->template->numberOfReservations = $numberOfReservations;
}

    public function actionDefault($run)
    {
        if ($this->isAjax()) {
            if ($run == "getChartData") {
                $this->getChartData();
            }
        }
    }

    private function getChartData() {
        $reservations = $this->database->table("reservations")->where("date>=? AND status='VERIFIED'", date("Y-m-d"))->fetchAll();
        bdump($reservations);
        $data = [];
    }


}
