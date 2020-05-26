<?php
class BL_Client_Tasker {

  function __construct() {

  }

  public static function api_call_triage() {
    
  }

  public static function review_scrape($index) {
    $this_option = get_option('bl_api_client_settings');
    $auth = get_option('bl_api_client');
    //check if CR Suite business options has the required lookup info
    $biz_info = new BL_Biz_Info_Monster($this_option);
    // single locale validation - one 'row'
    // this function should accept an arument to determine which row to use.
    $req_body = BL_CR_Suite_Client::validate_business_data('business');
    // if no CR Suite table exists, or CRS override is in place . . .
    // check if BL Client business options are set
    if (!$req_body) {
      $req_body = $biz_info->places[0];
      error_log('cr-suite business options not found; used bl-client lookup');
    } else {
      error_log('found cr-suite business options');
    }
    define('BL_API_KEY', $auth['api_key']);
    define('BL_API_SECRET', $auth['api_secret']);
    self::bl_api_get_reviews('google',$req_body,$auth,$this_option);
    self::bl_api_get_reviews('facebook',$req_body,$auth,$this_option);
  }

  public static function bl_api_get_reviews($dir,$req_body,$auth,$this_option) {
    $commit = get_option('bl_api_client_activity');
    error_log('cron scheduler is running api call');
    error_log("\r\n\n\nREQUEST BDOY PARAMS VALIDATION TEST\r\n");
    $valid_req_body = BL_Biz_Info_Monster::valid_api_params($this_option,0,$req_body,$dir);
    if ($valid_req_body) {
      error_log('found all required business options keys');
      foreach($valid_req_body as $key=>$val) {
        error_log($key);
        error_log($val);
      }
      error_log("\r\n");
      if (defined('BL_API_KEY') && defined('BL_API_SECRET')) {
        error_log('found api keys');
        //NOTE:THIS IS THE API CALL - UNCOMMENT TO RUN
        //
        $result = BL_Scraper::sim_call_local_dir($auth,$req_body,$commit,'fetch-reviews',$dir);
      } else {
        error_log('api keys not found');
      }
    } else {
      error_log('required business options keys not found');
    }

    //NOTE:DATABASE SUBROUTINE - needs dev work:
    // experiment with committing review data to 'activity' table as a callback to the API call;
    // currently doing database commit within the API call static function scope;
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

}

 ?>
