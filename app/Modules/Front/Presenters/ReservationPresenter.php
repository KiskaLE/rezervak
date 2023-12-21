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
use App\Modules\Moment;
use Nette\DI\Attributes\Inject;


final class ReservationPresenter extends BasePresenter
{
    private $user_uuid;
    private $user;
    private $service;

    #[Inject] public Nette\Database\Explorer $database;


    public function __construct(
        private AvailableDates          $availableDates,
        private Mailer                  $mailer,
        private Payments                $payments,
        private DiscountCodes           $discountCodes,
        private Moment                  $moment
    )
    {
    }

    protected function startup()
    {
        parent::startup();
        $this->template->times = [];
        $this->template->backupTimes = [];
    }

    public function actionDefault($run, $day, $service_id, $discountCode = "")
    {
        $this->user = $this->database->table("users")->fetch();
        $this->user_uuid = $this->user->uuid;
        $u = $this->user->uuid;
        if ($this->isAjax()) {
            if ($run == "fetch") {
                $user_settings = $this->user->related("settings")->fetch();
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->service = $service;
                $this->payload->postGet = true;
                $this->payload->url = $this->link("default");
                $this->sendJson(["availableDates" => $this->availableDates->getAvailableDates($u, $service->duration, $user_settings->number_of_days, intval($service_id))]);
            } else if ($run == "setDate") {
                $this->setDate($u, intval($service_id), $day);
            } else if ($run == "verifyCode") {
                $this->verifyDiscountCode($this->user->id, intval($service_id), $discountCode);
            } else if ($run == "getServiceName") {
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->sendJson(["serviceName" => $service->name]);
            }
            $this->payload->postGet = true;
            $this->payload->url = $this->link("default");
        }

        $services = $this->database->table("services")->where("user_id=? AND hidden=?", [$this->user->id, 0])
            ->fetchAll();
        $this->template->services = $services;
        $this->redrawControl("content");

    }

    public
    function actionConfirmation($id)
    {
        $this->template->uuid = $id;
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->template->reservation = $reservation;
    }

    public
    function actionBackup($id)
    {
        $reservation = $this->database->table("reservations")->where("uuid=? AND type=1", $id)->fetch();
        $this->template->reservation = $reservation;
    }

    protected function createComponentForm(): Form
    {
        $form = new Form;
        $form->addhidden("service")
            ->setRequired()
            ->addRule($form::PATTERN, 'Vybraná služba je neplatná', '\d+');
        $form->addHidden("dateType")
            ->setRequired()
            ->addRule($form::PATTERN, 'Neplatný druh rezervace', 'default|backup');
        $form->addHidden("date")
            ->setRequired()
            ->addRule($form::PATTERN, 'Neplatný datum', '^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$');
        $form->addHidden("time")
            ->setRequired()
            ->addRule($form::PATTERN, 'Čas musí být číslo', '\d+');
        $form->addText("firstname", "Jmeno:")
            ->setRequired();
        $form->addText("lastname", "Příjmení:")
            ->setRequired();
        $form->addText("phone", "Telefon:")
            ->setRequired()
            ->addRule($form::PATTERN, 'Telefoní čílo není platný', '\d+');
        $form->addText("email", "E-mail:")
            ->setRequired()
            ->addRule($form::EMAIL, 'Neplatný formát e-mailu');
        $form->addText("address", "Adresa a čp:")
            ->setRequired();
        $form->addText("code", "PSČ:")
            ->setRequired()
            ->addRule($form::PATTERN, 'Neplatný formát PSČ', '^\d{5}$');
        $form->addText("city", "Město:")
            ->setRequired();

        $form->addText("dicountCode", "Kód slevy:");
        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "formSucceeded"];
        return $form;
    }

    public function formSucceeded(Form $form, \stdClass $data): void
    {
        $session = $this->getSession('Reservation');
        $uuid = strval(Uuid::uuid4());
        $email = $data->email;

        if ($data->dateType == "default") {
            $times = $session->availableTimes;
            $time = $times[$data->time];
            if (!$this->checkAvailability($this->user_uuid, $data->date, $data->service, $time)) {
                $this->flashMessage("Nepovedlo se vytvořit rezervaci. Termín je již obsazen", "error");
                $this->redirect("default", $this->user_uuid);
            }
            $result = $this->insertReservation($uuid, $data, "default", $time);
            if (!$result) {
                $this->flashMessage("Nepovedlo se uložit rezervaci.", "error");
                $this->redirect("default", $this->user_uuid);
            }
            $this->mailer->sendConfirmationMail($email, $this->link("Payment:default", $uuid), $result);
            $this->redirect("Reservation:confirmation", ["id" => $uuid]);
        } else if ($data->dateType == "backup") {
            $times = $session->availableBackupTimes;
            $time = $times[$data->time];
            if (!$this->checkAvailability($this->user_uuid, $data->date, $data->service, $time, "backup")) {
                $this->flashMessage("Nepovedlo se vytvořit rezervaci. Termín je již obsazen", "error");
                $this->redirect("default", $this->user_uuid);
            }
            $result = $this->insertReservation($uuid, $data, "backup", $time);
            if (!$result) {
                $this->flashMessage("Nepovedlo se uložit rezervaci.", "error");
                $this->redirect("default", $this->user_uuid);
            }
            $this->mailer->sendBackupConfiramationMail($email, $this->link("Payment:backup", $uuid), $result);
            $this->redirect("Reservation:confirmation", ["id" => $uuid]);
        }
        $this->redirect("default");
    }


    private function checkAvailability(string $u, $date, $service_id, $time, $type = "default"): bool
    {
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = $service->duration;
        switch ($type) {
            case "backup":
                $available = $this->availableDates->getBackupHours($u, $date, intval($service->duration), intval($service_id));
                break;
            default:
                $available = $this->availableDates->getAvailableStartingHours($u, $date, intval($duration), intval($service_id));
                break;
        }
        if (in_array($time, $available)) {
            return true;
        }
        return false;
    }

    /**
     * Inserts a reservation into the specified table.
     *
     * @param string $uuid The UUID of the reservation.
     * @param object $data The reservation data.
     * @param string $type The type of reservation.
     * @param array $times The array of available times.
     * @return mixed The inserted reservation data.
     */
    private function insertReservation(string $uuid, $data, string $type, $time)
    {
         $result = $this->database->transaction(function ($database) use ($uuid, $data, $type, $time) {
            $start = $data->date . " " . $time;
            $service_id = $data->service;
             $success = true;
            try {
                $reservation = $this->database->table("reservations")->insert([
                    "uuid" => $uuid,
                    "service_id" => $service_id,
                    "start" => $start,
                    "firstname" => $data->firstname,
                    "lastname" => $data->lastname,
                    "phone" => $data->phone,
                    "email" => $data->email,
                    "address" => $data->address,
                    "code" => $data->code,
                    "city" => $data->city,
                    "created_at" => date("Y-m-d H:i:s"),
                    "user_id" => $this->user->id,
                    "type" => $type == "backup" ? 1 : 0,
                ]);
                if (!$this->payments->createPayment($database, $reservation, $data->dicountCode)) {
                    $success = false;
                }

            } catch (\Throwable $e) {
                $success = false;
            }
             if ($success) {
                 return $reservation;
             }
        });
        return $result;
    }

    /**
     * Sets the date for a given service ID and day.
     *
     * @param int $service_id The ID of the service.
     * @param string $day The day for which to set the date.
     * @return void
     * @throws Exception If the service cannot be fetched from the database.
     */
    private
    function setDate(string $u, int $service_id, string $day): void
    {
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $duration = $service->duration;
        $availableTimes = $this->availableDates->getAvailableStartingHours($u, $day, intval($duration), intval($service_id));
        $availableBackup = $this->availableDates->getBackupHours($u, $day, intval($duration), intval($service_id));

        //store data in session
        $session = $this->getSession('Reservation');
        $session->availableTimes = $availableTimes;
        $session->availableBackupTimes = $availableBackup;

        $this->template->times = $availableTimes;
        $this->template->backupTimes = $availableBackup;
        $this->redrawControl("content");
    }

    /**
     * Verifies a discount code for a given service.
     *
     * @param int $service_id The ID of the service.
     * @param string $discountCode The discount code to verify.
     * @return void
     * @throws None
     */
    private
    function verifyDiscountCode(int $user_id, int $service_id, string $discountCode): void
    {
        $discount = $this->discountCodes->isCodeValid($user_id, $service_id, $discountCode);
        $service = $this->discountCodes->getService($service_id);
        $price = $service->price;
        if ($discount) {

            if ($discount["type"] == 0) {
                if ($discount["value"] >= $price) {
                    $price = 0;
                } else {
                    $price = $service->price - $discount["value"];
                }
            } else {
                if ($discount["value"] >= 100) {
                    $price = 0;
                } else {
                    $price = $price - $service->price * $discount["value"] / 100;
                }
            }
            $this->sendJson(["status" => true, "price" => $price]);
        } else {
            $this->sendJson(["status" => false, "price" => $price]);
        }
    }

}
