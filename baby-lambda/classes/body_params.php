<?php

class Body_Params {

  function __construct() {

  }

  public static function get_nap_req_bod($name_key,$place_index,$directory_index,$client_data) {
    if ($name_key && isset($client_data[$name_key]) ) {
      //if client exists:
      $directories = ['google','facebook'];
      $directory = ( isset($directories[$directory_index]) ) ?
        $directories[$directory_index] : $directories[0];
      $body_params = ( isset($client_data[$name_key][$place_index]) ) ?
        $client_data[$name_key][$place_index] : $client_data[$name_key][0];
      $body_params['local-directory'] = $directory;
      $body_params['reviews-limit'] = 'all';

    } else {
      $body_params = null;
    }
    return $body_params;
  }

 public static function post_rev_req_bod() {

 }

}
