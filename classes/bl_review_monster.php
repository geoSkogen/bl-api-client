<?php
class BL_Review_Monster  {
  public $reviews = array('google'=>array(),'facebook'=>array());
  public $rating = array('google'=>array(),'facebook'=>array());
  public $count = array('google'=>array(),'facebook'=>array());
  public static $props = ['log','reviews','aggregate_rating'];
  public static $dirs = ['google','facebook'];
  public static $review_props = ['author_avatar','author','timestamp','rating','text','id'];
  public static $star_img_path = '/wp-content/plugins/bl-api-client/assets/gold-star.png';

  function __construct($options_arr) {
    foreach (self::$dirs as $dir) {

      foreach (self::$props as $prop) {
        $key = $dir . '_';
        $key .= $prop;
        if (isset($options_arr[$key])) {
          switch ($prop) {
            case 'reviews' :
              $this->reviews[$dir] = $options_arr[$key];
              break;
            case 'aggregate_rating' :
              $this->rating[$dir] = $options_arr[$key]['rating'];
              $this->count[$dir] = $options_arr[$key]['count'];
              break;
          }
        }
      }
    }
  }

  public function do_reviews_table() {
    $dirs = ['google','facebook'];
    $result = "<table id='bl_api_client_reviews_table'>";
    foreach($dirs as $dir) {
      foreach($this->reviews[$dir] as $review_obj) {
        $result .= "<tr>";
        foreach(self::$review_props as $review_prop) {
          $minwidth = '';
          $this_class = "class='bl_api_client_review_{$review_prop}''";
          if (isset($review_obj[$review_prop])) {
            switch($review_prop) {
              case 'author_avatar' :
                $inner_html = "<img {$this_class} src={$review_obj[$review_prop]} &nbsp;/>";
                break;
              case 'author' :
              case 'timestamp' :
                $inner_html = "<span {$this_class}>{$review_obj[$review_prop]}</span>";
                break;
              case 'rating' :
                $coeff = strval(floatval($review_obj[$review_prop]) * 20);
                $style_rule = "style='height:25px;width: {$coeff}%;background: url( " .
                  site_url() . self::$star_img_path . " ) repeat-x 0 0;background-position: 0 -25px;'";
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
    }
    $result .= "</table>";
    return $result;
  }

}
