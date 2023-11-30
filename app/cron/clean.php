#!/usr/bin/env php
<?php

namespace App\Cron;

use App;
use Nette;

require __DIR__ . '/../../vendor/autoload.php';

# Necháme konfigurátor, aby nám sestavil DI kontejner
$container = App\Bootstrap::bootForCron()->createContainer();


$presenterFactory = $container->getByType(Nette\Application\IPresenterFactory::class);
$presenter = $presenterFactory->createPresenter("Admin:Api");
$presenter->autoCanonicalize = FALSE;


$request = new Nette\Application\Request("Api", "GET", array('action' => 'clean'));
$response = $presenter->run($request);

die("konec");
