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

        $form->addText("notifyTime", "Úpozornění rezervace")
            ->setHtmlAttribute("type", "number")
            ->setDefaultValue($this->settings->notify_time)
            ->setRequired("Zadejte čas pro označení")
            ->addRule($form::Max, "Čas pro označení nesmí být výce než 1000 minut", 1000);

        $form->addText("homepage")
        ->setDefaultValue($this->settings->homepage)
        ->addRule($form::URL, "Neplatný url formát");

        $form->addText("email")
            ->setDefaultValue($this->settings->info_email)->addRule($form::EMAIL, "Neplatný mailový formát");

        $form->addSubmit("submit", "Uložit změny");

        $form->onSuccess[] = [$this, "basicSettingsFormSucceeded"];

        return $form;
    }

    public function basicSettingsFormSucceeded(Form $form, $data)
    {

        try {
            $this->database->table("settings")->where("user_id=?", $this->user->id)->update([
                "sample_rate" => $data->sampleRate,
                "verification_time" => $data->verificationTime,
                "number_of_days" => $data->numberOfDays,
                "time_to_pay" => $data->timeToPay,
                "homepage" => $data->homepage,
                "info_email" => $data->email,
                "updated_at" => date("Y-m-d H:i:s"),
                "notify_time" => $data->notifyTime
            ]);
            $this->flashMessage("Změny byly uloženy.", "success");
        } catch (\Throwable $th) {
            $this->flashMessage("Nepodarilo se uložit změny!", "error");
        }
        $this->redirect("this");


    }


}