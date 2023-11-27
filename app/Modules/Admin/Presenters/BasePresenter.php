<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Presenter;

class BasePresenter extends Presenter
{

    public $backlink;
    public $timezones;


    public function __construct()
    {
        parent::__construct();
    }

    protected function startup()
    {
        parent::startup();
        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        // Your code here
    }
    protected function beforeRender()
    {
        parent::beforeRender();
    }

}