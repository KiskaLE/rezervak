<?php

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;

class ProfilePresenter extends SecurePresenter
{

    private $userRow;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectedPage = "profile";
    }

    protected function startup()
    {
        parent::startup();
    }

    public function actionDefault()
    {
        $this->userRow = $this->database->table('users')->get($this->user->id);

    }

    protected function createComponentEditUserForm(): Form
    {
        $form = new Form;
        $userSettings = $this->database->table("settings")->where("user_id=?" , $this->user->id)->fetch();

        $form->addText("firstname")
            ->setDefaultValue($this->userRow->firstname)
            ->setRequired("Zadejte prosím jméno");

        $form->addText("lastname")
            ->setDefaultValue($this->userRow->lastname)
            ->setRequired("Zadejte prosím příjmení");

        $form->addText("email", "Email")
            ->addRule($form::Email, "Neplatný mailový formát")
            ->setDefaultValue($this->userRow->email)
            ->setMaxLength(255);

        $form->addText("phone", "Telefon")
            ->setDefaultValue($this->userRow->phone)
            ->setMaxLength(20);

        $form->addText("paymentInfo", "payment info")
            ->setDefaultValue($this->userRow->payment_info);

        $form->addText("company", "Společnost")
            ->setDefaultValue($this->userRow->company)
            ->setMaxLength(255);

        $form->addText("ico", "IČO")
            ->setDefaultValue($this->userRow->ico)
            ->setMaxLength(255);

        $form->addText("dic", "DIČ")
            ->setDefaultValue($this->userRow->dic)
            ->setMaxLength(255);

        $form->addText("address")
            ->setDefaultValue($this->userRow->address);

        $form->addText("city")
            ->setDefaultValue($this->userRow->city);

        $form->addText("zip")
            ->setDefaultValue($this->userRow->zip);

        $form->addSubmit("submit", "Uložit změny");

        $form->onSuccess[] = [$this, 'editUserFormSubmitted'];

        return $form;
    }

    public function editUserFormSubmitted(Form $form, $values) {
        $this->database->transaction(function ($database) use ($values) {
            //user table
            $database->table("users")->where("id=?" , $this->user->id)->update([
                "firstname" => $values->firstname,
                "lastname" => $values->lastname,
                "payment_info" => $values->paymentInfo,
                "email" => $values->email,
                "phone" => $values->phone,
                "address" => $values->address,
                "city" => $values->city,
                "zip" => $values->zip,
                "company" => $values->company,
                "ico" => $values->ico,
                "dic" => $values->dic
            ]);
        });

        
        $this->flashMessage("Profil byl aktualizován", "success");
        $this->redirect("this");
    }




    

}