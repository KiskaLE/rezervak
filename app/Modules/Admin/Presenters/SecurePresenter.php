<?php

namespace App\Modules\admin\Presenters;

use Nette\Database\Explorer;

class SecurePresenter extends BasePresenter
{

    public $timezones;

    public function __construct(
        public Explorer $database
    )
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
        $user_uuid = $this->database->table('users')->where("id=?", $this->user->id)->fetch()->uuid;
        $this->template->userPath = $user_uuid;

        if ($this->user?->getRoles()[0] === "ADMIN") {
        } else {
            if (!$this->user->getRoles()[0] === "UNVERIFIED") {
                $this->flashMessage("Ověrte svůj email", "error");
            }
            $this->redirect("Sign:in");
        }

        $this->redrawControl();
    }

    public function render() {
    }
}