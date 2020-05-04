<?php

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

class BL_Scraper {

  public $concerns = array(

  );

  function __construct() {

  }

  public static function call_local_dir($auth,$options,$api_endpoint,$directory) {

    $return_val = new stdClass();
    /*
    define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f');
    define('BL_API_SECRET', '58190962d27d9');
    */
    define('BL_API_KEY', $auth['api_key']);
    define('BL_API_SECRET', $auth['api_secret']);

    $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
    $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));

    error_log( urlencode($sig) ); // for get requests
    error_log( $sig );

    $api = new Api(BL_API_KEY, BL_API_SECRET);
    $batchApi = new BatchApi($api);
    if (isset($options['gmb'])) {
      $append_endpoint = '';
      $body_params['profile_url'] = $options['gmb'];
      $body_params['country'] = $options['country'];
    } else {
      $append_endpoint = '-by-business-data';
      $body_params = $options;
      $body_params['local-directory'] = $directory;
    }

    $batchId = $batchApi->create();

    if ($batchId) {
      $body_params['batch-id'] = $batchId;
      error_log('Created batch ID ' . $batchId . PHP_EOL);

      $result = $api->call('/v4/ld/'. $api_endpoint . $append_endpoint, $body_params);
      //$result = $api->call('/v4/ld/fetch-profile-url', $body_params);

      if ($result['success']) {
        error_log('Added job with ID ' . strval($result['job-id']) . ' ' . strval(PHP_EOL));
      } else {
        print_r($result);
      }

      if ($batchApi->commit($batchId)) {
        error_log( 'Committed batch successfully.'. PHP_EOL);
        // poll for results
        do {
              $results = $batchApi->get_results($batchId);
              //sleep(10); // limit how often you poll
        } while (!in_array($results['status'], array('Stopped', 'Finished')));
          //print_r($results);
          //print_r($results['results']['LdFetchReviews'][0]['results'][0]['reviews']);
         //refer to the data-sample.txt file
         //THIS IS THE PATH TO THE REVIEWS ARRAY!
          print('reviews<br/>');
          print_r($results['results']['LdFetchReviews'][0]['results'][0]['reviews']);
          //THIS IS THE PATH TO THE REVIEWS Count & Agg Rating!
          print('aggregate count<br/>');
          print_r($results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']);
          print('aggregate rating<br/>');
          print_r($results['results']['LdFetchReviews'][0]['results'][0]['star-rating']);
         //log results--add timestamp to db

        $reviews = $results['results']['LdFetchReviews'][0]['results'][0]['reviews'];
        $aggregate_rating = array(
          'rating' => $results['results']['LdFetchReviews'][0]['results'][0]['star-rating'],
          'count' => $results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']
        );
      }
    }
    $return_val->reviews = ($reviews) ? $reviews : null;
    $return_val->aggregate_rating = ($aggregate_rating) ? $aggregate_rating : null;
    $commit['log'][] = time();
    if ($return_val->reviews && $return_val->aggregate_rating) {
      $commit['reviews'] = $return_val->reviews;
      $commit['aggregate_rating'] = $return_val->aggregate_rating;
    } else {
      error_log('review scrape error occurred');
    }
    update_option('bl_api_client_activity',$commit);
    return $return_val;
  }

}
