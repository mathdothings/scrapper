<?php

function build_body(): void
{
    if (PHP_SAPI !== 'cli') {
        echo <<<HTML
<body style="
padding: 1rem;
background-color: #262626;
color: white;
font-family: 'Fira Mono', monospace;
font-size: 1rem;
line-break: anywhere;
">
HTML;
    }
}
