<?php
/*
Plugin Name:  BrightLocal Client Reviews
Description:  Live Reviews & Ratings for Your Local Business
Version:      2020.12.08
Author:       City Ranked Media
Author URI:   https://cityranked.com/
Text Domain:  bl_api_client
*/
defined( 'ABSPATH' ) or die( 'We make the path by walking.');

use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

//Controllers
if ( !class_exists( 'Schema' ) ) {
  include_once 'classes/schema.php';
}

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

if ( !class_exists( 'BL_Init_Review_Post' ) ) {
  include_once 'classes/bl_init_review_post.php';
}

if ( !class_exists( 'BL_Client_Task_Exec' ) ) {
  include_once 'classes/bl_client_task_exec.php';
}

if ( !class_exists( 'BL_Reviews_Importer' ) ) {
  include_once 'classes/bl_reviews_importer.php';
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

register_activation_hook( __FILE__, array('BL_Client_Task_Exec','bl_api_client_activate') );
register_deactivation_hook( __FILE__, array('BL_Client_Task_Exec','bl_api_client_deactivate') );

add_action( 'wp_enqueue_scripts',
  array('BL_Review_Templater','local_reviews_style')
);

add_shortcode('bl_client_local_reviews',
  array('BL_Review_Templater','local_reviews_shortcode_handler')
);

add_shortcode('bl_client_agg_rating',
  array('BL_Review_Templater','aggregate_rating_shortcode_handler')
);

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
