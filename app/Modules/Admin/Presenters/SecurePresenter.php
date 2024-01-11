<?php

namespace App\Modules\admin\Presenters;

use Nette\DI\Attributes\Inject;
use Nette\Database\Explorer;
class SecurePresenter extends BasePresenter
{

    public $timezones;
    #[Inject] public Explorer $database;

    public function __construct(
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

    public function render() {
    }
}