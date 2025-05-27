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
require_once __DIR__ . '/Request/Request.php';
require_once __DIR__ . '/Scrapper/IntelbrasScrapper.php';
require_once __DIR__ . '/Extractor/IntelbrasExtractor.php';

use App\CSV\Reader;
use App\Request\Request;
use App\Scrapping\IntelbrasScrapper;
use App\Toolkit\Timer;
use App\System\Filer;
use App\Processing\IntelbrasExtractor;

negateFaviconRequest();
build_head();
build_body();

$timer = new Timer;
$filer = new Filer;
$request = new Request;
$scrapper = new IntelbrasScrapper;
$extractor = new IntelbrasExtractor;

$start = $timer->start();

$filepaths = $filer->readFiles('/Output/items');

$elapsed = $timer->elapsed();

echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed, 2) . " segundos!";
echo '<br />';
echo "# ✅ Duração: " . number_format($elapsed / 60, 2) . " minutos!";
echo '<br />';
