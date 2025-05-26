<?php

require_once __DIR__ . '/Utils/negate_favicon.php';
require_once __DIR__ . '/Utils/build_body.php';
require_once __DIR__ . '/Toolkit/Timer.php';
require_once __DIR__ . '/CSV/Reader.php';
require_once __DIR__ . '/Filesystem/Filer.php';
require_once __DIR__ . '/Request/Request.php';
require_once __DIR__ . '/Scrapper/IntelbrasScrapper.php';

use App\CSV\Reader;
use App\Request\Request;
use App\Scrapping\IntelbrasScrapper;
use App\Toolkit\Timer;
use App\System\Filer;

negateFaviconRequest();
buildBody();

$timer = new Timer;
$filer = new Filer;
$request = new Request;
$scrapper = new IntelbrasScrapper;

$start = $timer->start();

$url = $scrapper->findProductURLByBarcode('7899298680475');
$product = $scrapper->findProductContentByBarcodeAndURL('7899298680475', $url);
echo '<pre>';
print_r($product);
echo '<pre />';

$end = $timer->elapsed();
echo '<br />';
echo '<br />';
echo 'Elapsed time: ' . $end . PHP_EOL;
