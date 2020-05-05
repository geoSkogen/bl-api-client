<?php
class BL_CR_Suite_Client {

  public static $business_props = array(
    'name'=>'business-names',
    'address'=>'street-address',
    'locality'=>'city',
    'phone'=>'telephone',
    'zipcode'=>'postcode'
  );
  public static $db_slug = 'crs_business_options';
  public static $business_options = array();

  public static function init_business_options() {
    $options = get_option('crs_business_options');
    self::$business_options = (isset($options)) ? $options : null;
  }

  public static function get_business_option($option_str, $bool) {
    $prefix = ($bool) ? 'business_' : '';
    $result = isset( self::$business_options['business_' . $option_str] ) ?
      sanitize_text_field(self::$business_options[ $prefix . $option_str]) : '';
    return $result;
  }

  public static function validate_business_data() {
    $result_arr = [];
    $result_str = '';
    $keys = array_keys(self::$business_props);
    self::init_business_options();
    foreach ($keys as $prop) {
      $result_str = self::get_business_option($prop, true);
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
