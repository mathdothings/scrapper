<?php
function countLinksInFile($filePath)
{
    include $filePath;

    if (!isset($links) || !is_array($links)) {
        return 0;
    }

    $count = 0;
    foreach ($links as $value) {
        if (!empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            $count++;
        }
    }

    return $count;
}

function countLinksInAllFiles($directory)
{
    $totalLinks = 0;
    $processedFiles = 0;

    $files = glob($directory . '/*_link.php');

    foreach ($files as $file) {
        $count = countLinksInFile($file);
        $totalLinks += $count;
        $processedFiles++;

        echo "File: " . basename($file) . " - $count links";
        echo '</br>';
    }

    echo '</br>';
    echo "Total: $processedFiles arquivos lidos";
    echo '</br>';
    echo "Encontradas: $totalLinks URLs";

    return $totalLinks;
}

// usage:
// $directory = 'Output/links/';
// countLinksInAllFiles($directory);
// die();