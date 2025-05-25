<?php

function array_split(array $array, int $split): array
{
    $length = count($array);
    $size = ceil($length / $split);
    return array_chunk($array, $size);
}
