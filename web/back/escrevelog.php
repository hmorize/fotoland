<?php

function loga_linha($onde, $msg)
{
    return;
    $ARQUIVO_LOG = '/var/www/logs/log9.txt';
    file_put_contents($ARQUIVO_LOG, "{$onde}: {$msg}\n", FILE_APPEND);
}
