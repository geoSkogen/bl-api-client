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


register_activation_hook( __FILE__, 'bl_api_client_activate' );

function bl_api_client_activate() {
  $commit = array('log'=>array('placeholder'));
  update_option('bl_api_client',$commit);
}

$options = get_option('bl_api_client');

if (isset($options)) {
  error_log('found db slug');
  error_log('iterating db entries');
    foreach ($options['log'] as $entry) {
      error_log($entry);
    }
} else {
  error_log('db slug not found');
}

//add_action( 'bl_api_client_cron_hook', 'bl_api_call' );
/*
if ( ! wp_next_scheduled( 'bl_api_client_cron_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'bl_api_client_cron_hook' );
}
*/
//https://search.google.com/local/reviews?placeid=ChIJsc2v07GxlVQRRK-jGkZfiw0
//https://local.google.com/place?id=975978498955128644&use=srp&hl=en
//ChIJsc2v07GxlVQRRK-jGkZfiw0
//975978498955128644

function bl_api_call() {
  error_log('cron scheduler');
  $options = array(
    'business-names'  => 'Earthworks Excavating Services',
    'city'            => 'Battle Ground',
    'postcode'        => '98604',
    'street-address'  => '1420 SE 13TH ST',
    'country'         => 'USA',
    'telephone'       => '(360) 772-0088'
  );

  bl_api_call_local_dir($options,'google');
}

function bl_api_call_local_dir($options,$dir) {

  define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f ');
  define('BL_API_SECRET', '58190962d27d9');
  $commit = get_option('bl_api_client');
  $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
  $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));

  error_log( urlencode($sig) ); // for get requests
  error_log( $sig );

  $api = new Api(BL_API_KEY, BL_API_SECRET);
  $batchApi = new BatchApi($api);
  $body_params = $options;
  $body_params['local-directory'] = $dir;
  $batchId = $batchApi->create();

  if ($batchId) {
    $body_params['batch-id'] = $batchId;
    error_log('Created batch ID %d%s', $batchId, PHP_EOL);

    $result = $api->call('/v4/ld/fetch-reviews-by-business-data', $body_params);

    if ($result['success']) {
      error_log('Added job with ID %d%s ' . strval($result['job-id']) . ' ' . strval(PHP_EOL));
    } else {
      error_log(print_r($result));
    }

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
       $commit['log'][] = time();
       update_option('bl_api_client',$commit);
    }
  }
}
