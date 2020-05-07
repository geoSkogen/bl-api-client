<?php
class BL_CR_Suite_Client {

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

  public static function init_business_options() {
    $options = get_option('crs_business_options');
    self::$business_options = (isset($options)) ? $options : null;
    return (isset($options)) ? $options : null;
  }

  public static function get_business_option($option_str, $prefix) {
    $slug = $prefix . '_'. $option_str;
    $result = isset( self::$business_options[ $slug ] ) ?
      sanitize_text_field( self::$business_options[ $slug ] ) : '';
    return $result;
  }

  public static function business_options_rollup_report() {
    self::init_business_options();
    $count = (isset(self::$business_options['business_locations'])) ?
      numval($count) : 1;

  }

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
      return false;
    }
  }




}


?>
