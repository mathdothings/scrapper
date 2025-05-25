<?php

/**
 * Generates a URL with the given query parameters.
 *
 * @param string $base_url The base URL (e.g., 'http://localhost:8000/s').
 * @param array $params An associative array of query parameters.
 * Example: ['k' => '7899298674719', '__mk_pt_BR' => 'xyz', 'crid' => '1MG7FU2LX89J0']
 * @return string The complete URL with encoded query parameters.
 */
function generateProductSearchUrlByBarcode(string $barcode, string $base_url): string
{
    $params = [
        'k' => '7899298674719',
        '__mk_pt_BR' => 'ÃMÃŽÕÑ',
        'crid' => '1MG7FU2LX89J0',
        'sprefix' => '7899298674719,aps,283',
        'ref' => 'nb_sb_noss',
    ];
    $random_crid = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
    $params['crid'] = $random_crid;
    $params['k'] = $barcode;
    $parts = explode(',', $params['sprefix']);
    $params['sprefix'] = urlencode($barcode . ',' . $parts[1] . $parts[2]);

    $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $url = $base_url . '?' . $query_string;
    return $url;
}
