<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Json;
use Ramsey\Uuid\Uuid;
use Nette\DI\Attributes\Inject;


final class DiscountCodesPresenter extends SecurePresenter
{

    private $id;
    private $selectedServices;
    private $discountCode;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct()
    {
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "discountCodes";
    }

    public function actionDefault(int $page = 1)
    {
        $discountCodesCount = $this->database->table("discount_codes")
            ->count();

        $paginator = new Nette\Utils\Paginator;
        $paginator->setItemCount($discountCodesCount);
        $paginator->setItemsPerPage(10);
        $paginator->setPage($page);

        $discountCodes = $this->database->table("discount_codes")
            ->limit($paginator->getLength(), $paginator->getOffset())
            ->fetchAll();
        $this->template->discountCodes = $discountCodes;
        $this->template->paginator = $paginator;
    }

    public function actionEdit(int $id)
    {
        $this->id = $id;

        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;

        $discountCode = $this->database->table("discount_codes")->where("id=?", $id)->fetch();
        $this->discountCode = $discountCode;
        $services2discountCode = $discountCode->related("service2discount_code.discount_code_id")->fetchAll();
        $selectedServices = [];
        foreach ($services2discountCode as $row) {
            $id = $row?->ref("services", "service_id")?->id;
            if ($id) {
                $selectedServices[] = $id;
            }
        }
        $this->selectedServices = $selectedServices;
        $this->template->selectedServices = $selectedServices;
        $this->template->discountCode = $discountCode;
    }

    public function actionCreate()
    {
        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
    }

    public function handleDelete($uuid)
    {
        $this->database->table("discount_codes")->where("uuid=?", $uuid)->delete();
        $this->flashMessage("Kód byl smazán", "success");
        $this->redirect("this");
    }

    public function handleHide($uuid)
    {
        $active = $this->database->table("discount_codes")->where("uuid=?", $uuid)->fetch("hidden")->active;
        if ($active) {
            $this->database->table("discount_codes")->where("uuid=?", $uuid)->update([
                "active" => 0
            ]);
            $this->flashMessage("Kód není neaktivní", "success");
        } else {
            $this->database->table("discount_codes")->where("uuid=?", $uuid)->update([
                "active" => 1
            ]);
            $this->flashMessage("Kód je aktivní", "success");
        }

        $this->redirect("this");
    }

    protected function createComponentForm(): Form
    {
        $types = [0 => "Částka", 1 => "Procento"];
        $services = $this->database->table("services")->fetchAll();
        $form = new Form;
        $form->addCheckbox("active", "Aktivní");
        $form->addText("code", "Code")
            ->setRequired("Zadejte slevový kód");
        $form->addSelect("type", "Typ", $types)
            ->setRequired("Vyberte typ slevy");
        $form->addText("value", "Hodnota")
            ->setHtmlAttribute("type", "number")
            ->setRequired("Zadejte hodnotu slevy")
            ->addRule($form::Min, "Hodnota musí být větší než 0", 1)
            ->addConditionOn($form["type"], $form::Equal, 1)
            ->addRule($form::Max, "Hodnota nesmí být větší než 100", 100);
        $i = 1;
        foreach ($services as $service) {
            $form->addCheckbox(strval("service" . $i), $service->name);
            $i++;
        }
        $form->addSubmit("save", "Vytvořit kód");

        $form->onSuccess[] = [$this, "formSucceeded"];

        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {
        $uuid = Uuid::uuid4();
        $services = $this->database->table("services")->fetchAll();
        $enabled = [];
        $i = 1;
        foreach ($services as $service) {
            $value = $values["service" . $i];
            if ($value) {
                $enabled[] = $service->id;
            }
            $i++;
        }
        $status = $this->database->transaction(function ($database) use ($uuid, $values, $enabled) {
            $isSuccess = true;
            try {
                $discountCode = $this->database->table("discount_codes")->insert([
                    "uuid" => $uuid,
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
                $this->flashMessage("Nepodarilo se vytvořit, kód nesmí být duplicitní", "error");
                $isSuccess = false;
            }
            return $isSuccess;
        });
        if (!$status) {
            $this->redirect("this");
        }
        $this->flashMessage("Vytvořeno", "success");
        $this->redirect("DiscountCodes:");
    }

    protected function createComponentEditForm(): Form
    {
        $types = [0 => "Částka", 1 => "Procento"];
        $services = $this->database->table("services")->fetchAll();
        $type = $this->discountCode->type;
        $form = new Form;
        $form->addText("code", "Code")
            ->setDefaultValue($this->discountCode->code)
            ->setRequired("Zadejte slevový kód");
        $form->addText("value", "Hodnota")
            ->setDefaultValue($this->discountCode->value)
            ->setHtmlAttribute("type", "number")
            ->setRequired("Zadejte hodnotu slevy")
            ->addRule($form::Min, "Hodnota musí být větě než 0", 1)
            ->addCondition($type == 1)
            ->addRule($form::Max, "Hodnota nesmí být větě než 100", 100);
        $i = 1;
        foreach ($services as $service) {
            $form->addCheckbox(strval("service" . $i), $service->name);
            $i++;
        }
        $form->addSubmit("submit", "Uložit změny");

        $form->onSuccess[] = [$this, "editFormSucceeded"];

        return $form;
    }

    public function editFormSucceeded(Form $form, $values)
    {
        $services = $this->database->table("services")->fetchAll();
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
                $database->table("discount_codes")->where("id=?", $this->id)->update([
                    "code" => $values->code,
                    "value" => $values->value,
                ]);
                //add new services
                foreach ($enabled as $row) {
                    if (!in_array($row, $this->selectedServices)) {
                        $database->table("service2discount_code")->insert([
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
                bdump($th);
                $isSuccess = false;
            }
            return $isSuccess;
        });

        if ($status) {
            $this->flashMessage("Uloženo", "success");
            $this->redirect("DiscountCodes:");
        } else {
            $this->flashMessage("Nepodarilo se uložit", "error");
            $this->redirect("this");
        }
    }
}
