<?php

namespace App\Modules\Front\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;

class BasePresenter extends Presenter
{
    public function __construct(
        public Explorer $database
    )
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
        $user = $this->database->table("users")->fetch();
        $this->template->user = $user;
        $this->template->userSettings = $user->related("settings")->fetch();
        
    }
}