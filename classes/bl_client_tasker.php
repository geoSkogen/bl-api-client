<?php
class BL_Client_Tasker {

  public static $init_key = '-1,-1';

  function __construct() {

  }

  public static function api_call_boot() {
    $option = get_option('bl_api_client_activity');
    $row = ['-1,-1','call series scheduler activated'];
    $option['log'][] = $row;
    update_option('bl_api_client_activity',$option);
    error_log('boot call updated activity log with ' . $row[0] . ' ' . $row[1]);
    add_action( 'bl_api_client_call_series',
      array('BL_Client_Tasker','api_call_triage' )
    );
    if ( ! wp_next_scheduled( 'bl_api_client_call_series' ) ) {
        wp_schedule_event( time(), 60, 'bl_api_client_call_series' );
    }
  }

  public static function api_call_triage() {
    $commit = get_option('bl_api_client_activity');
    $this_option = get_option('bl_api_client_settings');
    $commit_log = (isset($commit['log'])) ? end($commit['log']) : [[self::$init_key,'(not set)']];
    $xy_str = (isset($commit_log[0])) ? $commit_log[0] : self::$init_key;
    // 'decode' the last activity log
    $x_y = self::index_task($xy_str);
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
      self::review_scrape($loc_index,$dir,$this_option);
    } else {
      // null task-indexing value commits one 'stop-code' to the log
      $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
      wp_unschedule_event( $timestamp, 'bl_api_client_call_series' );
      $new_commit_log = [
        '-2,-2',
        'task stop'
      ];
      if ($new_commit_log[0]!=$xy_str) {
        error_log('found stop task index: ' . $xy_str);
        $commit['log'][] = $new_commit_log;
        update_option('bl_api_client_activity',$commit);
        //var_dump($commit);
      }
    }
  }

  public static function index_task($log) {
    // get index numbers of listing directory and business locale
    // initiate with argument of '-1,-1' in order to return '0,0'
    if ($log==='-2,-2') { return null; }
    $result = array();
    error_log("re-indexing tasks");
    error_log('bl_client field count is ' . strval(BL_API_Client_Settings::get_field_count()) );
    $loc_count = BL_API_Client_Settings::get_field_count();
    $counts = [$loc_count-1,count(BL_Review_Monster::$dirs)-1];
    $keys = ['loc','dir'];
    $xy = explode(',',$log);
    $xxyy = [0,0];
    $stop = 0;
    $boot = 0;
    // manage dynamic place values - [locale-index,directory-index]
    // right hand base is number of directories
    // left hand base is number of locales
    if ($xy[1] >= $counts[1]) {
      $xxyy[0] = ($xy[0] >= $counts[0]) ? 0 : $xy[0]+1;
    } else {
      $xxyy[1] = $xy[1]+1;
      $xxyy[0] = ($xy[0] < $xxyy[0]) ? $xxyy[0] : $xy[0];
    }
    for ($i = 0; $i < count($keys); $i++) {
      $result[$keys[$i]] = $xxyy[$i];
      $stop+= ($xxyy[$i]===0) ? 1 : 0;
      $boot+= ($xy[$i]==='-1') ? 1 : 0;
    }
    error_log('stop  count is ' . strval($stop));
    error_log('and boot count is ' . strval($boot));
    // if the cycle rolls over, return null
    return ($stop===count($keys) && $boot!=count($keys)) ? null : $result;
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
    $dir_slug = ($directory==='google') ? 'gmb' : $directory;
    $link_prop = $dir_slug . '_link_' . strval($index+1);
    if (isset($this_option[$link_prop]) && '' != $this_option[$link_prop]) {
      error_log('found ' . $link_prop . '; starting api call body validation');
      self::bl_api_get_reviews($directory,$index,$req_body,$this_option);
    } else {
      error_log($link_prop . ' not found; skipping api call body validation');
    }

  }

  public static function bl_api_get_reviews($dir,$index,$req_body,$this_option) {
    // data validation follwed by call to scraper
    $auth = get_option('bl_api_client');
    error_log('cron scheduler is running api call');
    error_log("\r\n\n\nREQUEST BDOY PARAMS VALIDATION TEST\r\n");
    $valid_req_body = BL_Biz_Info_Monster::valid_api_params($this_option,$index,$req_body,$dir);
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
