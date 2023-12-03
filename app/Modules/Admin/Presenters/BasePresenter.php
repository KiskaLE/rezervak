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
        // Your code here
    }
    protected function beforeRender()
    {
        parent::beforeRender();
    }

    public function handleBack($defaultRoute)
    {
        if ($this->backlink) {
            try {
                $this->restoreRequest($this->backlink);
            } catch (InvalidLinkException $e) {
                // Handle invalid backlink, log error or redirect to a default route
                $this->redirect($defaultRoute);
            }
        } else {
            $this->redirect($defaultRoute);
        }
    }

}