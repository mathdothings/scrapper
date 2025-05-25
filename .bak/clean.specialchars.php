<?php
function cleanSpecialChars(string $text): string
{
    $text = preg_replace('/[\x00-\x1F\x7F\xA0\x{200E}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $text);
    $text = trim($text);

    return $text;
}
