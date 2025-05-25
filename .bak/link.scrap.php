<?php

use Dom\HTMLDocument;

function linkscrap(string $barcode, string $url): string
{
    $link = '';
    $amz = 'https://www.amazon.com.br';
    for ($i = 1; $i <= 30; $i++) {
        echo '</br>';
        echo '# Código de barras: ' . $barcode;
        echo '</br>';
        echo 'Tentativa ' . $i;
        echo '</br>';

        $response = attempt($url);
        if ($response) {
            // echo $response;
            $dom = HTMLDocument::createFromString($response);
            $a_element = $dom->querySelector('a.a-link-normal.s-line-clamp-4.s-link-style.a-text-normal');
            if ($a_element) {
                $href_value = $a_element->getAttribute('href');
                if (strpos($href_value, 'sspa/click?ie') === false) {
                    echo 'Link: ' . '<a target="_blank"' . "href='$amz$href_value'" . '>' . $amz . $href_value . '</a>';
                    $link = $amz . $href_value;
                    echo '</br>';
                } else {
                    echo "O produto não foi encontrado.";
                    echo '</br>';
                }
            } else {
                echo "O produto não foi encontrado.";
                echo '</br>';
            }
            break;
        } else {
            echo 'Tentativa ' . $i . ' #Falha! Tentando novamente...';
            echo '</br>';
            echo 'Aguardando...';
            echo '</br>';
            sleep(2);
        }
    }
    return $link;
}
