<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;
use Nette\DI\Attributes\Inject;


final class HomePresenter extends BasePresenter
{

    #[Inject] public Nette\Database\Explorer $database;
    public function __construct(
    )
    {
    }

    public function renderDefault() {
    }
}
