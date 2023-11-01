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
        private AvailableDates          $availableDates,
        private Mailer                  $mailer
    )
    {

    }

    protected function startup()
    {
        parent::startup();
        $this->template->times = [];
        $this->template->backupTimes = [];


    }


    protected function beforeRender()
    {
        parent::beforeRender();

        $services = $this->database->table("services")->fetchAll();
        $this->template->services = $services;
        $this->redrawControl("content");
    }

    public function actionCreate($run, $day, $service_id, $discountCode = "")
    {

        if ($this->isAjax()) {
            if ($run == "fetch") {
                //TODO number of Days stored in database
                $this->sendJson(["availableDates" => $this->availableDates->getAvailableDates(30, 60)]);
            }else if ($run == "setDate") {
                $service = $this->database->table("services")->where("id=?", $service_id + 1)->fetch();
                $duration = $service->duration;
                $availableTimes = $this->availableDates->getAvailableStartingHours($day, intval($duration));
                $availableBackup = $this->availableDates->getBackupHours($day, intval($duration));
                $this->template->times = $availableTimes;
                $this->template->backupTimes = $availableBackup;
                $this->redrawControl("content");
            } else if ($run == "verifyCode") {
                //TODO verify code
                $discount = $this->database->table("discount_codes")->where("code=? AND active=1", $discountCode)->fetch();
                if ($discount) {
                    $this->sendJson(["status" => true, "type" => $discount->type, "discount" => ["type" => $discount->type, "value" => $discount->value]]);
                }else {
                    $this->sendJson(["status" => false]);
                }
            }
            $this->payload->postGet = true;
            $this->payload->url = $this->link("Reservation:create");
        }
    }

    public
    function actionConfirmation($uuid)
    {
        $this->template->uuid = $uuid;
        $reservation = $this->database->table("reservations")->where("uuid=?", $uuid)->fetch();
        $this->template->reservation = $reservation;
    }

    public
    function actionBackup($uuid)
    {
        $reservation = $this->database->table("backup_reservations")->where("uuid=?", $uuid)->fetch();
        $this->template->reservation = $reservation;
    }

    protected
    function createComponentForm(): Form
    {
        $services = $this->database->table("services")->fetchAll();
        $this->services = $services;

        $form = new Form;
        $form->addhidden("service")->setRequired();
        $form->addHidden("dateType")->setRequired();
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

        $form->addText("dicountCode", "Kód slevy:");
        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "formSucceeded"];
        return $form;
    }

    public
    function formSucceeded(Form $form, $data): void
    {
        $service_id = $this->services[$data->service + 1]->id;
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = intval($service->duration);
        $uuid = Uuid::uuid4();
        if ($data->dateType == "default") {
            $times = $this->availableDates->getAvailableStartingHours($data->date, $duration);
            $status = $this->database->table("reservations")->insert([
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
                "city" => $data->city,
                "created_at" => date("Y-m-d H:i:s")
            ]);
            if ($status) {
                $this->mailer->sendConfirmationMail("vojtech.kylar@securitynet.cz", $this->link("Payment:default", strval($uuid)));
                $this->redirect("Reservation:confirmation", ["uuid" => strval($uuid)]);
            } else {
                $this->flashMessage("Nepovedlo se uložit rezervaci.");
            }
        } else if ($data->dateType == "backup") {
            $times = $this->availableDates->getBackupHours($data->date, $service->duration);
            $status = $this->database->table("backup_reservations")->insert([
                "uuid" => $uuid,
                "date" => $data->date,
                "service_id" => $service_id,
                "start" => $times[$data->time]->start,
                "firstname" => $data->firstname,
                "lastname" => $data->lastname,
                "phone" => $data->phone,
                "email" => $data->email,
                "address" => $data->address,
                "code" => $data->code,
                "city" => $data->city,
                "created_at" => date("Y-m-d H:i:s")
            ]);
            if ($status) {
                $this->mailer->sendBackupConfiramationMail("vojtech.kylar@securitynet.cz", $this->link("Payment:backup", strval($uuid)));
                $this->redirect("Reservation:backup", ["uuid" => strval($uuid)]);
            } else {
                $this->flashMessage("Nepovedlo se uložit rezervaci.");
            }
        }
    }

}
