<?php
/*
Plugin Name:  BrightLocal Client Reviews - Alpha Build
Description:  Live Reviews & Ratings for Your Local Business
Version:      2020.06.19
Author:       City Ranked Media
Author URI:   https://cityranked.com/
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

if ( !class_exists( 'BL_API_Client_Auth' ) ) {
  include_once 'classes/bl_api_client_auth.php';
}

if ( !class_exists( 'BL_Client_Tasker' ) ) {
  include_once 'classes/bl_client_tasker.php';
}

if ( !class_exists( 'BL_Client_Task_Exec' ) ) {
  include_once 'classes/bl_client_task_exec.php';
}

if ( !class_exists( 'BL_Init_Review_Post' ) ) {
  include_once 'classes/bl_init_review_post.php';
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

add_shortcode('bl_client_agg_rating',
  array('BL_Review_Templater','aggregate_rating_shortcode_handler')
);

function bl_api_client_activate() {
  $activity = get_option('bl_api_client_activity');
  $settings = get_option('bl_api_client_settings');
  $commit = ($activity) ? $activity : array(
  // comment-out line above and uncomment line below to active w/ blank data table
  // $commit = array(
    'reviews'=>[],
    'facebook_aggregate_rating'=>[],
    'google_aggregate_rating'=>[]
  );
  // when api_call_triage() finds -1,-1 in the database, index_task() turns it
  // into 0,0 - the executable arguments for the first request body in the series
  $commit['log'] = [[BL_Client_Tasker::$init_key,'plugin activated ' . date('F d Y H:i',time())]];
  // return indexed associative arrays of request params per CR Suite locale
  // if a locale dosn't fully validate, it adds a null to the array
  $body_params = BL_CR_Suite_Client::business_options_rollup();
  // transfer CR Suite Business Options data into BL API Client Settings table
  // - per valid biz entry, if a null value is present in the array, nothing happens
  if ($body_params && !$settings['crs_override']) {
    $crs_handshake = BL_Biz_Info_Monster::crs_handshake($body_params,$settings);
    update_option('bl_api_client_settings',$crs_handshake);
  }
  // register custom post type 'review' if not already registered
  if (!post_type_exists('crs_review')) {
    BL_Init_Review_Post::review_rewrite_flush();
  }
  //instatiate activity table with new log and recycled review data if found;
  update_option('bl_api_client_activity',$commit);
}

function bl_api_client_deactivate() {
  // turn off scheduled cron tasks
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  wp_unschedule_event( $timestamp, 'bl_api_client_cron_hook' );
  error_log('timestamp for outer cron hook was : ' . strval($timestamp));
  $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
  wp_unschedule_event( $timestamp, 'bl_api_client_call_series' );
  error_log('timestamp for inner cron hook was : ' . strval($timestamp));
}

// define our custom time interval requirements
add_filter( 'cron_schedules', array('BL_Client_Task_Exec','bl_api_client_add_cron_intervals') );

BL_Client_Task_Exec::bl_api_client_schedule_executor();

add_action( 'bl_api_client_cron_hook',
  array('BL_Client_Tasker','api_call_boot')
);

add_action( 'bl_api_client_call_series',
  array('BL_Client_Tasker','api_call_triage')
);

if (!post_type_exists('crs_review')) {
  add_action( 'init', array( 'BL_Init_Review_Post', 'review_custom_post_type' ) );
  add_action( 'init', array( 'BL_Init_Review_Post', 'crs_review_star_tax' ) );
  add_action( 'init', array( 'BL_Init_Review_Post', 'crs_review_star_numbers' ) );
}

//DEV NOTES
//API CALL FORMAT! work on discovering the correct URL format for GMB pings
//different lookup-by-URL formats; so far none is accepted:
//https://search.google.com/local/reviews?placeid=ChIJsc2v07GxlVQRRK-jGkZfiw0
//https://local.google.com/place?id=975978498955128644&use=srp&hl=en
//Keys are stashed here:
//ChIJsc2v07GxlVQRRK-jGkZfiw0
//975978498955128644
