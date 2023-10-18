<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette;


final class ReservationPresenter extends BasePresenter
{

    public function __construct(
        private Nette\Database\Connection $database,
    ){

    }

}
