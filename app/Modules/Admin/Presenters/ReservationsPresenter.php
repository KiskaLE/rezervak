<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class ReservationsPresenter extends SecurePresenter
{

    private int $id;

    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
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

    public function actionEdit(int $id)
    {
        $this->id = $id;
        $reservation = $this->database->table("reservations")->get($id);
        $this->template->reservation = $reservation;
    }

    public function actionDetail(int $id)
    {
        $reservation = $this->database->table("reservations")->get($id);
        $this->template->reservation = $reservation;
    }

    public function actionDelete(int $id)
    {
        $this->id = $id;
        $reservation = $this->database->table("reservations")->where("id=?", $id)->fetch();
        $this->template->reservation = $reservation;

    }

    protected function createComponentForm(): Form
    {
        $form = new Form;

        $form->addHidden("action")->setRequired();
        $form->addText('firstname')->setRequired();
        $form->addText('lastname')->setRequired();
        $form->addText('email')->setRequired();
        $form->addText('phone')->setRequired();
        $form->addText('service_id')->setRequired();
        $form->addText('status')->setRequired();
        $form->addSubmit('submit', 'Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];

        return $form;
    }

    public function formSucceeded(Form $form, \stdClass $data): void
    {

        if ($data->action === "edit") {
            $this->database->table('reservations')->where('id=?', $this->id)->update([
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'email' => $data->email,
                'phone' => $data->phone,
                'service_id' => $data->service_id,
                'status' => $data->status,
            ]);
        }

        $this->redirect('Reservations:show');
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
        $this->database->table('reservations')->where('id=?', $this->id)->delete();
        $this->redirect('Reservations:show');
    }

}
