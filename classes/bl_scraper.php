<?php

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

class BL_Scraper {

  public $concerns = array(

  );

  function __construct() {

  }

  public static function call_local_dir($options,$api_endpoint,$directory,$ymd) {
    //in this case the arg $options are request body key=>val pairs
    global $wpdb;
    $return_val = new stdClass();
    $commit = get_option('bl_api_client_activity');
    // data values for API response
    $reviews = [];
    $aggregate_rating = [];
    // task-routing values
    $commit_log = (isset($commit['log'])) ? end($commit['log']) : ['0,0','(not set)'];
    $locale_index = $commit_log[0][0];
    $db_dir = ($directory==='google') ? 'gmb' : $directory;
    $msg = '';
    $err_msg = '';
    // data vars -
    $final_reviews_batch = [];
    $all_agg_ratings = [];
    /*
    define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f');
    define('BL_API_SECRET', '58190962d27d9');
    */
    $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
    $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));
    error_log( urlencode($sig) ); // for get requests
    error_log( $sig );
    // instantiate API client
    $api = new Api(BL_API_KEY, BL_API_SECRET);
    $batchApi = new BatchApi($api);
    // NOTE: THIS IS THE LISTENER FOR THE PROFILE URL - needs validation & testing!
    // Leave this commented out until lookup-by-URL actually returns a non-error
    // NOTE: BL_Client_Tasker::bl_api_get_request_body() still uses the presence/absence
    // of a profile URL (e.g.,['gmb_link']) to determine whether to make the call or not
    // so, it will be in the request body and needs validation if used
    /*
    if (isset($options[$db_dir . '_link'])) {
      $append_endpoint = '';
      $body_params['profile-url'] = $options[$db_dir . '_link'];
      $body_params['country'] = $options['country'];
    } else {
    */
      $append_endpoint = '-by-business-data';
      $body_params = $options;
      $body_params['local-directory'] = $directory;
      $body_params['date-from'] = $ymd;
      error_log('api call start date set for ' . $ymd);
    //}

    $batchId = $batchApi->create();

    if ($batchId) {
      // add the crucial batch ID to the req body params
      $body_params['batch-id'] = $batchId;
      error_log('Created batch ID ' . $batchId . PHP_EOL);

      // THIS IS THE ACTUAL API CALL from the BL API Client Library
      $result = $api->call('/v4/ld/'. $api_endpoint . $append_endpoint, $body_params);

      if ($result['success']) {
        error_log('Added job with ID ' . strval($result['job-id']) . ' ' . strval(PHP_EOL));
      } else {
        error_log(print_r($result));
        $err_msg .= ' API call error summary: ';
        foreach($result as $key => $val) {
          $err_msg .= $key . ':' . $val . ', ';
        }
      }
      //
      if ($batchApi->commit($batchId)) {
        error_log( 'Committed batch successfully.'. PHP_EOL);
        // poll for results here?
        do {
          $results = $batchApi->get_results($batchId);
          // limit how often you poll?
        } while (!in_array($results['status'], array('Stopped', 'Finished')));
        // DATA
        //refer to the /xmpl/data-sample.txt file
        //THIS IS THE PATH TO THE REVIEWS ARRAY!
        //error_log(var_dump($results['results']['LdFetchReviews'][0]['results'][0]['reviews']));

        //THIS IS THE PATH TO THE REVIEWS Count & Agg Rating!
        error_log('aggregate count');
        error_log($results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']);
        error_log('aggregate rating');
        error_log($results['results']['LdFetchReviews'][0]['results'][0]['star-rating']);
        // store the API response data if present
        $reviews = (isset($results['results']['LdFetchReviews'][0]['results'][0]['reviews'])) ?
          $results['results']['LdFetchReviews'][0]['results'][0]['reviews'] : null;
        $aggregate_rating = (
          isset($results['results']['LdFetchReviews'][0]['results'][0]['star-rating']) &&
          isset($results['results']['LdFetchReviews'][0]['results'][0]['reviews-count'])
          ) ? array(
            'rating' => $results['results']['LdFetchReviews'][0]['results'][0]['star-rating'],
            'count' => $results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']
            ) : null;
      } else {
        $err_msg .= ' BL API library batch commit failure ';
      }

    } else {
      $err_msg .=  ' invalid batch ID ';
    }
    // DATA VALIDATION TASK!!!
    // Build the return values
    $return_val->reviews = self::valid_batch_array(
      'reviews',
      $reviews,
      $commit,
      $locale_index,
      $directory
    );
    $return_val->aggregate_rating = self::valid_batch_array(
      'aggregate_rating',
      $aggregate_rating,
      $commit,
      $locale_index,
      $directory
    );
    // commit the results based on outcome for reviews, agg, and log
    if ($return_val->reviews) {
      // update options table with recent activtiy
      $commit['reviews'] = self::valid_activity_array(
        $return_val->reviews,
        $commit,
        $locale_index,
        $directory
      );
      // post new reviews crs_review style
      BL_Review_Monster::post_reviews($return_val->reviews);
      $msg .= ' successful review scrape - '. date('F d Y H:i',time());
      //
    } else {
      $msg .= ' no recent reviews found in batch - ' . date('F d Y H:i',time());
    }

    if ($return_val->aggregate_rating) {
      $commit[$directory . '_aggregate_rating'] = $return_val->aggregate_rating;
      $msg .= ' - successful aggregate rating response';
      error_log($msg);
    } else {
      $err_msg .= ' - no data found in final reviews batch ';
      $msg = 'error: ' . date('F d Y H:i',time());
      $msg .= '' . $err_msg;
      error_log($msg);
    }

    $commit['log'][] = [$commit_log[0],$msg];
    update_option('bl_api_client_activity',$commit);
    //NOTE: return val needed? - or will this cause blocking effects? Does it matter?
    //return $return_val;
  }


  public static function valid_batch_array($batch_type,$data,$commit,$locale_index,$directory) {
    $result = null;
    if ($data) {
      $result = [];
      switch($batch_type) {
        case 'aggregate_rating' :
          if ( isset($commit[$directory . '_aggregate_rating']) &&
            is_array($commit[$directory . '_aggregate_rating'])) {
           // make record exluding the current locale's previously stored aggregate . . .
            foreach ($commit[$directory . '_aggregate_rating'] as $current_rating_obj) {
              if ($current_rating_obj['locale_id']!=strval($locale_index+1)) {
                $result[] = $current_rating_obj;
              }
            }
          }
          $data['locale_id'] = strval($locale_index+1);
          $result[] = $data;
          break;
        case 'reviews' :
          foreach($data as $review) {
            $this_review = $review;
            $this_review['locale_id'] = strval($locale_index+1);
            $this_review['listing_directory'] = $directory;
            $result[] = $this_review;
          }
          break;
        default :
      }
    }
    return $result;
  }

  public static function valid_activity_array($data,$commit,$locale_index,$directory) {
    // for activity admin panel only - recent activity backlog in options table
    $result = null;
    $staging_arr = [];
    if ($data) {
      $result = [];
      if (count($commit['reviews'])) {
        foreach($commit['reviews'] as $review) {
          if ( $review['locale_id']!=strval($locale_index+1) ||
            $directory!=$review['listing_directory']) {
          // if the locale id or directory listing don't match the current
          // push the backlogged review onto another separate array
            $staging_arr[] = $review;
          }
        }
      }
      // meerge the new reviews with the existing
      $result = array_merge($data,$staging_arr);
    }
    return $result;
  }

  // FAKE API CALL - returns fake data for testing puposes only
  // Don't include this in production code
  public static function sim_call_local_dir($options,$api_endpoint,$directory,$ymd) {
    //in this case the arg $options are request body key=>val pairs
    global $wpdb;
    $return_val = new stdClass();
    $commit = get_option('bl_api_client_activity');
    // data values for API response
    $reviews = [];
    $aggregate_rating = [];
    // task-routing values
    $commit_log = (isset($commit['log'])) ? end($commit['log']) : ['0,0','(not set)'];
    $locale_index = $commit_log[0][0];
    $db_dir = ($directory==='google') ? 'gmb' : $directory;
    $msg = '';
    $err_msg = '';
    // data vars -
    $final_reviews_batch = [];
    $all_agg_ratings = [];
    /*
    define('BL_API_KEY', 'f972131781582b6dfead1afa4f8082fe79a4765f');
    define('BL_API_SECRET', '58190962d27d9');
    */
    $expires = (int) gmdate('U') + 1800; // not more than 1800 seconds
    $sig = base64_encode(hash_hmac('sha1', BL_API_KEY.$expires, BL_API_SECRET, true));
    error_log( urlencode($sig) ); // for get requests
    error_log( $sig );
    // instantiate API client
    $api = new Api(BL_API_KEY, BL_API_SECRET);
    $batchApi = new BatchApi($api);
    // NOTE: THIS IS THE LISTENER FOR THE PROFILE URL - needs validation & testing!
    // Leave this commented out until lookup-by-URL actually returns a non-error
    // NOTE: BL_Client_Tasker::bl_api_get_request_body() still uses the presence/absence
    // of a profile URL (e.g.,['gmb_link']) to determine whether to make the call or not
    // so, it will be in the request body and needs validation if used
    /*
    if (isset($options[$db_dir . '_link'])) {
      $append_endpoint = '';
      $body_params['profile-url'] = $options[$db_dir . '_link'];
      $body_params['country'] = $options['country'];
    } else {
    */
      $append_endpoint = '-by-business-data';
      $body_params = $options;
      $body_params['local-directory'] = $directory;
      $body_params['date-from'] = $ymd;
      error_log('api call start date set for ' . $ymd);
    //}

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
         'rating' => 2,
         'author' => 'serjvvvffrmim Ksejcccigp',
         'timestamp' => '2020-06-20',
         'text' =>'We had a great experience with Earthworks Excavating Services Spaghetti. The communication was wonderful.',
         'positive' => array ( ),
         'critical' => array ( ),
         'author_avatar' => 'https://lh4.googleusercontent.com/-QRcjn8rMZx4/AAAAAAAAAAI/AAAAAAAAAAA/c0DpEHMERks/s40-c-rp-mo-br100/photo.jpg',
         'id' => '50399aec6a38ab58426ae2e77057a05c36167f52'
     ),
          array (
             'rating' => 3,
             'author' => 'Saglxxxre eglrvvvvffo',
             'timestamp' => '2020-06-20',
             'text' =>'',
             'positive' => array ( ),
             'critical' => array ( ),
             'author_avatar' => 'https://lh4.googleusercontent.com/-QRcjn8rMZx4/AAAAAAAAAAI/AAAAAAAAAAA/c0DpEHMERks/s40-c-rp-mo-br100/photo.jpg',
             'id' => '50399aec6a38ab58426ae2e77057a05c36167f52'
         ), array (
             'rating' => 4,
             'author' => 'Tfnbvvvjjjffoim Trdseqr',
             'timestamp' => '2020-06-20',
             'text' => 'We had a great experience with Earthworks Excavating Services Spaghetti. The communication was wonderful.',
             'positive' => array ( ),
             'critical' => array ( ),
             'author_avatar' => 'https://lh6.googleusercontent.com/-m-jjYqGDLyE/AAAAAAAAAAI/AAAAAAAAAAA/ynbQXsyEu50/s40-c-rp-mo-br100/photo.jpg',
             'id' => '68d81651c71f99b6cb857c2c8c2b31464e23d83a',
        ),
        array (
           'rating' => 3,
           'author' => 'Sadttteke eefffecccses',
           'timestamp' => '2020-06-20',
           'text' =>'',
           'positive' => array ( ),
           'critical' => array ( ),
           'author_avatar' => 'https://lh4.googleusercontent.com/-QRcjn8rMZx4/AAAAAAAAAAI/AAAAAAAAAAA/c0DpEHMERks/s40-c-rp-mo-br100/photo.jpg',
           'id' => '50399aec6a38ab58426ae2e77057a05c36167f52'
       ), array (
           'rating' => 5,
           'author' => 'Caffeferkrk kefennnnon',
           'timestamp' => '2020-06-120',
           'text' => 'We had a great experience with Earthworks Excavating Services Spaghetti. The communication was wonderful.',
           'positive' => array ( ),
           'critical' => array ( ),
           'author_avatar' => 'https://lh6.googleusercontent.com/-m-jjYqGDLyE/AAAAAAAAAAI/AAAAAAAAAAA/ynbQXsyEu50/s40-c-rp-mo-br100/photo.jpg',
           'id' => '68d81651c71f99b6cb857c2c8c2b31464e23d83a',
      )
    );
    $aggregate_rating = array('count'=>111,'rating'=>3.3);
    }
    // DATA VALIDATION TASK!!!
    // Build the return values
    $return_val->reviews = self::valid_batch_array(
      'reviews',
      $reviews,
      $commit,
      $locale_index,
      $directory
    );
    $return_val->aggregate_rating = self::valid_batch_array(
      'aggregate_rating',
      $aggregate_rating,
      $commit,
      $locale_index,
      $directory
    );
    // commit the results based on outcome for reviews, agg, and log
    if ($return_val->reviews) {
      // update options table with recent activtiy
      $commit['reviews'] = self::valid_activity_array(
        $return_val->reviews,
        $commit,
        $locale_index,
        $directory
      );
      // post new reviews crs_review style
      BL_Review_Monster::post_reviews($return_val->reviews);
      $msg .= ' successful review scrape - '. date('F d Y H:i',time());
      //
    } else {
      $msg .= ' no recent reviews found in batch - ' . date('F d Y H:i',time());
    }

    if ($return_val->aggregate_rating) {
      $commit[$directory . '_aggregate_rating'] = $return_val->aggregate_rating;
      $msg .= ' - successful aggregate rating response';
      error_log($msg);
    } else {
      $err_msg .= ' - no data found in final reviews batch ';
      $msg = 'error: ' . date('F d Y H:i',time());
      $msg .= '' . $err_msg;
      error_log($msg);
    }

    $commit['log'][] = [$commit_log[0],$msg];
    update_option('bl_api_client_activity',$commit);
    //NOTE: return val needed? - or will this cause blocking effects? Does it matter?
    //return $return_val;
  }

}
