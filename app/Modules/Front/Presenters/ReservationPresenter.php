<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class ReservationPresenter extends BasePresenter
{

    public function __construct(
        private Nette\Database\Explorer $database
    ){

    }

    protected function beforeRender()
    {
        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
    }

    protected function createComponentReservationForm(): Form
    {
        $form = new Form;
        $form->addText("test", "test");


        return $form;
    }

}
