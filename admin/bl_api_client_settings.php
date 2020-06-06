<?php

class BL_API_Client_Settings {

  public static $bl_api_client_label_toggle = array(
    "business_name",
    "address",
    "city",
    "zipcode",
    "country",
    "phone",
    'gmb_line',
    "gmb_link",
    "facebook_link"
  );

  public static $current_field_index = 0;
  public static $bl_api_client_label_toggle_index = 0;
  public static $crs_business_options = array();
  public static $crs_keys = array();
  public static $crs_prepends = array();
  public static $crs_locale_count = 0;
  public static $crs_override = null;
  public static $options = array();
  public static $caveat_text = 'Using a tracking line for your GMB phone number? Enter it separately here.';

  public static function crs_handshake() {
    //error_log("\r\n\r\nCRS HANDSHAKE\r\n");
    if (!count(array_keys(self::$crs_business_options))) {
      self::$crs_business_options = BL_CR_Suite_Client::init_business_options();
      self::$crs_keys = BL_CR_Suite_Client::$client_props;
      self::$crs_prepends = BL_CR_Suite_Client::$prefixes;
      self::$crs_locale_count = (isset(self::$crs_business_options['business_locations']) &&
        intval(self::$crs_business_options['business_locations'])) ?
          intval(self::$crs_business_options['business_locations']) : 0 ;
      /*
      error_log('reset cr client options passive record - retuned locale count');
      error_log(strval(self::$crs_locale_count));
      */
    } else {
      //error_log('found cr client passive record w/ options intact');
    }
    if (!count(array_keys(self::$options))||!isset(self::$crs_override)) {
      self::$options = get_option('bl_api_client_settings');
      self::$crs_override = ( isset(self::$options['crs_override']) )  ?
        intval(self::$options['crs_override']) : 0;
        /*
        error_log('reset bl client options passive record - retuned crs override ');
        error_log(strval(get_option('bl_api_client_settings')['crs_override']));
        */
    } else {
      //error_log('found bl client passive record w/ options intact');
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
    //NOTE: whitelist each table metadata key here or it will get dropped!
    //metas instantly are added back to the result array . . .
    $meta_data = ['drop','field_count','prev_field_count','crs_override'];
    foreach ($meta_data as $meta_datum) {
      $result[$meta_datum] = self::$options[$meta_datum];
    }
    //any repopulating value fields will have indices less than new 'field count'
    for ($i = 1; $i < $stop; $i++) {
      foreach (self::$bl_api_client_label_toggle as $bl_api_client_label) {
        $result[$bl_api_client_label . '_' . strval($i)] =
          (isset(self::$options[$bl_api_client_label . '_' . strval($i)]) &&
            "" != self::$options[$bl_api_client_label . '_' . strval($i)]) ?
              self::$options[$bl_api_client_label . '_' . strval($i)] : '';
      }
    }
    //re-commit the new data array before rendering the form fields, so they render with the upated data
    //in case the passive record falls back on the database
    update_option('bl_api_client_settings', $result);
    return;
  }

  public static function settings_api_init() {
    //CR Suite data import using passive-record technique:
    //if values are found in this object's static properties, no database hit is required
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
      'bl_api_client_activity'                                //page-slug
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
    //dynamic grouped settings fields - reiterates all items on the list: $bl_api_client_label_toggle
    for ($i = 1; $i < self::get_field_count() + 1; $i++) {
      self::$current_field_index = $i;
      for ($ii = 0; $ii < count(self::$bl_api_client_label_toggle); $ii++) {
        $field_name = self::$bl_api_client_label_toggle[$ii];
        $this_field = $field_name . "_" . strval(self::$current_field_index);
        $label_name = ucwords(str_replace('_',' ',$field_name));
        $this_label = str_replace('Gmb','GMB ',$label_name) .
          "<span class='locale_index'>&nbsp;&nbsp;&nbsp;locale&nbsp;#" .
          strval(self::$current_field_index) . "</span>";

        if ($field_name === 'gmb_line') {
          $tracker_status = (!self::$options[$this_field]) ?
            "<span class='alert_me'>&nbsp;» " . self::$caveat_text . " </span>" :
            '';
          $this_label .= $tracker_status;
        }

        add_settings_field(
          $this_field,                  //uniqueID - "param_1", etc.
          $this_label,                  //uniqueTitle -
          array('BL_API_Client_Settings','bl_api_client_settings_info_field'),//callback
          'bl_api_client_settings',     //page-slug
          'bl_api_client_settings'      //section (parent settings-section uniqueID)
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
    /*
    error_log('field query crs override');
    error_log(strval(self::$crs_override));
    */
    $is_selected = ['',''];
    $is_selected[intval(self::$crs_override)] = 'checked';
    $result .= "<div class='flexOuterStart'/>";
    $result .= "<input type='radio' name='bl_api_client_settings[crs_override]' value='0' ";
    $result .= " {$is_selected[0]} />";
    $result .= "<label for='crs_override'>use CRS business options</label>";
    $result .= "<input type='radio' name='bl_api_client_settings[crs_override]' value='1' ";
    $result .= " {$is_selected[1]} />";
    $result .= "<label for='crs_override'>override CRS business options</label>";
    $result .= "</div>";

    echo $result;
  }

  public static function valid_call_now($str) {
    $arr = explode(',',$str);
    $score = 0;
    for($i = 0; $i < count($arr); $i++) {
      switch(strval($i)) {
        case '0' :
          if (intval($arr[$i]) <= self::get_field_count()-1) {
            $score+=1;
          }
          break;
        case '1' :
          $score+= (intval($arr[$i]) > -1 && intval($arr[$i] <= 1)) ? 1 : 0;
          break;
      }
    }
    return ($score===2) ? true : false;
  }

  public static function bl_api_client_call_now() {
    $field_name = 'call_now';
    $db_slug = 'activity';
    $value = (
      isset(self::$options['call_now']) &&
      self::valid_call_now(self::$options['call_now'])
      ) ? self::$options['call_now'] : '-1,-1';
    $style_rule = 'style="display:none;"';

    $result = "<input $style_rule value='1' name='bl_api_client_{$db_slug}[{$field_name}]'/>";

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

  public static function sticky_field($dir,$prop,$db_slug,$json_str) {
    $style_rule = 'style="display:none;"';
    $result = '<input ' . $style_rule . ' value=' . $json_str .
      ' name=' . $db_slug . '[' . $dir . '_' . $prop . ']/>';
    return $result;
  }

  public static function bl_api_client_dynamic_settings_section($db_slug) {
    $options = get_option('bl_api_client' . $db_slug);
    if ($db_slug!='_activity') {
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
    } else {
      if (isset($options['call_now']) && $options['call_now']===1) {
        $options['call_now'] = '0';
        //update_option('bl_api_client_activity',$options);
      }
      $review_monster = new BL_Review_Monster($options);
      $review_table = "<input name='submit' type='submit' id='submit' class='button-primary' value='Call Now' />";
      $review_table .= $review_monster->do_activity_log_table();
      $review_table .= $review_monster->do_reviews_table();
      echo $review_table;
    }
  }

}
?>
