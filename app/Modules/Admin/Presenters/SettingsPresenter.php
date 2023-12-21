<?php

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;

class SettingsPresenter extends SecurePresenter
{

    private $settings;
    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "settings";
    }

    protected function startup()
    {
        parent::startup();
    }

    public function actionDefault()
    {
        $settings = $this->database->table('settings')->where("user_id=?", $this->user->id)->fetch();
        $this->settings = $settings;
        $this->template->settings = $settings;
        $user_uuid = $this->database->table('users')->where("id=?", $this->user->id)->fetch()->uuid;
        $this->template->userPath = $user_uuid;
    }

    protected function createComponentBasicSettingsForm(string $name): Form
    {
        $form = new Form;

        $form->addText("sampleRate", "sample rate")
            ->setHtmlAttribute("type", "number")
            ->setDefaultValue($this->settings->sample_rate)
            ->setRequired("Zadejte čar rozdělení kalendáře")
            ->addRule($form::Min, "Rozdělení kalendáře nesmí být méně než 1", 1);
        $form->addText("paymentInfo", "payment info")
            ->setDefaultValue($this->settings->payment_info);
        $form->addText("verificationTime", "time to verify reservation")
            ->setHtmlAttribute("type", "number")
            ->setDefaultValue($this->settings->verification_time)
            ->setRequired("Zadejte čas na ověrení rezervace")
            ->addRule($form::Min, "Čas na ověření rezervace nesmí být méně než 1", 1);
        $form->addText("numberOfDays", "number of days")
            ->setHtmlAttribute("type", "number")
            ->setDefaultValue($this->settings->number_of_days)
            ->setRequired("Zadejte počet dní pro rezervování")
            ->addRule($form::Min, "Počet dní pro rezervování nesmí být méně než 1", 1);
        $form->addText("timeToPay", "time to pay")
            ->setHtmlAttribute("type", "number")
            ->setDefaultValue($this->settings->time_to_pay)
            ->setRequired("Zadejte čas na zaplacení")
            ->addRule($form::Min, "Čas na zaplacení nesmí být méně než 1", 1);

        $form->addText("company", "Společnost")
            ->setDefaultValue($this->settings->company)
            ->setMaxLength(255);

        $form->addText("phone", "Telefon")
            ->setDefaultValue($this->settings->phone)
            ->setMaxLength(20);

        $form->addText("email", "Email")
            ->addRule($form::Email, "Neplatný mailový formát")
            ->setDefaultValue($this->settings->email)
            ->setMaxLength(255);

        $form->addSubmit("submit", "Uložit změny");

        $form->onSuccess[] = [$this, "basicSettingsFormSucceeded"];

        return $form;
    }

    public function basicSettingsFormSucceeded(Form $form, $data)
    {

        try {
            $this->database->table("settings")->where("user_id=?", $this->user->id)->update([
                "sample_rate" => $data->sampleRate,
                "payment_info" => $data->paymentInfo,
                "verification_time" => $data->verificationTime,
                "number_of_days" => $data->numberOfDays,
                "time_to_pay" => $data->timeToPay,
                "company" => $data->company,
                "phone" => $data->phone,
                "email" => $data->email,
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $this->flashMessage("Změny byly uloženy.", "success");
        } catch (\Throwable $th) {
            $this->flashMessage("Nepodarilo se uložit změny!", "error");
        }
        $this->redirect("this");


    }


}