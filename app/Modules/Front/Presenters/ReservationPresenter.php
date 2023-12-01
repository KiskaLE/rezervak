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


final class ReservationPresenter extends BasePresenter
{
    private $user_uuid;
    private $user;
    private $service;


    public function __construct(
        private Nette\Database\Explorer $database,
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

    public function actionCreate($u, $run, $day, $service_id, $discountCode = "")
    {
        $this->user_uuid = $u;
        $this->user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        if ($this->isAjax()) {
            if ($run == "fetch") {
                $user_settings = $this->user->related("settings")->fetch();
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->service = $service;
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
            $this->payload->url = $this->link("Reservation:create", $u);
        }

        $services = $this->database->table("services")->where("user_id=? AND hidden=?", [$this->user->id, 0])
            ->fetchAll();
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
        $reservation = $this->database->table("reservations")->where("uuid=? AND type=1", $r)->fetch();
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
            ->setRequired()
            ->addCondition($form::FILLED)
            ->addRule($form::PATTERN, 'Jméno nesmí obsahovat čísla', '^[a-zA-Z]+$');
        $form->addText("lastname", "Příjmení:")
            ->setRequired()
            ->addCondition($form::FILLED)
            ->addRule($form::PATTERN, 'Příjmení nesmí obsahovat čísla', '^[a-zA-Z]+$');
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
            ->setRequired()
            ->addCondition($form::FILLED)
            ->addRule($form::PATTERN, 'Město nesmí obsahovat čísla', '^[a-zA-Z]+$');

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
                $this->flashMessage("Nepovedlo se vytvořit rezervaci. Termín je již obsazen", "alert-danger");
                $this->redirect("create", $this->user_uuid);
            }
            $result = $this->insertReservation($uuid, $data, "default", $time);
            bdump($result);
            if (!$result) {
                $this->flashMessage("Nepovedlo se uložit rezervaci.", "alert-danger");
                $this->redirect("create", $this->user_uuid);
            }
            $this->mailer->sendConfirmationMail($email, $this->link("Payment:default", $uuid));
            $this->redirect("Reservation:confirmation", ["r" => $uuid]);
        } else if ($data->dateType == "backup") {
            $times = $session->availableBackupTimes;
            $time = $times[$data->time];
            if (!$this->checkAvailability($this->user_uuid, $data->date, $data->service, $time, "backup")) {
                $this->flashMessage("Nepovedlo se vytvořit rezervaci. Termín je již obsazen", "alert-danger");
                $this->redirect("create", $this->user_uuid);
            }
            $result = $this->insertReservation($uuid, $data, "backup", $time);
            if (!$result) {
                $this->flashMessage("Nepovedlo se uložit rezervaci.", "alert-danger");
                $this->redirect("create", $this->user_uuid);
            }
            $this->mailer->sendBackupConfiramationMail($email, $this->link("Payment:backup", $uuid));
            $this->redirect("Reservation:confirmation", ["r" => $uuid]);
        }
        $this->redirect("create");
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
        bdump($available);
        bdump($time);
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
                $this->payments->createPayment($reservation, $data->dicountCode);
                return $reservation;
            } catch (\Throwable $e) {
                return false;
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
                    $price = $price - $service->price * $discount->value / 100;
                }
            }
            $this->sendJson(["status" => true, "price" => $price]);
        } else {
            $this->sendJson(["status" => false, "price" => $price]);
        }
    }

}
