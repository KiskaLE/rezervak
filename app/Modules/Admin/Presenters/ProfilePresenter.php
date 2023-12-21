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

        $form->addText("firstname")
            ->setDefaultValue($this->userRow->firstname)
            ->setRequired("Zadejte prosím jméno");
        $form->addText("lastname")
            ->setDefaultValue($this->userRow->lastname)
            ->setRequired("Zadejte prosím příjmení");
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

        $this->userRow->update($values);
        $this->flashMessage("Profil byl aktualizován");
        $this->redirect("this");
    }




    

}