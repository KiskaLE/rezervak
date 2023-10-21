<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Modules\AvailableDates;


final class ReservationPresenter extends BasePresenter
{

    private $services;

    public function __construct(
        private Nette\Database\Explorer $database,
        private AvailableDates $availableDates
    ){

    }

    protected function startup()
    {
        parent::startup();
        $this->template->hours = [];

    }


    protected function beforeRender()
    {
        parent::beforeRender();

        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
        $this->redrawControl("content");



    }

    public function handleSendDate(string $date, string $service_id) {
        $service = $this->database->table("services")->where("id=?", $service_id+1)->fetch();
        $duration = $service->duration;
        $times = $this->availableDates->getAvailableStartingHours($date, intval($duration) );

        $this->template->hours = $times;
        $this->redrawControl("content");
    }

    protected function createComponentForm(): Form
    {
        $services = $this->database->table("services")->fetchAll();
        $this->services = $services;
        $servicesList = [];
        foreach ($services as $service){
            $servicesList[] = $service->name;
        }

        $form = new Form;
        $form->addSelect("service", "Služba:", $servicesList)->setRequired();
        $form->addText("date")->setHtmlAttribute("type", "date")->setRequired();
        //$form->addSelect("time", "Čas:", $this->hours)->setRequired();
        $form->addHidden("time")->setRequired();
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
        $times = $this->availableDates->getAvailableStartingHours($data->date, $duration );
        $this->database->table("registereddates")->insert([
            "date" => $data->date,
            "service_id" => $service_id,
            "start" => $times[$data->time],
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
