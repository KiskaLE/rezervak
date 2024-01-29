<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Database\Explorer;

class SecurePresenter extends BasePresenter
{

    public $timezones;
    #[Inject] public Explorer $database;

    public function __construct()
    {
    }

    protected function startup()
    {
        parent::startup();
        // Your code here
    }

    protected function beforeRender()
    {
        parent::beforeRender();

        if ($this->user?->getRoles()[0] === "ADMIN") {
            $user_uuid = $this->database->table('users')->where("id=?", $this->user->id)->fetch()?->uuid;
            $this->template->userPath = $user_uuid;
        } else {
            if (!$this->user->getRoles()[0] === "UNVERIFIED") {
                $this->flashMessage("Ověrte svůj email", "error");
            }
            $this->redirect("Sign:in");
        }

        $this->redrawControl();
    }

    public function render()
    {
    }


    protected function createComponentReservationsListFilterForm(): Form
    {
        $session = $this->getSession("reservationsFilter");
        $form = new Form;

        $form->addText("name")->setDefaultValue($session->filterName);
        $form->addText("vs")->setDefaultValue($session->filterVs)
            ->addFilter(function ($value) {
                //remove everything that is not a number from string
                return preg_replace("/[^0-9]/", "", $value);
            });
        $form->addSubmit("submit", "filtrovat");

        $form->onSuccess[] = [$this, "reservationsListFilterFormSuccesses"];

        return $form;
    }

    public function reservationsListFilterFormSuccesses(Form $form, \stdClass $data)
    {
        $session = $this->getSession("reservationsFilter");
        $session->filterName = $data->name;
        $session->filterVs = $data->vs;
        $this->redirect('this');
    }
}
