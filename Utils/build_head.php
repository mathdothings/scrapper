<?php

function build_head(): void
{
    if (PHP_SAPI !== 'cli') {
        echo <<<HTML
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Red+Hat+Mono:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">
</head>
HTML;
    }
}
