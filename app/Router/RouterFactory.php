<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
        $router->withModule('Admin')->withPath('admin')->addRoute("<presenter>/<action>[/<id>]", "Home:default");
        $router->withModule('Front')->addRoute("<presenter>/<action>[/<id>]", "Home:default");
		return $router;
	}
}
