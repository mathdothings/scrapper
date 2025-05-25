<?php

function filewrite($variable, $directory = '.')
{
    $now = new DateTime();

    $filename = $directory . '/' . $now->format('Y-m-d_H-i') . '_item.php';

    $output = var_export($variable, true);
    $content = "<?php\n\n\$links = " . $output . ';' . PHP_EOL;

    if (file_put_contents($filename, $content, FILE_APPEND)) {
        echo "Dados escritos em '$filename' utilizando var_export.";
    } else {
        echo "Não foi possível escrever o arquivo '$filename'.";
    }
}
