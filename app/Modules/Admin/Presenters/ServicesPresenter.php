<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class ServicesPresenter extends SecurePresenter
{
    public $id;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User $user)
    {

    }

    public function renderShow() {
        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
}
    public function actionEdit($id) {
        $this->id = $id;
        $service = $this->database->table("services")->where("id=?", $id)->fetch();
        $this->template->service = $service;

    }

    public function actionCreate(){

    }

    protected function createComponentForm(): Form {
        //TODO editable in admin
        $currencies = ["CZK", "EUR"];

        $form = new Form;
        $form->addHidden("action");
        $form->addText("name", "Name")->setRequired();
        $form->addText("price", "Price")->setRequired();
        $form->addSubmit("submit", "Save");

        $form->onSuccess[] = [$this, "formSuccess"];

        return $form;
    }

    public function formSuccess(Form $form, $data){
        if ($data->action === "edit"){
            $this->database->table("services")->where("id=?", $this->id)->update([
                "name" => $data->name,
                "price" => $data->price,
            ]);
        }
        if ($data->action === "create"){
            $this->database->table("services")->insert([
                "name" => $data->name,
                "price" => $data->price,
            ]);
        }


        $this->redirect("Services:show");
    }

}