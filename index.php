<?php

set_time_limit(0);

require_once __DIR__ . '/Utils/negate_favicon.php';
require_once __DIR__ . '/Utils/build_body.php';
require_once __DIR__ . '/Utils/array_split.php';
require_once __DIR__ . '/Utils/count_links_in_directory.php';
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

countLinksInDirectory('/Output/links');

// $product = $scrapper->findProductContentByBarcodeAndURL('7899298680475', $url);

$elapsed = $timer->elapsed();

echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed, 2) . " segundos!";
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed / 60, 2) . " minutos!";
echo '<br />';
