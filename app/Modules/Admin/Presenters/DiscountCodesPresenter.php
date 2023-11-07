<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Json;


final class DiscountCodesPresenter extends SecurePresenter
{

    private $id;

    public function __construct(
        private Nette\Database\Explorer $database
    )
    {
    }

    public function beforeRender()
    {
        parent::beforeRender();

    }

    public function actionShow()
    {
        $discountCodes = $this->database->table("discount_codes")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->discountCodes = $discountCodes;
    }

    public function actionEdit(int $id)
    {
        $this->id = $id;
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->services = $services;
        $discountCode = $this->database->table("discount_codes")->where("id=?", $id)->fetch();
        $selectedServices = Json::decode($discountCode->services);
        $this->template->selectedServices = $selectedServices;
        $this->template->discountCode = $discountCode;
    }

    public function actionCreate()
    {
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->services = $services;
    }

    protected function createComponentForm(): Form
    {
        $types = [0 => "Částka", 1 => "Procento"];
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $form = new Form;
        $form->addCheckbox("active", "Aktivní");
        $form->addText("code", "Code")->setRequired();
        $form->addSelect("type", "Typ", $types)->setRequired();
        $form->addText("value", "Hodnota")->setHtmlAttribute("type", "number")->setRequired();
        $i = 1;
        foreach ($services as $service) {
            $form->addCheckbox(strval("service".$i), $service->name);
            $i++;
        }
        $form->addSubmit("save", "Vytvořit");

        $form->onSuccess[] = [$this, "formSucceeded"];

        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {
        $services = $this->database->table("services")->fetchAll();
        $show = $values->active ? 1 : 0;
        $status = false;
        $enabled = [];
        $i = 1;
        foreach ($services as $service) {
            $value = $values["service" . $i];
            if ($value) {
                $enabled[] = $service->id;
            }
        }
        $json = Json::encode($enabled);
        try {
            $status = $this->database->table("discount_codes")->insert([
                "user_id" => $this->user->id,
                "code" => $values->code,
                "value" => $values->value,
                "type" => $values->type,
                "active" => $show,
                "services" => $json
            ]);
        } catch (\Throwable $th) {
            $this->flashMessage("Nepodarilo se vytvořit, kód nesmí být duplicitní", "alert-danger");
        }
        if ($status) {
            $this->flashMessage("Vytvořeno", "alert-success");
            $this->redirect("DiscountCodes:show");
        }
    }

    protected function createComponentEditForm(): Form
    {
        $types = [0 => "Částka", 1 => "Procento"];
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $form = new Form;
        $form->addCheckbox("active", "Aktivní");
        $form->addText("code", "Code")->setRequired();
        $form->addText("value", "Hodnota")->setHtmlAttribute("type", "number")->setRequired();
        $i = 1;
        foreach ($services as $service) {
            $form->addCheckbox(strval("service".$i), $service->name);
            $i++;
        }
        $form->addSubmit("save", "Uložit");

        $form->onSuccess[] = [$this, "editFormSucceeded"];

        return $form;
    }

    public function editFormSucceeded(Form $form, $values) {
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $show = $values->active ? 1 : 0;
        $status = false;
        $enabled = [];
        $i = 1;
        foreach ($services as $service) {
            $value = $values["service" . $i];
            if ($value) {
                $enabled[] = $service->id;
            }
        }
        $json = Json::encode($enabled);

        try {
            $status = $this->database->table("discount_codes")->where("id=?", $this->id)->update([
                "code" => $values->code,
                "value" => $values->value,
                "active" => $show,
                "services" => $json
            ]);
        } catch (\Throwable $th) {
            $this->flashMessage("Nepodarilo se uložit", "alert-danger");
        }
        if ($status) {
            $this->flashMessage("Uloženo", "alert-success");
            $this->redirect("DiscountCodes:show");
        }

    }


}
