<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;


final class HomePresenter extends BasePresenter
{
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    public function renderDefault() {
        $users = $this->database->table("users")->fetchAll();
        $this->template->users = $users;
    }
}
