<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Json;
use Ramsey\Uuid\Uuid;


final class DiscountCodesPresenter extends SecurePresenter
{

    private $id;
    private $selectedServices;

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
        $services2discountCode = $discountCode->related("service2discount_code.discount_code_id")->fetchAll();
        $selectedServices = [];
        foreach ($services2discountCode as $row) {
            $selectedServices[] = $row->ref("services", "service_id")->id;
        }
        $this->selectedServices = $selectedServices;
        $this->template->selectedServices = $selectedServices;
        $this->template->discountCode = $discountCode;
    }

    public function actionCreate()
    {
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->services = $services;
    }

    public function handleDelete($uuid)
    {
        $this->database->table("discount_codes")->where("uuid=?", $uuid)->delete();
        $this->redirect("this");
    }

    public function handleHide($uuid)
    {
        $active = $this->database->table("discount_codes")->where("uuid=?", $uuid)->fetch("hidden")->active;
        if ($active) {
            $this->database->table("discount_codes")->where("uuid=?", $uuid)->update([
                "active" => 0
            ]);
            $this->flashMessage("Kód není neaktivní", "alert-success");
        } else {
            $this->database->table("discount_codes")->where("uuid=?", $uuid)->update([
                "active" => 1
            ]);
            $this->flashMessage("Kód je aktivní", "alert-success");
        }

        $this->redirect("this");
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
            $form->addCheckbox(strval("service" . $i), $service->name);
            $i++;
        }
        $form->addSubmit("save", "Vytvořit");

        $form->onSuccess[] = [$this, "formSucceeded"];

        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {
        $uuid = Uuid::uuid4();
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $enabled = [];
        $i = 1;
        foreach ($services as $service) {
            $value = $values["service" . $i];
            if ($value) {
                $enabled[] = $service->id;
            }
            $i++;
        }
        $status = $this->database->transaction(function ($database) use ($uuid, $values, $show, $enabled) {
            $isSuccess = true;
            try {
                $discountCode = $this->database->table("discount_codes")->insert([
                    "uuid" => $uuid,
                    "user_id" => $this->user->id,
                    "code" => $values->code,
                    "value" => $values->value,
                    "type" => $values->type,
                    "active" => 0,
                ]);
                foreach ($enabled as $row) {
                    $this->database->table("service2discount_code")->insert([
                        "discount_code_id" => $discountCode->id,
                        "service_id" => $row
                    ]);
                }
            } catch (\Throwable $th) {
                $this->flashMessage("Nepodarilo se vytvořit, kód nesmí být duplicitní", "alert-danger");
                $isSuccess = false;
            }
            return $isSuccess;
        });
        if (!$status) {
            $this->redirect("this");
        }
        $this->flashMessage("Vytvořeno", "alert-success");
        $this->redirect("DiscountCodes:show");
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
            $form->addCheckbox(strval("service" . $i), $service->name);
            $i++;
        }
        $form->addSubmit("save", "Uložit");

        $form->onSuccess[] = [$this, "editFormSucceeded"];

        return $form;
    }

    public function editFormSucceeded(Form $form, $values)
    {
        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $status = false;
        $enabled = [];
        $i = 1;
        foreach ($services as $service) {
            $value = $values["service" . $i];
            if ($value) {
                $enabled[] = $service->id;
            }
            $i++;
        }
        $status = $this->database->transaction(function ($database) use ($values, $enabled) {
            $isSuccess = true;
            try {
                $this->database->table("discount_codes")->where("id=?", $this->id)->update([
                    "code" => $values->code,
                    "value" => $values->value,
                ]);
                //add new services
                foreach ($enabled as $row) {
                    if (!in_array($row, $this->selectedServices)) {
                        $this->database->table("service2discount_code")->insert([
                            "discount_code_id" => $this->id,
                            "service_id" => $row
                        ]);
                    }
                }
                //delete uchecked services
                foreach ($this->selectedServices as $row) {
                    if (!in_array($row, $enabled)) {
                        $this->database->table("service2discount_code")->where("discount_code_id=? AND service_id=?", $this->id, $row)->delete();
                    }
                }
            } catch (\Throwable $th) {
                $this->flashMessage("Nepodarilo se uložit", "alert-danger");
                $isSuccess = false;
            }
            return $isSuccess;
        });

        if ($status) {
            $this->flashMessage("Uloženo", "alert-success");
            $this->redirect("DiscountCodes:show");
        }

    }


}
