<?php

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

class BL_Scraper {

  public $concerns = array(

  );

  function __construct() {

  }

  public static function call_local_dir($options,$concern) {

    define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f ');
    define('BL_API_SECRET', '58190962d27d9');
    $commit = get_option('bl_api_client');
    $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
    $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));

    error_log( urlencode($sig) ); // for get requests
    error_log( $sig );

    $api = new Api(BL_API_KEY, BL_API_SECRET);
    $batchApi = new BatchApi($api);
    if ($options['gmb']) {
      $append_endpoint = '';
      $body_params['profile_url'] = $options['gmb'];
      $body_params['country'] = $options['country'];
    } else {
      $append_endpoint = '-by-business-data';
      $body_params = $options;
      $body_params['local-directory'] = 'google';
    }

    $batchId = $batchApi->create();

    if ($batchId) {
      $body_params['batch-id'] = $batchId;
      error_log('Created batch ID ' . $batchId . PHP_EOL);

      $result = $api->call('/v4/ld/'. $concern . $append_endpoint, $body_params);
      //$result = $api->call('/v4/ld/fetch-profile-url', $body_params);

      if ($result['success']) {
        error_log('Added job with ID ' . strval($result['job-id']) . ' ' . strval(PHP_EOL));
      } else {
        error_log(print_r($result));
      }

      if ($batchApi->commit($batchId)) {
        error_log( 'Committed batch successfully.'. PHP_EOL);
        // poll for results
        do {
              $results = $batchApi->get_results($batchId);
              //sleep(10); // limit how often you poll
        } while (!in_array($results['status'], array('Stopped', 'Finished')));

         //THIS IS THE PATH TO THE REVIEWS ARRAY!
         //refer to the data-sample.txt file
         error_log(print_r($results['results']['LdFetchReviews'][0]['results'][0]['reviews']));
         //log results--add timestamp to db
         $commit['log'][] = time();
         update_option('bl_api_client',$commit);
      }
    }
  }

}
