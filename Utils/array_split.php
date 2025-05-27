<?php

/**
 * Splits an array into a specified number of chunks with balanced distribution.
 *
 * This function divides an array into exactly $split parts, distributing any remainder elements
 * evenly across the first chunks. The resulting chunks will differ in size by at most 1 element.
 *
 * @param array $array The input array to split
 * @param int $split The number of chunks to create (must be > 0 and <= array length)
 * @return array<int,array> An array containing $split sub-arays
 * @throws InvalidArgumentException If:
 * - $split is less than 1 (code: 0)
 * - $split exceeds array length (code: 1)
 */
function array_split(array $array, int $split): array
{
    if ($split <= 0) {
        throw new InvalidArgumentException('Split must be greater than 0', 0);
    }

    if ($split > count($array)) {
        throw new InvalidArgumentException('Split must be less than array length', 1);
    }

    $length = count($array);
    $remainder = $length % $split;

    $rem = array_slice($array, 0, $remainder);

    $slices = array_slice($array, $remainder);
    $arr = array_chunk($slices, $split);
    $arr[] = $rem;

    return $arr;
}
