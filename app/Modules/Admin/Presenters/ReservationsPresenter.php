<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use App\Modules\Formater;
use App\Modules\Mailer;

final class ReservationsPresenter extends SecurePresenter
{

    private $uuid;
    private $reservation;
    private $page;
    private $filterServices;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(

        private Formater $formater,
        private Mailer $mailer,

    ) {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "reservations";
    }

    public function actionDefault(int $page = 1, int $tab = 0, string $filterName = "", string $filterVs = "")
    {
        $this->page = $page;

        $session = $this->getSession("reservations");
        $sessionFilter = $this->getSession("reservationsFilter");
        // $session->filterStart = null;
        // $session->filterEnd = null;
        //tab
        $this->tab = $tab;
        $this->template->tab = $tab;

        //filter
        $start = $sessionFilter->filterStart ?? null;
        $end = $sessionFilter->filterEnd ?? null;
        $this->template->start = $start;
        $this->template->end = $end;

        $this->template->filterName = $filterName;
        $this->template->filterVs = $filterVs;

        $filterService = $session->filterService ?? null;
        if ($filterService) {
            $filterServiceName = $this->database->table('services')->get($filterService)->name;
            $this->template->filterService = $filterServiceName;
        } else {
            $this->template->filterService = null;
        }
        $originalQ = $this->database->table('reservations')
            ->select('reservations.*',)
            ->where("reservations.type=?", 0)
            ->where("reservations.firstname LIKE ? AND :payments.id_transaction LIKE ?", ["{$filterName}%", "{$filterVs}%"]);

        if ($start) {
            $originalQ = $originalQ->where('start >= ?', $start);
        }
        if ($end) {
            $originalQ = $originalQ->where('start <= ?', $end);
        }

        if ($filterService) {
            $originalQ = $originalQ->where('service_id = ?', $filterService);
        }

        $this->template->futureCount = (clone $originalQ)
            ->where("reservations.type=?", 0)->where('reservations.status=?', 'VERIFIED')->where('start >= ?', date('Y-m-d'))->where(':payments.status=?', 1)->order('start ASC')->count();
        $this->template->pastCount = (clone $originalQ)
            ->where("reservations.type=?", 0)->where('reservations.status=?', 'VERIFIED')->where('start < ?', date('Y-m-d'))->where(':payments.status=?', 1)->order('start ASC')->count();

        $this->template->unpaidCount = (clone $originalQ)
            ->where("reservations.type=?", 0)->where('reservations.status=?', 'VERIFIED')->where(':payments.status=?', 0)->order('start ASC')->count();

        $this->template->allCount = (clone $originalQ)
            ->where("reservations.type=?", 0)->where('reservations.status !=?', 'UNVERIFIED')->order('created_at DESC')->count();

        switch ($tab) {
            case 0:
                $q = (clone $originalQ)->where('reservations.status !=?', 'UNVERIFIED')->order('created_at DESC');
                break;
            case 1:
                //proběhlé
                $q = (clone $originalQ)->where('reservations.status=?', 'VERIFIED')->where('start < ?', date('Y-m-d'))->where(':payments.status=?', 1)->order('start ASC');
                break;
            case 2:
                //nezaplacené
                $q = (clone $originalQ)->where('reservations.status=?', 'VERIFIED')->where(':payments.status=?', 0)->order('start ASC');
                break;
            case 3:
                //nadcházející
                $q = (clone $originalQ)->where('reservations.status=?', 'VERIFIED')
                    ->where('start >= ?', date('Y-m-d'))
                    ->where(':payments.status=?', 1)->order('start ASC');
                break;
        }

        $numberOfReservations = (clone $q)->count();
        $paginator = $this->createPagitator($numberOfReservations, $page, 10);
        $reservations = (clone $q)->limit($paginator->getLength(), $paginator->getOffset())->fetchAll();

        $this->template->numberOfReservations = $numberOfReservations;
        $this->template->reservations = $reservations;
        $this->template->paginator = $paginator;
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

    protected function createComponentSetFilterForm(): Form
    {
        $this->filterServices = $this->database->table("services")
            ->where("hidden<?", 2)
            ->fetchAll();

        $form = new Form;
        $form->addText("range");
        $form->addSubmit("submit", "Nastavit");
        $form->addSelect("service", "Služba", ['' => 'Vyberte službu'] + array_map(function ($service) {
            return $service->name;
        }, $this->filterServices));

        $form->onSuccess[] = [$this, "setRangeFormSucceeded"];

        return $form;
    }

    public function setRangeFormSucceeded(Form $form, \stdClass $data): void
    {
        $session = $this->getSession("reservations");
        if ($data->range) {
            $range = $this->formater->getDataFromRangeInFormatDMY($data->range);
            $session->filterStart = $range['start'];
            $session->filterEnd = $range['end'] . " 23:59:59";
        }
        if ($data->service) {
            $session->filterService = $data->service;
        }
        $this->redirect('default');
    }


    public function handleSetTab($tab)
    {

        $session = $this->getSession("reservations");

        $session->reservations_tab = $tab;
        $this->redirect('default');
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
            $this->flashMessage("Rezervace byla zrušena", "success");
            $reservation = $this->database->table('reservations')->where('id=?', $reservationId)->fetch();
            $this->mailer->sendCancelationMail($reservation->email, $reservation, "Zrušeno správcem");
            $this->redirect('this');
        }
        $this->flashMessage("Rezervaci se nepodařilo zrušit", "error");
    }

    public function handleDeleteFilter($filter)
    {
        $session = $this->getSession("reservations");
        switch ($filter) {
            case "range":
                $session->filterStart = null;
                $session->filterEnd = null;
                break;
            case "service":
                $session->filterService = null;
                break;
        }

        $this->redirect('default');
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
}
