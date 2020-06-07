<?php

class BL_API_Client_Options {

  public static function bl_api_client_register_menu_page() {
      add_menu_page(
          'BrightLocal Client Authentication',                        // Page Title
          'BrightLocal Client',                       // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client',                    // menu Slug(page)
          array('BL_API_Client_Options','bl_api_client_auth_page'), // CB Function cb_bl_api_client_options_page()
          'dashicons-store',  // Menu Icon
          20
      );

      add_submenu_page(
          'bl_api_client',                         //parent menu
          'BrightLocal Client Business Info',                // Page Title
          'BL Business Info',               // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client_settings',             // menu Slug(page)
          array('BL_API_Client_Options','bl_api_client_settings_page')// CB Function plugin_options_page()
      );

      add_submenu_page(
          'bl_api_client',                         //parent menu
          'BrightLocal Client Activity',                // Page Title
          'BL Client Activity',               // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client_activity',             // menu Slug(page)
          array('BL_API_Client_Options','bl_api_client_activity_page')// CB Function plugin_options_page()
      );

      add_submenu_page(
          'bl_api_client',                         //parent menu
          'BrightLocal Client Permissions',                // Page Title
          'Authorize Your API',               // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client_permissions',             // menu Slug(page)
          array('BL_API_Client_Options','bl_api_client_permissions_page')// CB Function plugin_options_page()
      );
  }
  //// template 1 - <form> body
  public static function bl_api_client_auth_page() {
    self::bl_api_client_options_page('');
  }

  public static function bl_api_client_settings_page() {
    self::bl_api_client_options_page('_settings');
  }

  public static function bl_api_client_permissions_page() {
    self::bl_api_client_options_page('_permissions');
  }

  public static function bl_api_client_options_page($db_slug) {
    wp_register_style('bl_api_client_styles', plugin_dir_url(__FILE__) . '../style/' . 'bl_api_client_styles' . '.css');
    wp_enqueue_style('bl_api_client_styles');
    $submit_text = 'Save Changes';
    ?>
    <div class='form-wrap'>
      <h3>BrightLocal Client</h3>
      <form method='post' action='options.php'>
      <?php
         settings_fields( 'bl_api_client' . $db_slug );
         do_settings_sections( 'bl_api_client' . $db_slug );
      ?>
        <div class='inivs-div' style="display:none;">
          <input class='invis-input' id='drop_field' name=bl_api_client<?php echo $db_slug; ?>[drop] type='text'/>
        </div>
        <p class='submit'>
          <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e($submit_text); ?>' />
        </p>
      </form>
    </div>
    <?php
  }

  public static function bl_api_client_activity_page() {
    wp_register_style('bl_api_client_styles', plugin_dir_url(__FILE__) . '../style/' . 'bl_api_client_styles' . '.css');
    wp_enqueue_style('bl_api_client_styles');
    $db_slug='_activity';
    $submit_text = 'Call Now';
    ?>
    <div class='form-wrap no_form'>
      <h3>BrightLocal API Client Activity</h3>
      <?php
         settings_fields( 'bl_api_client' . $db_slug );
         do_settings_sections( 'bl_api_client' . $db_slug );
      ?>
      <p class='submit'>
        <button name='call_now' id='call_now' class='button-primary'/><?php _e($submit_text); ?></button>
      </p>
    </div>
    <?php
  }
}

?>
