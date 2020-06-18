<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/records/auth.php');
require_once(__DIR__ . '/records/places.php');
require_once(__DIR__ . '/classes/body_params.php');
require_once(__DIR__ . '/classes/creds.php');

ini_set('max_execution_time', 300);

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;
$api = new Api(
  BL_API_KEY, //key
  BL_API_SECRET //secret
);
