<?php
require '../Auth.php';
require '../../vendor/autoload.php';

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

// setup API wrappers
$api = new Api(API_KEY, API_SECRET, API_ENDPOINT);
$batchApi = new BatchApi($api);
print_r($batchApi->get_results($argv[1]));
