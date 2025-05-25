<?php

require_once __DIR__ . '/extract.table.data.php';

use Dom\HTMLDocument;

function contentscrap($barcode, $url)
{
    $arr = [];
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
            $techDetails = $dom->getElementById('productDetails_techSpec_section_1');

            $techSpecTds = stripeTableContent($dom, 'productDetails_techSpec_section_1');
            $detailBulletsTds = stripeTableContent($dom, 'productDetails_detailBullets_sections1');

            if (empty($detailBulletsTds)) {
                $detailBulletsTds = stripeTableContent($dom, 'detailBullets_feature_div');
            }

            $productTitle = $dom->getElementById('productTitle');
            if ($productTitle) {
                $arr['nome_produto'] = trim($productTitle->textContent);
            }

            $arr['imagem_produto'] = null;

            $img = $dom->querySelector('.a-dynamic-image.a-stretch-vertical');

            if ($img) {
                $src = $img->getAttribute('src');
                $arr['imagem_produto'] = $src;
            }

            $img = $dom->getElementById('landingImage');

            if ($img) {
                $src = $img->getAttribute('src');
                if ($src) {
                    $arr['imagem_produto'] = $src;
                } else {
                    $src = $img->getAttribute('data-old-hires') ?? '';
                    if ($src) {
                        $arr['imagem_produto'] = $src;
                    }
                }
            }

            $arr['info_produto'] = $techSpecTds;
            $arr['info_extra_produto'] = $detailBulletsTds;

            if (!$techDetails) {
                echo "O produto não foi encontrado.";
                echo '</br>';
            }

            break;
        } else {
            echo '</br>';
            echo 'Tentativa ' . $i . ' #Falha! Tentando novamente...';
            echo '</br>';
            echo 'Aguardando...';
            echo '</br>';
            sleep(2);
        }
    }
    return $arr;
}
