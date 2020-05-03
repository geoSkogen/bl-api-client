<?php

class BL_API_Client_Options {

  static function bl_api_client_register_menu_page() {
      add_menu_page(
          'bl_api_client',                        // Page Title
          'bl_api_client',                       // Menu Title
          'manage_options',             // for Capabilities level of user with:
          'bl_api_client',                    // menu Slug(page)
          array('BL_API_Client_Options','cb_bl_api_client_options_page'), // CB Function cb_bl_api_client_options_page()
          'dashicons-store',  // Menu Icon
          20
      );
  }
  //// template 1 - <form> body
  static function cb_bl_api_client_options_page() {
    wp_register_style('yuckstyle', plugin_dir_url(__FILE__) . '../style/' . 'yuckstyle' . '.css');
    wp_enqueue_style('yuckstyle');
    ?>
    <div class='form-wrap'>
      <h2>bl_api_client - settings</h2>
      <form method='post' action='options.php'>
      <?php
           settings_fields( 'bl_api_client' );
           do_settings_sections( 'bl_api_client' );
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
