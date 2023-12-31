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

    public function actionShow() {
        $reservations = $this->database->table("registereddates")->order("date ASC")->fetchAll();
        $this->template->reservations = $reservations;
    }
    public function  actionEdit(int $id) {
        $this->id = $id;
        $reservation = $this->database->table("registereddates")->get($id);
        $this->template->reservation = $reservation;
    }

    protected function createComponentForm(): Form {
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

    public function formSucceeded(Form $form,\stdClass $data): void {

        if ($data->action === "edit") {
            $this->database->table('registereddates')->where('id=?', $this->id)->update([
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

}
