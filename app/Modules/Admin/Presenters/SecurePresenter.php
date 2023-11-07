<?php

namespace App\Modules\admin\Presenters;

class SecurePresenter extends BasePresenter
{

    public function __construct(\Nette\Security\User $user)
    {
        parent::__construct();

    }

    protected function startup()
    {
        parent::startup();
        // Your code here
    }
    protected function beforeRender()
    {
        parent::beforeRender();

        if ($this->user->getRoles()[0] === "ADMIN"){
        }else{
            if (!$this->user->getRoles()[0] === "UNVERIFIED") {
                $this->flashMessage("Ověrte svůj email", "alert-danger");
            }
            $this->redirect("Sign:in");
        }

        $this->redrawControl();
    }
}