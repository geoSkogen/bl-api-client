<?php
// Review 'Monster' is an instantiated object; it will only do review.
// -used by review page and review snippet shortcode handlers
// -used by plugin settings to print data and API activity tables on WP admin page
class BL_Review_Monster  {
  public $reviews = array('google'=>array(),'facebook'=>array());
  public $ratings = array('google'=>array(),'facebook'=>array());
  public $count = array('google'=>array(),'facebook'=>array());
  public $reviews_all = array();
  public $rating_all = array();
  public $logs = [];
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
              $this->ratings[$dir] = $options_arr[$key];
              break;
          }
        }
      }
    }
    $this->reviews_all = $this->sort_by_date();
    $this->logs = $options_arr['log'];
  }
  //NOTE:Build out weighted average function
  public function get_weighted_aggregate() {
    $result = array('rating'=>0,'count'=>0);
    $count = 0;
    $rating = 0;
    foreach(self::$dirs as $dir) {
      if (  isset($this->ratings[$dir]) && count($this->ratings[$dir]) ) {
        foreach($this->ratings[$dir] as $rating_object) {
          $rating += intval($rating_object['rating']) * intval($rating_object['count']);
          $count += intval($rating_object['count']);
        }
      } else {
        error_log('ratings dir not found:');
        error_log($dir);
      }
    }
    $result['rating'] = $rating/$count;
    $result['count'] = $count;
    return $result;
  }
  // . . . also get agg by locale X biz
  public function get_rating_by($filter,$arg) {
    $result = array('rating'=>0,'count'=>0);
    switch($filter) {
      case 'directory' :
        break;
      case 'locale' :
        break;
      default :

    }
    return $result;
  }

  public function get_reviews_by($filter,$arg) {
    $result = array();
    return $result;
  }
  // NOTE: add filtration functions - BL_Review_Monster should have filtration
  // -by rating, -by locale, -by date

  public function sort_by_date() {
    $result = [];
    $new_schema = [];
    $new_obj = array();
    $date_objs = [];
    foreach(self::$dirs as $dir) {
      foreach($this->reviews[$dir] as $review_obj) {
        $new_obj = $review_obj;
        //add a new property to identify the review's home directory
        $new_obj['dir'] = $dir;
        $new_schema[] = $new_obj;
      }
    }
    $assoc_index = array();
    $index_val = 0;
    foreach ($new_schema as $elm) {
      $val = self::normalize_days($elm['timestamp']);
      // bug fix - increment the value until there's a unique key:
      while(isset($assoc_index[$val])) {
        $val+=0.01;
      }
      $assoc_index[strval($val)] = $index_val;
      $index_val++;
    }
    $result = self::get_new_order($assoc_index,$new_schema);
    return $result;
  }

  public static function get_new_order($assoc_index,$master_arr) {
    $result = [];
    //Key-based R-Sort = awesome computation power!!
    krsort($assoc_index);
    foreach ($assoc_index as $master_index) {
      $result[] = $master_arr[$master_index];
    }
    return $result;
  }

  public static function get_int_arr($str) {
    $int_arr = [];
    $str_arr = explode('-',$str);
    foreach($str_arr as $str) { $int_arr[] = intval($str); }
    return $int_arr;
  }

  public static function normalize_days($str) {
    $result = 0;
    $denoms = [365,30,1];
    $int_arr = self::get_int_arr($str);
    $index = 0;
    foreach($int_arr as $int) {
      $result += ($denoms[$index]*$int);
      $index++;
    }
    return $result;
  }

  public function do_reviews_table() {
    $result = "<table id='bl_api_client_reviews_table'>";
    foreach(self::$dirs as $dir) {
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

  public function do_activity_log_table() {
    $locales = BL_API_Client_Settings::get_field_count();
    $rev_data = array_slice($this->logs,count($this->logs)-((intval($locales)*4)+2));
    $data = array_reverse($rev_data);
    $result = '<h3>Most Recent API Call Logs</h3>';
    $result .= '<table>';
    foreach($data as $row) {
      $indexer = 0;
      $result .= '<tr>';
      foreach($row as $datum) {
        if (!$indexer) {
          switch($datum) {
            case '-1,-1' :
              $locale_index = '&nbsp&beta;&lambda;&nbsp';
              $dir_name = '&nbsp;&alpha;&nbsp';
              break;
            case '-2,-2' :
            case '-3,-3' :
              $locale_index = '&nbsp&beta;&lambda;&nbsp';
              $dir_name = '&nbsp&Omega;&nbsp';
              break;
            case '-4,-4' :
              $locale_index = '&nbsp&beta;&lambda;&nbsp';
              $dir_name = '&nbsp&mu;&nbsp';
              break;
            default :
            $arg_arr = explode(',',$datum);
            $locale_index = 'business locale #' . strval(intval($arg_arr[0])+1);
            $dir_name = self::$dirs[$arg_arr[1]];
          }
          $result .= "<td>$locale_index &mdash; $dir_name<td>";
        } else {
          $result .= "<td>&nbsp;&ndash;&nbsp;&nbsp;&nbsp;$datum<td>";
        }
        $indexer++;
      }
      $result .= '</tr>';
    }
    $result .= '</table><br/>';
    echo $result;
  }

}
