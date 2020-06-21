<?php
// Review 'Monster' is an instantiated object; it will only do review.
// -used by review page and review snippet shortcode handlers
// -used by plugin settings to print data and API activity tables on WP admin page
class BL_Review_Monster  {
  public $reviews = [];
  public $ratings = array('google'=>array(),'facebook'=>array());
  public $count = array('google'=>array(),'facebook'=>array());
  public $reviews_all = array();
  public $rating_all = array();
  public $logs = [];
  public static $options_slug = 'bl_api_client_activity';
  public static $props = ['log','reviews','aggregate_rating'];
  public static $dirs = ['google','facebook'];
  public static $review_props = ['author','author_avatar','timestamp','rating','text','listing_directory','locale_id','id'];
  public static $meta_props = ['review-author','author-email','author-avatar','timestamp','review-rating','listing-directory','locale-id','review-id'];
  public static $post_props = array('ID'=>'post_id','post_type'=>'crs_review','post_content'=>'text','post_author'=>'author','post_date'=>'timestamp');
  public static $taxonomy_props = array('table'=>'term_relationships','id'=>'object_id','lookup_key'=>'term_taxonomy_id');
  public static $taxa_props = array('table'=>'terms','id'=>'term_id','lookup_key'=>'name');
  public static $star_img_path = '/wp-content/plugins/bl-api-client/assets/gold-star.png';

  function __construct($table) {
    $options_arr = get_option(self::$options_slug);
    $this->reviews = $table;
    $this->ratings['google'] = $options_arr['google_aggregate_rating'];
    $this->ratings['facebook'] = $options_arr['facebook_aggregate_rating'];
    $this->reviews_all = $this->sort_by_date();
    $this->logs = $options_arr['log'];
  }
  //NOTE:Build out weighted average function
  public function get_weighted_aggregate() {
    $result = array('rating'=>0,'count'=>0);
    $count = 1;
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
    var_dump($this->ratings);
    $result['rating'] = $rating/$count;
    $result['count'] = (!$result['rating']) ? 0 : $count;
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

      foreach($this->reviews as $review_obj) {
        $new_obj = $review_obj;
        //add a new property to identify the review's home directory
        $new_schema[] = $new_obj;
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

    foreach($this->reviews as $review_obj) {
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

  public static function post_reviews($table) {
    global $wpdb;

    foreach($table as $review) {

      $review['text'] = empty($review['text']) ? '(not set)' : $review['text'];

      $meta_values_array['review-author'] = $review['author'];
      $meta_values_array['review-id'] = $review['id'];
      $meta_values_array['author-avatar'] = $review['author_avatar'];
      $meta_values_array['listing-directory'] = $review['listing_directory'];
      $meta_values_array['locale-id'] = $review['locale_id'];
      $meta_values_array['timestamp'] = $review['timestamp'];
      $meta_values_array['review-rating'] = $review['rating'];

      $review_number =  $review['rating'];

      if ( ( 5 < $review_number) || ( ! is_numeric($review_number) ) ) {
        $review_number = 5;
      }
      if (1 == $review_number) {
        $review_rating = $review_number . ' Star';
        # code...
      } else {
        $review_rating = $review_number . ' Stars';
      }
      // term ID becomes the value stored in the relationships table
      $term_id = 7 - $review_number;

      $review_post = array(
        'post_title'    => $review['author'],
        'post_content'  => $review['text'],
        'post_author'   => $review['author'],
        'post_type'     => 'crs_review',
        'post_status'   => 'publish',
        'post_date' => date('Y-m-d', strtotime($review['timestamp'])),
      );
      // POST
      $table_name = $wpdb->prefix . "posts";
      $test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

       if ( $wpdb->get_var( $test_query ) == $table_name ) {
         $wpdb->insert($table_name, $review_post);
      }
      // METAS & TAXA
      $this_page = get_page_by_title( $review['author'], OBJECT ,'crs_review');
      $this_page_id = ($this_page->ID) ? $this_page->ID : '' ;
      error_log('POSTED REVIEW - PAGE ID:');
      error_log($this_page_id);
      // METAS
      $meta_table_name = $wpdb->prefix . "postmeta";
      $meta_test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $meta_table_name ) );
      if ( $wpdb->get_var( $meta_test_query ) == $meta_table_name ) {
         foreach($meta_values_array as $key => $value) {
           $row = array(
             'post_id'=>$this_page_id,
             'meta_key'=>$key,
             'meta_value'=>$value
           );
           $wpdb->insert($meta_table_name, $row);
         }

      }
      // TAXA
      $term_table_name = $wpdb->prefix . "term_relationships";
      $term_test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $term_table_name ) );
      if ( $wpdb->get_var( $term_test_query ) == $term_table_name ) {
        $term_row = array(
          'object_id'=>$this_page_id,
          'term_taxonomy_id'=>$term_id,
          'term_order'=>'0'
        );
        $wpdb->insert($term_table_name, $term_row);
      }
    }
  }

  public static function get_post_type($str) {
    global $wpdb;
    $result = [];
    $post_row = [];
    $i = 0;
    do {
      $post_row = [];
      $response = $wpdb->get_row(
        "SELECT * FROM `wp_posts` WHERE `post_type` = '{$str}'",
        ARRAY_A,
        $i
      );
      /*
      error_log('response is');
      error_log(print_r($response,true));
      */
      foreach( self::$post_props as $post_key=>$post_val ) {
        /*
        error_log('post key is ');
        error_log($post_key);
        error_log('$response[$post_key] is');
        error_log($response[$post_key]);
        */
        if ($response[$post_key]) {
          $post_row[$post_key] = $response[$post_key];
        }
      }
      $result[$response['ID']] = $post_row;
      $i++;
    } while ($response);

    return $result;
  }

  public static function get_meta_rows($posts) {
    global $wpdb;
    $result = [];
    foreach($posts as $post) {
      //error_log(print_r($post,true));
      $table = [];
      if (!empty($post['ID'])) {
        $i = 0;
        do {
          $response = $wpdb->get_row(
            "SELECT * FROM `wp_postmeta` WHERE `post_id` = " . strval($post['ID']),
            ARRAY_A,
            $i
          );
          if (in_array($response['meta_key'],self::$meta_props)) {
            $table[] = $response;
          }
          $i++;
        } while ($response);
      } else {
        error_log('post not found');
        //error_log(print_r($post,true));
      }
      if (count($table)) {
        $result[strval($post['ID'])] = $table;
      }
    }
    return (count(array_keys($result))) ?  $result : '';
  }

  public static function get_taxa_vals() {
    $result = null;
    return $result;
  }

  public static function get_crs_reviews() {
    global $wpdb;
    $reviews = [];
    $posts = self::get_post_type(self::$post_props['post_type']);
    if ($posts) {
      $metas = self::get_meta_rows($posts);
      //$taxa = self::get_taxa_vals($posts);
      error_log('posts');
      error_log(count(array_keys($posts)));
      if ($metas) {
        $score = 0;
        error_log('metas');
        error_log(count(array_keys($metas)));
        //error_log('got metas');
        //error_log(print_r($metas,true));
        foreach($metas as $arr_key=>$arr_val) {

          if (isset($posts[$arr_key])) {
            $this_post = $posts[$arr_key];
            $meta_obj = array();
            $post_obj = array();
            foreach ($arr_val as $index_key=>$postmetas) {
              //error_log($arr_key);
              //error_log(print_r($postmetas,true));
              if ( is_array($postmetas) ) {
                $meta_obj[$postmetas['meta_key']]=$postmetas['meta_value'];
                //error_log(strval($arr_key));
                //error_log($postmetas['meta_value']);
              }
            }
            // NOTE:add subroutine here for the taxa merge - they'll be needed
            // for legacy reviews
            $post_obj = array_merge($this_post,$meta_obj);
            //error_log(print_r($post_obj,true));
            $reviews[] = $post_obj;
          }
        }
      } else {
        error_log('metadata location error for crs_reviews');
      }
    } else {
      error_log('crs_review post type not found in wp_posts table');
    }
    return $reviews;
  }

  public static function get_bl_client_reviews() {
    $result = [];
    $crs_reviews = self::get_crs_reviews();
    foreach ($crs_reviews as $crs_review) {
      $bl_review = [];
      foreach (self::$meta_props as $meta_prop) {
        $slug0 = str_replace('review-','',$meta_prop);
        $bl_prop = str_replace('-','_',$slug0);
        $bl_review[$bl_prop] = (isset($crs_review[$meta_prop])) ?
          $crs_review[$meta_prop] : '(not set)';
      }
      $bl_review['text'] = $crs_review['post_content'];
      $bl_review['timestamp'] = (isset($crs_review['timestamp'])) ?
       $crs_review['timestamp'] : $crs_review['post_date'];
      error_log(print_r($bl_review,true));
      $result[] = $bl_review;
    }
    return $result;
  }

}
