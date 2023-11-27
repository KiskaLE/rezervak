<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use App\Modules\Moment;


final class HomePresenter extends SecurePresenter
{

    public function __construct(
        private Nette\Database\Explorer $database,
        private Moment $moment,
    )
    {
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->moment->getTimezoneTimeFromUTCTime($this->moment->getUTCTime("now", "Africa/Cairo"), "Europe/Prague");

    }

    public function renderDefault()
    {
        $numberOfReservationsToday = $this->database->table("reservations")->where("start=? AND user_id=? AND status='VERIFIED' AND type=0", [date("Y-m-d"), $this->user->id])->count();
        $this->template->numberOfReservationsToday = $numberOfReservationsToday;

        $numberOfReservations = $this->database->table("reservations")->where("start>=? AND user_id=? AND status='VERIFIED' AND type=0", [date("Y-m-d"), $this->user->id])->count();
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
        $reservations = $this->database->table("reservations")->where(" status='VERIFIED' AND user_id=? AND type=0", $this->user->id)->fetchAll();
        $sortedArray = [];
        foreach ($reservations as $row) {
            $date = strval($row->start);
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
