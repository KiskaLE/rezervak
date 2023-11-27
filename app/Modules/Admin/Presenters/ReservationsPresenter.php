<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;


final class ReservationsPresenter extends SecurePresenter
{

    private $uuid;
    private $reservation;

    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();

    }

    public function actionShow()
    {
        //$reservations = $this->database->query("SELECT reservations.* FROM `reservations` LEFT JOIN payments ON reservations.id = payments.reservation_id WHERE reservations.status='VERIFIED' AND payments.status=1 AND reservations.user_id=?;", $this->user->id)->fetchAll();
        //$reservations = $this->database->table("reservations")->select("payments")->where("date>=? AND user_id=? AND status='VERIFIED'", [date("Y-m-d"), $this->user->id])->fetchAll();
        $reservations = $this->database->table('reservations')
            ->select('reservations.*',)
            ->where('reservations.status=?', 'VERIFIED')
            ->where("reservations.type=?", 0)
            ->where('user_id=?', $this->user->id)
            ->where(':payments.status=?', 1)->fetchAll();
        $this->template->reservations = $reservations;
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
        $this->flashMessage("Uloženo", "alert-success");
        $this->redirect('show');
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
        $this->redirect('Reservations:show');
    }

    public function handleBack($default)
    {
        if ($this->backlink) {
            $this->restoreRequest($this->backlink);
        }
        $this->redirect($default);
    }

}
