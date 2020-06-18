<?php
class Creds {

  function __construct() {

 }

 public static function write_rest_url($host,$resource) {
   $api_type = '/wp-json/wp/v2/';
   $result = 'https://' . $host . $api_type . $resource;
   return $result;
 }

 public static function write_headers_arr($host,$permission) {
   $result = array();
   if ($permission) {

   } else {

   }
   return $result;
 }
}
