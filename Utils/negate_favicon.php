<?php

function negateFaviconRequest(): void
{
    if (PHP_SAPI !== 'cli') {
        $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        if ($uri === '/favicon.ico') {
            exit;
        }
    }
}
