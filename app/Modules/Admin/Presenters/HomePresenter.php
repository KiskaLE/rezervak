<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use App\Modules\Moment;
use App\Modules\Mailer;
use Nette\DI\Attributes\Inject;

final class HomePresenter extends SecurePresenter
{
    //inject database

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
        private Moment $moment,
        private Mailer $mailer
    ) {
    }

    public function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->selectedPage = "dashboard";
    }

    public function renderDefault(int $page = 1): void
    {
        $session = $this->getSession("home");
        $tab = $session->reservations_tab ?? 0;
        $this->template->tab = $tab;

        //get number of reservations for each tabs
        $numberOfReservationsToday = $this->database->table("reservations")->where("start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [date("Y-m-d") . " 00:00:00", date("Y-m-d") . " 23:59:59"])->count();
        $numberOfReservationsTomorow = $this->database->table("reservations")->where("start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [date("Y-m-d", strtotime(date("Y-m-d") . "+1 day")) . " 00:00:00", date("Y-m-d", strtotime(date("Y-m-d") . "+1 day")) . " 23:59:59"])->count();
        $numberOfReservationsAfterTomorow = $this->database->table("reservations")->where("start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [date("Y-m-d", strtotime(date("Y-m-d") . "+2 day")) . " 00:00:00", date("Y-m-d", strtotime(date("Y-m-d") . "+2 day")) . " 23:59:59"])->count();
        $this->template->numberOfReservationsToday = $numberOfReservationsToday;
        $this->template->numberOfReservationsTomorow = $numberOfReservationsTomorow;
        $this->template->numberOfReservationsAfterTomorow = $numberOfReservationsAfterTomorow;

        //get number of reservations for selected tab
        $q = $this->database->table("reservations")->where("status='VERIFIED' AND type=0");
        switch ($tab) {
            case 0:
                $q = $q->where("start BETWEEN ? AND ?", [date("Y-m-d") . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
                break;
            case 1:
                $q = $q->where("start BETWEEN ? AND ?", [date("Y-m-d", strtotime(date("Y-m-d") . "+1 day")) . " 00:00:00", date("Y-m-d", strtotime(date("Y-m-d") . "+1 day")) . " 23:59:59"]);
                break;
            case 2:
                $q = $q->where("start BETWEEN ? AND ?", [date("Y-m-d", strtotime(date("Y-m-d") . "+2 day")) . " 00:00:00", date("Y-m-d", strtotime(date("Y-m-d") . "+2 day")) . " 23:59:59"]);
                break;
        }
        $numberOfTodaysReservations = $q->count();
        $paginator = $this->createPagitator($numberOfTodaysReservations, $page, 10);

        $reservations = $q
            ->limit($paginator->getLength(), $paginator->getOffset())
            ->fetchAll();
        $this->template->reservations = $reservations;
        $this->template->paginator = $paginator;

        //info-cards
        $futureReservations = $this->database->table("reservations")->where("start>? AND reservations.status='VERIFIED' AND :payments.status=1 AND reservations.type=0", date("Y-m-d"))->count();
        $this->template->futureReservations = $futureReservations;
        $doneReservations = $this->database->table("reservations")->where("start<? AND reservations.status='VERIFIED' AND :payments.status=1 AND reservations.type=0", date("Y-m-d H:i:s"))->count();
        $this->template->doneReservations = $doneReservations;
        $sales = $this->database->table("payments")->where("status=1")->sum("price");
        $this->template->sales = $sales;
        $unpaidReservations = $this->database->table("reservations")->where("reservations.status='VERIFIED' AND :payments.status=0 AND reservations.type=0")->count();
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


    public function handleCancel($reservationId)
    {
        $isSuccess = true;
        $reservation = $this->database->table('reservations')->where('id=?', $reservationId)->fetch();
        try {
            $res = $reservation->update([
                'status' => 'CANCELED',
            ]);
            if (!$res) {
                $isSuccess = false;
            }
        } catch (\Throwable $th) {
            $isSuccess = false;
        }
        if ($isSuccess) {
            $this->mailer->sendCancelationMail($reservation->email, $reservation, "Zrušeno správcem");
            $this->flashMessage("Rezervace byla zrušena", "success");
            $reservation = $this->database->table('reservations')->where('id=?', $reservationId)->fetch();
            $this->mailer->sendCancelationMail($reservation->email, $reservation, "Zrušeno správcem");
            $this->redirect('this');
        }
        $this->flashMessage("Rezervaci se nepodařilo zrušit", "error");
    }

    public function handleSetPaid($id)
    {
        $isSuccess = true;
        $payment = $this->database->table('payments')->where('reservation_id=?', $id)->fetch();
        $reservation = $payment->ref('reservations', "reservation_id");
        try {
            $res = $payment->update([
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
            $this->mailer->sendPaymentConfirmationMail($reservation->email, $reservation, $payment);
            $this->flashMessage("Rezervace byla zaplacena", "success");
            $this->redirect('Reservations:');
        }
        $this->flashMessage("Nepovedlo se zaplatit", "error");
    }

    public function handleUpcommingReservations($tab)
    {
        $session = $this->getSession("reservations");
        $session->reservations_tab = $tab;
        $this->redirect('Reservations:default');
    }

    public function handleSetTab($tab)
    {

        $session = $this->getSession("home");

        $session->reservations_tab = $tab;
        $this->redirect('default');
    }
}
