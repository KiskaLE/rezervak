#!/usr/bin/env php
<?php

namespace App\Cron;

use App;
use Dibi;

# Autoloading tříd přes Composer - tedy i naší Bootstrap třídy
require __DIR__ . '/../vendor/autoload.php';

# Necháme konfigurátor, aby nám sestavil DI kontejner
$container = App\Bootstrap::bootForCron()
    ->createContainer();

# Z kontejneru si nyní můžeme vytahat služby, které potřebujeme
$db = $container->getByType(Nette\Database\Connection::class);

# Kousek kódu, který chceme vykonat
bdump($db->table("services")->fetchAll());