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


$request = new Nette\Application\Request("Api", "GET", array('action' => 'verifyPayments'));
$response = $presenter->run($request);

die("test");


# Z kontejneru si nyní můžeme vytahat služby, které potřebujeme
$database = $container->getByType(\Nette\Database\Connection::class);
# Kousek kódu, který chceme vykonat
$database->connect("mysql:host=127.0.0.1;dbname=rezervak", "root", "");

//TODO for test purposes changed to 15 minutes, should be -24 hours
$yesterday = date("Y-m-d H:i:s", strtotime("-15 minutes"));
$unpaidPayments = $database->query("SELECT * FROM payments WHERE created_at > '$yesterday' AND status=0")->fetchAll();
foreach ($unpaidPayments as $unpaidPayment) {
    //if payment is not older than 24 hours and bank api says that payment was paid then set status to 1
    //TODO add bank API
    if (true) {
        $database->query("UPDATE payments SET status=1 WHERE id=$unpaidPayment->id");
    }
}

