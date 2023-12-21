<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;


final class ReservationsPresenter extends SecurePresenter
{

    private $uuid;
    private $reservation;
    private $page;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
    
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

        $numberOfReservations = $this->database->table('reservations')
            ->select('reservations.*',)
            ->where('reservations.status=?', 'VERIFIED')
            ->where("reservations.type=?", 0)
            ->where('user_id=?', $this->user->id)
            ->where(':payments.status=?', 1)->count();

        $paginator = $this->createPagitator($numberOfReservations, $page, 5);


        switch ($tab) {
            case 0:
                $numberOfReservations = $this->database->table('reservations')
                ->select('reservations.*',)
                ->where('reservations.status=?', 'VERIFIED')
                ->where('start >= ?', date('Y-m-d'))
                ->where("reservations.type=?", 0)
                ->where('user_id=?', $this->user->id)
                ->where(':payments.status=?', 1)
                ->count();

                $reservations = $this->database->table('reservations')
                ->select('reservations.*',)
                ->where('reservations.status=?', 'VERIFIED')
                ->where('start >= ?', date('Y-m-d'))
                ->where("reservations.type=?", 0)
                ->where('user_id=?', $this->user->id)
                ->where(':payments.status=?', 1)
                ->order('start ASC')
                ->limit($paginator->getLength(), $paginator->getOffset())
                ->fetchAll();
                break;
            
            case 1:
                $numberOfReservations = $this->database->table('reservations')
                ->select('reservations.*',)
                ->where('reservations.status=?', 'VERIFIED')
                ->where('start < ?', date('Y-m-d'))
                ->where("reservations.type=?", 1)
                ->where('user_id=?', $this->user->id)
                ->where(':payments.status=?', 1)
                ->count();

                $reservations = $this->database->table('reservations')
                ->select('reservations.*',)
                ->where('reservations.status=?', 'VERIFIED')
                ->where('start < ?', date('Y-m-d'))
                ->where("reservations.type=?", 1)
                ->where('user_id=?', $this->user->id)
                ->where(':payments.status=?', 1)
                ->order('start ASC')
                ->limit($paginator->getLength(), $paginator->getOffset())
                ->fetchAll();
                break;
            case 2:
                $numberOfReservations = $this->database->table('reservations')
                ->select('reservations.*',)
                ->where('reservations.status=?', 'VERIFIED')
                ->where("reservations.type=?", 0)
                ->where('user_id=?', $this->user->id)
                ->where(':payments.status=?', 0)
                ->count();

                $reservations = $this->database->table('reservations')
                ->select('reservations.*',)
                ->where('reservations.status=?', 'VERIFIED')
                ->where("reservations.type=?", 0)
                ->where('user_id=?', $this->user->id)
                ->where(':payments.status=?', 0)
                ->order('start ASC')
                ->limit($paginator->getLength(), $paginator->getOffset())
                ->fetchAll();
        }
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

    public function actionDelete($id, $backlink)
    {
        $this->backlink = $backlink;
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


    public function handleSetTab($tab) {
       
        $session = $this->getSession("reservations");
    
        $session->reservations_tab = $tab;
        $this->redirect('default');
    }

}
