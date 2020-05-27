<?php
class BL_Client_Tasker {

  function __construct() {

  }

  public static function api_call_triage() {
      $commit = get_option('bl_api_client_activity');
      $this_option = get_option('bl_api_client_settings');
      $commit_log = (isset($commit['log'])) ? end($commit['log']) : ['',''];

      $biz_index = 0;
      $dir_index = 1;
      $dir = BL_Review_Monster::$dirs[$dir_index];
      self::review_scrape($biz_index,$dir,$this_option);
  }

  public static function boot_task($log,$option) {
    $loc_count = (isset($option['field_count'])) ?
      intval($option['field_count']) : 1;
    $biz_index = 0;
    $dir_index = 0;
    if ($log[0]!='placeholder') {
      $biz_index_was = intval(explode(',',$log[0])[0]);
      $biz_index_was = intval(explode(',',$log[0])[1]);
      $biz_index = ($biz_index_was > $loc_count-1) ? 0 : $biz_index_was+1;
      $dir_index = ($dir_index_was > count(BL_Review_Monster::$dirs)-1) ?
        0 : $dir_index_was+1;
    }
    

  }

  public static function review_scrape($index,$directory,$this_option) {
    $req_body = null;
    $biz_info = new BL_Biz_Info_Monster($this_option);
    //check if CR Suite business options has the required lookup info
    if (!$this_option['crs_override'] && $index < 4) {
      $biz_key = BL_CR_Suite_Client::$prefixes[$index];
      $req_body = BL_CR_Suite_Client::validate_business_data($biz_key);
      error_log('using cr-suite business options for bl-client');
    }
    // if no CR Suite table exists, or CRS override is in place . . .
    // check if BL Client business options are set
    if (!$req_body) {
      $req_body = $biz_info->places[$index];
      error_log('cr-suite business options not used; using bl-client lookup');
    }
    self::bl_api_get_reviews($directory,$req_body,$this_option);
  }

  public static function bl_api_get_reviews($dir,$req_body,$this_option) {
    $auth = get_option('bl_api_client');
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
      define('BL_API_KEY', $auth['api_key']);
      define('BL_API_SECRET', $auth['api_secret']);
      if (defined('BL_API_KEY') && defined('BL_API_SECRET')) {
        error_log('found api keys');
        //NOTE:THIS IS THE API CALL - UNCOMMENT TO RUN
        //
        $result = BL_Scraper::sim_call_local_dir($req_body,'fetch-reviews',$dir);
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
