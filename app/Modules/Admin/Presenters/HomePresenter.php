<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use App\Modules\Moment;
use App\Modules\Payments;
use Nette\DI\Attributes\Inject;

final class HomePresenter extends SecurePresenter
{
    //inject database

    #[Inject] public Nette\Database\Explorer $database;
    public function __construct(
        private Moment $moment,
    )
    {
    }

    public function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->selectedPage = "dashboard";

    }

    public function renderDefault(int $page = 1): void
    {
        $numberOfReservationsToday = $this->database->table("reservations")->where("start=? AND user_id=? AND status='VERIFIED' AND type=0", [date("Y-m-d"), $this->user->id])->count();
        $this->template->numberOfReservationsToday = $numberOfReservationsToday;

        $numberOfReservations = $this->database->table("reservations")->where("start>=? AND user_id=? AND status='VERIFIED' AND type=0", [date("Y-m-d"), $this->user->id])->count();
        $this->template->numberOfReservations = $numberOfReservations;

        //Table with today reservations
        $q = $this->database->table("reservations")
        ->where("start>=? AND start<? AND user_id=? AND status='VERIFIED' AND type=0", [date("Y-m-d"), date("Y-m-d") . " 23:59:59", $this->user->id])
        ->order("start ASC");
        $numberOfTodaysReservations = $q->count();
        $paginator = $this->createPagitator($numberOfTodaysReservations, $page, 4);
        $todayReservations = $q
            ->limit($paginator->getLength(), $paginator->getOffset())
            ->fetchAll();
        $this->template->todayReservations = $todayReservations;
        $this->template->paginator = $paginator;

        //info-cards
        $futureReservations = $this->database->table("reservations")->where("start>? AND user_id=? AND reservations.status='VERIFIED' AND :payments.status=1 AND reservations.type=0", [date("Y-m-d"), $this->user->id])->count();
        $this->template->futureReservations = $futureReservations;
        $doneReservations = $this->database->table("reservations")->where("start<? AND user_id=? AND reservations.status='VERIFIED' AND :payments.status=1 AND reservations.type=0", [date("Y-m-d H:i:s") ,$this->user->id])->count();
        $this->template->doneReservations = $doneReservations;
        $sales = $this->database->table("payments")->where("status=1")->sum("price");
        $this->template->sales = $sales;
        $unpaidReservations = $this->database->table("reservations")->where("user_id=? AND reservations.status='VERIFIED' AND :payments.status=0 AND reservations.type=0", $this->user->id)->count();
        $this->template->unpaidReservations = $unpaidReservations;
    }

    public function actionDefault(): void
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
            $data[] = ["date" => $date, "value" => count($key)];
        }
        $this->sendJson($data);


    }


    public function handleCancel($reservationId) {
        $isSuccess = true;
        try {
            $res = $this->database->table('reservations')->where('id=?', $reservationId)->update([
                'status' => 'CANCELED',
            ]);
            if (!$res) {
                $isSuccess = false;
            }
        } catch (\Throwable $th) {
            $isSuccess = false;
        }
        if ($isSuccess) {
            $this->flashMessage("Rezervace byla zrušena", "success");
            $reservation = $this->database->table('reservations')->where('id=?', $reservationId)->fetch();
            $this->mailer->sendCancelationMail($reservation->email, $reservation, "Zrušeno správcem");
            $this->redirect('Reservations:');
        }
        $this->flashMessage("Rezervaci se nepodařilo zrušit", "error");

    }

    public function handleUpcommingReservations($tab) {
        $session = $this->getSession("reservations");
        $session->reservations_tab = $tab;
        $this->redirect('Reservations:default');

    }

    public function handleSetPaid($id) {
        $isSuccess = true;
        try {
            $res = $this->database->table('payments')->where('reservation_id=?', $id)->update([
                'status' => '1',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if (!$res) {
                $isSuccess = false;
            }
        } catch (\Throwable $th) {
            $isSuccess = false;
        }
        if ($isSuccess) {
            $this->flashMessage("Rezervace byla zaplacena", "success");
            $this->redirect('this');
        }
        $this->flashMessage("Nepovedlo se zaplatit", "error");
    }


}
