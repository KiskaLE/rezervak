<?php

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use App\Modules\Authenticator;

final class SignPresenter extends BasePresenter
{
    public $session;

    public function __construct(
        private Passwords $passwords,
        private Authenticator $authenticator,
    )
    {

    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout("login");
    }


    public function actionOut()
    {
        $this->getUser()->logout();
        $this->redirect("Sign:in");
    }

    protected function createComponentSignInForm(string $name): Form
    {
        $form = new Form;
        $form->addText("username", "email")->setRequired();
        $form->addPassword("password", "password")->setRequired();
        $form->addSubmit("submit", "Přihlásit se");

        $form->onSuccess[] = [$this, "signInFormSucceeded"];
        return $form;
    }

    public function signInFormSucceeded(Form $form, \stdClass $data): void
    {
        try {
            $this->getUser()->setAuthenticator($this->authenticator)->login($data->username, $data->password);
            $this->redirect("Home:");
        } catch (Nette\Security\AuthenticationException $e) {
            $this->flashMessage("Špatný email nebo heslo.", "error");
        }


    }

    protected function createComponentCreateForm(string $name): Form
    {
        $form = new Form;
        $form->addText("username", "email")->setRequired();
        $form->addPassword("password", "password")->setRequired();
        $form->addSubmit("submit", "Vytvořit učet");

        $form->onSuccess[] = [$this, "createFormSucceeded"];
        return $form;
    }

    public function createFormSucceeded(Form $form, \stdClass $data): void
    {
        $error = false;
        if (!$this->authenticator->createUser($data->username, $data->password)) {
            $error = true;
        }
        if (!$error) {
            $this->redirect("Sign:in");
        }
        $this->flashMessage("Účet s tímto emailem již existuje.", "alert-danger");


    }
}