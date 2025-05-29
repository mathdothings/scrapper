<?php

set_time_limit(0);

require_once __DIR__ . '/Utils/negate_favicon.php';
require_once __DIR__ . '/Utils/build_head.php';
require_once __DIR__ . '/Utils/build_body.php';
require_once __DIR__ . '/Utils/array_split.php';
require_once __DIR__ . '/Utils/count_links_in_directory.php';
require_once __DIR__ . '/Utils/dd.php';
require_once __DIR__ . '/Utils/pretty_print.php';
require_once __DIR__ . '/Toolkit/Timer.php';
require_once __DIR__ . '/CSV/Reader.php';
require_once __DIR__ . '/Filesystem/Filer.php';
require_once __DIR__ . '/Scrapper/MultilaserScrapper.php';
require_once __DIR__ . '/Extractor/IntelbrasExtractor.php';

use App\CSV\Reader;
use App\Scrapping\MultilaserScrapper;
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
    $reader = new Reader('/Input/LISTA_PRODUTOS_MULTILASER.csv', ';');
    $barcodes = $reader->read()->getColumn('PROD_COD_BAR');

    $filesystem = new Filer;
    $scrapper = new MultilaserScrapper;

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
            ->format('d-m-Y_H-i-s') . '_link.php';

        $filesystem->writeFile("/Output/links/multilaser/$filename", $links);
    }
}

function findContent(): void
{
    $filesystem = new Filer;
    $scrapper = new MultilaserScrapper;

    $filepaths = $filesystem->readFiles('/Output/links/multilaser');

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
            ->format('d-m-Y_H-i-s') . '_item.php';

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
            ->format('d-m-Y_H-i-s') . "_export$i.csv";
        $extractor->export($filepath, $filename);
        $i++;
    }
}

// count_links_in_directory('/Output/links/multilaser');

// getURLs();
// findContent();
// exportContent();

$elapsed = $timer->elapsed();

$filename = '/Audit/' . new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
    ->format('d-m-Y_H-i-s') . "_audit.txt";

$filesystem->appendFile($filename, "> Realizado em: " . new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
    ->format('d-m-Y_H-i-s'));
$filesystem->appendFile($filename, "> Operação: Realizar scrapping dos dados");
$filesystem->appendFile($filename, "# ✅ Duração (s):"
    . "\t\t"
    . number_format($elapsed, 2, ',', '.'));
$filesystem->appendFile($filename, "# ✅ Duração (min):"
    . "\t\t"
    . number_format($elapsed / 60, 2, ',', '.'));
$filesystem->appendFile($filename, "# ✅ Duração (h):"
    . "\t\t"
    . number_format($elapsed / 120, 2, ',', '.'));

echo '<br />';
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed, 2, ',', '.') . " segundos!";
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed / 60, 2, ',', '.') . " minutos!";
echo '<br />';
