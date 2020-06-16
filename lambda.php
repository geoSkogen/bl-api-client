<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/records/my_place.php');
use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

//SERVERLESS DEPLOYMENT of lookup-by-url for dev debugging
$api = new Api(
  'f972131781582b6dfead1afa4f8082fe79a4765f', //key
  '58190962d27d9' //secret
);
$batchApi = new BatchApi($api);
$directory = 'google';
$err_msg = '';
$api_endpoint = 'fetch-reviews';
$append_endpoint = '-by-business-data';
$body_params = array(
  'business-names'=> $biz_info['business-names'],
  'street-address'=> $biz_info['street-address'],
  'city'=>$biz_info['city'],
  'country'=>$biz_info['country'],
  'postcode'=> $biz_info['postcode'],
  'telephone'=> $biz_info['telephone']
);
$body_params['local-directory'] = $directory;

$batchId = $batchApi->create();

if ($batchId) {
  // add the crucial batch ID to the req body params
  $body_params['batch-id'] = $batchId;
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
    error_log(var_dump($results['results']['LdFetchReviews'][0]['results'][0]['reviews']));
    $biz_slug = str_replace(' ','',$biz_info['business_names'])
    $export_str = json_encode($results);
    file_put_contents('exports/' . $biz_slug . '.json',$export_str);
    //THIS IS THE PATH TO THE REVIEWS Count & Agg Rating!
    error_log('aggregate count<br/>');
    error_log($results['results']['LdFetchReviews'][0]['results'][0]['reviews-count']);
    error_log('aggregate rating<br/>');
    error_log($results['results']['LdFetchReviews'][0]['results'][0]['star-rating']);

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
if ($err_msg) {
  error_log($err_msg);
}
?>
