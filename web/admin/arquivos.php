<?php

namespace dataset;

require_once 'sku.php';

function get_subdir($env, $sku) {
    return sprintf("%s/%s/%s/", $env, sku_extract($sku, ['date'])[0], $sku);
}

function get_basedir($env) {
    return sprintf("/var/www/data/images/%s/", $env);
}

function get_targetdir($env, $sku) {
    return "/var/www/images/" . get_subdir($env, $sku);
}

function get_producturl($env, $sku) {
    return 'https://' . $_SERVER['HTTP_HOST'] . '/images/' . get_subdir($env, $sku);
}

