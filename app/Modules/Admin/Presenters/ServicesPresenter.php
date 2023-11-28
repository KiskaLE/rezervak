<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Cassandra\Duration;
use Nette;
use Nette\Application\UI\Form;


final class ServicesPresenter extends SecurePresenter
{
    private $id;
    private $service;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User $user)
    {

    }

    public function actionShow() {
        $services = $this->database->table("services")->where("user_id", $this->user->id)->fetchAll();
        $this->template->services = $services;
}
    public function actionEdit($id) {
        $this->id = $id;
        $service = $this->database->table("services")->where("id=?", $id)->fetch();
        $this->service= $service;
        $this->template->service = $service;

    }
    public function renderShowCustomSchedule($id) {

    }

    protected function createComponentCreateForm(): Form {
        $form = new Form;
        $form->addHidden("action");
        $form->addText("name", "Name")->setRequired();
        $form->addTextArea("description", "Description")->setMaxLength(255)->setRequired();
        $form->addText("duration", "Duration")->setHtmlAttribute("type", "number")->setRequired();
        $form->addText("price", "Price")->setHtmlAttribute("type", "number")->setRequired();
        $form->addSubmit("submit", "Uložit");

        $form->onSuccess[] = [$this, "createFormSuccess"];

        return $form;
    }

    public function createFormSuccess(Form $form, $data){
            $this->database->table("services")->insert([
                "name" => $data->name,
                "price" => $data->price,
                "duration" => $data->duration,
                "user_id" => $this->user->id,
                "description" => $data->description
            ]);

        $this->redirect("Services:show");
    }

    protected function createComponentEditForm(): Form {
        $form = new Form;
        $form->addText("name", "Name")
            ->setDefaultValue($this->service->name)
            ->setRequired();
        $form->addTextArea("description", "Description")
            ->setDefaultValue($this->service->description)
            ->setMaxLength(255)
            ->setRequired();
        $form->addText("price", "Price")
            ->setDefaultValue($this->service->price)
            ->setHtmlAttribute("type", "number")
            ->setRequired();
        $form->addSubmit("submit", "Uložit");

        $form->onSuccess[] = [$this, "editFormSuccess"];

        return $form;
    }

    public function editFormSuccess(Form $form, $data){
            $this->database->table("services")->where("id=?", $this->id)->update([
                "name" => $data->name,
                "price" => $data->price,
                "description" => $data->description
            ]);

            $this->flashMessage("Uloženo", "alert-success");

        $this->redirect("Services:show");
    }

    public function actionActionHide($id) {
        $hidden = $this->database->table("services")->get($id)->hidden;
        if ($hidden) {
            $this->database->table("services")->where("id=?", $id)->update([
                "hidden" => 0
            ]);
            $this->flashMessage("Služba je zobrazena", "alert-success");
        } else {
            $this->database->table("services")->where("id=?", $id)->update([
                "hidden" => 1
            ]);
            $this->flashMessage("Služba je Skryta", "alert-success");
        }

        $this->redirect("Services:show");

        die("success");
    }

}