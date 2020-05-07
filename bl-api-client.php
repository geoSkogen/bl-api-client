<?php
/*
Plugin Name:  bl-api-client
Description:  BrightLocal Client
Version:      2020.05.04
Author:       City Ranked Media
Author URI:
Text Domain:  bl_api_client
*/
defined( 'ABSPATH' ) or die( 'We make the path by walking.');

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

register_activation_hook( __FILE__, 'bl_api_client_activate' );
register_deactivation_hook( __FILE__, 'bl_api_client_deactivate' );

function bl_api_client_activate() {
  $activity = get_option('bl_api_client_activity');
  $settings = get_option('bl_api_client_settings');
  $commit = array(
    'log'=>['placeholder'],
    'reviews'=> (isset($activity['reviews'])) ? $activity['reviews'] : '',
    'aggregate_rating'=> (isset($activity['aggregate_rating'])) ?
       $activity['aggregate_rating'] : array('rating'=>'','count'=>'')
  );

  //error_log('crs biz options validatior running');
  $body_params = BL_CR_Suite_Client::validate_business_data('business');
  /*
  if ($body_params) {
    if (count(array_keys($body_params))===6) {
      error_log('got cr suite body params');
    }
  }
  error_log('bl api client biz options validator running');
  */
  $crs_handshake = BL_Biz_Info_Monster::crs_handshake([$body_params],$settings);

  update_option('bl_api_client_activity',$commit);
  update_option('bl_api_client_settings',$crs_handshake);
}

function bl_api_client_deactivate() {
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  wp_unschedule_event( $timestamp, 'bl_api_client_cron_hook' );
}
//test pattern for scheduled api call jobs
/*
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
*/
//Controller
if ( !class_exists( 'BL_Scraper' ) ) {
  require_once(__DIR__ . '/vendor/autoload.php');
  include_once 'classes/bl_scraper.php';
}

if ( !class_exists( 'BL_CR_Suite_Client' ) ) {
  include_once 'classes/bl_cr_suite_client.php';
}

if ( !class_exists( 'BL_Biz_Info_Monster' ) ) {
  include_once 'classes/bl_biz_info_monster.php';
}

if ( !class_exists( 'BL_Review_Monster' ) ) {
  include_once 'classes/bl_review_monster.php';
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

if ( !class_exists( 'BL_CR_Suite_Client' ) ) {
  include_once 'classes/bl_cr_suite_client.php';
}

//Build out flow controls here!!!
//Cron job should schedule itself but not run at time of scheduling, and . . .
//only if the lookup info is validate;
//add reiteration of API call for businesses with mutiple entries or
//multiple locales in CR Suite; add alternation for both Facebook and GMB.

//
//add_action( 'bl_api_client_cron_hook', 'bl_api_call' );
/*
if ( ! wp_next_scheduled( 'bl_api_client_cron_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'bl_api_client_cron_hook' );
}
*/

//different lookup-by-URL formats; so far none is accepted:
//https://search.google.com/local/reviews?placeid=ChIJsc2v07GxlVQRRK-jGkZfiw0
//https://local.google.com/place?id=975978498955128644&use=srp&hl=en
//ChIJsc2v07GxlVQRRK-jGkZfiw0
//975978498955128644
//FAKE DATA TABLE: use this to test in absence of CR Suite business options

bl_api_call();

function bl_api_call() {
  $this_option = get_option('bl_api_client_settings');
  $commit = get_option('bl_api_client_activity');
  $auth = get_option('bl_api_client');
  $crs_biz = get_option('crs_business_options');

  //check if CR Suite business options has the required lookup info
  $biz_info = new BL_Biz_Info_Monster($this_option);
  $req_body = BL_CR_Suite_Client::validate_business_data('business');
  //check if BL Client business options are set
  if (!$req_body) {
    $req_body = $biz_info->places[0];
    error_log('cr-suite business options not found; used bl-client lookup');
  } else {
    error_log('found cr-suite business options');
  }
  error_log('cron scheduler is running api call');
  //TEST PATTERNS - uncomment for debugging
  /*
  error_log('test pattern - all reviews data dump');
  foreach($commit['reviews'] as $assoc) {
    error_log('review data');
    foreach($assoc as $prop) {
      if (!is_array($prop)) {
        error_log($prop);
      }

    }
  }
  */
  /*
  error_log('test pattern for agg rating data');
  foreach($commit['aggregate_rating'] as $key => $val) {
    error_log($key);
    error_log($val);
  }
  */
  /*
  error_log('test pattern for valid keys');
  foreach(array_keys($biz_info->valid_keys) as $this_key) {
    error_log($this_key);
  }
  error_log('test pattern for req body');
  foreach(array_keys($req_body) as $this_key) {
    error_log($this_key);
  }
  */
  /*
  error_log('test pattern for valid crs biz');
  foreach(array_keys($crs_biz) as $this_key => $this_value) {
    error_log($this_key);
    error_log($this_value);
  }
*/
  if ( count(array_keys($req_body))===count(array_keys($biz_info->valid_keys)) ) {
    $req_body['country'] = 'USA';
    error_log('found all required business options keys');
    if (isset($auth['api_key']) && isset($auth['api_secret'])) {
      error_log('found api keys');
      //THIS IS THE API CALL - UNCOMMENT TO RUN
      //$result = BL_Scraper::call_local_dir($auth,$req_body,'fetch-reviews','google');
    } else {
      error_log('api keys not found');
    }
  } else {
    error_log('required business options keys not found');
  }

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
