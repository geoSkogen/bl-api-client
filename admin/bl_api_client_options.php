<?php

class BL_API_Client_Options {

  static function bl_api_client_register_menu_page() {
      add_menu_page(
          'BrightLocal Client Authorization',                        // Page Title
          'BL API Client',                       // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client',                    // menu Slug(page)
          array('BL_API_Client_Options','bl_api_client_auth_page'), // CB Function cb_bl_api_client_options_page()
          'dashicons-store',  // Menu Icon
          20
      );

      add_submenu_page(
          'bl_api_client',                         //parent menu
          'BrightLocal Client Business Info',                // Page Title
          'BL API Client Info',               // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client_settings',             // menu Slug(page)
          array('BL_API_Client_Options','bl_api_client_settings_page')// CB Function plugin_options_page()
    );
  }
  //// template 1 - <form> body
  static function bl_api_client_auth_page() {
    self::bl_api_client_options_page('');
  }

  static function bl_api_client_settings_page() {
    self::bl_api_client_options_page('_settings');
  }

  static function bl_api_client_options_page($db_slug) {
    wp_register_style('yuckstyle', plugin_dir_url(__FILE__) . '../style/' . 'yuckstyle' . '.css');
    wp_enqueue_style('yuckstyle');
    ?>
    <div class='form-wrap'>
      <h2>BrightLocal API Client</h2>
      <form method='post' action='options.php'>
      <?php
           settings_fields( 'bl_api_client' . $db_slug );
           do_settings_sections( 'bl_api_client' . $db_slug );
      ?>
            <div class='inivs-div' style="display:none;">
                <input class='invis-input' id='drop_field' name=bl_api_client[drop] type='text'/>
           </div>
           <p class='submit'>
                <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
           </p>
      </form>
    </div>
    <?php
  }

}

?>
