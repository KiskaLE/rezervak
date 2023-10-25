<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Modules\AvailableDates;
use App\Modules\Mailer;
use Ramsey\Uuid\Uuid;


final class ReservationPresenter extends BasePresenter
{

    private $services;

    public function __construct(
        private Nette\Database\Explorer $database,
        private AvailableDates $availableDates,
        private Mailer $mailer
    ){

    }

    protected function startup()
    {
        parent::startup();
        $this->template->times = [];

    }


    protected function beforeRender()
    {
        parent::beforeRender();

        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
        $this->redrawControl("content");
    }
    public function actionCreate($run, $day, $service_id) {

        if ($this->isAjax()) {
            if ($run === "fetch") {
                //TODO number of Days stored in database
                $this->sendJson(["availableDates" => $this->availableDates->getAvailableDates(30, 60)]);
            } else if ($run === "setDate") {
                $service = $this->database->table("services")->where("id=?", $service_id+1)->fetch();
                $duration = $service->duration;
                $times = $this->availableDates->getAvailableStartingHours($day, intval($duration) );

                bdump($times);
                $this->template->times = $times;
                $this->redrawControl("content");
            }
        }
    }

    public function actionConfirmation($uuid) {
        $this->template->uuid = $uuid;
        $reservation = $this->database->table("registereddates")->where("uuid=?", $uuid)->fetch();
        $this->template->reservation = $reservation;
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
        $form->addhidden("service")->setRequired();
        $form->addHidden("date")->setRequired();
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
        $uuid = Uuid::uuid4();
        $service_id = $this->services[$data->service+1]->id;
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = intval($service->duration);
        $times = $this->availableDates->getAvailableStartingHours($data->date, $duration );
        $status = $this->database->table("registereddates")->insert([
            "uuid" => $uuid,
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
        if ($status) {
            $this->mailer->sendConfirmationMail("vojtech.kylar@securitynet.cz", $this->link("Payment:default", strval($uuid)));
            $this->redirect("Reservation:confirmation" , ["uuid" => strval($uuid)]);
        } else {
            $this->flashMessage("Nepovedlo se uložit rezervaci.");
        }




    }

}
