<?php
class BL_Client_Tasker {
  // Tasker is ALL STATIC - organizes scheduling and validation of API call data
  public static $init_key = '-1,-1';
  public static $stop_key = '-2,-2';

  function __construct() {
  //
  }
  // Scheduling - api_call_boot(), api_call_traige()
  public static function api_call_boot() {
    $option = get_option('bl_api_client_activity');
    $row = [self::$init_key,'call series scheduler activated ' . date('F d Y H:i',time())];
    $option['log'][] = $row;
    update_option('bl_api_client_activity',$option);

    if ( ! wp_next_scheduled( 'bl_api_client_call_series' ) ) {
        error_log('got cron hook schedule - inner ring ');
        wp_schedule_event( time(), 'five_minutes', 'bl_api_client_call_series' );
    } else {
      $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
      error_log('timestamp for next inner cron hook is : ' . strval($timestamp));
    }
  }

  public static function api_call_triage() {
    $commit = get_option('bl_api_client_activity');
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
        'triage call - ' . date('F d Y H:i',time())
      ];
      error_log('found valid task index: ' . $new_commit_log[0]);
      $commit['log'][] = $new_commit_log;
      update_option('bl_api_client_activity',$commit);
      self::bl_api_get_request_body($loc_index,$dir);
      //
    } else {
      // null task-indexing value commits one 'stop-code' to the log
      $timestamp = wp_next_scheduled( 'bl_api_client_call_series' );
      wp_unschedule_event( $timestamp, 'bl_api_client_call_series' );
      error_log('timestamp for next inner cron hook was : ' . strval($timestamp));
      $new_commit_log = [
        self::$stop_key,
        'task stop - ' . date('F d Y H:i',time())
      ];
      if ($new_commit_log[0]!=$xy_str) {
        //determines whether task index just returned null; stops superfluous commits
        error_log('found stop task index: ' . $xy_str);
        self::bl_api_client_flush_activity_log($commit,$new_commit_log);
      }
    }
  }

  public static function index_task($log) {
    // get index numbers of listing directory and business locale
    // initiate with argument of '-1,-1' in order to return '0,0'
    if ($log===self::$stop_key) { error_log('null call to index task()'); return null; }
    $result = array();
    error_log("\r\nRe-Indexing Tasks\r\n");
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

  public static function bl_api_client_flush_activity_log($activity,$new_log) {
    error_log('activity log was: ' . strval(count($activity['log'])));
    $locales = BL_API_Client_Settings::get_field_count();
    $rev_data = array_slice(
      $activity['log'],
      count($activity['log'])-((intval($locales)*12)+2)
    );
    $activity['log'] = $rev_data;
    $activity['log'][] = $new_log;
    error_log('activity log is: ' . strval(count($activity['log'])));
    update_option('bl_api_client_activity',$activity);
  }
  // Services - bl_api_get_request_body(), bl_api_get_reviews()
  public static function bl_api_get_request_body($index,$directory) {
    // a conversation with local biz options
    $req_body = null;
    $biz_info = new BL_Biz_Info_Monster();
    $dir_slug = ($directory==='google') ? 'gmb' : $directory;
    $link_prop = $dir_slug . '_link_' . strval($index+1);
    // for the first four local listings, if override isn't set,
    // check if CR Suite business options has the required lookup info
    if (!$biz_info->crs_override && $index < 4) {
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

    if (isset($biz_info->table[$link_prop]) && '' != $biz_info->table[$link_prop]) {
      error_log('found ' . $link_prop . '; starting api call body validation');
      // NEXT step in API validation chain
      self::bl_api_get_reviews($directory,$index,$req_body,$biz_info->table);
    } else {
      error_log($link_prop . ' not found; skipping api call body validation');
    }
  }

  public static function bl_api_get_reviews($dir,$index,$req_body,$this_option) {
    // data validation follwed by call to scraper
    error_log('cron scheduler is running api call');
    error_log("\r\n\n\nREQUEST BDOY PARAMS VALIDATION TEST\r\n");
    $valid_req_body = BL_Biz_Info_Monster::valid_api_params($this_option,$index,$req_body,$dir);
    if ($valid_req_body) {
      error_log('bl api client found all required business options keys');
      $start_ymd = BL_Client_Task_Exec::get_interval_start_ymd();
      error_log('last ymd');
      error_log(strval($start_ymd));
      $auth = new BL_API_Client_Auth();

      if (defined('BL_API_KEY') && defined('BL_API_SECRET')) {
        error_log('found api keys');
        //NOTE: THIS IS THE API CALL - UNCOMMENT TO RUN
        // RE: $result -- see comments below;
        $result = BL_Scraper::call_local_dir($req_body,'fetch-reviews',$dir,$start_ymd);
        //$result = BL_Scraper::sim_call_local_dir($req_body,'fetch-reviews',$dir,$start_ymd);

      } else {
        error_log('bl api keys not found');
      }
    } else {
      error_log('bl api client required business options keys not found');
    }

  }

}
