<?php

require_once __DIR__ . '/clean.specialchars.php';

use Dom\HTMLDocument;

function stripeTableContent(HTMLDocument $dom, $tableId)
{
    $table = $dom->getElementById($tableId);
    $data = [];

    if ($table) {
        $trs = $table->getElementsByTagName('tr');

        foreach ($trs as $tr) {
            $ths = $tr->getElementsByTagName('th');
            $tds = $tr->getElementsByTagName('td');

            if ($ths->length > 0 && $tds->length > 0) {
                $th = $ths->item(0);
                $td = $tds->item(0);

                $key = cleanSpecialChars(trim($th->textContent));
                $value = cleanSpecialChars(trim($td->textContent));

                if (!empty($key)) {
                    $data[$key] = $value;
                }
            }
        }
    }

    return $data;
}
