# Scrapper

Para realizar o scrapping, primeiro devemos utilizar a class **Scrapper** para encontrar a *URL* do produto. Em seguida, devemos utilizar o **Scrapper** para extrair os dados do HTML. Uma vez que temos os dados, guardamos em um arquivo utilizando o **Filer** e finalizamos utilizando o **Extractor** para exportar os dados para um arquivo txt que pode ser importado em uma planilha.

<hr />

### Exemplo:

```php
<?php

set_time_limit(0);

require_once __DIR__ . '/Utils/negate_favicon.php';
require_once __DIR__ . '/Utils/build_body.php';
require_once __DIR__ . '/Utils/array_split.php';
require_once __DIR__ . '/Request/Request.php';
require_once __DIR__ . '/Filesystem/Filer.php';
require_once __DIR__ . '/Extractor/Extractor.php';
require_once __DIR__ . '/Scrapper/AmazonBrazilScrapper.php';
require_once __DIR__ . '/Toolkit/Timer.php';

use App\Request\Request;
use App\System\Filer;
use App\Scrapping\AmazonBrazilScrapper;
use App\Processing\Extractor;
use App\Toolkit\Timer;

negateFaviconRequest();
buildBody();

$request = new Request;
$filesystem = new Filer;
$scrapper = new AmazonBrazilScrapper;
$processor = new Extractor;
$timer = new Timer;

$startTime = $timer->start();

function getURLs()
{
    require_once __DIR__ . '/barcodes.php';

    $filesystem = new Filer;
    $scrapper = new AmazonBrazilScrapper;

    $chunks = array_split($barcodes, 30); // count($barcodes) / 100

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

function findContent()
{
    $filesystem = new Filer;
    $scrapper = new AmazonBrazilScrapper;

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

function exportContent()
{
    $filesystem = new Filer;
    $processor = new Extractor;

    $filepaths = $filesystem->readFiles('/Output/items');

    $i = 1;
    foreach ($filepaths as $filepath) {
        $filename = 'Output/exports/' . new DateTime(timezone: new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d_H-i-s') . "_export$i.txt";
        $processor->export($filepath, $filename);
        $i++;
    }
}

// orchestra
getURLs();
findContent();
exportContent();

$elapsed = $timer->elapsed();

echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed, 2) . " segundos!";
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed / 60, 2) . " minutos!";
echo '<br />';
```