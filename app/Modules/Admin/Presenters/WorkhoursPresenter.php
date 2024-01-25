<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Ramsey\Uuid\Uuid;
use App\Modules\AvailableDates;
use App\Modules\Formater;
use Nette\DI\Attributes\Inject;

final class WorkhoursPresenter extends SecurePresenter
{
    private $id;
    private $editId;
    private $day;

    private $exceptionUuid;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
        private Nette\Security\User $user,
        private AvailableDates      $availableDates,
        private Formater            $formater
    )
    {

    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "workhours";
        $this->backlink = $this->storeRequest();
        $this->template->backlink = $this->backlink;
    }

    public function actionDefault()
    {
        $this->template->selectedPage = "workhours";

        $this["workingHoursForm"]->setDefaults($this->getDefaultWorkingHours());

        //holidays
        $this->template->holidays = $this->database->table("workinghours_exceptions")->where("end > NOW()")->fetchAll();
    }

    private function getWorkingHours(int $day)
    {
        $workingHours = $this->database->table("workinghours")->where("weekday=? AND service_id=0", $day)->fetchAll();
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

        $defaultWorkingHours = $this->getDefaultWorkingHours();

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
                $database->table('workinghours')->where("weekday=0")->where("service_id=0")->delete();
                if ($data->mo) {
                    foreach ($data->multiplier as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 0,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }
                //tuesday
                $database->table("workinghours")->where("weekday=1")->where("service_id=0")->delete();
                if ($data->tu) {
                    foreach ($data->multiplierTu as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 1,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }
                //wednesday
                $database->table("workinghours")->where("weekday=2")->where("service_id=0")->delete();
                if ($data->we) {
                    foreach ($data->multiplierWe as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 2,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }
                //thursday
                $database->table("workinghours")->where("weekday=3")->where("service_id=0")->delete();
                if ($data->th) {
                    foreach ($data->multiplierTh as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 3,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }
                //friday
                $database->table("workinghours")->where("weekday=4")->where("service_id=0")->delete();
                if ($data->fr) {
                    foreach ($data->multiplierFr as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 4,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }
                //saturday
                $database->table("workinghours")->where("weekday=5")->where("service_id=0")->delete();
                if ($data->sa) {
                    foreach ($data->multiplierSa as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 5,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }
                //sunday
                $database->table("workinghours")->where("weekday=6")->where("service_id=0")->delete();
                if ($data->su) {
                    foreach ($data->multiplierSu as $key => $value) {
                        $database->table("workinghours")->insert([
                            "weekday" => 6,
                            "start" => $value["start"],
                            "stop" => $value["end"],
                            "user_id" => $this->user->id
                        ]);
                    }
                }

                $isSuccess = true;
            } catch (\Throwable $th) {
            }
        });
        if ($isSuccess) {
            $this->flashMessage("Uloženo", "success");
            $this->redirect("this");
        } else {
            $this->flashMessage("Nastala chyba", "error");
        }


    }

    protected function createComponentCreateHolidayForm(): Form
    {
        $form = new Form;

        $form->addText("range", "Rozsah")
            ->setRequired("Zadejte rozsah");

        $form->addText("name", "Název")
            ->setRequired("Zadejte název");

        $form->addSubmit("submit", "Vytvořit");

        $form->onSuccess[] = [$this, "createHolidayFormSucceeded"];

        return $form;
    }

    public function createHolidayFormSucceeded(Form $form, array $values)
    {
        $rangeData = $this->formater->getDataFromString($values["range"]);
        $isSuccess = false;
        try {
            $this->database->table("workinghours_exceptions")->insert([
                "start" => $rangeData["start"],
                "end" => $rangeData["end"],
                "name" => $values["name"],
            ]);

            $isSuccess = true;
        } catch (\Throwable $th) {
        }

        if ($isSuccess) {
            $this->flashMessage("Uloženo", "success");
            $this->redirect("this");
        } else {
            $this->flashMessage("Nastala chyba", "error");
            $this->redirect("this");
        }
    }

    public function handleDeleteHoliday($id)
    {
        $isSuccess = false;
        try {
            $this->database->table("workinghours_exceptions")->where("id", $id)->delete();
            $isSuccess = true;
        } catch (\Throwable $th) {
        }
        if ($isSuccess) {
            $this->flashMessage("Volno smazáno", "success");
            $this->redirect("this");
        } else {
            $this->flashMessage("Nastala chyba", "error");
            $this->redirect("this");
        }
    }
}
