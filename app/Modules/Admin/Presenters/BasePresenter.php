<?php

namespace App\Modules\admin\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;
use Nette\Utils\Paginator;
use Nette\DI\Attributes\Inject;

class BasePresenter extends Presenter
{

    #[Inject] public Explorer $database;


    public function __construct()
    {
        parent::__construct();
    }

    protected function startup()
    {
        parent::startup();
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $user = $this->database->table("users")->order("created_at ASC")->fetch();
        $this->template->logoUrl = $user->logo_url;
    }

    /**
     * Handles the "back" functionality of the application.
     *
     * @param mixed $defaultRoute The default route to redirect to if no backlink is present.
     * @return void
     * @throws InvalidLinkException If the backlink is invalid.
     */

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
