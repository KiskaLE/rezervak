<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class WorkhoursPresenter extends SecurePresenter
{
    public $id;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User $user)
    {

    }

    public function renderShow()
    {
        $days = $this->database->table("workinghours")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->days = $days;
    }

    public function actionEdit($id) {
        $this->id = $id;
        $day = $this->database->table("workinghours")->where("user_id=? AND id=?", [$this->user->id, $id])->fetch();
        $this->template->day = $day;
    }


    protected function createComponentCreateForm(): Form
    {
        //todo check if stop is later than start
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

        $form->addSubmit("submit" , "Create");

        $form->onSuccess[] = [$this, "createSuccess"];

        return $form;
    }

    public function createSuccess(Form $form, $data) {
        //combines start and end
        $count = 0;
        $daysTimes = [];
        $days = [];
        foreach ($data as $time){
            $daysTimes[] = $time;
            if ($count<1){
                $count++;
            }else{
                $count = 0;
                $days[] = $daysTimes;
                $daysTimes = [];
            }
        }
        //writes into database
        $i = 0;
        foreach ($days as $day){
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


    protected function createComponentEditForm(): Form{
        $form = new Form;
        $form->addText("start")->setRequired();
        $form->addText("stop")->setRequired();
        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "editSuccess"];

        return $form;
    }

    public function editSuccess(Form $form, $data){
        $this->database->table("workinghours")->where("id=?", $this->id)->update([
            "start" => $data->start,
            "stop" => $data->stop,
        ]);
        $this->redirect("Workhours:show");
    }
}
