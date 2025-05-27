<?php

function pretty_print(mixed $object): void
{
    echo '<br />';
    echo '<pre>';
    print_r($object);
    echo '<br />';
    echo '<pre />';
}
