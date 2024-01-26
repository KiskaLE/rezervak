<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Ramsey\Uuid\Uuid;
use App\Modules\Formater;
use App\Modules\AvailableDates;
use Nette\DI\Attributes\Inject;
use Tester\Runner\Output\Logger;

final class ServicesPresenter extends SecurePresenter
{
    private $id;
    private $service;

    //custom schedule
    private $schedule;
    private $days;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
        private Nette\Security\User $user,
        private Formater            $formater,
        private AvailableDates      $availableDates
    ) {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "services";
    }

    public function actionDefault(int $page = 1)
    {
        $q = $this->database->table("services")->where("hidden<?", 2);
        $numberOfServices = $q->count();
        $paginator = new Nette\Utils\Paginator;
        $paginator->setItemCount($numberOfServices);
        $paginator->setItemsPerPage(10);
        $paginator->setPage($page);

        $this->template->paginator = $paginator;

        $services = $q->limit($paginator->getLength(), $paginator->getOffset())->fetchAll();
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

        //custom schedule conflicts
        $this->template->customScheduleConflicts = $this->availableDates->getCustomSchedulesConflictsIds($service);


        $userSettings = $this->database->table("settings")->fetch();
        $this->template->userSettings = $userSettings;


        $calendarPeriod = gmdate("H:i:s", $userSettings->sample_rate * 60);
        $this->template->calendarPeriod = $calendarPeriod;
    }

    public function actionEditSchedule($id)
    {
        $service = $this->database->table("services")->get($id);
        $this->template->service = $service;
        $this->service = $service;
        $this["workingHoursForm"]->setDefaults($this->getDefaultWorkingHours());
    }

    public function actionEditCustomSchedule($id)
    {
        $schedule = $this->database->table("services_custom_schedules")->where("uuid=?", $id)->fetch();
        $this->schedule = $schedule;

        $service = $schedule->ref("services", "service_id");
        $this->template->service = $service;

        $days = $schedule->related("service_custom_schedule_days")->fetchAll();
        $this->days = $days;
        $this->template->days = $days;

        $daysDefaults = array();
        $userSettings = $this->database->table("settings")->fetch();
        $this->template->userSettings = $userSettings;


        $calendarPeriod = gmdate("H:i:s", $userSettings->sample_rate * 60);
        $this->template->calendarPeriod = $calendarPeriod;


        foreach ($days as $day) {
            $daysDefaults[] = [
                "uuid" => $day->uuid,
                "day" => $this->formater->getDateFormatedFromTimeStamp($day->start),
                "timeStart" => $this->formater->getTimeFormatedFromTimeStamp($day->start),
                "timeEnd" => $this->formater->getTimeFormatedFromTimeStamp($day->end)
            ];
        }
    }

    public function handleDeleteService($id)
    {
        $hidden = $this->database->table("services")->get($id)->hidden;
        if ($hidden) {
            $this->database->table("services")->where("id=?", $id)->update([
                "hidden" => 0
            ]);
            $this->flashMessage("Služba je zobrazena", "success");
        } else {
            $this->database->table("services")->where("id=?", $id)->update([
                "hidden" => 1
            ]);
            $this->flashMessage("Služba je Skryta", "success");
        }

        $this->redirect("Services:");
    }

    private function getWorkingHours(int $day)
    {
        $workingHours = $this->database->table("workinghours")->where("weekday=? AND service_id=?", [$day, $this->service->id])->fetchAll();
        $return = [];
        if ($workingHours) {
            foreach ($workingHours as $key => $value) {
                $return[] = ["start" => $value->start, "end" => $value->stop];
            }
        }

        return $return ? $return : null;
    }

    protected function getDefaultWorkingHours()
    {
        $multiplier = $this->getWorkingHours(0);
        $multiplierTu = $this->getWorkingHours(1);
        $multiplierWe = $this->getWorkingHours(2);
        $multiplierTh = $this->getWorkingHours(3);
        $multiplierFr = $this->getWorkingHours(4);
        $multiplierSa = $this->getWorkingHours(5);
        $multiplierSu = $this->getWorkingHours(6);

        $defaultWorkingHours = [
            "mo" => $multiplier ? true : null,
            "tu" => $multiplierTu ? true : null,
            "we" => $multiplierWe ? true : null,
            "th" => $multiplierTh ? true : null,
            "fr" => $multiplierFr ? true : null,
            "sa" => $multiplierSa ? true : null,
            "su" => $multiplierSu ? true : null,
            "multiplier" => $multiplier,
            "multiplierTu" => $multiplierTu,
            "multiplierWe" => $multiplierWe,
            "multiplierTh" => $multiplierTh,
            "multiplierFr" => $multiplierFr,
            "multiplierSa" => $multiplierSa,
            "multiplierSu" => $multiplierSu,
        ];

        return $defaultWorkingHours;
    }

    protected function createComponentWorkingHoursForm(): Form
    {
        $timeComparison = function ($endTimeField, $startTimeField) {
            $startTime = $startTimeField;
            $endTime = $endTimeField->value;
            return $endTime >= $startTime;
        };

        $form = new Form;
        $form->addSubmit("submit", "Uložit změny");
        $copies = 1;
        $maxCopies = 10;

        $form->addCheckbox("mo", "po");
        $mo = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['mo'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['mo'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $mo->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $mo->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->addCheckbox("tu", "ut");
        $tu = $form->addMultiplier("multiplierTu", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['tu'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['tu'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $tu->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $tu->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->addCheckbox("we", "we");
        $we = $form->addMultiplier("multiplierWe", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['we'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['we'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $we->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $we->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->addCheckbox("th", "th");
        $th = $form->addMultiplier("multiplierTh", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['th'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['th'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $th->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $th->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->addCheckbox("fr", "fr");
        $fr = $form->addMultiplier("multiplierFr", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['fr'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['fr'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $fr->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $fr->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->addCheckbox("sa", "sa");
        $sa = $form->addMultiplier("multiplierSa", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['sa'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['sa'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $sa->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $sa->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->addCheckbox("su", "su");
        $su = $form->addMultiplier("multiplierSu", function (Nette\Forms\Container $container, Form $form) use ($timeComparison) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['su'], Form::FILLED)
                ->setRequired("Zacatek je povinny");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time")
                ->addConditionOn($form['su'], Form::FILLED)
                ->setRequired("Konec je povinny")
                ->addConditionOn($container["start"], Form::FILLED)
                ->addRule($timeComparison, "Konec musí byt ve časovém rozsahu", $container["start"]);
        }, $copies, $maxCopies);

        $su->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $su->addCreateButton("+")
            ->addClass("weekday-time-add");

        $form->onSuccess[] = [$this, "workingHoursSubmit"];

        return $form;
    }

    public function workingHoursSubmit(Form $form, $data)
    {
        $isSuccess = false;
        $this->database->transaction(function ($database) use ($data, &$isSuccess) {
            try {
                //monday
                $database->table('workinghours')->where("weekday=0")->where("service_id=?", $this->service->id)->delete();
                if ($data->mo) {
                    foreach ($data->multiplier as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 0,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }
                //tuesday
                $database->table("workinghours")->where("weekday=1")->where("service_id=?", $this->service->id)->delete();
                if ($data->tu) {
                    foreach ($data->multiplierTu as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 1,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }
                //wednesday
                $database->table("workinghours")->where("weekday=2")->where("service_id=?", $this->service->id)->delete();
                if ($data->we) {
                    foreach ($data->multiplierWe as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 2,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }
                //thursday
                $database->table("workinghours")->where("weekday=3")->where("service_id=?", $this->service->id)->delete();
                if ($data->th) {
                    foreach ($data->multiplierTh as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 3,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }
                //friday
                $database->table("workinghours")->where("weekday=4")->where("service_id=?", $this->service->id)->delete();
                if ($data->fr) {
                    foreach ($data->multiplierFr as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 4,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }
                //saturday
                $database->table("workinghours")->where("weekday=5")->where("service_id=?", $this->service->id)->delete();
                if ($data->sa) {
                    foreach ($data->multiplierSa as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 5,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }
                //sunday
                $database->table("workinghours")->where("weekday=6")->where("service_id=?", $this->service->id)->delete();
                if ($data->su) {
                    foreach ($data->multiplierSu as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 6,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id,
                            "service_id" => $this->service->id
                        ]);
                    }
                }

                $isSuccess = true;
            } catch (\Throwable $th) {
            }
        });
        if ($isSuccess) {
            $this->flashMessage("Uloženo", "success");
            $this->redirect("default");
        } else {
            $this->flashMessage("Nastala chyba", "error");
        }
    }

    protected function createComponentEditCustomScheduleForm(): Form
    {

        $form = new Form;

        //$form->addText("scheduleName")->setRequired("Název je povinný");
        //$form->addText("range")->setRequired();
        $form->addHidden("events");

        $form->addSubmit("submit", "Uložit");
        $form->onSuccess[] = [$this, "editCustomScheduleFormSuccess"];

        return $form;
    }

    public function editCustomScheduleFormSuccess(Form $form, $data)
    {
        $res = $this->database->transaction(function ($database) use ($data) {
            $success = true;
            try {
                //$range = $this->formater->getDataFromString($data->range);
                $database->table("services_custom_schedules")->where("id=?", $this->schedule->id)->update([
                    //"start" => $range["start"],
                    //"name" => $data->scheduleName,
                    // "end" => $range["end"],
                    "updated_at" => date("Y-m-d H:i:s")
                ]);
            } catch (\Exception $e) {
                $success = false;
            }
            if ($success) {
                try {
                    //remove all events
                    $database->table("service_custom_schedule_days")->where("service_custom_schedule_id=?", $this->schedule->id)->delete();
                } catch (\Exception $e) {
                    $success = false;
                }
            }

            if ($success) {
                //add new events

                try {
                    $events = Nette\Utils\Json::decode($data->events);
                    foreach ($events as $day) {
                        $uuid = Uuid::uuid4();
                        $start = date("Y-m-d H:i:s", strtotime($day->start));
                        $end = date("Y-m-d H:i:s", strtotime($day->end));
                        $this->database->table("service_custom_schedule_days")->insert([
                            "uuid" => $uuid,
                            "service_custom_schedule_id" => $this->schedule->id,
                            "start" => $start,
                            "end" => $end,
                            "type" => 0
                        ]);
                    }
                } catch (\Throwable $th) {
                    $success = false;
                }
            }

            return $success;
        });

        if ($res) {
            $this->flashMessage("Rozvrh byl uložen", "success");
            //$this->restoreRequest($this->backlink);
            $this->redirect("this");
        } else {
            $this->flashMessage("Nepodarilo se vytvořit službu", "error");
            $this->redirect("this");
        }
    }


    protected function createComponentCreateForm(): Form
    {


        $form = new Form;


        $form->addHidden("action");
        $form->addText("name", "Name")
            ->setRequired("Jméno je povinné");
        // $form->addTextArea("description", "Description")
        //     ->setMaxLength(500)
        //     ->setRequired("Popis je povinný");
        $form->addText("duration", "Duration")
            ->setHtmlAttribute("type", "number")
            ->setRequired("Doba je povinná")
            ->addRule($form::Min, "Doba nesmí být menší než 1", 1);
        $form->addText("price", "Price")
            ->setHtmlAttribute("type", "number")
            ->setRequired("Cena je povinná")
            ->addRule($form::Min, "Cena nesmí být meně než 0", 0);

        $form->addSelect("type", "Type", [0 => "Týdenní", 2 => "Vlastní týdenní", 1 => "Definovaný"]);

        // $form->addCheckbox("customSchedule", "Custom Schedule")
        //     ->addCondition($form::Equal, true)
        //     ->toggle('#servicesCustomFields');

        // $form->addText("range")
        //     ->addConditionOn($form["customSchedule"], $form::Equal, true)
        //     ->setRequired("Rozsah je povinný");
        // $form->addText("scheduleName")
        //     ->addConditionOn($form["customSchedule"], $form::Equal, true)
        //     ->setRequired("Název je povinný")
        //     ->addRule($form::PATTERN, "Název může obsahovat pouze znaky", "^[a-zA-Z0-9 ]+$");

        // $multiplier = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Nette\Forms\Form $form) {
        //     $container->addText("day", "text")
        //         ->addConditionOn($form["customSchedule"], $form::Equal, true)
        //         ->setRequired("Den je povinný");
        //     $container->addText("timeStart", "Začátek")
        //         ->setHtmlAttribute("type", "time")
        //         ->addConditionOn($form["customSchedule"], $form::Equal, true)
        //         ->setRequired("Začátek je povinný");
        //     $container->addText("timeEnd", "Konec")
        //         ->setHtmlAttribute("type", "time")
        //         ->addConditionOn($form["customSchedule"], $form::Equal, true)
        //         ->setRequired("Konec je povinný");

        //     // Custom validation function
        //     $validateTimeRange = function ($timeEndField) use ($container) {
        //         $timeStart = $container['timeStart']->getValue();
        //         $timeEnd = $timeEndField->getValue();

        //         $start = strtotime($timeStart);
        //         $end = strtotime($timeEnd);

        //         return $end > $start;
        //     };

        //     $container['timeEnd']->addConditionOn($form["customSchedule"], $form::Equal, true)->addRule($validateTimeRange, 'Čas ukončení musí být později než čas začátku.');
        // }, 1);

        $form->addSubmit("submit", "Uložit");
        $form->onSuccess[] = [$this, "createFormSuccess"];
        // $multiplier->addCreateButton('Přidat')
        //     ->addClass('btn btn-primary');
        // $multiplier->addRemoveButton('Odebrat')
        //     ->addClass('btn btn-danger');

        return $form;
    }

    public function createFormSuccess(Form $form, $data)
    {
        bdump($data);
        $res = $this->database->transaction(function ($database) use ($data) {
            $success = true;
            try {
                $service = $database->table("services")->insert([
                    "name" => $data->name,
                    "price" => $data->price,
                    "duration" => $data->duration,
                    // for custom schedules only
                    "type" => $data->type
                ]);
            } catch (\Exception $e) {
                $success = false;
            }
            if ($data->type == 1) {
                //create schedule
                try {
                    $uuid = Uuid::uuid4();
                    $database->table("services_custom_schedules")->insert([
                        "uuid" => $uuid,
                        "service_id" => $service->id,
                        "type" => 0
                    ]);
                } catch (\Throwable $th) {
                    $success = false;
                }
            }
            return $success;
        });

        if ($res) {
            $this->flashMessage("Služba byla vytvořena", "success");
            $this->redirect("default");
        } else {
            $this->flashMessage("Nepodarilo se vytvořit službu", "error");
            $this->redirect("this");
        }
    }

    protected function createComponentEditForm(): Form
    {
        $form = new Form;
        $form->addText("name", "Name")
            ->setDefaultValue($this->service->name)
            ->setRequired("Jméno je povinné");
        // $form->addTextArea("description", "Description")
        //     ->setDefaultValue($this->service->description)
        //     ->setMaxLength(500);

        $form->addText("duration", "Duration")
            ->setDefaultValue($this->service->duration);
        $form->addText("price", "Price")
            ->setDefaultValue($this->service->price)
            ->setHtmlAttribute("type", "number")
            ->setRequired("Cena je povinna")
            ->addRule($form::Min, "Cena musí být větší než 0", 0);

        $form->addSubmit("submit", "Uložit změny");

        $form->onSuccess[] = [$this, "editFormSuccess"];

        return $form;
    }

    public function editFormSuccess(Form $form, $data)
    {
        $isSuccess = true;
        try {
            $this->database->table("services")->where("id=?", $this->id)->update([
                "name" => $data->name,
                "price" => $data->price,
            ]);
        } catch (\Throwable $th) {
            $isSuccess = false;
        }

        if ($isSuccess) {
            $this->flashMessage("Změny byly uloženy", "success");
            $this->redirect("default");
        }
        $this->flashMessage("Změny se nepodařili uložit", "error");
        $this->redirect("this");
    }

    public function handleDelete($id)
    {
        $this->database->transaction(function ($database) use ($id) {
            $service = $database->table("services")->get($id);
            if ($service->related("reservations")->count() == 0) {
                $service->delete();
                $this->flashMessage("Služba byla smazána", "success");
            } else {
                $this->flashMessage("Služba se nepodařila smazat", "error");
            }
        });
        $this->redirect("this");
    }

    public function handleArchive($id)
    {
        $this->database->transaction(function ($database) use ($id) {
            try {
                $database->table("services")->where("id=?", $id)->update([
                    "hidden" => 2
                ]);
                $this->flashMessage("Služba byla archivována", "success");
            } catch (\Throwable $th) {
                $this->flashMessage("Služba se nepodařila archivovat", "error");
            }
        });
        $this->redirect("this");
    }
}
