<?php
class BL_Client_Tasker {

  function __construct() {

  }

  public static function api_call_triage() {
    $commit = get_option('bl_api_client_activity');
    $this_option = get_option('bl_api_client_settings');
    $commit_log = (isset($commit['log'])) ? end($commit['log']) : ['-1','-1'];

    $loc_index = 0;
    $dir_index = 1;
    $dir = BL_Review_Monster::$dirs[$dir_index];
    self::review_scrape($loc_index,$dir,$this_option);


  }

  public static function boot_task($log,$option) {
    // transforms the log cipher into a scrape task

    $result = array();
    error_log('bl_client field count is ' . strval(BL_API_Client_Settings::get_field_count()) );
    $loc_count = (isset($option['field_count'])) ?
      intval($option['field_count']) : 1;
    $loc_count = BL_API_Client_Settings::get_field_count();
    $counts = [$loc_count-1,count(BL_Review_Monster::$dirs)-1];
    $keys = ['loc','dir'];
    $xy = explode(',',$log[0]);
    $xxyy = [0,0];
    if ($xy[1] >= $counts[1]) {
      $xxyy[0] = ($xy[0] >= $counts[0]) ? 0 : $xy[0]+1;
    } else {
      $xxyy[1] = $xy[1]+1;
    }
    for ($i = 0; $i < count($keys); $i++) {
      $result[$keys[$i]] = $xxyy[$i];
    }
    return $result;
  }

  public static function review_scrape($index,$directory,$this_option) {
    // a conversation with local biz options
    $req_body = null;
    $biz_info = new BL_Biz_Info_Monster($this_option);
    // for the first four local listings, if override isn't set,
    // check if CR Suite business options has the required lookup info
    if (!$this_option['crs_override'] && $index < 4) {
      $biz_key = BL_CR_Suite_Client::$prefixes[$index];
      $req_body = BL_CR_Suite_Client::validate_business_data($biz_key);
      error_log('using cr-suite business options for bl-client');
    }
    // if no CR Suite table exists, or CRS override is in place,
    // check if BL Client business options are set
    if (!$req_body) {
      $req_body = $biz_info->places[$index];
      error_log('cr-suite business options not used; using bl-client lookup');
    }
    self::bl_api_get_reviews($directory,$req_body,$this_option);
  }

  public static function bl_api_get_reviews($dir,$req_body,$this_option) {
    // data validation follwed by call to scraper
    $auth = get_option('bl_api_client');
    error_log('cron scheduler is running api call');
    error_log("\r\n\n\nREQUEST BDOY PARAMS VALIDATION TEST\r\n");
    $valid_req_body = BL_Biz_Info_Monster::valid_api_params($this_option,0,$req_body,$dir);
    if ($valid_req_body) {
      error_log('bl api client found all required business options keys');
      // TEST PATTERN ONLY - for valid request body
      /*
      foreach($valid_req_body as $key=>$val) {
        error_log($key);
        error_log($val);
      }
      error_log("\r\n");
      */
      //NOTE: data validation for API keys here!!!
      define('BL_API_KEY', $auth['api_key']);
      define('BL_API_SECRET', $auth['api_secret']);
      if (defined('BL_API_KEY') && defined('BL_API_SECRET')) {
        error_log('found api keys');
        //NOTE: THIS IS THE API CALL - UNCOMMENT TO RUN
        //
        $result = BL_Scraper::sim_call_local_dir($req_body,'fetch-reviews',$dir);
      } else {
        error_log('bl api keys not found');
      }
    } else {
      error_log('bl api client required business options keys not found');
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
