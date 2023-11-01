#!/usr/bin/env php
<?php

namespace App\Cron;

use App;

require __DIR__ . '/../../vendor/autoload.php';

# Necháme konfigurátor, aby nám sestavil DI kontejner
$container = App\Bootstrap::bootForCron()->createContainer();

# Z kontejneru si nyní můžeme vytahat služby, které potřebujeme
$database = $container->getByType(\Nette\Database\Connection::class);
$mailer = $container->getByType(\App\Modules\Mailer::class);
# Kousek kódu, který chceme vykonat
$database->connect("mysql:host=127.0.0.1;dbname=rezervak", "root", "");

// clean unverified reservations older than 1 day from database
$yesterday = date("Y-m-d H:i:s", strtotime("-24 hours"));
$database->query("DELETE FROM reservations WHERE status='UNVERIFIED' AND created_at < '$yesterday'");

// clean verified reservations that are expired older than 3 months from database
$time = date("Y-m-d H:i:s", strtotime("-3 months"));
$database->query("DELETE FROM reservations WHERE status='VERIFIED' AND created_at < '$time'");