<?php
function count_links_in_file($filePath)
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

function count_links_in_directory($directory)
{
    $totalLinks = 0;
    $processedFiles = 0;

    $files = glob(__DIR__ . '/../' . $directory . '/*_link.php');

    foreach ($files as $file) {
        $count = count_links_in_file($file);
        $totalLinks += $count;
        $processedFiles++;

        echo "File: " . basename($file) . " - $count links";
        echo '</br>';
    }

    echo '</br>';
    echo "Total: $processedFiles files read";
    echo '</br>';
    echo "Found: $totalLinks links";

    return $totalLinks;
}

// usage:
// $directory = 'Output/links/';
// countLinksInAllFiles($directory);
// die();