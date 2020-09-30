<?php

class BL_Reviews_Importer {

  function __construct() {

  }

  public static function csv_upload_client() {
    $result = new stdClass();
    $options = get_option('bl_api_client_history');
    $result->raw_path = (!empty($options['upload_path'])) ?
     $options['upload_path'] : '';
    $test_file_name = self::valid_upload_path($result->raw_path);
    $result->structure = array();
    $result->file_path = '';
    $result->valid_file_name = '';
    if ($test_file_name) {
      $result->valid_file_name = $test_file_name;
      $result->file_path = $result->raw_path;
      $valid_file = self::valid_upload_structure($result->raw_path);
    }
    return $result;
  }

  public static function valid_upload_path($path) {
    // will return the filename of a csv uploaded to the media library
    // or null if none is found - filename must be alphanumeric with dashes and underscores only
    $result = null;
    // NOTE:bug fix for wp engine staging - the site URL is https but the install is http
    //$path = str_replace('http://','https://',$path);
    // NOTE:comment out the above line in production or other local dev environments
    $my_domain = (strpos($path,site_url())===0)? true : false;
    $uploads_patt = '/\/wp-content\/uploads\/[0-9]{4}\/[0-9]{2}\//';
    $filename_patt = '/^[a-zA-Z0-9\-\_]*\.csv$/';
    $test = preg_match($uploads_patt,$path,$matches);
    if ($test & $my_domain) {
      $uri_path = str_replace(site_url(),'',$path);
      $filename = preg_replace($uploads_patt,'',$uri_path);
      $fname_test = preg_match($filename_patt,$filename,$matches);
      $result = ($fname_test) ? $matches[0] : $result;
    }
    return $result;
  }

  public static function valid_upload_structure($path) {
    $new_rows = [];
    $err = [];
    $msgs = [];
    $schema = new Schema($path);
    $table = $schema->data_index;
    $index = 0;
    foreach ($table as $row) {}
  }


}
