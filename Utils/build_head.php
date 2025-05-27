<?php

function build_head(): void
{
    if (PHP_SAPI !== 'cli') {
        echo <<<HTML
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Mono:wght@400;500;700&display=swap" rel="stylesheet">
</head>
HTML;
    }
}
