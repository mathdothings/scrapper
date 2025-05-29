<?php

function dd(mixed $value): void
{
    echo '<br />';
    echo '<pre>';
    var_dump($value);
    echo '<pre />';
    echo '<br />';

    die;
}
