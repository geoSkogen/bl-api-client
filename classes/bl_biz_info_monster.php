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
      $places[] = $req_body;
    }
    return $places;
  }
  //imports data from cr-suite-sourced bl-api-requqest-params into plugin settings table
  public static function crs_handshake($req_body_arr,$settings_table) {
    for ($i = 0; $i < count($req_body_arr);$i++) {
      $score = 0;
      foreach($req_body_arr[$i] as $key=>$val) {
        $new_key = self::$data_keys[$key] . '_' . strval($i+1);
        $settings_table[$new_key] = $val;
        $score++;
      }
      error_log('crs handshake row ' . strval($i) . ' - number of values imported:');
      error_log(strval($score));
    }
    //error_log(var_dump($settings_table));
    return $settings_table;
  }

}
