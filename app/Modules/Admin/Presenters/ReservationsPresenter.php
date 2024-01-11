<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use App\Modules\Formater;


final class ReservationsPresenter extends SecurePresenter
{

    private $uuid;
    private $reservation;
    private $page;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(

        private Formater $formater
    
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "reservations";

    }

    public function actionDefault(int $page = 1)
    {
        $this->page = $page;

        $session = $this->getSession("reservations");
        //tab
        $tab = $session->reservations_tab ?? 0;
        $this->template->tab = $tab;

        //filter
        $start = $session->filterStart ?? null;
        $end = $session->filterEnd ?? null;
        $this->template->start = $start;
        $this->template->end = $end;

        // $numberOfReservations = $this->database->table('reservations')
        //     ->select('reservations.*',)
        //     ->where('reservations.status=?', 'VERIFIED')
        //     ->where("reservations.type=?", 0)
        //     ->where('user_id=?', $this->user->id)
        //     ->where(':payments.status=?', 1)->count();


        $q = $this->database->table('reservations')
        ->select('reservations.*',)
        ->where('user_id=?', $this->user->id)
        ->where("reservations.type=?", 0);

        if ($start) {
            $q = $q->where('start >= ?', $start);
        }
        if ($end) {
            $q = $q->where('start <= ?', $end);
        }

        switch ($tab) {
            case 0:
                //nadcházející
                $q = $q->where('reservations.status=?', 'VERIFIED')
                    ->where('start >= ?', date('Y-m-d'))
                    ->where(':payments.status=?', 1)->order('start ASC');
                break;
            
            case 1:
                //proběhlé
                $q = $q->where('reservations.status=?', 'VERIFIED')->where('start < ?', date('Y-m-d'))->where(':payments.status=?', 1)->order('start ASC');
                break;
            case 2:
                //nezaplacené
                $q = $q->where('reservations.status=?', 'VERIFIED')->where(':payments.status=?', 0)->order('start ASC');
                break;
            case 3:
                $q = $q->where('reservations.status !=?', 'UNVERIFIED')->order('created_at DESC');
                break;

        }
        $numberOfReservations = $q->count();
        $paginator = $this->createPagitator($numberOfReservations, $page, 5);
        $reservations = $q->limit($paginator->getLength(), $paginator->getOffset())->fetchAll();


        $this->template->reservations = $reservations;
        $this->template->paginator = $paginator;
    }

    public function actionEdit($id, $backlink)
    {
        $this->backlink = $backlink;
        $this->uuid = $id;
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->reservation = $reservation;
        $this->template->reservation = $reservation;
    }

    public function actionDetail($id, $backlink)
    {
        $this->backlink = $backlink;
        $this->uuid = $id;
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->template->reservation = $reservation;
    }

    public function actionDelete($id)
    {
        $this->uuid = $id;
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->template->reservation = $reservation;

    }

    protected function createComponentEditForm(): Form
    {
        $form = new Form;
        $form->addText('firstname')
            ->setDefaultValue($this->reservation->firstname)
            ->setRequired();
        $form->addText('lastname')
            ->setDefaultValue($this->reservation->lastname)
            ->setRequired();
        $form->addText('email')
            ->setDefaultValue($this->reservation->email)
            ->setRequired();
        $form->addText('phone')
            ->setDefaultValue($this->reservation->phone)
            ->setRequired();
        $form->addSubmit('submit', 'Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];

        return $form;
    }

    public function formSucceeded(Form $form, \stdClass $data): void
    {
        $this->database->table('reservations')->where('uuid=?', $this->uuid)->update([
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'email' => $data->email,
            'phone' => $data->phone,
        ]);
        if ($this->backlink) {
            $this->restoreRequest($this->backlink);
        }
        $this->flashMessage("Uloženo", "success");
        $this->redirect('default');
    }

    protected function createComponentDeleteForm(string $name): Form
    {
        $form = new Form;
        $form->addSubmit("submit", "delete");

        $form->onSuccess[] = [$this, "deleteFormSucceeded"];

        return $form;
    }

    public function deleteFormSucceeded(Form $form, \stdClass $data): void
    {
        $this->database->table('reservations')->where('uuid=?', $this->uuid)->delete();
        $this->restoreRequest($this->backlink);
        if ($this->backlink) {
            $this->redirect($this->backlink);
        }
        $this->redirect('Reservations:');
    }

    protected function createComponentSetRangeForm(): Form
    {
        $form = new Form;
        $form->addText("range")->setRequired("Prosím nastavte rozsah");
        $form->addSubmit("submit", "Nastavit");

        $form->onSuccess[] = [$this, "setRangeFormSucceeded"];

        return $form;
    }

    public function setRangeFormSucceeded(Form $form, \stdClass $data): void
    {
        $session = $this->getSession("reservations");
        $range = $this->formater->getDataFromRange($data->range);
        $session->filterStart = $range['start'];
        $session->filterEnd = $range['end']. " 23:59:59";
        $this->redirect('default');
    }


    public function handleSetTab($tab) {
       
        $session = $this->getSession("reservations");
    
        $session->reservations_tab = $tab;
        $this->redirect('default');
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
            $this->redirect('Reservations:');
        }
        $this->flashMessage("Rezervaci se nepodařilo zrušit", "error");

    }

    public function handleDeleteFilter($filter) {
        $session = $this->getSession("reservations");
        switch($filter) {
            case "range":
                $session->filterStart = null;
                $session->filterEnd = null;
            break;
        }

        $this->redirect('default');
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
            $this->redirect('Reservations:');
        }
        $this->flashMessage("Nepovedlo se zaplatit", "error");
    }

}
