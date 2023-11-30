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

    //custom schedule
    private $schedule;
    private $days;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User     $user,
        private Formater                $formater
    )
    {

    }

    protected function beforeRender()
    {
        parent::beforeRender();
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

        $customSchedules = $service->related("services_custom_schedules")->fetchAll();
        $this->template->customSchedules = $customSchedules;

        $this->backlink = $this->storeRequest();
        $this->template->backlink = $this->backlink;

    }

    public function actionEditCustomSchedule($id, $backlink)
    {
        $this->backlink = $backlink;
        $schedule = $this->database->table("services_custom_schedules")->where("uuid=?", $id)->fetch();
        $this->schedule = $schedule;
        $days = $schedule->related("service_custom_schedule_days")->fetchAll();
        $this->days = $days;
        $daysDefaults = array();
        foreach ($days as $day) {
            $daysDefaults[] = [
                "uuid" => $day->uuid,
                "day" => $this->formater->getDateFormatedFromTimeStamp($day->start),
                "timeStart" => $this->formater->getTimeFormatedFromTimeStamp($day->start),
                "timeEnd" => $this->formater->getTimeFormatedFromTimeStamp($day->end)
            ];
        }
        $defaults = [
            "scheduleName" => $schedule->name,
            "range" => $this->formater->getDateFormatedFromTimestamp($schedule->start) . " " . $this->formater->getTimeFormatedFromTimeStamp($schedule->start) . " - " . $this->formater->getDateFormatedFromTimestamp($schedule->end) . " " . $this->formater->getTimeFormatedFromTimeStamp($schedule->end),
            "multiplier" => $daysDefaults
        ];
        $this["editCustomScheduleForm"]->setDefaults($defaults);
    }

    public function actionCreateCustomSchedule($id, $backlink)
    {
        $this->backlink = $backlink;
        $service = $this->database->table("services")->get($id);
        $this->service = $service;
    }

    protected function createComponentEditCustomScheduleForm(): Form
    {

        $form = new Form;

        $form->addText("scheduleName")->setRequired("Název je povinný");
        $form->addText("range")->setRequired();
        $multiplier = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
            $container->addHidden("uuid");
            $container->addText("day", "text")->setRequired();
            $container->addText("timeStart", "Začátek")->setHtmlAttribute("type", "time")->setRequired();;
            $container->addText("timeEnd", "Konec")->setHtmlAttribute("type", "time")->setRequired();;
        }, 1);


        $multiplier->addCreateButton('Přidat')
            ->addClass('btn btn-primary');
        $multiplier->addRemoveButton('Odebrat')
            ->addClass('btn btn-danger');

        $form->addSubmit("submit", "Uložit");
        $form->onSuccess[] = [$this, "editCustomScheduleFormSuccess"];

        return $form;
    }

    public function editCustomScheduleFormSuccess(Form $form, $data)
    {
        $res = $this->database->transaction(function ($database) use ($data) {
            $success = true;
            try {
                $range = $this->formater->getDataFromString($data->range);
                $database->table("services_custom_schedules")->where("id=?", $this->schedule->id)->update([
                    "start" => $range["start"],
                    "name" => $data->scheduleName,
                    "end" => $range["end"],
                    "updated_at" => date("Y-m-d H:i:s")
                ]);

                //remove removed
                foreach ($this->days as $day) {
                    $delete = true;
                    foreach ($data->multiplier as $dayData) {
                        if ($dayData->uuid == $day->uuid) {
                            $delete = false;
                            break;
                        }
                    }
                    if ($delete) {
                        $this->database->table("service_custom_schedule_days")->where("uuid=?", $day->uuid)->delete();
                    }
                }

                //update existing days and create new ones
                $days = $data->multiplier;
                foreach ($days as $day) {
                    $date = explode("/", $day["day"]);
                    $databaseDate = $date[2] . "-" . $date[1] . "-" . $date[0];
                    $start = $databaseDate . " " . $day["timeStart"];
                    $end = $databaseDate . " " . $day["timeEnd"];
                    if (!empty($day->uuid) || $day->uuid != null) {
                        $this->database->table("service_custom_schedule_days")->where("uuid=?", $day->uuid)->update([
                            "start" => $start,
                            "end" => $end
                        ]);
                    } else {
                        $uuid = Uuid::uuid4();
                        $this->database->table("service_custom_schedule_days")->insert([
                            "uuid" => $uuid,
                            "service_custom_schedule_id" => $this->schedule->id,
                            "start" => $start,
                            "end" => $end,
                            "type" => 0
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $success = false;
            }
            return $success;
        });

        if ($res) {
            $this->flashMessage("Vytvořeno", "alert-success");
            $this->redirect("this");
        } else {
            $this->flashMessage("Nepodarilo se vytvořit službu", "alert-danger");
        }
    }

    protected function createComponentCreateCustomScheduleForm(): Form
    {
        $form = new Form;

        $form->addText("scheduleName")->setRequired("Název je povinný");
        $form->addText("range")->setRequired("Rozsah je povinný");

        $multiplier = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
            $container->addText("day", "text")
                ->setRequired("Den je povinný");
            $container->addText("timeStart", "Začátek")
                ->setHtmlAttribute("type", "time")
                ->setRequired("Začátek je povinný");
            $container->addText("timeEnd", "Konec")
                ->setHtmlAttribute("type", "time")
                ->setRequired("Konec je povinný");

            // Custom validation function
            $validateTimeRange = function ($timeEndField) use ($container) {
                $timeStart = $container['timeStart']->getValue();
                $timeEnd = $timeEndField->getValue();

                $start = strtotime($timeStart);
                $end = strtotime($timeEnd);

                return $end > $start;
            };

            $container['timeEnd']->addRule($validateTimeRange, 'Čas ukončení musí být později než čas začátku.');
        }, 1);

        $form->addSubmit("submit", "Uložit");
        $form->onSuccess[] = [$this, "createCustomScheduleFormSuccess"];
        $multiplier->addCreateButton('Přidat')
            ->addClass('btn btn-primary');
        $multiplier->addRemoveButton('Odebrat')
            ->addClass('btn btn-danger');

        return $form;
    }

    public function createCustomScheduleFormSuccess(Form $form, $data)
    {
        $res = $this->database->transaction(function ($database) use ($data) {
            $success = true;
            try {
                $range = $this->formater->getDataFromString($data->range);
                $uuid = Uuid::uuid4();
                $days = $data->multiplier;
                $serviceSchedule = $this->database->table("services_custom_schedules")->insert([
                    "service_id" => $this->service->id,
                    "name" => $data->scheduleName,
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
            } catch (\Exception $e) {
                $success = false;
            }
            return $success;
        });

        if ($res) {
            $this->flashMessage("Vytvořeno", "alert-success");
            $this->restoreRequest($this->backlink);
        } else {
            $this->flashMessage("Nepodarilo se vytvořit službu", "alert-danger");
        }
    }

    protected function createComponentCreateForm(): Form
    {


        $form = new Form;


        $form->addHidden("action");
        $form->addText("name", "Name")->setRequired("Jméno je povinné");
        $form->addTextArea("description", "Description")->setMaxLength(255)->setRequired("Popis je povinný");
        $form->addText("duration", "Duration")->setHtmlAttribute("type", "number")->setRequired("Doba je povinná");
        $form->addText("price", "Price")->setHtmlAttribute("type", "number")->setRequired("Cena je povinná");
        $form->addCheckbox("customSchedule", "Custom Schedule")
            ->addCondition($form::Equal, true)
            ->toggle('#servicesCustomFields');;

        $form->addText("range")->addConditionOn($form["customSchedule"], $form::Equal, true)->setRequired("Rozsah je povinný");
        $form->addText("scheduleName")->addConditionOn($form["customSchedule"], $form::Equal, true)->setRequired("Název je povinný");

        $multiplier = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
            $container->addText("day", "text")
                ->addConditionOn($form["customSchedule"], $form::Equal, true)
                ->setRequired("Den je povinný");
            $container->addText("timeStart", "Začátek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form["customSchedule"], $form::Equal, true)
                ->setRequired("Začátek je povinný");
            $container->addText("timeEnd", "Konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form["customSchedule"], $form::Equal, true)
                ->setRequired("Konec je povinný");

            // Custom validation function
            $validateTimeRange = function ($timeEndField) use ($container) {
                $timeStart = $container['timeStart']->getValue();
                $timeEnd = $timeEndField->getValue();

                $start = strtotime($timeStart);
                $end = strtotime($timeEnd);

                return $end > $start;
            };

            $container['timeEnd']->addConditionOn($form["customSchedule"], $form::Equal, true)->addRule($validateTimeRange, 'Čas ukončení musí být později než čas začátku.');
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
                    $serviceSchedule = $this->database->table("services_custom_schedules")->insert([
                        "service_id" => $service->id,
                        "name" => $data->scheduleName,
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
            } catch (\Exception $e) {
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

        $this->redirect("this");
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

    public function handleBack($default)
    {
        if ($this->backlink) {
            $this->restoreRequest($this->backlink);
        }
        $this->redirect($default);
    }

    public function handleDeleteCustomSchedule($cutomScheduleId)
    {
        $this->database->table("services_custom_schedules")->where("uuid=?", $cutomScheduleId)->delete();
        $this->flashMessage("Smazano", "alert-success");
        $this->redirect("edit", $this->id);
    }

}