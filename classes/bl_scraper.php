<?php

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

class BL_Scraper {

  public $concerns = array(

  );

  function __construct() {

  }

  public static function sim_call_local_dir($options,$api_endpoint,$directory) {

    $return_val = new stdClass();
    $commit = get_option('bl_api_client_activity');
    // meta values
    $commit_log = (isset($commit['log'])) ? end($commit['log']) : ['-1,-1','not set)'];
    $xy_str = (isset($commit_log[0])) ? $commit_log[0] : '-1,-1';
    $log_index = (isset($commit['log'])) ? count($commit['log'])-1 : 0;
    $locale_index = explode(',',$xy_str)[0];
    $msg = '(not set)';
    // data values
    $reviews = [];
    $aggregate_rating = [];
    /*
    define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f');
    define('BL_API_SECRET', '58190962d27d9');
    */
    /*
    define('BL_API_KEY', $auth['api_key']);
    define('BL_API_SECRET', $auth['api_secret']);
    */
    $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
    $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));

    error_log( urlencode($sig) ); // for get requests
    error_log( $sig );

    $db_dir = ($directory==='google') ? 'gmb' : $directory;
    $api = new Api(BL_API_KEY, BL_API_SECRET);
    $batchApi = new BatchApi($api);
    if (isset($options[$db_dir . '_link'])) {
      $append_endpoint = '';
      $body_params['profile-url'] = $options[$db_dir . '_link'];
      $body_params['country'] = $options['country'];
    } else {
      $append_endpoint = '-by-business-data';
      $body_params = $options;
      $body_params['local-directory'] = $directory;
    }

    $batchId = $batchApi->create();

    if ($batchId) {
      //
      /*
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
          error_log('reviews<br/>');
          //error_log(var_dump($results['results']['LdFetchReviews'][0]['results'][0]['reviews']));
          //THIS IS THE PATH TO THE REVIEWS Count & Agg Rating!
          error_log('aggregate count<br/>');
          error_log($results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']);
          error_log('aggregate rating<br/>');
          error_log($results['results']['LdFetchReviews'][0]['results'][0]['star-rating']);
         //log results--add timestamp to db

        $reviews = $results['results']['LdFetchReviews'][0]['results'][0]['reviews'];
        $aggregate_rating = array(
          'rating' => $results['results']['LdFetchReviews'][0]['results'][0]['star-rating'],
          'count' => $results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']
        );
      }
    //
    */
    $reviews = array(
          array (
             'rating' => 5,
             'author' => 'Kathy Asato',
             'timestamp' => '2020-04-02',
             'text' =>'Facebook We had a great experience with Earthworks Excavating Services 988. The communication was wonderful.',
             'positive' => array ( ),
             'critical' => array ( ),
             'author_avatar' => 'https://lh4.googleusercontent.com/-QRcjn8rMZx4/AAAAAAAAAAI/AAAAAAAAAAA/c0DpEHMERks/s40-c-rp-mo-br100/photo.jpg',
             'id' => '50399aec6a38ab58426ae2e77057a05c36167f52'
         ), array (
             'rating' => 5,
             'author' => 'Advanced Plumbing',
             'timestamp' => '2020-03-02',
             'text' => 'Facebook We had a great experience with Earthworks Excavating Services 988. The communication was wonderful.',
             'positive' => array ( ),
             'critical' => array ( ),
             'author_avatar' => 'https://lh6.googleusercontent.com/-m-jjYqGDLyE/AAAAAAAAAAI/AAAAAAAAAAA/ynbQXsyEu50/s40-c-rp-mo-br100/photo.jpg',
             'id' => '68d81651c71f99b6cb857c2c8c2b31464e23d83a',
        ),
        array (
           'rating' => 5,
           'author' => 'Kay Ao',
           'timestamp' => '2020-05-02',
           'text' =>'Facebook We had a great experience with Earthworks Excavating Services 988. The communication was wonderful.',
           'positive' => array ( ),
           'critical' => array ( ),
           'author_avatar' => 'https://lh4.googleusercontent.com/-QRcjn8rMZx4/AAAAAAAAAAI/AAAAAAAAAAA/c0DpEHMERks/s40-c-rp-mo-br100/photo.jpg',
           'id' => '50399aec6a38ab58426ae2e77057a05c36167f52'
       ), array (
           'rating' => 5,
           'author' => 'Aed Ping',
           'timestamp' => '2020-05-02',
           'text' => 'Facebook We had a great experience with Earthworks Excavating Services 988. The communication was wonderful.',
           'positive' => array ( ),
           'critical' => array ( ),
           'author_avatar' => 'https://lh6.googleusercontent.com/-m-jjYqGDLyE/AAAAAAAAAAI/AAAAAAAAAAA/ynbQXsyEu50/s40-c-rp-mo-br100/photo.jpg',
           'id' => '68d81651c71f99b6cb857c2c8c2b31464e23d83a',
      )
    );
    $aggregate_rating = array('count'=>111,'rating'=>3.3);
    }
    // data vars
    $final_reviews_batch = [];
    $other_reviews = [];
    $all_reviews = [];
    $all_agg_ratings = [];
    // ensure each new item has a locale id
    foreach($reviews as $review) {
      $review['locale_id'] = $locale_index;
      $final_reviews_batch[] = $review;
    }
    $aggregrate_rating['locale_id'] = $locale_index;
    // make record exluding the current locale's previous reviews
    // and merge it with the new reviews for this locale
    foreach ($commit[$directory . '_reviews'] as $current_review) {
      if ($current_review['locale_id']!=strval($locale_index)) {
        $other_reviews[] = $current_review;
      }
    }
    $all_reviews = array_merge($final_reviews_batch,$other_reviews);
    foreach ($commit[$directory . '_aggregate_rating'] as $current_rating_obj) {
      if ($current_rating_obj['locale_id']!=strval($locale_index)) {
        $all_agg_ratings[] = $current_rating_obj;
      }
    }
    $all_agg_ratings[] = $aggregate_rating;
    $return_val->reviews = (count($final_reviews_batch)) ? $final_reviews_batch : null;
    $return_val->aggregate_rating = (count($aggregate_rating)) ? $aggregate_rating : null;

    if ($return_val->reviews && $return_val->aggregate_rating) {
      $commit[$directory . '_reviews'] = $all_reviews;
      $commit[$directory . '_aggregate_rating'] = $all_agg_ratings;
      $msg = time();
      error_log('review scrape ' . strval($msg));
    } else {
      $msg = 'review scrape error occurred';
      error_log('review scrape error occurred');
    }
    $commit['log'][] = [$xy_str,$msg];
    var_dump($commit);
    update_option('bl_api_client_activity',$commit);
    //return $return_val;
  }

  public static function call_local_dir($auth,$options,$commit,$api_endpoint,$directory) {

    $return_val = new stdClass();
    /*
    define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f');
    define('BL_API_SECRET', '58190962d27d9');
    */
    /*
    define('BL_API_KEY', $auth['api_key']);
    define('BL_API_SECRET', $auth['api_secret']);
    */

    $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
    $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));

    error_log( urlencode($sig) ); // for get requests
    error_log( $sig );

    $db_dir = ($directory==='google') ? 'gmb' : $directory;
    $api = new Api(BL_API_KEY, BL_API_SECRET);
    $batchApi = new BatchApi($api);
    if (isset($options[$db_dir . '_link'])) {
      $append_endpoint = '';
      $body_params['profile-url'] = $options[$db_dir . '_link'];
      $body_params['country'] = $options['country'];
    } else {
      $append_endpoint = '-by-business-data';
      $body_params = $options;
      $body_params['local-directory'] = $directory;
    }

    $batchId = $batchApi->create();
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
          error_log('reviews<br/>');
          //error_log(var_dump($results['results']['LdFetchReviews'][0]['results'][0]['reviews']));
          //THIS IS THE PATH TO THE REVIEWS Count & Agg Rating!
          error_log('aggregate count<br/>');
          error_log($results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']);
          error_log('aggregate rating<br/>');
          error_log($results['results']['LdFetchReviews'][0]['results'][0]['star-rating']);
         //log results--add timestamp to db
        //NOTE: iterate through reviews here and add location ID to each one!!!
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
      $commit[$directory . '_reviews'] = $return_val->reviews;
      $commit[$directory . '_aggregate_rating'] = $return_val->aggregate_rating;
    } else {
      error_log('review scrape error occurred');
    }
    update_option('bl_api_client_activity',$commit);
    return $return_val;
  }
}
