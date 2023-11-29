<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Ramsey\Uuid\Uuid;
use App\Modules\Formater;


final class ServicesPresenter extends SecurePresenter
{
    private $id;
    private $service;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User     $user,
        private Formater                $formater
    )
    {

    }

    public function actionShow()
    {
        $services = $this->database->table("services")->where("user_id", $this->user->id)->fetchAll();
        $this->template->services = $services;
    }

    public function actionEdit($id)
    {
        $this->id = $id;
        $service = $this->database->table("services")->where("id=?", $id)->fetch();
        $this->service = $service;
        $this->template->service = $service;

    }

    public function renderShowCustomSchedule($id)
    {

    }

    protected function createComponentCreateForm(): Form
    {
        $form = new Form;

        $form->addHidden("action");
        $form->addText("name", "Name")->setRequired();
        $form->addTextArea("description", "Description")->setMaxLength(255)->setRequired();
        $form->addText("duration", "Duration")->setHtmlAttribute("type", "number")->setRequired();
        $form->addText("price", "Price")->setHtmlAttribute("type", "number")->setRequired();;
        $form->addCheckbox("customSchedule", "Custom Schedule");

        $form->addText("range")->addConditionOn($form["customSchedule"], $form::Equal, true)->setRequired();

        $multiplier = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
            $container->addText("day", "text")->addConditionOn($form["customSchedule"], $form::Equal, true)->setRequired();
            $container->addText("timeStart", "Začátek")->setHtmlAttribute("type", "time")->addConditionOn($form["customSchedule"], $form::Equal, true)->setRequired();;
            $container->addText("timeEnd", "Konec")->setHtmlAttribute("type", "time")->addConditionOn($form["customSchedule"], $form::Equal, true)->setRequired();;
        }, 1);

        $form->addSubmit("submit", "Uložit");
        $form->onSuccess[] = [$this, "createFormSuccess"];
        $multiplier->addCreateButton('Přidat')
            ->addClass('btn btn-primary');
        $multiplier->addRemoveButton('Odebrat')
            ->addClass('btn btn-danger');

        return $form;
    }

    public function createFormSuccess(Form $form, $data)
    {
        $res = $this->database->transaction(function ($database) use ($data) {
            $success = true;
            try {
                $service = $this->database->table("services")->insert([
                    "name" => $data->name,
                    "price" => $data->price,
                    "duration" => $data->duration,
                    "user_id" => $this->user->id,
                    "description" => $data->description
                ]);
                //if custom schedule is checked
                if ($data->customSchedule) {
                    $range = $this->formater->getDataFromString($data->range);
                    $uuid = Uuid::uuid4();
                    $days = $data->multiplier;
                    $serviceSchedule = $this->database->table("services_custom_ schedule")->insert([
                        "service_id" => $service->id,
                        "uuid" => $uuid,
                        "start" => $range["start"],
                        "end" => $range["end"],
                        "type" => 0
                    ]);
                    foreach ($days as $day) {
                        $uuid = Uuid::uuid4();
                        $date = explode("/", $day["day"]);
                        $databaseDate = $date[2] . "-" . $date[1] . "-" . $date[0];
                        $start = $databaseDate . " " . $day["timeStart"];
                        $end = $databaseDate . " " . $day["timeEnd"];
                        $this->database->table("service_custom_schedule_days")->insert([
                            "uuid" => $uuid,
                            "service_custom_schedule_id" => $serviceSchedule->id,
                            "start" => $start,
                            "end" => $end,
                            "type" => 0
                        ]);
                    }
                }
            }catch (\Exception $e) {
                $success = false;
            }
            return $success;
        });

        if ($res) {
            $this->flashMessage("Vytvořeno", "alert-success");
            $this->redirect("Services:show");
        } else {
            $this->flashMessage("Nepodarilo se vytvořit službu", "alert-danger");
        }


    }

    protected function createComponentEditForm(): Form
    {
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

    public function editFormSuccess(Form $form, $data)
    {
        $this->database->table("services")->where("id=?", $this->id)->update([
            "name" => $data->name,
            "price" => $data->price,
            "description" => $data->description
        ]);

        $this->flashMessage("Uloženo", "alert-success");

        $this->redirect("Services:show");
    }

    public function actionActionHide($id)
    {
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