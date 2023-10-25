<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Presenter;

class BasePresenter extends Presenter
{


    public function __construct()
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
    }
}