<?php

class BL_Client_Task_Exec {

  public static $interval_days_int = 0;
  public static $interval_start_ymd = '';
  public static $revoke_key = '-3,-3';
  public static $manual_key = '-4,-4';

  function __construct() {

  }

  public static function get_days_interval() {
    if (!self::$interval_days_int) {
      $permissions = get_option('bl_api_client_permissions');
      $interval = isset($permissions['days_interval']) ?
        intval($permissions['days_interval'] ) : 7;
      $start_date = mktime(0, 0, 0, date("m"), date("d")-intval($interval),   date("Y"));
      $start_ymd = date('Y-m-d',$start_date);
      self::$interval_days_int = $interval;
      self::$interval_start_ymd = $start_ymd;
    }
    return self::$interval_days_int;
  }

  public static function get_schedule_interval() {
    self::get_days_interval();
    return self::$interval_days_int * 60 * 60 * 24;
  }

  public static function get_interval_start_ymd() {
    self::get_days_interval();
    return self::$interval_start_ymd;
  }

  public static function bl_api_client_add_cron_intervals( $schedules ) {
      $seconds_int = self::get_schedule_interval();
      $seconds_key = 'bl_api_client_' . strval($seconds_int);
      $seconds_label = 'Every ' . strval($seconds_int) . ' Seconds';

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

  public static function bl_api_client_schedule_executor() {
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
        $seconds_int = self::get_schedule_interval();
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
        $new_log =  array(self::$revoke_key,'tasks unscheduled - settings unverified');
        BL_CLient_Tasker::bl_api_client_flush_activity_log($activity,$new_log);
        bl_api_client_deactivate();
      } else if ( isset($permissions['cron_override']) &&
          $permissions['cron_override'] &&
          $permissions['verified'] ) {
        error_log('bl api client running in manual mode');
        if (end($activity['log'])[0]!='-4,-4') {
          error_log('outer cron hook un-scheduled - manual override event');
          $new_log =  array(self::$manual_key,'tasks unscheduled - manual override');
          BL_CLient_Tasker::bl_api_client_flush_activity_log($activity,$new_log);
          bl_api_client_deactivate();
        }
      }
    }
  }

}
