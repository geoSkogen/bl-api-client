<?php

class BL_API_Client_Settings {

  public static $bl_api_client_label_toggle = array(
    "business_name",
    "address",
    "city",
  //  "state",
    "zipcode",
    "country",
    "phone",
    "gmb",
    "facebook"
  );

  public static $current_field_index = 0;
  public static $bl_api_client_label_toggle_index = 0;
  public static $crs_business_options = array();
  public static $crs_keys = array();
  public static $crs_prepends = array();
  public static $crs_locale_count = 0;
  public static $crs_override = 0;
  public static $options = array();

  public static function crs_handshake() {
    if (!count(array_keys(self::$crs_business_options))) {
      self::$crs_business_options = BL_CR_Suite_Client::init_business_options();
      self::$crs_keys = BL_CR_Suite_Client::$client_props;
      self::$crs_prepends = BL_CR_Suite_Client::$prefixes;
      self::$crs_locale_count = (isset(self::$crs_business_options['business_locations']) &&
        intval(self::$crs_business_options['business_locations'])) ?
          intval(self::$crs_business_options['business_locations']) : 0 ;
    }
    if (!count(array_keys(self::$options))) {
      self::$options = get_option('bl_api_client_settings');
      self::$crs_override = ( isset(self::$options['crs_override']) )  ?
        intval(self::$options['crs_override']) : 0;
        error_log('retuned crs override ');
        //error_log(strval(self::$options['crs_override']));

    }
    return self::$crs_business_options;
  }

  public static function get_field_count() {
    self::crs_handshake();
    $crs_result = 1;
    $bl_client_result = (isset(self::$options['field_count'])) ?
      intval(self::$options['field_count']) : 1;
    //self::$options = get_option('bl_api_client_settings');
    if (isset(self::$crs_locale_count) && !self::$crs_override) {
      $crs_result = (self::$crs_locale_count) ?
        self::$crs_locale_count : $crs_result;
      return ($bl_client_result > 4) ? $bl_client_result : $crs_result;
    } else {
      return $bl_client_result;
    }
  }
  //instantiates the correct number of form fields
  public static function trim_fields() {
    $stop = self::get_field_count() + 1;
    $result = array();
    $meta_data = ['drop','field_count','prev_field_count'];
    foreach ($meta_data as $meta_datum) {
      $result[$meta_datum] = self::$options[$meta_datum];
    }
    for ($i = 1; $i < $stop; $i++) {
      foreach (self::$bl_api_client_label_toggle as $bl_api_client_label) {
        $result[$bl_api_client_label . '_' . strval($i)] =
          (isset(self::$options[$bl_api_client_label . '_' . strval($i)]) &&
            "" != self::$options[$bl_api_client_label . '_' . strval($i)]) ?
              self::$options[$bl_api_client_label . '_' . strval($i)] : '';
      }
    }
    update_option('bl_api_client_settings', $result);
    return;
  }

  public static function settings_api_init() {
    //cr suite data import using passive-record technique
    self::crs_handshake();
    $data_status = (self::$crs_business_options && !self::$crs_override) ?
        'Found Your Business Info in CR Suite' : 'Enter Your Business Info';

    add_settings_section(
      'bl_api_client_auth',                         //uniqueID
      'BrightLocal Authorization - Submit Your API Keys',   //Title
      array('BL_API_Client_Settings','bl_api_client_auth_section'),//CallBack Function
      'bl_api_client'                                //page-slug
    );

    add_settings_section(
      'bl_api_client_settings',                         //uniqueID
      'BrightLocal Review Profiles - ' . $data_status,   //Title
      array('BL_API_Client_Settings','bl_api_client_settings_section'),//CallBack Function
      'bl_api_client_settings'                                //page-slug
    );


    add_settings_section(
      'bl_api_client_activity',                         //uniqueID
      'BrightLocal API Activity',   //Title
      array('BL_API_Client_Settings','bl_api_client_activity_section'),//CallBack Function
      'bl_api_client'                                //page-slug
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
      'crs_override',
      'Override CRS business options?',
      array('BL_API_Client_Settings','bl_api_client_crs_override'),
      'bl_api_client_settings',
      'bl_api_client_settings'
    );

    add_settings_field(
      'field_count',
      'Number of Brick & Mortars',
      array('BL_API_Client_Settings','bl_api_client_field_count'),
      'bl_api_client_settings',
      'bl_api_client_settings'
    );

    add_settings_field(
      'call_now',
      'CALL NOW?',
      array('BL_API_Client_Settings','bl_api_client_call_now'),
      'bl_api_client_activity',
      'bl_api_client_activity'
    );
    //dynamic grouped settings fields - reiterates all items on the list $bl_api_client_label_toggle
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
  public static function bl_api_client_api_key_field() {
    echo self::bl_api_client_dynamic_settings_field('bl_api_client','api_key','(not set)');
  }

  public static function bl_api_client_api_secret_field() {
    echo self::bl_api_client_dynamic_settings_field('bl_api_client','api_secret','(not set)');
  }

  public static function bl_api_client_dynamic_settings_field($db_slug,$this_field,$fallback_str) {
    //factor the database hits out into static record instance within the api_init() call?
    $options = ( get_option($db_slug) ) ? get_option($db_slug) : $db_slug;
    $placeholder = (isset($options[$this_field])) ? $options[$this_field] : $fallback_str;
    $value_tag = ($placeholder === $fallback_str) ? "placeholder" : "value";
    return "<input type='text' class='bl_api_client zeroTest' id='{$this_field}'
      name={$db_slug}[$this_field] {$value_tag}='{$placeholder}'/>";
  }

  public static function bl_api_client_settings_info_field() {
    $divider = (self::$bl_api_client_label_toggle_index < count(self::$bl_api_client_label_toggle)-1) ?
      "" : "<br/><br/><hr/>";
    $field_name = self::$bl_api_client_label_toggle[self::$bl_api_client_label_toggle_index];
    $this_field = $field_name . "_" . strval(self::$current_field_index);
    $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);
    $placeholder = '(not set)';
    //determine if CRS business options table will repopulate form fields
    if (!self::$crs_override && self::$crs_business_options && isset(self::$crs_keys[$field_name])
        && isset(self::$crs_prepends[self::$current_field_index-1])) {
      $this_prepend = (self::$crs_keys[$field_name]==='name'||self::$crs_keys[$field_name]==='country') ?
        'business' : self::$crs_prepends[self::$current_field_index-1];
      $this_crs_slug = $this_prepend . '_' . self::$crs_keys[$field_name];
      $placeholder = (isset(self::$crs_business_options[$this_crs_slug]) &&
        ''!=self::$crs_business_options[$this_crs_slug]) ?
        self::$crs_business_options[$this_crs_slug] : $placeholder;
    } else {
    //fallback on local database
      $placeholder = isset(self::$options[$this_field]) ?
        self::$options[$this_field] : $placeholder;
    }
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
  //numeric input
  public static function bl_api_client_field_count() {
    $result = '<div>';
    $this_field = 'field_count';
    $ghost_field = 'prev_field_count';
    $invis_atts = "class='invis-input' id='prev_field_count'";
    $style_rule = "style='display:none'";
    $bl_client_val = (isset(self::$options[$this_field]) && "" != self::$options[$this_field]) ?
      self::$options[$this_field] : strval(1);
    $bl_client_ghost_val = (isset(self::$options[$ghost_field]) && "" != self::$options[$ghost_field]) ?
      self::$options[$ghost_field] : strval(1);

    if (self::$crs_locale_count && !self::$crs_override) {
      $val = (intval($bl_client_val) > 4) ?
        $bl_client_val : strval(self::$crs_locale_count);
      $ghost_val = (intval($bl_client_ghost_val) > 4) ?
        $bl_client_ghost_val : strval(self::$crs_locale_count);
    } else {
      $val = $bl_client_val;
      $ghost_val = $bl_client_ghost_val;
    }

    $result .= "<input name=bl_api_client_settings[{$this_field}] type='number' value='{$val}'/>";
    $result .= "<input name='submit' type='submit' id='update' class='button-primary' value='Update' />";
    $result .= "<input {$style_rule} {$invis_atts} name=bl_api_client_settings[{$ghost_field}] type='number' value='{$val}'/>";
    $result .= "</div><hr/>";
    echo $result;
  }

  public static function bl_api_client_crs_override() {
    $result = '';
    error_log('field query crs override');
    error_log(strval(self::$crs_override));
    $is_selected = ['',''];
    $is_selected[intval(self::$crs_override)] = 'checked';
    $result .= "<div class='flexOuterStart'/>";
    $result .= "<input type='radio' name='bl_api_client_settings[crs_override]' value='0' ";
    $result .= " {$is_selected[0]} />";
    $result .= "<label for='crs_override'>use CRS business options</label>";
    $result .= "</div>";
    $result .= "<input type='radio' name='bl_api_client_settings[crs_override]' value='1' ";
    $result .= " {$is_selected[1]} />";
    $result .= "<label for='crs_override'>override CRS business options</label>";

    echo $result;
  }

  public static function bl_api_client_call_now() {
    $field_name = 'call_now';
    $db_slug = 'activity';
    $style_rule = 'style="display:none;"';

    $result = "<input $style_rule value='1' name='{$db_slug}[{$field_name}]'></input>";

    echo $result;
  }
  ////template 2 - after settings section title
  public static function bl_api_client_auth_section() {
    self::bl_api_client_dynamic_settings_section('');
  }

  public static function bl_api_client_settings_section() {
    self::bl_api_client_dynamic_settings_section('_settings');
  }

  public static function bl_api_client_activity_section() {
    self::bl_api_client_dynamic_settings_section('_activity');
  }

  public static function bl_api_client_dynamic_settings_section($db_slug) {
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
