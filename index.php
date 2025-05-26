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

$reader = new Reader('intelbras_produtos.csv', ';');
$reader->read();
$barcodes = $reader->getColumn('PROD_COD_BAR');

$slice = array_slice($barcodes, 101, 200);

$urls = [];
foreach ($slice as $barcode) {
    $url = $scrapper->findProductURLByBarcode($barcode);
    if ($url !== '') {
        $urls[$barcode] = $url;
    };
}

$filename = new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
    ->format('Y-m-d_H-i-s') . '_link.php';
$filer->writeFile("Output/links/$filename", $urls);

// $product = $scrapper->findProductContentByBarcodeAndURL('7899298680475', $url);

$elapsed = $timer->elapsed();

echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed, 2) . " segundos!";
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed / 60, 2) . " minutos!";
echo '<br />';
