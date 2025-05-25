<?php

require_once __DIR__ . '/Toolkit/Timer.php';

use App\Toolkit\Timer;

$timer = new Timer;
$timer->start();
sleep(2);
echo $timer->elapsed() . PHP_EOL;
