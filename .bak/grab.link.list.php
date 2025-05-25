<?php

require_once __DIR__ . '/link.scrap.php';

function grabLinkList(array $list, string $baseUrl): array
{
    $links = [];
    foreach ($list as $item) {
        $links[(string)$item] = '';

        if (strlen($item) === 13) {
            $url = generateProductSearchUrlByBarcode($item, $baseUrl);
            $links[(string)$item] = linkscrap($item, $url);
        }
    }

    return $links;
}
