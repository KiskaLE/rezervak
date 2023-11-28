<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Presenter;
use App\Modules\Formater;
use Nette\Database\Explorer;

class BasePresenter extends Presenter
{

    public $backlink;


    public function __construct(
    )
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