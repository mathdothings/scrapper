<?php

set_time_limit(0);

require_once __DIR__ . '/Utils/negate_favicon.php';
require_once __DIR__ . '/Utils/build_head.php';
require_once __DIR__ . '/Utils/build_body.php';
require_once __DIR__ . '/Utils/array_split.php';
require_once __DIR__ . '/Utils/count_links_in_directory.php';
require_once __DIR__ . '/Utils/pretty_print.php';
require_once __DIR__ . '/Toolkit/Timer.php';
require_once __DIR__ . '/CSV/Reader.php';
require_once __DIR__ . '/Filesystem/Filer.php';
require_once __DIR__ . '/Scrapper/IntelbrasScrapper.php';
require_once __DIR__ . '/Extractor/IntelbrasExtractor.php';

use App\CSV\Reader;
use App\Scrapping\IntelbrasScrapper;
use App\Toolkit\Timer;
use App\System\Filer;
use App\Processing\IntelbrasExtractor;

negateFaviconRequest();
build_head();
build_body();

$timer = new Timer;
$start = $timer->start();

$filesystem = new Filer;

function getURLs(): void
{
    $reader = new Reader(__DIR__ . '/Input/LISTA_PRODUTOS_INTELBRAS.csv', ';');
    $barcodes = $reader->read()->getColumn('PROD_COD_BAR');

    $filesystem = new Filer;
    $scrapper = new IntelbrasScrapper;

    $chunks = array_split($barcodes, 100);

    foreach ($chunks as $chunk) {
        $links = [];
        foreach ($chunk as $barcode) {
            $link = $scrapper->findProductURLByBarcode($barcode);
            if ($link !== '') {
                $links[$barcode] = $link;
            }
        }

        $filename = new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d_H-i-s') . '_link.php';

        $filesystem->writeFile("/Output/links/$filename", $links);
    }
}

function findContent(): void
{
    $filesystem = new Filer;
    $scrapper = new IntelbrasScrapper;

    $filepaths = $filesystem->readFiles('/Output/links');

    foreach ($filepaths as $filepath) {
        require_once $filepath;
        $items = [];
        foreach ($links as $barcode => $url) {
            $item = [];
            if ($item = $scrapper->findProductContentByBarcodeAndURL($barcode, $url)) {
                $items[$barcode] = $item;
            }
        }
        $filename = new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d_H-i-s') . '_item.php';

        $filesystem->writeFile("/Output/items/$filename", $items);
    }
}

function exportContent(): void
{
    $filesystem = new Filer;
    $extractor = new IntelbrasExtractor;

    $filepaths = $filesystem->readFiles('/Output/items');

    $i = 1;
    foreach ($filepaths as $filepath) {
        $filename = 'Output/exports/' . new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d_H-i-s') . "_export$i.txt";
        $extractor->export($filepath, $filename);
        $i++;
    }
}

count_links_in_directory('/Output/links');
// getURLs();
// findContent();
// exportContent();

$elapsed = $timer->elapsed();

$filename = '/Audit/' . new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
    ->format('d-m-Y_H-i-s') . "_audit.txt";

$filesystem->appendFile($filename, "Operação: Realizar scrapping");
$filesystem->appendFile($filename, "# ✅ Duração (s): " . number_format($elapsed, 2));
$filesystem->appendFile($filename, "# ✅ Duração: (min)" . number_format($elapsed / 60, 2));
$filesystem->appendFile($filename, "# ✅ Duração: (h)" . number_format($elapsed / 120, 2));

echo '<br />';
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed, 2) . " segundos!";
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed / 60, 2) . " minutos!";
echo '<br />';
