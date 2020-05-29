<?php

class BL_Biz_Info_Monster {
  //instantiates active-record for plugin settings table
  public $count;
  public $places;
  public $valid_keys = array(
      'business_name'=>'business-names','city'=>'city','zipcode'=>'postcode',
      'address'=>'street-address','country'=>'country','phone'=>'telephone');
  public static $data_keys = array(
      'business-names'=>'business_name','city'=>'city','postcode'=>'zipcode',
      'street-address'=>'address','country'=>'country','telephone'=>'phone');
  //returns an empty array if no field count
  function __construct($table) {
    $this->count = (isset($table['field_count']) && ''!=$table['field_count']) ?
      $table['field_count'] : 0;
    $this->places = $this->get_places($this->count,$table);
  }
  //returns indexed array per biz locale - elements are API request params objects
  //same data structure as BL_CR_Suite_Client::places
  public function get_places($count,$assoc) {
    //var_dump($assoc);
    $places = [];
    for ($i = 1; $i < $count+1; $i++) {
      $req_body = array();
      $indexer = '_' . strval($i);
      foreach (array_keys($this->valid_keys) as $valid_key) {
        $this_key = $valid_key . $indexer;
        if (isset($assoc[$this_key]) && '' !=$assoc[$this_key]) {
            $req_body[$this->valid_keys[$valid_key]] = $assoc[$this_key];
        }
      }
      if (count(array_keys($req_body))===count(array_keys($this->valid_keys))) {
        $places[] = $req_body;
      }
    }
    return $places;
  }
  //imports data from cr-suite-sourced bl-api-requqest-params into plugin settings table
  public static function crs_handshake($req_body_arr,$settings_table) {
    //takes its own table as argument . . .
    for ($i = 0; $i < count($req_body_arr);$i++) {
      $score = 0;
      if ($req_body_arr[$i]) {
        foreach($req_body_arr[$i] as $key=>$val) {
          $new_key = self::$data_keys[$key] . '_' . strval($i+1);
          //overwrites old key=>val pairs with cr-suite info
          $settings_table[$new_key] = $val;
          $score++;
        }
      } else {
        error_log('crs client handshake returned null value; check your data entry');
      }
      error_log('crs handshake row ' . strval($i) . ' - number of values imported:');
      error_log(strval($score));
    }
    //error_log(var_dump($settings_table));
    return $settings_table;
  }

  public static function valid_api_params($options,$index,$body,$directory) {
    $valid_options = null;
    $score = 0;
    $tracker = '';
    $db_dir = ($directory==='google') ? 'gmb' : $directory;
    $tracker = (isset($options[$db_dir . '_line_' . strval($index+1)])) ?
    $options[$db_dir . '_line_' . strval($index+1)] : $tracker;
    /* triage directory specifics & validate - return error messages to info page */
    error_log('got dir tracker?  ' . $db_dir . '_line_' . strval($index+1) );
    foreach( array_keys(self::$data_keys) as $valid_key) {
      if ($body[$valid_key]) {
        switch($valid_key) {
          case 'postcode'  :
          //
            $valid_options[$valid_key] = $body[$valid_key];
            break;
          case 'country' :
          //
            $valid_options[$valid_key] = $body[$valid_key];
            break;
          case 'telephone' :
          //
            $valid_options[$valid_key] = ($tracker) ? : $body[$valid_key];
            error_log('got dir tracker: ' . strval($tracker) );
            break;
          default :
            $valid_options[$valid_key] = $body[$valid_key];
        }
      }
    }
    // add error handling around array keys of potential null value
    return (count(array_keys($valid_options))===count(array_keys(self::$data_keys))) ?
      $valid_options : null ;
  }

}
