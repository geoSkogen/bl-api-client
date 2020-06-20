<?php
/*
Plugin Name:  BrightLocal Client Reviews - Alpha Build
Description:  Live Reviews & Ratings for Your Local Business
Version:      2020.06.15
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

if ( !class_exists( 'BL_Client_Tasker' ) ) {
  include_once 'classes/bl_client_tasker.php';
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
  //$commit = ($activity) ? $activity : array(
  // comment-out line above and uncomment line below to active w/ blank data table
   $commit = array(
    'reviews'=>[],
    'facebook_aggregate_rating'=>[],
    'google_aggregate_rating'=>[]
  );
  // when api_call_triage() finds -1,-1 in the database, index_task() turns it
  // into 0,0 - the executable arguments for the first request body in the series
  $commit['log'] = [['-1,-1','plugin activated ' . date('F d Y H:i',time())]];
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
add_filter( 'cron_schedules', 'bl_api_client_add_cron_intervals' );

function bl_api_client_add_cron_intervals( $schedules ) {

    $seconds_int = BL_Client_Tasker::get_schedule_interval();
    $seconds_key = 'bl_api_client_' . strval($seconds_int);
    $seconds_label = 'Every ' . strval($seconds_int) . ' Seconds';
    //error_log('raw secs');
    //error_log(strval($seconds_int));

    $schedules[$seconds_key] = array(
      'interval'=> $seconds_int,
      'display'=> esc_html__( $seconds_label )
    );
    $schedules['five_minutes'] = array(
        'interval' => 300,
        'display'  => esc_html__( 'Every Five Minutes' ),
    );
    return $schedules;
}

function bl_api_client_schedule_executor() {
  $permissions = get_option('bl_api_client_permissions');
  $activity = get_option('bl_api_client_activity');
  if (!isset($activity['log'])) {
    $activity = array();
    $activity['log'] = [];
  }
  //
  if ( isset($permissions['verified']) &&
       $permissions['verified'] &&
       !$permissions['cron_override']) {
    if ( !wp_next_scheduled( 'bl_api_client_cron_hook' ) ) {
      $seconds_int = BL_Client_Tasker::get_schedule_interval();
      $seconds_key = 'bl_api_client_' . strval($seconds_int);
      error_log('got cron hook schedule - outer ring: ' . $seconds_key);
      wp_schedule_event( time(), $seconds_key, 'bl_api_client_cron_hook' );
      $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
      //everything below this until the outer else satement is debugging code only; remove in production
      error_log('timestamp for outer cron hook is : ' . strval($timestamp));
    } else {
      $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
      error_log('timestamp for next outer cron hook is : ' . strval($timestamp));
      $timestamp1 = wp_next_scheduled( 'bl_api_client_call_series' );
      error_log('timestamp for next inner cron hook is : ' . strval($timestamp1));
      error_log('the current time is : ' . strval(time()));
    }
  } else {
    if (!$permissions['verified'] && end($activity['log'])[0]!='-3,-3') {
      error_log('outer cron hook un-scheduled - permissions error');
      $new_log =  array('-3,-3','tasks unscheduled - settings unverified');
      BL_CLient_Tasker::bl_api_client_flush_activity_log($activity,$new_log);
      bl_api_client_deactivate();
    } else if ( isset($permissions['cron_override']) &&
        $permissions['cron_override'] &&
        $permissions['verified'] ) {
      error_log('bl api client running in manual mode');
      if (end($activity['log'])[0]!='-4,-4') {
        error_log('outer cron hook un-scheduled - manual override event');
        $new_log =  array('-4,-4','tasks unscheduled - manual override');
        BL_CLient_Tasker::bl_api_client_flush_activity_log($activity,$new_log);
        bl_api_client_deactivate();
      }
    }
  }
}
//
bl_api_client_schedule_executor();
// assign the api call's 'boot' task to the main cron job -
// it 'seeds' the database and schedules the temporary 'triage' series
add_action( 'bl_api_client_cron_hook',
  array('BL_Client_Tasker','api_call_boot')
);
// assign the locale-to-directory 'triage' task to the temporary cron job series
// it unschedules itself when completed
add_action( 'bl_api_client_call_series',
  array('BL_Client_Tasker','api_call_triage')
);
if (!post_type_exists('crs_review')) {
  add_action( 'init', array( 'BL_Init_Review_Post', 'review_custom_post_type' ) );
  add_action( 'init', array( 'BL_Init_Review_Post', 'crs_review_star_tax' ) );
  add_action( 'init', array( 'BL_Init_Review_Post', 'crs_review_star_numbers' ) );
}

/*

================
BL Review Schema
================
author,
author_avatar, //URL
timestamp,  // Y-m-d
rating, // int
text,
id, //unique hash
listing_directory, //e.g. 'google','facebook'
locale_id // unique integer for indexed array

*/
function spaghetti() {
global $wpdb;
$review = array(
  'author' => 'Mac the Knife',
  'id'=> '1212',
  'author_avatar'=>'Time',
  'timestamp'=>'2020-06-19',
  'rating'=>'4',
  'text'=>'this is fun.',
  'locale_id'=>'1',
  'listing_directory'=>'google'
);

$meta_values_array['review-author'] = $review['author'];
$meta_values_array['review-id'] = $review['id'];
$meta_values_array['author-email'] = '(not set)';
$meta_values_array['author-avatar'] = $review['author_avatar'];
$meta_values_array['listing-directory'] = $review['listing_directory'];
$meta_values_array['locale-id'] = $review['locale_id'];
$meta_values_array['timestamp'] = $review['timestamp'];
$meat_values_array['rating'] = $review['rating'];

$review_number =  $review['rating'];

if ( ( 5 < $review_number) || ( ! is_numeric($review_number) ) ) {
  $review_number = 5;
}
if (1 == $review_number) {
  $review_rating = $review_number . ' Star';
  # code...
} else {
  $review_rating = $review_number . ' Stars';
}

$term_id = 7 - $review_number;

$review_post = array(
  'post_title'    => $review['author'],
  'post_content'  => $review['text'],
  'post_author'   => $review['author'],
  'post_type'     => 'crs_review',
  'post_status'   => 'publish',
  'post_date' => date('Y-m-d', strtotime($review['timestamp'])),
/*
  'tax_input'     => array(
    'rating'        => $review_rating,
  ),
*/
//  'meta_input'   => $meta_values_array
);
$table_name = $wpdb->prefix . "posts";
$test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

 if ( $wpdb->get_var( $test_query ) == $table_name ) {
   $wpdb->insert($table_name, $review_post);
}

$this_page = get_page_by_title( $review['author'], OBJECT ,'crs_review');
$this_page_id = ($this_page->ID) ? $this_page->ID : '' ;
error_log('PAGE ID');
error_log($this_page_id);
$result = $wpdb->get_row(
    "SELECT * FROM wp_posts WHERE post_title = " . $review['author'],
    ARRAY_A
  );
$post_id = $result['ID'];
error_log($post_id);
$meta_table_name = $wpdb->prefix . "postmeta";
$meta_test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $meta_table_name ) );
if ( $wpdb->get_var( $meta_test_query ) == $meta_table_name ) {
   foreach($meta_values_array as $key => $value) {
     $row = array(
       'post_id'=>$post_id,
       'meta_key'=>$key,
       'meta_value'=>$value
     );
     $wpdb->insert($meta_table_name, $row);
   }

}

$term_table_name = $wpdb->prefix . "term_relationships";
$term_test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $term_table_name ) );
if ( $wpdb->get_var( $term_test_query ) == $term_table_name ) {
  $term_row = array('object_id'=>$post_id,'term_taxonomy_id'=>$term_id,'term_order'=>'0');
  $wpdb->insert($term_table_name, $term_row);
}
wp_die();
}

//spaghetti();
/*
$post_made = wp_insert_post( $review_post );
wp_set_object_terms( $post_made, $review_rating, 'rating');
if( is_wp_error($post_made) ) error_log( $result->get_error_message());
//wp_die();
*/

/*
$activity = get_option('bl_api_client_activity');
$google_reviews = [];
$facebook_reviews = [];
foreach($activity['google_reviews'] as $review) {
  $this_review = $review;
  $this_review['listing_directory'] = 'google';
  $google_reviews[] = $this_review;
}
foreach($activity['facebook_reviews'] as $review) {
  $this_review = $review;
  $this_review['listing_directory'] = 'facebook';
  $facebook_reviews[] = $this_review;
}
$all_reviews = array_merge($google_reviews,$facebook_reviews);
$activity['reviews'] = $all_reviews;
update_option('bl_api_client_activity',$activity);
*/
//$activity = get_option('bl_api_client_activity');
//var_dump($activity);
//DEV NOTES
//API CALL FORMAT! work on discovering the correct URL format for GMB pings
//different lookup-by-URL formats; so far none is accepted:
//https://search.google.com/local/reviews?placeid=ChIJsc2v07GxlVQRRK-jGkZfiw0
//https://local.google.com/place?id=975978498955128644&use=srp&hl=en
//Keys are stashed here:
//ChIJsc2v07GxlVQRRK-jGkZfiw0
//975978498955128644
