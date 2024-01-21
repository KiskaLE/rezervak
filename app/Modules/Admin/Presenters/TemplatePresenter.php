<?php

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\ComponentModel\IComponent;
use Nette\DI\Attributes\Inject;
use Nette\Application\UI\Form;

final class TemplatePresenter extends SecurePresenter {

    #[Inject] public Nette\Database\Explorer $database;
    public function __construct(
    )
    {

    }

    public function beforeRender() {

        parent::beforeRender();
    }

    public function actionDefault() {
        $this->template->selectedPage = "dashboard";
        $user_uuid = $this->database->table('users')->where("id=?", $this->user->id)->fetch()->uuid;
        $this->template->userPath = $user_uuid;
    }

    protected function createComponentWorkingHours(): Form 
    {
        $form = new Form;
        $form->addSubmit("submit");
        $copies = 1;
        $maxCopies = 10;
        
        $mo = $form->addMultiplier("multiplier", function (Nette\Forms\Container $container, Form $form) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time");
        },$copies, $maxCopies);

        $mo->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $mo->addCreateButton("add")
            ->addClass("weekday-time-add");

        $tu = $form->addMultiplier("multiplierTu", function (Nette\Forms\Container $container, Form $form) {
            $container->addText("start", "zacatek")
                ->setHtmlAttribute("type", "time");
            $container->addText("end", "konec")
                ->setHtmlAttribute("type", "time");
        },$copies, $maxCopies);

        $tu->addRemoveButton("X")
            ->addClass("weekday-time-delete");
        $tu->addCreateButton("add")
            ->addClass("weekday-time-add");

       

        $form->onSubmit[] = [$this, "workingHoursSubmit"];

        return $form;
    
    }

    public function workingHoursSubmit(Form $form, $data)
    {
        $this->flashMessage("Uloženo", "success");
    }
    

}

