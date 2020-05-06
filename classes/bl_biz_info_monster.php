<?php

class BL_Biz_Info_Monster {

  public $count;
  public $places;
  public $valid_keys = array(
      'business_name'=>'business-names','city'=>'city','zipcode'=>'postcode',
      'address'=>'street-address','country'=>'country','phone'=>'telephone');
  public static $data_keys = array(
      'business-names'=>'business_name','city'=>'city','postcode'=>'zipcode',
      'street-address'=>'address','country'=>'country','telephone'=>'phone');
  function __construct($table) {
    $this->count = $table['field_count'];
    $this->places = $this->get_places($this->count,$table);
  }

  public function get_places($count,$assoc) {
    var_dump($assoc);
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

  public static function crs_handshake($req_body_arr,$settings_table) {
    $score = 0;
    for ($i = 0; $i < count($req_body_arr);$i++) {
      foreach($req_body_arr[$i] as $key=>$val) {
        $new_key = self::$data_keys[$key] . '_' . strval($i+1);
        $settings_table[$new_key] = $val;
        $score++;
      }
    }
    error_log('crs handshake score - number of values imported:');
    error_log(strval($score));
    return $settings_table;
  }

}
