<?php
// CR Suite 'Client' is ALL STATIC - a collection of public methods for
// getting a snapshot of the CR Suite business options table and using the data
class BL_CR_Suite_Client {
  //exports BL API request body params from CR Suite directly at activation
  //exposes static data and methods for plugin settings admin page
  //allows CR biz info to dynamically populate BL Client biz info
  // ( in the latter case via BL_API_Client_Settings::crs_handshake() )
  public static $business_props = array(
    'name'=>'business-names',
    'address'=>'street-address',
    'locality'=>'city',
    'phone'=>'telephone',
    'zip'=>'postcode',
    'country'=>'country'
  );

  public static $client_props =  array(
      'business_name'=>'name','city'=>'locality','zipcode'=>'zip',
      'address'=>'address','country'=>'country','phone'=>'phone'
  );
  public static $prefixes = ['business','second','third','fourth'];
  public static $db_slug = 'crs_business_options';
  public static $business_options = array();
  public static $places = array();

  public static function init_business_options() {
    $options = get_option('crs_business_options');
    self::$business_options = (isset($options)) ? $options : null;
    return (isset($options)) ? $options : null;
  }
  //gets just the table keys needed per API request body param
  public static function get_business_option($option_str, $prefix) {
    $prepend = ($option_str==='name' || $option_str==='country') ?
      'business' : $prefix;
    $slug = $prepend . '_'. $option_str;
    //error_log('looking up slug:');
    //error_log($slug);
    $result = isset( self::$business_options[ $slug ] ) ?
      sanitize_text_field( self::$business_options[ $slug ] ) : '';
    //error_log('result:');
    //error_log($result);
    return $result;
  }
  //returns indexed array per biz locale - elements are API request params objects
  //same data structure as BL_Biz_Info_Monster->places;
  public static function business_options_rollup() {
    //error_log('crs biz options validatior running');
    self::init_business_options();
    $result = (self::$business_options) ? array() : null;
    $row = array();
    $count = (isset(self::$business_options['business_locations'])) ?
      intval(self::$business_options['business_locations']) : 1;
    //error_log('# of biz locales');
    //error_log(strval($count));
    for ($i = 0; $i < $count; $i++) {
      //error_log('fetching biz data iteration# ' . strval($i) . ' for:');
      //error_log(self::$prefixes[$i]);
      $row = self::validate_business_data(self::$prefixes[$i]);
      if ($row) {
        $result[] = $row;
      }
    }
    self::$places = $result;
    return $result;
  }
  //returns just the biz info props needed by the API request
  public static function validate_business_data($prefix) {
    $result_arr = [];
    $result_str = '';
    $keys = array_keys(self::$business_props);
    self::init_business_options();
    foreach ($keys as $prop) {
      $result_str = self::get_business_option($prop, $prefix);
      if ($result_str) {
        $result_arr[self::$business_props[$prop]] = $result_str;
      }
    }
    if (count(array_keys($result_arr)) ===
      count(array_keys(self::$business_props))) {
      return $result_arr;
    } else {
      return null;
    }
  }
}
?>
