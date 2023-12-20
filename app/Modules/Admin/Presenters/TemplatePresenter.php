<?php

namespace App\Modules\admin\Presenters;

use Nette;

final class TemplatePresenter extends SecurePresenter {

    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {

    }

    public function beforeRender() {

        parent::beforeRender();
        $this->setLayout("new");
    }

    public function renderDefault() {
        $this->template->selectedPage = "dashboard";
        $user_uuid = $this->database->table('users')->where("id=?", $this->user->id)->fetch()->uuid;
        $this->template->userPath = $user_uuid;
    }

}

