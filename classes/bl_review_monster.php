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
  public static $taxon_assoc = array();
  public static $options_slug = 'bl_api_client_activity';
  public static $props = ['log','reviews','aggregate_rating'];
  public static $dirs = ['google','facebook'];
  public static $review_props = ['author','author_avatar','timestamp','rating','text','listing_directory','locale_id','id'];
  public static $meta_props = ['review-author','author-email','author-avatar','timestamp','review-rating','listing-directory','locale-id','review-id'];
  public static $post_props = array('ID'=>'ID','post_type'=>'crs_review','post_content'=>'text','post_author'=>'author','post_date'=>'timestamp');
  public static $taxon_props = array('table'=>'term_relationships','id_key'=>'object_id','lookup_key'=>'term_taxonomy_id');
  public static $taxa_props = array('table'=>'terms','id_key'=>'term_id','lookup_key'=>'slug');
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
    $result = new stdClass();
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
    $result->rating = $rating/$count;
    $result->count = (!$result->rating) ? 0 : $count;
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
    $assoc_index = array();
    $index_val = 0;
    foreach ($this->reviews as $elm) {
      $val = self::normalize_days($elm['timestamp']);
      // bug fix - increment the value until there's a unique key:
      while(isset($assoc_index[$val])) {
        $val+=1;
      }
      $assoc_index[strval($val)] = $index_val;
      $index_val++;
    }
    $result = self::get_new_order($assoc_index,$this->reviews);
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
    return $result * 10;
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
      $review_taxon = 'Star';
      $review_number = ((5 < $review_number) || (!is_numeric($review_number))) ?
        5 : $review_number;
      $review_rating = $review_number . $review_taxon;
      $review_rating .= ($review_number > 1) ? 's' : '';
      /*
      if (1 == $review_number) {
        $review_rating = $review_number . ' Star';
        # code...
      } else {
        $review_rating = $review_number . ' Stars';
      }
      */
      // term ID becomes the value stored in the relationships table
      $dictionary = self::get_terms_map($review_taxon);

      $term_id = $dictionary[$review_number];

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
      foreach( self::$post_props as $post_key=>$post_val ) {
        if ($response[$post_key]) {
          $post_row[$post_val] = $response[$post_key];
        }
      }
      $result[$response['ID']] = $post_row;
      //error_log(print_r($post_row,true));
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

  public static function get_taxon_codes($posts) {
    global $wpdb;
    $result = [];
    foreach ($posts as $post) {
      if (!empty($post['ID'])) {
        $response = $wpdb->get_row(
          "SELECT * FROM wp_" . self::$taxon_props['table'] . " WHERE " .
          self::$taxon_props['id_key'] . " = " . strval($post['ID']),
          ARRAY_A,
          0
        );
        if ($response) {
          $taxon_code = $response[self::$taxon_props['lookup_key']];
          $result[strval($post['ID'])] = $taxon_code;
          if (empty(
            self::$taxon_assoc[strval($taxon_code)]
          )) {
            self::$taxon_assoc[strval($taxon_code)] =
              array('tally'=>1,'star_value'=>null);
          } else {
            self::$taxon_assoc[strval($taxon_code)]['tally']+=1;
          }
        }
      }
    }
    return $result;
  }

  public static function get_terms_map($str) {
    global $wpdb;
    $result = [];
    for ( $i = 0; $i < 5; $i++) {
      $slug = ($i) ? $str . 's' : $str;
      $response = $wpdb->get_row(
        "SELECT * FROM wp_" . self::$taxa_props['table'] . " WHERE " .
        self::$taxa_props['lookup_key'] . " = '" . strval($i+1) . "-" . $slug . "'",
        ARRAY_A,
        0
      );
      $result[strval($i+1)] = $response[self::$taxa_props['id_key']];
    }

    return $result;
  }

  public static function get_taxa_map($arr) {
    global $wpdb;
    $result = array();
    $i = 0;
    foreach($arr as $taxon_val) {
      $response = $wpdb->get_row(
        "SELECT * FROM wp_" . self::$taxa_props['table'] . " WHERE " .
        self::$taxa_props['id_key'] . " = " . strval($taxon_val),
        ARRAY_A,
        0
      );
      if ($response[self::$taxa_props['lookup_key']]) {
        $result[strval($taxon_val)] = $response[self::$taxa_props['lookup_key']];
      }
    }
    return (count(array_keys($result))) ? $result : '';
  }

  public static function valid_get_crs_reviews($metas,$posts,$taxa,$taxa_map) {
    $reviews = [];
    // iterate all associated meta key-val pairs by post ID
    foreach($metas as $arr_key=>$arr_val) {
      // instantiate a new object for each one
      if (isset($posts[$arr_key])) {
        $this_post = $posts[$arr_key];
        $meta_obj = array();
        $post_obj = array();
        $err = 0;
        foreach ($arr_val as $index_key=>$postmetas) {
        // load up the meta object with all the relevant key-val pairs
          if ( is_array($postmetas) ) {
            $meta_obj[$postmetas['meta_key']]=$postmetas['meta_value'];
          }
        }
        // merge the metas with their associated post attributes
        $post_obj = array_merge($this_post,$meta_obj);
        // for the taxa merge - they'll be needed for legacy reviews
        if (!empty($post_obj['timestamp']) && strtotime($post_obj['timestamp']) > 1 ) {
          // no emtpty timestamp
          if ( (empty($post_obj['review-rating']) ||
               $post_obj['review-rating']==='(not set)'
               ) && $taxa && $taxa_map) {
               // found rating property in the metas table
            if (!empty( $taxa[strval($post_obj['ID'])] )) {
               // found post id on taxa lookup table
              $this_taxon_code = $taxa[strval($post_obj['ID'])];
              if (!empty( $taxa_map[$this_taxon_code] )) {
                 // found taxon on taxa table
                $taxon = $taxa_map[$this_taxon_code];
                $rating = $taxon[0];
                /*
                error_log('added taxon rating for ' . strval($post_obj['ID']));
                error_log($taxon);
                error_log($rating);
                */
                $post_obj['review-rating'] = $rating;
              } else {
                error_log('empty taxon code for post ' . $post_obj['ID']);
                $err++;
              }
            } else {
              error_log('empty taxon ID for post ' . $post_obj['ID']);
              $err++;
            }
          } else {
            /*
            error_log('found review rating property for ' . $post_obj['ID'] );
            error_log($post_obj['review-rating']);
            */
          }
        } else {
          error_log('timestamp unreadable for post ' . $post_obj['ID']);
          $err++;
        }
        if (!$err) {
          $reviews[] = $post_obj;
          //error_log(print_r($post_obj,true));
        }
      }
    }
    return (count($reviews)) ? $reviews : '';
  }

  public static function get_crs_reviews() {
    global $wpdb;
    $reviews = [];
    $posts = self::get_post_type(self::$post_props['post_type']);
    $taxa = self::get_taxon_codes($posts);
    $taxa_map = self::get_taxa_map(array_keys(self::$taxon_assoc));
    if ($posts) {
      $metas = self::get_meta_rows($posts);
      if ($metas) {
        $reviews = self::valid_get_crs_reviews($metas,$posts,$taxa,$taxa_map);
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
    $fallbacks = array(
      'listing_directory'=>'website',
      'author_avatar'=> site_url(). '/wp-content/plugins/bl-api-client/assets/author-avatar.png',
      'locale_id'=>'1',
      'text'=>'(no comments)'
    );
    $crs_reviews = self::get_crs_reviews();
    // translates the object property keynames
    // from crs conventions to bl api conventions
    foreach ($crs_reviews as $crs_review) {
      $bl_review = [];
      foreach (self::$meta_props as $meta_prop) {
        $slug0 = str_replace('review-','',$meta_prop);
        $bl_prop = str_replace('-','_',$slug0);
        $bl_review[$bl_prop] = (!empty($crs_review[$meta_prop])) ?
          $crs_review[$meta_prop] : '(not set)';
      }
      // add this one literally, not in the meta props array - its a post prop
      $bl_review['text'] = $crs_review['text'];
      $bl_review['timestamp'] = substr($bl_review['timestamp'],0,10);
      foreach ($fallbacks as $key=>$val) {
        if (empty($bl_review[$key]) || $bl_review[$key]==='(not set)') {
          $bl_review[$key] = $val;
        }
      }
      //error_log(print_r($bl_review,true));
      $result[] = $bl_review;
    }
    error_log('bl client reviews valid total:');
    error_log(strval(count($result)));
    return $result;
  }
}
