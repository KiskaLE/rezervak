<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class WorkhoursPresenter extends SecurePresenter
{
    private $id;
    private $edit_id;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User     $user)
    {

    }

    public function renderShow()
    {
        $days = $this->database->table("workinghours")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->days = $days;
    }

    public function actionEdit($id)
    {
        $this->id = $id;
        $day = $this->database->table("workinghours")->where("user_id=? AND id=?", [$this->user->id, $id])->fetch();
        $this->template->day = $day;
        //breaks
        $breaks = $day->related("breaks")->fetchAll();
        $this->template->breaks = $breaks;
    }

    public function actionCreateBreak($id)
    {
        $this->template->id = $id;
        $this->id = $id;

    }

    public function actionEditBreak($id, $edit_id) {
        $this->template->id = $id;
        $this->id = $id;
        $this->edit_id = $edit_id;
        $this->template->edit_id = $edit_id;
    }

    public function actionDeleteBreak($id, $edit_id)
    {
        $this->template->id = $id;
        $this->edit_id = $edit_id;
        $this->template->edit_id = $edit_id;
        $this->id = $id;
    }

    protected function createComponentCreateBreakForm(): Form
    {
        $form = new Form;
        $form->addText("start")->setRequired()->setHtmlAttribute("type", "time");
        $form->addText("stop")->setRequired()->setHtmlAttribute("type", "time");
        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "createBreakSuccess"];

        return $form;
    }

    public function createBreakSuccess(Form $form, $data)
    {
        $day = $this->database->table("workinghours")->where("id=?", $this->id)->fetch();
        $dayStart = strtotime($day->start);
        $dayEnd = strtotime($day->stop);

        if (strtotime($data->start) >= $dayStart && strtotime($data->stop) <= $dayEnd) {
            $start = strtotime($data->start);
            $stop = strtotime($data->stop);
            if ($start > $stop) {
                $this->flashMessage("Začátek nemůže být větší než konec", "error");
            } else {
                $this->database->table("breaks")->insert([
                    "start" => $data->start,
                    "end" => $data->stop,
                    "workinghour_id" => $this->id,
                    "type" => 0
                ]);
                $this->redirect("Workhours:edit", $this->id);
            }
        } else {
            $this->flashMessage("Přestávka musí být v rozsahu {$day->start} - {$day->stop} hodin", "error");
        }
    }

    protected function createComponentDeleteBreakForm(): Form
    {
        $form = new Form;

        $form->addSubmit("submit", "Smazat");
        $form->onSuccess[] = [$this, "deleteBreakSuccess"];

        return $form;
    }

    protected function createComponentEditBreakForm(): Form
    {
        $id = $this->id;
        $break = $this->database->table("breaks")->where("id=?", $id)->fetch();
        $form = new Form;
        $form->addText("start")->setRequired()->setHtmlAttribute("type", "time")->setDefaultValue($break->start);
        $form->addText("stop")->setRequired()->setHtmlAttribute("type", "time")->setDefaultValue($break->end);
        $form->addSubmit("submit");
        $form->onSuccess[] = [$this, "editBreakSuccess"];
        return $form;
    }

    public function editBreakSuccess(Form $form, $data)
    {
        $day = $this->database->table("workinghours")->where("id=?", $this->edit_id)->fetch();
        $dayStart = strtotime($day->start);
        $dayEnd = strtotime($day->stop);

        if (strtotime($data->start) >= $dayStart && strtotime($data->stop) <= $dayEnd) {
            $start = strtotime($data->start);
            $stop = strtotime($data->stop);
            if ($start > $stop) {
                $this->flashMessage("Začátek nemůže být větší než konec", "error");
            } else {
                $this->database->table("breaks")->where("id=?", $this->id)->update([
                    "start" => $data->start,
                    "end" => $data->stop,
                ]);
                $this->redirect("Workhours:edit", $this->edit_id);
            }
        } else {
            $this->flashMessage("Přestávka musí být v rozsahu {$day->start} - {$day->stop} hodin", "error");
        }
    }

    public function deleteBreakSuccess(Form $form, $data)
    {
        //TODO exceptins
        $this->database->table("breaks")->where("id=?", $this->id)->delete();
        $this->redirect("Workhours:edit", $this->edit_id);

    }


    protected function createComponentCreateForm(): Form
    {
        $form = new Form;
        //Monday
        $form->addText("mo_start")->setRequired();
        $form->addText("mo_stop")->setRequired();
        //tuesday
        $form->addText("tu_start")->setRequired();
        $form->addText("tu_stop")->setRequired();
        //wednesday
        $form->addText("we_start")->setRequired();
        $form->addText("we_stop")->setRequired();
        //thursday
        $form->addText("th_start")->setRequired();
        $form->addText("th_stop")->setRequired();
        //friday
        $form->addText("fr_start")->setRequired();
        $form->addText("fr_stop")->setRequired();
        //saturday
        $form->addText("sa_start")->setRequired();
        $form->addText("sa_stop")->setRequired();
        //sunday
        $form->addText("su_start")->setRequired();
        $form->addText("su_stop")->setRequired();

        $form->addSubmit("submit", "Create");

        $form->onSuccess[] = [$this, "createSuccess"];

        return $form;
    }

    public function createSuccess(Form $form, $data)
    {
        //combines start and end
        $count = 0;
        $daysTimes = [];
        $days = [];
        foreach ($data as $time) {
            $daysTimes[] = $time;
            if ($count < 1) {
                $count++;
            } else {
                $count = 0;
                $days[] = $daysTimes;
                $daysTimes = [];
            }
        }
        //writes into database
        $i = 0;
        foreach ($days as $day) {
            $this->database->table("workinghours")->insert([
                "weekday" => $i,
                "start" => $day[0],
                "stop" => $day[1],
                "user_id" => $this->user->id,
            ]);
            $i++;
        }
        $this->redirect("Workhours:show");
    }


    protected function createComponentEditForm(): Form
    {
        $form = new Form;
        $form->addText("start")->setRequired();
        $form->addText("stop")->setRequired();
        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "editSuccess"];

        return $form;
    }

    public function editSuccess(Form $form, $data)
    {
        $start = strtotime($data->start);
        $stop = strtotime($data->stop);
        if ($start > $stop) {
            $this->flashMessage("Začátek nemůže být větší než konec", "error");
        } else {
            $this->database->table("workinghours")->where("id=?", $this->id)->update([
                "start" => $data->start,
                "stop" => $data->stop,
            ]);
            $this->redirect("Workhours:show");
        }

    }
}
