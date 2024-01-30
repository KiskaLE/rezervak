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


final class HomePresenter extends BasePresenter
{
    private $user_uuid;
    private $user;
    private $service;

    #[Inject] public Nette\Database\Explorer $database;


    public function __construct(
        private AvailableDates $availableDates,
        private Mailer         $mailer,
        private Payments       $payments,
        private DiscountCodes  $discountCodes,
        private Moment         $moment
    ) {
    }

    protected function startup()
    {
        parent::startup();
        $this->template->times = [];
        $this->template->backupTimes = [];
    }

    public function actionDefault($run, $day, $service_id, $discountCode = "", $krok = 1)
    {
        $user_settings = $this->database->table("settings")->fetch();
        if ($this->isAjax()) {
            if ($run == "fetch") {
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->service = $service;
                $this->template->selectedService = $service;
                $this->payload->postGet = true;
                $this->payload->url = $this->link("default", ["krok" => 2]);
                $this->sendJson(["availableDates" => $this->availableDates->getAvailableDates($user_settings->number_of_days, $service)]);
            } else if ($run == "setDate") {
                $this->setDate(intval($service_id), $day);
            } else if ($run == "verifyCode") {
                $this->verifyDiscountCode(intval($service_id), $discountCode);
            } else if ($run == "getServiceName") {
                $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
                $this->sendJson(["serviceName" => $service->name]);
            }
            $this->payload->postGet = true;
            $this->payload->url = $this->link("default", ["krok" => $krok]);
            $this->redrawControl("content");
        } else {

            $formSession = $this->getSession('newReservationForm');
            if (!$formSession->step) {
                $formSession->step = 1;
                $this->redirect("default", ["krok" => 1]);
            }
            if ($krok != $formSession->step) {
                $this->redirect("default", ["krok" => $formSession->step]);
            }

            if ($krok == 1) {
                $this->getSession('newReservationForm')->setExpiration("10 minutes");
            }
            $this->template->step = $krok;
            $this->user = $this->database->table("users")->order("created_at ASC")->fetch();
            $this->user_uuid = $this->user->uuid;


            switch ($krok) {
                case 1:
                    $services = $this->database->table("services")->where("hidden=0")
                        ->fetchAll();
                    $servicesAvailableTimesCount = [];
                    foreach ($services as $service) {
                        $servicesAvailableTimesCount[$service->id] = $this->availableDates->getNumberOfAvailableTimes($user_settings->number_of_days, $service);
                    }
                    $this->template->servicesAvailableTimesCount = $servicesAvailableTimesCount;
                    $this->template->services = $services;
                case 2:
                    if ($formSession->service) {
                        $service = $this->database->table("services")->where("id=?", $formSession->service)->fetch();
                        $this->template->selectedService = $service;
                        $available = $this->availableDates->getAvailableDates($user_settings->number_of_days, $service);
                        $explode = explode("-", $available[0]);
                        $this->template->month = $explode[1];
                        $this->template->year = $explode[0];
                        $this->template->availableDates = $available;
                    }
                    break;
                case 3:
                    $service = $this->database->table("services")->where("id=?", $formSession->service)->fetch();
                    $this->template->selectedService = $service;
                    $this->template->date =  $formSession->date;
                    $this->template->selectedTime = $formSession->time;
                    break;
                case 4:
                    $service = $this->database->table("services")->where("id=?", $formSession->service)->fetch();
                    $this->template->selectedService = $service;
                    $this->template->date =  $formSession->date;
                    $this->template->selectedTime = $formSession->time;
                    $this->template->end = date("Y-m-d H:i:s", strtotime($formSession->date . " " . $formSession->time . " + " . $service->duration . " minutes"));
                    $this->template->firstname = $formSession->firstname;
                    $this->template->lastname = $formSession->lastname;
                    $this->template->phone = $formSession->phone;
                    $this->template->email = $formSession->email;
                    $this->template->address = $formSession->address;
                    $this->template->code = $formSession->code;
                    $this->template->city = $formSession->city;
                    break;
            }

            $this->template->gdprUrl = $user_settings->gdpr_url;
            $this->redrawControl("newReservationForm");
        }
    }


    public function actionConfirmation($id)
    {
        $this->template->uuid = $id;
        $reservation = $this->database->table("reservations")->where("uuid=?", $id)->fetch();
        $this->template->reservation = $reservation;
        $this->template->settings = $this->database->table("settings")->fetch();
    }

    public function actionBackup($id)
    {
        $reservation = $this->database->table("reservations")->where("uuid=? AND type=1", $id)->fetch();
        $this->template->reservation = $reservation;
    }

    protected function createComponentFormPartOne(): Form
    {
        $form = new Form;
        $form->addHidden("service");
        $form->addSubmit("submit", "Pokračovat");

        $form->onSuccess[] = [$this, "formSucceededPartOne"];
        return $form;
    }

    public function formSucceededPartOne(Form $form, $values)
    {
        if ($values->service !== "") {
            $session = $this->getSession('newReservationForm');
            $session->service = $values->service;
            $session->step = 2;
            $this->redirect("default", ["krok" => 2]);
        }
    }

    protected function createComponentFormPartTwo(): Form
    {
        $form = new Form;
        $form->addHidden("dateType")
            ->setRequired()
            ->addRule($form::PATTERN, 'Neplatný druh rezervace', 'default|backup');
        $form->addHidden("date")
            ->setRequired()
            ->addRule($form::PATTERN, 'Neplatný datum', '^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$');
        $form->addHidden("time")
            ->setRequired()
            ->addRule($form::PATTERN, 'Čas musí být číslo', '\d+');

        $form->addSubmit("submit", "Pokračovat");
        $form->onSuccess[] = [$this, "formSucceededPartTwo"];

        return $form;
    }

    public function formSucceededPartTwo(Form $form, $values)
    {
        $session = $this->getSession('newReservationForm');
        $sessionReservation = $this->getSession('Reservation');
        $session->dateType = $values->dateType;
        $session->date = $values->date;
        if ($session->dateType == "default") {
            $times = $sessionReservation->availableTimes;
            $time = $times[$values->time];
            $session->time = $time;
        } else {
            $times = $sessionReservation->availableBackupTimes;
            $time = $times[$values->time];
            $session->time = $time;
        }
        $session->step = 3;
        $this->redirect("default", ["krok" => 3]);
    }

    protected function createComponentFormPartThree(): Form
    {
        $form = new Form;

        $form->addText("firstname", "Jmeno:")
            ->setRequired("Jméno je povinné");
        $form->addText("lastname", "Příjmení:")
            ->setRequired("Príjmení je povinné");
        $form->addText("phone", "Telefon:")
            ->setRequired("Telefon je povinny")
            ->addRule($form::PATTERN, 'Telefoní čílo není platný', '\d+');
        $form->addText("email", "E-mail:")
            ->setRequired("E-mail je povinny")
            ->addRule($form::EMAIL, 'Neplatný formát e-mailu');
        $form->addText("address", "Adresa a čp:");
        $form->addText("code", "PSČ:")
            ->addFilter(function ($value) {
                return str_replace(' ', '', $value);
            })
            ->addRule($form::PATTERN, 'Neplatný formát PSČ', '^\d{5}$');
        $form->addText("city", "Město:");
        $form->addCheckbox("gdpr", "gdpr")->setRequired("Souhlas s GDPR je povinný");

        $form->addSubmit("submit", "Další");

        $form->onSuccess[] = [$this, "formSucceededPartThree"];

        $this->template->form = $form;

        return $form;
    }

    public function formSucceededPartThree(Form $form, $values)
    {
        $session = $this->getSession('newReservationForm');
        $session->firstname = $values->firstname;
        $session->lastname = $values->lastname;
        $session->phone = $values->phone;
        $session->email = $values->email;
        $session->address = $values->address;
        $session->code = $values->code;
        $session->city = $values->city;
        $session->gdpr = $values->gdpr;
        $session->step = 4;
        $this->redirect("default", ["krok" => 4]);
    }

    protected function createComponentFormPartFour(): Form
    {
        $form = new Form;
        $form->addSubmit("submit", "Rezervovat");

        $form->onSuccess[] = [$this, "formSucceeded"];

        return $form;
    }



    protected function createComponentForm(): Form
    {
        $form = new Form;
        $form->addHidden("service")
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
        $form->addText("address", "Adresa a čp:");
        $form->addText("code", "PSČ:")
            ->addRule($form::PATTERN, 'Neplatný formát PSČ', '^\d{5}$');
        $form->addText("city", "Město:");
        $form->addCheckbox("gdpr", "gdpr")->setRequired();

        $form->addText("dicountCode", "Kód slevy:");
        $form->addSubmit("submit");

        $form->onSuccess[] = [$this, "formSucceeded"];
        return $form;
    }

    public function formSucceeded(Form $form, \stdClass $data): void
    {
        $sessionReservation = $this->getSession('Reservation');
        $sessionNewReservationForm = $this->getSession('newReservationForm');
        $uuid = strval(Uuid::uuid4());
        $email = $sessionNewReservationForm->email;

        if (!$sessionNewReservationForm->gdpr) {
            $this->flashMessage("Prosím, vyplníte souhlas s GDPR.", "error");
            //$this->redirect("default");
        }

        if ($sessionNewReservationForm->dateType == "default") {
            if (!$this->checkAvailability($sessionNewReservationForm->date, $sessionNewReservationForm->service, $sessionNewReservationForm->time)) {
                $this->flashMessage("Nepovedlo se vytvořit rezervaci. Termín je již obsazen", "error");
                //$this->redirect("default");
            }
            $result = $this->insertReservation($uuid, "default", $sessionNewReservationForm->time);
            if (!$result) {
                $this->flashMessage("Nepovedlo se uložit rezervaci.", "error");
                //$this->redirect("default");
            }
            $this->mailer->sendConfirmationMail($email, $this->link("Payment:default", $uuid), $result);
            $this->redirect("confirmation", ["id" => $uuid]);
        } else if ($sessionNewReservationForm->dateType == "backup") {
            if (!$this->checkAvailability($sessionNewReservationForm->date, $sessionNewReservationForm->service, $sessionNewReservationForm->time, "backup")) {
                $this->flashMessage("Nepovedlo se vytvořit rezervaci. Termín je již obsazen", "error");
                $this->redirect("default");
            }
            $result = $this->insertReservation($uuid, "backup", $sessionNewReservationForm->time);
            if (!$result) {
                $this->flashMessage("Nepovedlo se uložit rezervaci.", "error");
                $this->redirect("default", $this->user_uuid);
            }
            $this->mailer->sendBackupConfiramationMail($email, $this->link("Payment:backup", $uuid), $result);
            $this->redirect("confirmation", ["id" => $uuid]);
        }
        //$this->redirect("default");
    }


    private function checkAvailability($date, $service_id, $time, $type = "default"): bool
    {
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        switch ($type) {
            case "backup":
                $available = $this->availableDates->getBackupHours($date, $service);
                break;
            default:
                $available = $this->availableDates->getAvailableStartingHours($date, $service);
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
    private function insertReservation(string $uuid, string $type, $time)
    {
        $result = $this->database->transaction(function ($database) use ($uuid, $type, $time) {
            $sessionNewReservationForm = $this->getSession('newReservationForm');
            $start = $sessionNewReservationForm->date . " " . $time;
            $service_id = $sessionNewReservationForm->service;
            $success = true;
            try {
                $reservation = $this->database->table("reservations")->insert([
                    "uuid" => $uuid,
                    "service_id" => $service_id,
                    "start" => $start,
                    "firstname" => $sessionNewReservationForm->firstname,
                    "lastname" => $sessionNewReservationForm->lastname,
                    "phone" => $sessionNewReservationForm->phone,
                    "email" => $sessionNewReservationForm->email,
                    "address" => $sessionNewReservationForm->address,
                    "code" => $sessionNewReservationForm->code,
                    "city" => $sessionNewReservationForm->city,
                    "created_at" => date("Y-m-d H:i:s"),
                    "type" => $type == "backup" ? 1 : 0,
                ]);

                //reset session
                $this->getSession('newReservationForm')->remove();
                if (!$this->payments->createPayment($database, $reservation, $sessionNewReservationForm->dicountCode ?? "")) {
                    $success = false;
                }
            } catch (\Throwable $e) {
                $success = false;
            }
            if ($success) {
                return $reservation ?? [];
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
    function setDate(int $service_id, string $day): void
    {
        $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
        $availableTimes = $this->availableDates->getAvailableStartingHours($day, $service);
        $availableBackup = $this->availableDates->getBackupHours($day, $service);

        //store data in session
        $session = $this->getSession('Reservation');
        $session->availableTimes = $availableTimes;
        $session->availableBackupTimes = $availableBackup;

        $this->template->times = $availableTimes;
        $this->template->selectedService = $service;
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
    function verifyDiscountCode(int $service_id, string $discountCode): void
    {
        $discount = $this->discountCodes->isCodeValid($service_id, $discountCode);
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

    public function handleBack($step)
    {
        $session = $this->getSession('newReservationForm');
        $session->step = $step;
        $this->redirect("default", ["krok" => $step]);
    }
}
