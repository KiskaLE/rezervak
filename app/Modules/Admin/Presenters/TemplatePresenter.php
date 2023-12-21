<?php

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\DI\Attributes\Inject;

final class TemplatePresenter extends SecurePresenter {

    #[Inject] public Nette\Database\Explorer $database;
    public function __construct(
    )
    {

    }

    public function beforeRender() {

        parent::beforeRender();
    }

    public function renderDefault() {
        $this->template->selectedPage = "dashboard";
        $user_uuid = $this->database->table('users')->where("id=?", $this->user->id)->fetch()->uuid;
        $this->template->userPath = $user_uuid;
    }

}

