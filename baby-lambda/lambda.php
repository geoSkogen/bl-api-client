<?php
require_once(__DIR__ . '../../vendor/autoload.php');
require_once(__DIR__ . '/records/auth.php');
require_once(__DIR__ . '/records/places.php');
require_once(__DIR__ . '/classes/body_params.php');
require_once(__DIR__ . '/classes/creds.php');

ini_set('max_execution_time', 300);

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

//SERVERLESS DEPLOYMENT of Review Scrape

$name_key = ( isset($argv[1]) ) ? $argv[1] : '';
$place_index = ( isset($argv[2]) ) ? $argv[2] : 0;
$directory_index = ( isset($argv[3]) ) ? $argv[3] : 0;

$body_params = Body_Params::get_nap_req_bod(
  $name_key,
  $place_index,
  $directory_index,
  $client_data
);

if ($body_params) {
  $response = call_local_directory($body_params,$place_index,false);
  if ($response['results']['LdFetchReviews'][0]['results'][0]['reviews'] &&
      $response['results']['LdFetchReviews'][0]['results'][0]['reviews-count'] &&
      $response['results']['LdFetchReviews'][0]['results'][0]['star-rating']) {
    // determine how many 'pages' of reviews we have
    $all_reviews = $response['results']['LdFetchReviews'][0]['results'][0]['reviews'];
    $total_reviews_count = $response['results']['LdFetchReviews'][0]['results'][0]['reviews-count'];
    $rating = $response['results']['LdFetchReviews'][0]['results'][0]['star-rating'];
    $review_array_length = count($response['results']['LdFetchReviews'][0]['results'][0]['reviews']);
    $pages_total = ceil(intval($total_reviews_count)/500);

    error_log(' response page 1 - got reviews: ' . strval(count($all_reviews)));

    if ($pages_total > 1) {
      for ($i = 2; $i < $pages_total+1; $i++) {
        error_log(' multi page response - calling page ' . strval($i));
        $response = call_local_directory($body_params,$place_index,strval($i));
        if ($response['results']['LdFetchReviews'][0]['results'][0]['reviews'] &&
            $response['results']['LdFetchReviews'][0]['results'][0]['reviews-count'] &&
            $response['results']['LdFetchReviews'][0]['results'][0]['star-rating']
           ) {
          // append older reviews pages to newer
          $all_reviews = array_merge(
            $all_reviews,
            $response['results']['LdFetchReviews'][0]['results'][0]['reviews']
          );
          error_log('new review array length: ' . strval(count($all_reviews)));
        } else {
          error_log('api error on page ' . strval($i) . ' - no reviews object found');
        }
      } // end 'pages' iteration
    } else {
      error_log('single page response');
    }
    $data = array(
      'reviews'=>$all_reviews,
      'aggregate_rating'=> array(
        'count'=>$total_reviews_count,
        'rating'=>$rating
      )
    );
    $biz_slug = str_replace(' ','',$body_params['business-names']);
    $biz_slug .= '_' . strval(intval($place_index)+1) . '_' . $body_params['local-directory'];
    $json_export_str = json_encode($data);
    file_put_contents('exports/' . $biz_slug . '.json',$json_export_str);
    $csv_export_str = get_labeled_csv_columns($data['reviews']);
    file_put_contents('exports/' . $biz_slug . '.csv',$csv_export_str);
  } else {
    error_log('api error on page 1 - no reviews object found');
    $err_msg = ' api error on page 1 - no reviews object found';
  }
} else {
  error_log('client not found');
  $err_msg = ' client not found';
}

if ($err_msg) {
  error_log($err_msg);
}

function call_local_directory($body_params,$place_index,$page) {
  global $err_msg;
  $api = new Api(
    BL_API_KEY, //key
    BL_API_SECRET //secret
  );

  $batchApi = new BatchApi($api);
  error_log(var_dump($body_params));
  $err_msg = '';
  $api_endpoint = 'fetch-reviews';
  $append_endpoint = '-by-business-data';

  $batchId = $batchApi->create();

  if ($batchId) {
    // add the crucial batch ID to the req body params
    $body_params['batch-id'] = $batchId;
    if ($page) {
      $body_params['start-page'] = $page;
    }
    error_log('Created batch ID ' . $batchId . PHP_EOL);
    // THIS IS THE ACTUAL API CALL from the BL API Client Library
    $result = $api->call('/v4/ld/'. $api_endpoint . $append_endpoint, $body_params);
    // This is how you would make a similar request for your own profile URL . . .
    //$result = $api->call('/v4/ld/fetch-profile-url', $body_params);
    // . . . response body handling not included; so far, lookup-by-link returns errors
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
        //sleep(10); // . . . e.g., to limit how often you poll?
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

    } else {
      $err_msg .= ' BL API library batch commit failure ';
    }
  } else {
    $err_msg .=  ' invalid batch ID ';
  }
  return $results;
}

function get_labeled_csv_columns($table) {
  $str = '';
  $row_index = 0;

  foreach($table as $row) {
    $label_str = '';
    $row_str = '';
    $key_index = 0;
    foreach($row as $key=>$val) {
      if (!$row_index) {
        $label_str .= '"' . $key . '"';
        $label_str .= ($key_index < count(array_keys($row))-1) ? ',' : "\r\n";
      }
      /*
      error_log('key index');
      error_log(strval($key_index));
      error_log('array keys count');
      error_log(strval(count(array_keys($row))-1));
      */
      if (!is_array($val)) {
        $row_str .= '"' . $val . '"';
      } else {
        $row_str .= '"' . 'Array' . '"';
      }
      $row_str .= ($key_index < count(array_keys($row))-1) ? ',' : "\r\n";
      $key_index++;
    }
    $str .= (!$row_index) ? $label_str . $row_str : $row_str;
    $row_index++;
  }
  return $str;
}

?>
