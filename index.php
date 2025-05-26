<?php

require_once __DIR__ . '/Toolkit/Timer.php';
require_once __DIR__ . '/CSV/Reader.php';
require_once __DIR__ . '/Filesystem/Filer.php';

use App\CSV\Reader;
use App\Toolkit\Timer;
use App\System\Filer;

$timer = new Timer;
$filer = new Filer;

$start = $timer->start();

// Example usage
try {
    $readerAll = new Reader('List de produtos para pesquisa na web 19052025.csv', ';');
    $readerAll->read();
    $all = $readerAll->getColumn('PROD_COD_BAR');
    print_r($all);

    $readerFound = new Reader('PRODUTOS VIP AMAZON 23-05-2025.csv', ';');
    $readerFound->read();
    $found = $readerFound->getColumn('codigo_de_barras');

    $diff = array_diff($all, $found);

    $filer->createDirectory('/Output/diff');
    $filer->writeFile('Output/diff/diff.php', $diff);

    echo 'Total: ' . count($all) . ' - ' . count($found) . ' = ' . count($diff) . PHP_EOL;
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}

$end = $timer->elapsed();
echo 'Elapsed time: ' . $end . PHP_EOL;
