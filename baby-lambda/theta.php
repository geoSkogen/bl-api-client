<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/records/auth.php');
require_once(__DIR__ . '/records/places.php');
require_once(__DIR__ . '/classes/body_params.php');
require_once(__DIR__ . '/classes/creds.php');

use GuzzleHttp\Client;

$name_key = ( isset($argv[1]) ) ? $argv[1] : '';
$args = array();
$host = ( isset($client_data[$name_key]['host'])) ?
  $client_data[$name_key]['host'] : '';
//$req_schema = BodyParams::post_rev_req_bod($args);
$req_schema = array();
     //UNCOMMENT TO MAKE REST REQUEST
if ($host) {
  $res = rest_request('GET',$host,'pages',false,$req_schema);
  error_log(print(json_encode($req_schema))  . "\r\n");
}


function rest_request($method,$host,$resource,$permission,$schema) {
  $client = new GuzzleHttp\Client();
  $url = Creds::write_rest_url($host,$resource);
  $params = array();
  $options = ["headers" => Creds::write_headers_arr($host,$permission), "body" => json_encode($schema)];
  error_log($url . "\r\n");
  error_log(print_r($options["headers"]));
  error_log(print_r($options["body"]));
  $response = $client->request($method,$url,$options);
  //error_log($response->getBody()->read(5000));
  echo $response->getBody()->read(5000) ;
  return $response;
}
