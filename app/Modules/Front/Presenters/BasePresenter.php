<?php

namespace App\Modules\Front\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;
use Nette\DI\Attributes\Inject;


class BasePresenter extends Presenter
{

    #[Inject] public Explorer $database;
    public function __construct(
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

        $user = $this->database->table("users")->fetch();
        $this->template->logoUrl = $user->logo_url;
        
    }
}