<?php

require_once __DIR__ . '/Toolkit/Timer.php';
require_once __DIR__ . '/CSV/Reader.php';

use App\CSV\Reader;
use App\Toolkit\Timer;

// Example usage
try {
    $reader = new Reader('List de produtos para pesquisa na web 19052025.csv', ';');
    $reader->read(true);

    // Get all data
    // $allData = $reader->getData();
    // print_r($allData);

    // Get specific column
    $barcodes = $reader->getColumn('PROD_COD_BAR');
    print_r($barcodes);

    // Get row count
    echo "Total: " . $reader->count() . PHP_EOL;
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
