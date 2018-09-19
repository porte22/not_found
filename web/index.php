<?php
use Porte22\NotFound\CheckUrl;
set_time_limit(600);
require '../vendor/autoload.php';

$fileBase = '../data/error_' . (new \DateTime())->format('Y-m-d').'_%s.txt';
//audi,mercedes-benz etc
$fileErrorMaker = sprintf($fileBase,'maker');
$fileErrorUnknown = sprintf($fileBase,'unknown');
$fileMakerList = '../data/makers/list.txt';
$fixedBase = '../data/fixed_' . (new \DateTime())->format('Y-m-d').'_%s.txt';

$checkUrl = new CheckUrl($fileMakerList);

if (!file_exists($fileErrorMaker)) {
    $checkUrl->loadUrlInErrorFromCsv("../csv/*.csv", 'URL');
    $checkUrl->writeUrlInError($fileErrorMaker,$fileErrorUnknown);
}
//$checkUrl->showUrlInError($fileErrorMaker);
//$checkUrl->showUrlInError($fileErrorUnknown);
$fixedMakerList = $checkUrl->fixMaker($fileErrorMaker,sprintf($fixedBase,'maker'));
echo "<pre>";
print_r($fileMakerList);
echo "</pre>";

