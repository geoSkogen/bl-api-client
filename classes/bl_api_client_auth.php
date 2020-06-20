<?php

class BL_API_Client_Auth {

  public $status = false;
  public static $options_slug = 'bl_api_client';

  function __construct() {
    $auth = get_option(self::$options_slug);
    if (isset($auth['api_key']) && isset($auth['api_secret'])) {
      define('BL_API_KEY', $auth['api_key']);
      define('BL_API_SECRET', $auth['api_secret']);
      $this->status = true;
    } else {
      error_log('API authentication creds requested but not set');
    }
  }
}
