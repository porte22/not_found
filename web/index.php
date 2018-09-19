<?php

use Porte22\NotFound\CheckUrl;
set_time_limit(300);
require 'vendor/autoload.php';

$checkUrl = new CheckUrl();
foreach (glob("csv/*.csv") as $filename) {
    $csv = array_map('str_getcsv', file($filename));
    $header = array_flip(array_shift($csv));

    foreach ($csv as $item) {
        $url = $item[$header['URL']];
        $checkUrl->analizeUrl($url);
    }
}

//print_r($checkUrl->getList());
$checkUrl->check();
