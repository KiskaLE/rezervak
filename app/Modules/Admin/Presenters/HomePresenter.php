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

    public function renderDefault()
    {
        $numberOfReservationsToday = $this->database->table("reservations")->where("date=?", date("Y-m-d"))->count();
        $this->template->numberOfReservationsToday = $numberOfReservationsToday;

        $numberOfReservations = $this->database->table("reservations")->where("date>=? AND status='VERIFIED'", date("Y-m-d"))->count();
        $this->template->numberOfReservations = $numberOfReservations;
    }

    public function actionDefault($run)
    {
        if ($this->isAjax()) {
            $this->getChartData();

        }
        $this->payload->postGet = true;
        $this->payload->url = $this->link("Home:default");
    }

    private function getChartData()
    {
        $reservations = $this->database->table("reservations")->where(" status='VERIFIED'")->fetchAll();
        $sortedArray = [];
        foreach ($reservations as $row) {
            $date = strval($row->date);
            $sortedArray[$date][] = $date;
        }
        $data = [];
        foreach ($sortedArray as $key) {
            $date = date("d.m.Y", strtotime(explode(" ", $key[0])[0]));
            $data[] = ["date"=> $date,"value" => count($key)];
        }
        $this->sendJson($data);


    }


}
