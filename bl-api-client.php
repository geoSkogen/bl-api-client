<?php
/*
Plugin Name:  bl-api-client
Description:  bl-api-client
Version:      2020.04.30
Author:
Author URI:
Text Domain:  bl_api_client
*/
require(__DIR__ . "/vendor/autoload.php");
use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f ');
define('BL_API_SECRET', '58190962d27d9');

$expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
$sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));

error_log( urlencode($sig) ); // for get requests
error_log( $sig );


$api = new Api(BL_API_KEY, BL_API_SECRET);
$batchApi = new BatchApi($api);

$batchId = $batchApi->create();

if ($batchId) {
    error_log('Created batch ID %d%s', $batchId, PHP_EOL);
    //foreach ($profileUrls as $profileUrl) {
        $result = $api->call('/v4/ld/fetch-reviews-by-business-data', array(
            'batch-id'        => $batchId,
            'business-names'  => 'Earthworks Excavating Services',
            'city'            => 'Battle Ground',
            'postcode'        => '98604',
            'street-address'  => '1420 SE 13TH ST',
            'local-directory' => 'google',
            'country'         => 'USA',
            'telephone'       => '(360) 772-0088'
        ));
        if ($result['success']) {
            error_log('Added job with ID %d%s ' . strval($result['job-id']) . ' ' . strval(PHP_EOL));
        } else {
          error_log(print_r($result));
        }
    //}
    if ($batchApi->commit($batchId)) {
        error_log( 'Committed batch successfully.'.PHP_EOL);
        // poll for results, in a real world example you might
        // want to do this in a separate process (such as via an
        // AJAX poll)

        do {
            $results = $batchApi->get_results($batchId);
            //sleep(10); // limit how often you poll
        } while (!in_array($results['status'], array('Stopped', 'Finished')));
        error_log(print_r($results));
    }
}
