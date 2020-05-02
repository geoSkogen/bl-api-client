<?php
/*
Plugin Name:  bl-api-client
Description:  bl-api-client
Version:      2020.04.30
Author:       City Ranked Media
Author URI:
Text Domain:  bl_api_client
*/


use BrightLocal\Api;
use BrightLocal\Batches\V4 as BatchApi;

register_activation_hook( __FILE__, 'bl_api_client_activate' );
register_deactivation_hook( __FILE__, 'bl_api_client_deactivate' );

function bl_api_client_activate() {
  $commit = array('log'=>array('placeholder'));
  update_option('bl_api_client',$commit);

}

function bl_api_client_deactivate() {
  $timestamp = wp_next_scheduled( 'bl_api_client_cron_hook' );
  wp_unschedule_event( $timestamp, 'bl_api_client_cron_hook' );
}

$options = get_option('bl_api_client');

if (isset($options)) {
  error_log('found db slug');
  error_log('iterating db entries');
  foreach ($options['log'] as $entry) {
    error_log($entry);
  }
} else {
  error_log('db slug not found');
}

if ( !class_exists( 'BL_Scraper' ) ) {
  require_once(__DIR__ . '/vendor/autoload.php');
  include_once 'classes/bl_scraper.php';
}

add_action( 'bl_api_client_cron_hook', 'bl_api_call' );

if ( ! wp_next_scheduled( 'bl_api_client_cron_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'bl_api_client_cron_hook' );
}

//https://search.google.com/local/reviews?placeid=ChIJsc2v07GxlVQRRK-jGkZfiw0
//https://local.google.com/place?id=975978498955128644&use=srp&hl=en
//ChIJsc2v07GxlVQRRK-jGkZfiw0
//975978498955128644

//bl_api_call();

function bl_api_call() {
  error_log('cron scheduler');
  $options = array(
    'business-names'  => 'Earthworks Excavating Services',
    'city'            => 'Battle Ground',
    'postcode'        => '98604',
    'street-address'  => '1420 SE 13TH ST',
    'country'         => 'USA',
    'telephone'       => '(360) 772-0088'//,
    //'gmb'             => "https://local.google.com/place?id=975978498955128644"
  );
  $commit = get_option('bl_api_client');
  $commit['log'][] = time();
  update_option('bl_api_client',$commit);
  //BL_Scraper::call_local_dir($options,'fetch-reviews');
}
