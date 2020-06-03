<?php
/*
Plugin Name:  BrightLocal Client
Description:  Extends CR-Suite with Live Reviews
Version:      2020.05.25
Author:       City Ranked Media
Author URI:
Text Domain:  bl_api_client
*/
defined( 'ABSPATH' ) or die( 'We make the path by walking.');

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

//Controllers
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

if ( !class_exists( 'BL_Review_Templater' ) ) {
  include_once 'classes/bl_review_templater.php';
}

if ( !class_exists( 'BL_Client_Tasker' ) ) {
  include_once 'classes/bl_client_tasker.php';
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

register_activation_hook( __FILE__, 'bl_api_client_activate' );
register_deactivation_hook( __FILE__, 'bl_api_client_deactivate' );

add_action( 'wp_enqueue_scripts',
  array('BL_Review_Templater','local_reviews_style')
);

add_shortcode('bl_client_local_reviews',
  array('BL_Review_Templater','local_reviews_shortcode_handler')
);

function bl_api_client_activate() {
  $activity = get_option('bl_api_client_activity');
  $settings = get_option('bl_api_client_settings');
  $commit = ($activity) ? $activity : array(
  //$commit = array(
    'google_reviews'=>[],'facebook_reviews'=>[],
    'google_aggregate_rating'=>[],'facebook_aggregate_rating'=>[]
  );
  $commit['log'] = [['-1,-1','plugin activated']];
  // return indexed associative arrays of request params per CR Suite locale
  // if a locale dosn't fully validate, it adds a null to the array
  $body_params = BL_CR_Suite_Client::business_options_rollup();
  // transfer CR Suite Business Options data into BL API Client Settings table
  // - per valid biz entry, if null values are present in the array, nothing happens
  if ($body_params && !$settings['crs_override']) {
    $crs_handshake = BL_Biz_Info_Monster::crs_handshake($body_params,$settings);
    update_option('bl_api_client_settings',$crs_handshake);
  }
  //instatiate activity table with new log and recycled review data if found;
  update_option('bl_api_client_activity',$commit);
}

function bl_api_client_deactivate() {
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  wp_unschedule_event( $timestamp, 'bl_api_client_cron_hook' );
  error_log('timestamp for outer cron hook was : ' . strval($timestamp));
  $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
  wp_unschedule_event( $timestamp, 'bl_api_client_call_series' );
  error_log('timestamp for outer cron hook was : ' . strval($timestamp));
}
//TEST PATTERN CODE ONLY - for inspecting run-times of scheduled api call jobs
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
add_filter( 'cron_schedules', 'bl_api_client_add_cron_intervals' );

function bl_api_client_add_cron_intervals( $schedules ) {
    $schedules['fifteen_seconds'] = array(
       'interval' => 15,
       'display'  => esc_html__( 'Every Fifteen Seconds' ),
    );
    $schedules['thirty_seconds'] = array(
       'interval' => 30,
       'display'  => esc_html__( 'Every Fifteen Seconds' ),
    );
    $schedules['one_minute'] = array(
        'interval' => 60,
        'display'  => esc_html__( 'Every Sixty Seconds' ),
    );
    $schedules['three_minutes'] = array(
        'interval' => 180,
        'display'  => esc_html__( 'Every Five Minutes' ),
    );
    $schedules['five_minutes'] = array(
        'interval' => 300,
        'display'  => esc_html__( 'Every Five Minutes' ),
    );
    return $schedules;
}


if ( !wp_next_scheduled( 'bl_api_client_cron_hook' ) ) {
  error_log('got cron hook schedule - outer ring ');
  wp_schedule_event( time(), 'five_minutes', 'bl_api_client_cron_hook' );
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  error_log('timestamp for outer cron hook is : ' . strval($timestamp));
} else {
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  error_log('timestamp for next outer cron hook is : ' . strval($timestamp));
  $timestamp1 = wp_next_scheduled( 'bl_api_client_call_series' );
  error_log('timestamp for next inner cron hook is : ' . strval($timestamp1));
}

add_action( 'bl_api_client_cron_hook',
  'api_call_boot'
);

add_action( 'bl_api_client_call_series',
  'api_call_triage'
);

function api_call_boot() {
  $option = get_option('bl_api_client_activity');
  $row = ['-1,-1','call series scheduler activated'];
  $option['log'][] = $row;
  update_option('bl_api_client_activity',$option);

  if ( ! wp_next_scheduled( 'bl_api_client_call_series' ) ) {
    error_log('got cron hook schedule - inner ring ');
    wp_schedule_event( time(),'fifteen_seconds', 'bl_api_client_call_series' );
    $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
    error_log('timestamp for inner cron hook is : ' . strval($timestamp));
  } else {
    $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
    error_log('timestamp for next inner cron hook is : ' . strval($timestamp));
  }
}

function api_call_triage() {
  $commit = get_option('bl_api_client_activity');
  $this_option = get_option('bl_api_client_settings');
  $commit_log = (isset($commit['log'])) ? end($commit['log']) : [[BL_Client_Tasker::$init_key,'(not set)']];
  $xy_str = (isset($commit_log[0])) ? $commit_log[0] : BL_Client_Tasker::$init_key;
  // 'decode' the last activity log
  $x_y = BL_Client_Tasker::index_task($xy_str);
  // use valid index numbers to schedule directory call y for biz locale x
  if ($x_y) {
    $loc_index = $x_y['loc'];
    $dir_index = $x_y['dir'];
    $dir = BL_Review_Monster::$dirs[$dir_index];
    $new_commit_log = [
      strval($loc_index) . "," . strval($dir_index),
      'triage call'
    ];
    error_log('found valid task index: ' . $new_commit_log[0]);
    $commit['log'][] = $new_commit_log;
    update_option('bl_api_client_activity',$commit);
    //var_dump($commit);
    BL_Client_Tasker::review_scrape($loc_index,$dir,$this_option);
  } else {
    // null task-indexing value commits one 'stop-code' to the log
    $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
    wp_unschedule_event( $timestamp, 'bl_api_client_call_series' );
    error_log('timestamp for next inner cron hook was : ' . strval($timestamp));
    $new_commit_log = [
      '-2,-2',
      'task stop'
    ];
    //if ($new_commit_log[0]!=$xy_str) {
      error_log('found stop task index: ' . $xy_str);
      $commit['log'][] = $new_commit_log;
      update_option('bl_api_client_activity',$commit);
      //var_dump($commit);
    //}
  }
}

//API CALL FORMAT! work on discovering the correct URL format for GMB pings
//different lookup-by-URL formats; so far none is accepted:
//https://search.google.com/local/reviews?placeid=ChIJsc2v07GxlVQRRK-jGkZfiw0
//https://local.google.com/place?id=975978498955128644&use=srp&hl=en
//ChIJsc2v07GxlVQRRK-jGkZfiw0
//975978498955128644

//API CALL
//manual deployment for dev purposes; this should never run on its own;
//BL API Call should only run on scheduled events at traffic down times

//TEST FRAMEWORK - Uncomment this code to run the api call series
// IMPORTANT - Recomment it and save immediately after running
// Change the something in the test data in scraper first, then repeatedly
// refresh the page executing the reviews shortcode handler, and watch it update
//BL_Client_Tasker::api_call_boot();
//BL_Client_Tasker::api_call_triage();
