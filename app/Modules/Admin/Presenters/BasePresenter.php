<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;
use Nette\Utils\Paginator;

class BasePresenter extends Presenter
{

    public $backlink;


    public function __construct(
        private Explorer $database,
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

    /**
     * Handles the "back" functionality of the application.
     *
     * @param mixed $defaultRoute The default route to redirect to if no backlink is present.
     * @return void
     * @throws InvalidLinkException If the backlink is invalid.
     */
    public function handleBack($defaultRoute)
    {
        if ($this->backlink) {
            try {
                $this->restoreRequest($this->backlink);
            } catch (\Throwable $e) {
                // Handle invalid backlink, log error or redirect to a default route
                $this->redirect($defaultRoute);
            }
        } else {
            $this->redirect($defaultRoute);
        }
    }

    /**
     * Creates a paginator object for pagination.
     *
     * @param int $total The total number of items.
     * @param int $page The current page number.
     * @param int $rowsPerPage The number of rows per page.
     * @return Paginator The paginator object.
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