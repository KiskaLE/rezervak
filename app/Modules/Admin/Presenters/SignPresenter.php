<?php
namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use App\Modules\Authenticator;

final class SignPresenter extends BasePresenter {
    public $session;
    public function __construct(
        private Passwords $passwords,
        private Authenticator $authenticator,
    ) {

    }

    public function actionOut() {
        $this->getUser()->logout();
        $this->redirect("Sign:in");
    }

    protected function createComponentSignInForm(string $name): Form {
        $form = new Form;
        $form->addText("username", "email")->setRequired();
        $form->addPassword("password", "password")->setRequired();
        $form->addSubmit("submit", "login");

        $form->onSuccess[] = [$this, "signInFormSucceeded"];
        return $form;
    }

    public function signInFormSucceeded(Form $form, \stdClass $data): void {
        try {
            $this->getUser()->setAuthenticator($this->authenticator)->login($data->username, $data->password);
            $this->redirect("Home:");
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('incorect username or password');
        }


    }

    protected function createComponentCreateForm(string $name): Form {
        $form = new Form;
        $form->addText("username", "email")->setRequired();
        $form->addPassword("password", "password")->setRequired();
        $form->addSubmit("submit", "create");

        $form->onSuccess[] = [$this, "createFormSucceeded"];
        return $form;
    }

    public function createFormSucceeded(Form $form, \stdClass $data): void {
        $error = false;
        try {
            $this->authenticator->createUser($data->username, $data->password);

        } catch (\Throwable $th) {
            $form->addError("Account already exists");
            $error = true;
        }
        if (!$error) {
            $this->redirect("Sign:in");
        }


    }
}