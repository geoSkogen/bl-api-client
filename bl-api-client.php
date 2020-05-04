<?php
/*
Plugin Name:  bl-api-client
Description:  bl-api-client
Version:      2020.05.01
Author:       City Ranked Media
Author URI:
Text Domain:  bl_api_client
*/

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

register_activation_hook( __FILE__, 'bl_api_client_activate' );
register_deactivation_hook( __FILE__, 'bl_api_client_deactivate' );

function bl_api_client_activate() {
  $options = get_option('bl_api_client_activity');
  $commit = array(
    'log'=>['placeholder'],
    'reviews'=> (isset($options['reviews'])) ? $options['reviews'] : '',
    'aggregate_rating'=> (isset($options['aggregate_rating'])) ?
       $options['aggregate_rating'] : array('rating'=>'','count'=>'')
  );
  update_option('bl_api_client_activity',$commit);
}

function bl_api_client_deactivate() {
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  wp_unschedule_event( $timestamp, 'bl_api_client_cron_hook' );
}
//test pattern
$options = get_option('bl_api_client_activity');
if (isset($options)) {
  error_log('found db slug');
  error_log('iterating db entries');
  if ($options['aggregate_rating']) {
    foreach ($options['aggregate_rating'] as $key => $value) {
      error_log($key);
      error_log($value);
    }
  }
} else {
  error_log('db slug not found');
}
//Admin
if ( !class_exists( 'BL_API_Client_Options' ) ) {
   include_once 'admin/bl_api_client_options.php';
   add_action(
    'admin_menu',
    array('BL_API_Client_Options','bl_api_client_register_menu_page')
  );
}
if ( !class_exists( 'BL_API_Client_Settings' ) ) {
   include_once 'admin/bl_api_client_settings.php';
   add_action(
     'admin_init',
     array('BL_API_Client_Settings','settings_api_init')
   );
}

if ( !class_exists( 'BL_Scraper' ) ) {
  require_once(__DIR__ . '/vendor/autoload.php');
  include_once 'classes/bl_scraper.php';
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

//bl_api_call();

function bl_api_call() {
  $one_option = get_option('bl_api_client_settings');
  $commit = get_option('bl_api_client_activity');
  $auth = get_option('bl_api_client');
  $req_body = array();
  $valid_keys = array(
    'business_name'=>'business-names','city'=>'city','zipcode'=>'postcode',
    'address'=>'street-address','phone'=>'telephone');
  $i = 1;
  $indexer = '_' . strval($i);
  foreach (array_keys($valid_keys) as $valid_key) {
    if (isset($one_option[$valid_key . $indexer])
      && '' !=$one_option[$valid_key . $indexer]) {
        $req_body[$valid_keys[$valid_key]] = $one_option[$valid_key . $indexer];
      }
  }
  error_log('cron scheduler');
  error_log('test pattern for valid keys');
  foreach(array_keys($valid_keys) as $this_key) {
    error_log($this_key);
  }
  error_log('test pattern for req body');
  foreach(array_keys($req_body) as $this_key) {
    error_log($this_key);
  }
  if ( count(array_keys($req_body))===count(array_keys($valid_keys)) ) {
    $req_body['country'] = 'USA';
    error_log('found all valid keys');
    if (isset($auth['api_key']) && isset($auth['api_secret'])) {
      error_log('found api keys');
      $result = BL_Scraper::call_local_dir($auth,$req_body,'fetch-reviews','google');
    } else {
      error_log('api keys not found');
    }
  } else {
    error_log('valid keys not found');
  }

  $options = array(
    'business-names'  => 'Earthworks Excavating Services',
    'city'            => 'Battle Ground',
    'postcode'        => '98604',
    'street-address'  => '1420 SE 13TH ST',
    'country'         => 'USA',
    'telephone'       => '(360) 772-0088'//,
    //'gmb'             => "https://local.google.com/place?id=975978498955128644"
  );


  /*
  if ($result->reviews && $result->aggregate_rating) {
    $commit['reviews'] = $result->reviews;
    $commit['aggregate_rating'] = $result->aggregate_rating;
  } else {
    error_log('review scrape error occurred');
  }
  */
  //update_option('bl_api_client_activity',$commit);
}
