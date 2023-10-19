<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Modules\AvailableDates;


final class ReservationPresenter extends BasePresenter
{

    public function __construct(
        private Nette\Database\Explorer $database,
        private AvailableDates $availableDates
    ){

    }

    public function afterRender()
    {
        $this->availableDates->getAvailableStartingHours("2024-1-1", 30, );
    }

    protected function beforeRender()
    {
        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
    }

    public function handleSelectDay(): void
    {
        $this->redrawControl("dayOptions");
    }

    protected function createComponentForm(): Form
    {
        $services = $this->database->table("services")->fetchAll();
        $servicesList = [];
        foreach ($services as $service){
            $servicesList[] = $service->name;
        }
        $form = new Form;
        $form->addSelect("service", "", $servicesList)->setRequired();
        $form->addText("date")->setHtmlAttribute("type", "date")->setRequired();
        $form->addButton("select_day", "Vybrat")->setHtmlAttribute("class", "ajax" );
        $form->addSelect("Time")->setRequired();


        return $form;
    }

}
