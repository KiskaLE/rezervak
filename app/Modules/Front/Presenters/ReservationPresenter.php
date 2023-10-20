<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Modules\AvailableDates;


final class ReservationPresenter extends BasePresenter
{

    private $times;
    private $services;

    public function __construct(
        private Nette\Database\Explorer $database,
        private AvailableDates $availableDates
    ){

    }

    public function afterRender()
    {
        parent::afterRender();
        $this->availableDates->getAvailableStartingHours("2024-1-1", 30, );
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->redrawControl("content");

        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;


    }

    protected function createComponentForm(): Form
    {
        $services = $this->database->table("services")->fetchAll();
        $this->services = $services;

        $servicesList = [];
        foreach ($services as $service){
            $servicesList[] = $service->name;
        }


        $times = $this->availableDates->getAvailableStartingHours("2024-1-1",30 );
        $this->times = $times;

        $form = new Form;
        $form->addSelect("service", "Služba:", $servicesList)->setRequired();
        //$form->addText("date")->setHtmlAttribute("type", "date")->setRequired();
        $form->addSelect("time", "Čas:", $times)->setRequired();
        $form->addText("firstname", "Jmeno:")->setRequired();
        $form->addText("lastname", "Příjmení:")->setRequired();
        $form->addText("phone", "Telefon:")->setRequired();
        $form->addText("email", "E-mail:")->setRequired();
        $form->addText("address", "Adresa a čp:")->setRequired();
        $form->addText("code", "PSČ:")->setRequired();
        $form->addText("city", "Město:")->setRequired();

        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "formSucceeded"];
        return $form;
    }

    public function formSucceeded(Form $form, $data): void {
        $service_id = $this->services[$data->service+1]->id;
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = intval($service->duration);
        $date = "2024-1-1";
        $time = $this->times[$data->time];
        $this->database->table("registereddates")->insert([
            "date" => $date,
            "service_id" => $service_id,
            "start" => $time,
            "duration" => $duration,
            "firstname" => $data->firstname,
            "lastname" => $data->lastname,
            "phone" => $data->phone,
            "email" => $data->email,
            "address" => $data->address,
            "code" => $data->code,
            "city" => $data->city
        ]);
        $this->redirect("Home:");

    }

}
