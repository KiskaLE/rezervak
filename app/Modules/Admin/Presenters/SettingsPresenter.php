<?php

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;

class SettingsPresenter extends SecurePresenter
{

    private $settings;

    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
    }

    protected function startup()
    {
        parent::startup();
    }

    public function actionShow()
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

        $form->addText("sampleRate", "sample rate")->setHtmlAttribute("type", "number")->setDefaultValue($this->settings->sample_rate)->setRequired();
        $form->addText("paymentInfo", "payment info")->setDefaultValue($this->settings->payment_info);
        $form->addText("verificationTime", "time to verify reservation")->setHtmlAttribute("type", "number")->setDefaultValue($this->settings->verification_time)->setRequired();
        $form->addText("numberOfDays", "number of days")->setHtmlAttribute("type", "number")->setDefaultValue($this->settings->number_of_days)->setRequired();
        $form->addText("timeToPay", "time to pay")->setHtmlAttribute("type", "number")->setDefaultValue($this->settings->time_to_pay)->setRequired();

        $form->addSubmit("submit", "Uložit");

        $form->onSuccess[] = [$this, "basicSettingsFormSucceeded"];

        return $form;
    }

    public function basicSettingsFormSucceeded(Form $form, $data) {

        try {
            $this->database->table("settings")->where("user_id=?", $this->user->id)->update([
                "sample_rate" => $data->sampleRate,
                "payment_info" => $data->paymentInfo,
                "verification_time" => $data->verificationTime,
                "number_of_days" => $data->numberOfDays,
                "time_to_pay" => $data->timeToPay
            ]);
            $this->flashMessage("Změny byly uloženy.", "alert-success");
        } catch (\Throwable $th) {
            $this->flashMessage("Nepodarilo se uložit změny!", "alert-danger");
        }
            $this->redirect("this");


    }


}