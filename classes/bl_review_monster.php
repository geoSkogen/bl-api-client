<?php

class BL_Review_Monster  {
  public $reviews;
  public static $props = ['log','reviews','aggregate_rating'];
  public static $dirs = ['google','facebook','yelp'];
  public static $review_props = ['author_avatar','author','timestamp','rating','text','id'];
  function __construct($options_arr) {
    foreach(self::$props as $key) {
      if ($options_arr[$key]) {
        switch ($key) {
          case 'reviews' :
            $this->reviews = $options_arr[$key];
            break;
          case 'aggregate_rating' :
            $this->rating = $options_arr[$key]['rating'];
            $this->count = $options_arr[$key]['count'];
            break;
        }
      }
    }
  }

  public function do_reviews_table() {
    $result = "<table id='bl_api_client_reviews_table'>";
    foreach($this->reviews as $review_obj) {
      $result .= "<tr>";
      foreach(self::$review_props as $review_prop) {
        $minwidth = '';
        if ($review_obj[$review_prop]) {
          $this_class = "class='bl_api_client_review_{$review_prop}''";
          switch($review_prop) {
            case 'author_avatar' :
              $inner_html = "<img {$this_class} src={$review_obj[$review_prop]} &nbsp;/>";
              break;
            case 'author' :
            case 'timestamp' :
              $inner_html = "<span {$this_class}>{$review_obj[$review_prop]}</span>";
              break;
            case 'rating' :
              $star_img_url = 'http://localhost/joseph-scoggins/wp-content/plugins/bl-api-client/assets/gold-star.png';
              $coeff = strval(floatval($review_obj[$review_prop]) * 20);
              $style_rule = "style='height:25px;width: {$coeff}%;background: url( {$star_img_url}  ) repeat-x 0 0;background-position: 0 -25px;'";
              //$inner_html = "<span {$this_class}>{$review_obj[$review_prop]}</span>";
              $inner_html = "<div $style_rule></div>";
              $minwidth = "style='width:120px;'";
              break;
            case 'text' :
              $inner_html = "<p {$this_class}>{$review_obj[$review_prop]}</p>";
              break;
            default:
              $inner_html = null;
          }
        } else {
          $inner_html = "<span {$this_class}>(not set)</span>";
        }
        $result .= "<td {$minwidth} >{$inner_html}</td>";
      }
      $result .= "</tr>";
    }
    $result .= "</table>";
    return $result;
  }


}
