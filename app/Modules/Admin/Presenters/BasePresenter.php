<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Presenter;
use App\Modules\Formater;
use Nette\Database\Explorer;
use Nette\Utils\Paginator;

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

    /**
     * Generates a paginator object for a given total number of items, current page, and number of rows per page.
     *
     * @param int $total The total number of items.
     * @param int $page The current page.
     * @param int $rowsPerPage The number of rows per page.
     * @return N/A
     * @throws N/A
     */
    public function createPagitator(int $total, int $page, int $rowsPerPage)
    {
        $paginator = new Paginator();
        $paginator->setItemCount($total);
        $paginator->setItemsPerPage($rowsPerPage);
        $paginator->setPage($page);
        return $paginator;
    }

}