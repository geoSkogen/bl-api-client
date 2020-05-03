<?php

class BL_API_Client_Settings {

  public static $bl_api_client_label_toggle = array(
    "name",
    "address",
    "address_2",
    "city",
    "state",
    "zipcode",
    "phone",
    "gmb",
    "facebook"
  );

  public static $current_field_index = 0;
  public static $bl_api_client_label_toggle_index = 0;

  public static function get_field_count() {
    $result = '';
    $option = get_option('bl_api_client_settings');
    if (isset($option['field_count'])) {
      $result = $option['field_count'];
    } else {
      $result = 1;
    }
    return $result;
  }

  public static function trim_fields() {
    $option = get_option('bl_api_client_settings');
    $stop = (isset($option['field_count'])) ?
      intval($option['field_count']) + 1 : 2;
    $result = array();
    $meta_data = ['drop','field_count','prev_field_count'];
    foreach ($meta_data as $meta_datum) {
      $result[$meta_datum] = $option[$meta_datum];
    }
    for ($i = 1; $i < $stop; $i++) {
      foreach (self::$bl_api_client_label_toggle as $bl_api_client_label) {
        $result[$bl_api_client_label . '_' . strval($i)] =
          (isset($option[$bl_api_client_label . '_' . strval($i)]) &&
            "" != $option[$bl_api_client_label . '_' . strval($i)]) ?
              $option[$bl_api_client_label . '_' . strval($i)] : '';
      }
    }
    update_option('bl_api_client_settings', $result);
    return;
  }

  public static function settings_api_init() {

    add_settings_section(
      'bl_api_client_auth',                         //uniqueID
      'BrightLocal Authorization - Submit Your API Keys',   //Title
      array('BL_API_Client_Settings','bl_api_client_auth_section'),//CallBack Function
      'bl_api_client'                                //page-slug
    );

    add_settings_section(
      'bl_api_client_settings',                         //uniqueID
      'BrightLocal Review Profiles - Enter Your Business Info',   //Title
      array('BL_API_Client_Settings','bl_api_client_settings_section'),//CallBack Function
      'bl_api_client_settings'                                //page-slug
    );

    add_settings_field(
      'api_key',
      'API Key',
      array('BL_API_Client_Settings','bl_api_client_api_key_field'),
      'bl_api_client',
      'bl_api_client_auth'
    );

    add_settings_field(
      'api_secret',
      'API Secret',
      array('BL_API_Client_Settings','bl_api_client_api_secret_field'),
      'bl_api_client',
      'bl_api_client_auth'
    );

    add_settings_field(
      'field_count',
      'Number of Brick & Mortars',
      array('BL_API_Client_Settings','bl_api_client_field_count'),
      'bl_api_client_settings',
      'bl_api_client_settings'
    );

    for ($i = 1; $i < self::get_field_count() + 1; $i++) {
      self::$current_field_index = $i;
      for ($ii = 0; $ii < count(self::$bl_api_client_label_toggle); $ii++) {
        $field_name = self::$bl_api_client_label_toggle[$ii];
        $this_field = $field_name . "_" . strval(self::$current_field_index);
        $this_label = str_replace('_',' ',ucwords($field_name)) .
          "<span class='locale_index'>&nbsp;&nbsp;&nbsp;locale&nbsp;#" .
          strval(self::$current_field_index) . "</span>";
        add_settings_field(
          $this_field,                   //uniqueID - "param_1", etc.
          $this_label,                  //uniqueTitle -
          array('BL_API_Client_Settings','bl_api_client_settings_info_field'),//callback bl_api_client_settings_field();
          'bl_api_client_settings',                    //page-slug
          'bl_api_client_settings'            //section (parent settings-section uniqueID)
        );
      }
    }

    self::$current_field_index = 1;
    register_setting( 'bl_api_client', 'bl_api_client' );
    register_setting( 'bl_api_client_settings', 'bl_api_client_settings' );
    register_setting( 'bl_api_client_activity', 'bl_api_client_activity' );
  }
  //Templates
  ////template 3 - settings section field - dynamically rendered <input/>
  static function bl_api_client_api_key_field() {
    echo self::bl_api_client_dynamic_settings_field('bl_api_client','api_key','(not set)');
  }

  static function bl_api_client_api_secret_field() {
    echo self::bl_api_client_dynamic_settings_field('bl_api_client','api_secret','(not set)');
  }

  static function bl_api_client_dynamic_settings_field($db_slug,$this_field,$fallback_str) {
    $options = ( get_option($db_slug) ) ? get_option($db_slug) : $db_slug;
    //$placeholder = Snail_Tail::try_option_key($options,$this_field,$fallback_str);
    $placeholder = (isset($options[$this_field])) ? $options[$this_field] : $fallback_str;
    $value_tag = ($placeholder === $fallback_str) ? "placeholder" : "value";
    return "<input type='text' class='bl_api_client zeroTest' id='{$this_field}'
      name={$db_slug}[$this_field] {$value_tag}='{$placeholder}'/>";
  }

  static function bl_api_client_settings_info_field() {
    $options = get_option('bl_api_client');
    $divider = (self::$bl_api_client_label_toggle_index < count(self::$bl_api_client_label_toggle)-1) ?
      "" : "<br/><br/><hr/>";
    $field_name = self::$bl_api_client_label_toggle[self::$bl_api_client_label_toggle_index];
    $this_field = $field_name . "_" . strval(self::$current_field_index);
    $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);
    $placeholder =
      (isset($options[$this_field]) && "" != $options[$this_field]) ?
      $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    //reset globals - toggle label and increment pairing series as needed
    self::$bl_api_client_label_toggle_index +=
      (self::$bl_api_client_label_toggle_index < count(self::$bl_api_client_label_toggle)-1 ) ?
      1 : -(count(self::$bl_api_client_label_toggle)-1);
    self::$current_field_index += (self::$bl_api_client_label_toggle_index === 0) ?
      1 : 0;
    //make an <input/> with dynamic attributes
    echo "<input type='text' class='zeroText'
      name=bl_api_client_settings[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
  }

  static function bl_api_client_field_count() {
    $result = '<div>';
    $options = get_option('bl_api_client_settings');
    $this_field = 'field_count';
    $ghost_field = 'prev_field_count';
    $invis_atts = "class='invis-input' id='prev_field_count'";
    $style_rule = "style='display:none'";
    $val = (isset($options[$this_field]) && "" != $options[$this_field]) ?
      $options[$this_field] : strval(1);
    $ghost_val = (isset($options[$ghost_field]) && "" != $options[$ghost_field]) ?
      $options[$ghost_field] : strval(1);
    $result .= "<input name=bl_api_client_settings[{$this_field}] type='number' value='{$val}'/>";
    $result .= "<input name='submit' type='submit' id='update' class='button-primary' value='Update' />";
    $result .= "<input {$style_rule} {$invis_atts} name=bl_api_client_settings[{$ghost_field}] type='number' value='{$val}'/>";
    $result .= "</div><hr/>";
    echo $result;
  }
  ////template 2 - after settings section title

  static function bl_api_client_auth_section() {
    self::bl_api_client_dynamic_settings_section('');
  }

  static function bl_api_client_settings_section() {
    self::bl_api_client_dynamic_settings_section('_settings');
  }

  static function bl_api_client_dynamic_settings_section($db_slug) {
    $options = get_option('bl_api_client' . $db_slug);
    $dropped = (isset($options['drop'])) ? $options['drop'] : '(not set)';
    if ($dropped === "TRUE") {
      error_log('got drop');
      delete_option('bl_api_client' . $db_slug);
    } else {
      //error_log("drop=false");
    }
    if ($db_slug==='_settings') {
      self::trim_fields();
    }
    wp_enqueue_script('bl_api_client-unset-all', plugin_dir_url(__FILE__) . '../lib/bl_api_client-unset-all.js');
    ?>
    <hr/>
    <div style="display:flex;flex-flow:row wrap;justify-content:space-between;">
      <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
      <button id='drop_button' class='button-primary' style='border:1.5px solid red;'>
        <?php _e("Delete All") ?>
      </button>
    </div>
    <?php
  }
}
?>
