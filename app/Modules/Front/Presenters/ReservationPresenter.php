<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Modules\AvailableDates;
use App\Modules\Mailer;
use App\Modules\Payments;
use App\Modules\DiscountCodes;
use Ramsey\Uuid\Uuid;


final class ReservationPresenter extends BasePresenter
{

    private $services;
    private $user_uuid;
    private $user;

    public function __construct(
        private Nette\Database\Explorer $database,
        private AvailableDates          $availableDates,
        private Mailer                  $mailer,
        private Payments                $payments,
        private DiscountCodes           $discountCodes,
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
    }

    public function actionCreate($u, $run, $day, $service_id, $discountCode = "")
    {
        $this->user_uuid = $u;
        $this->user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        if ($this->isAjax()) {
            if ($run == "fetch") {
                $user_settings = $this->user->related("settings")->fetch();
                bdump($user_settings);
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->sendJson(["availableDates" => $this->availableDates->getAvailableDates($u, $service->duration, $user_settings->number_of_days)]);
            } else if ($run == "setDate") {
                $this->setDate($u, intval($service_id), $day);
            } else if ($run == "verifyCode") {
                $this->verifyDiscountCode($this->user->id ,intval($service_id), $discountCode);
            } else if ($run == "getServiceName") {
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->sendJson(["serviceName" => $service->name]);
            }
            $this->payload->postGet = true;
            $this->payload->url = $this->link("Reservation:create", $u);
        }

        $services = $this->database->table("services")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->services = $services;
        $this->redrawControl("content");

    }

    public
    function actionConfirmation($r)
    {
        $this->template->uuid = $r;
        $reservation = $this->database->table("reservations")->where("uuid=?", $r)->fetch();
        $this->template->reservation = $reservation;
    }

    public
    function actionBackup($r)
    {
        $reservation = $this->database->table("backup_reservations")->where("uuid=?", $r)->fetch();
        $this->template->reservation = $reservation;
    }

    protected function createComponentForm(): Form
    {
        $services = $this->database->table("services")->fetchAll();
        $this->services = $services;

        $form = new Form;
        $form->addhidden("service")->setRequired();
        $form->addHidden("dateType")->setRequired();
        $form->addHidden("date")->setRequired();
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

    public function formSucceeded(Form $form,\stdClass $data): void
    {
        $service_id = $data->service;
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = intval($service->duration);
        $uuid = strval(Uuid::uuid4());
        $email = $data->email;

        if ($data->dateType == "default") {
            $times = $this->availableDates->getAvailableStartingHours($this->user_uuid ,$data->date, $duration);
            $reservation = $this->insertReservation($uuid, $data, "reservations", $times);
            if ($reservation) {
                $this->payments->createPayment($reservation, $data->dicountCode);
                $this->mailer->sendConfirmationMail($email, $this->link("Payment:default", $uuid));
                $this->redirect("Reservation:confirmation", ["r" => $uuid]);
            } else {
                $this->flashMessage("Nepovedlo se uložit rezervaci.");
            }
        } else if ($data->dateType == "backup") {
            $times = $this->availableDates->getBackupHours($data->date, $service->duration);
            $reservation = $this->insertReservation($uuid, $data, "backup_reservations", $times);
            if ($reservation) {
                $this->payments->createPayment($reservation, $data->dicountCode);
                $this->mailer->sendBackupConfiramationMail($email, $this->link("Payment:backup", $uuid));
                $this->redirect("Reservation:backup", ["r" => $uuid]);
            } else {
                $this->flashMessage("Nepovedlo se uložit rezervaci.");
            }
        }

    }

    /**
     * Inserts a reservation into the specified table.
     *
     * @param string $uuid The UUID of the reservation.
     * @param object $data The reservation data.
     * @param string $insertTable The table to insert the reservation into.
     * @param array $times The array of available times.
     * @return mixed The inserted reservation data.
     */
    private function insertReservation(string $uuid, $data, string $insertTable, $times)
    {
        $service_id = $data->service;

        $reservation = $this->database->table($insertTable)->insert([
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
            "created_at" => date("Y-m-d H:i:s"),
            "user_id" => $this->user->id,
        ]);

        return $reservation;
    }
    /**
     * Sets the date for a given service ID and day.
     *
     * @param int $service_id The ID of the service.
     * @param string $day The day for which to set the date.
     * @throws Exception If the service cannot be fetched from the database.
     * @return void
     */
    private function setDate(string $u, int $service_id, string $day): void
    {
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = $service->duration;
        $availableTimes = $this->availableDates->getAvailableStartingHours($u, $day, intval($duration));
        $availableBackup = $this->availableDates->getBackupHours($day, intval($duration));
        $this->template->times = $availableTimes;
        $this->template->backupTimes = $availableBackup;
        $this->redrawControl("content");
    }
    /**
     * Verifies a discount code for a given service.
     *
     * @param int $service_id The ID of the service.
     * @param string $discountCode The discount code to verify.
     * @throws None
     * @return void
     */
    private function verifyDiscountCode(int $user_id, int $service_id, string $discountCode): void
    {
        $discount = $this->discountCodes->isCodeValid($user_id ,$service_id, $discountCode);
        $service = $this->discountCodes->getService(intval($service_id));
        $price = $service->price;
        if ($discount) {

            if ($discount->type == 0) {
                if ($discount->value >= $price) {
                    $price = 0;
                } else {
                    $price = $service->price - $discount->value;
                }
            } else {
                if ($discount->value >= 100) {
                    $price = 0;
                } else {
                    $price = $service->price  * $discount->value / 100;
                }
            }
            $this->sendJson(["status" => true, "price" => $price]);
        } else {
            $this->sendJson(["status" => false, "price" => $price]);
        }
    }

}
