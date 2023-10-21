<?php

namespace App\Modules\Front\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;

class BasePresenter extends Presenter
{
    private $database;
    public function __construct()
    {
        parent::__construct();
    }

    protected function startup()
    {
        parent::startup();
        // Your code here
    }
}