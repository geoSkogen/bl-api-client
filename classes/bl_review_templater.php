<?php
// ALL STATIC - for UX - strictly stylesheets and shortcode handlers
// public methods instantiate Review Monsters
class BL_Review_Templater {

  public static $props = ['log','reviews','aggregate_rating'];
  public static $dirs = ['google','facebook'];
  public static $review_props = ['author_avatar','author','timestamp','rating','text','id'];
  public static $star_img_path = '/wp-content/plugins/bl-api-client/assets/gold-star.png';

  public static function local_reviews_style() {
    $page_slug_whitelist = ['review','testimonial'];
    $is_reviews_page = false;
    foreach ($page_slug_whitelist as $slug) {
      if (strpos($_SERVER['REQUEST_URI'],$slug)) {
        $is_reviews_page = true ;
        break;
      }
    }
    if ($is_reviews_page) {
      wp_register_style('bl_local_reviews_styles', plugin_dir_url(__FILE__) . '../style/' . 'bl_local_reviews_styles' . '.css');
      wp_enqueue_style('bl_local_reviews_styles');
      error_log("got local reviews stylesheet request");
    }
  }
  //Integrates all directories, sorts by date
  public static function local_reviews_shortcode_handler() {
    //hit your local options table for recent activity
    $options_arr = get_option('bl_api_client_activity');
    //var_dump($options_arr);
    //instantiate the "review monster" active record
    $monster = new BL_Review_Monster($options_arr);
    //isntantiate the review shrine return string value
    $result = "<div id='my_review_shrine' class='bl_client_reviews_widget'>";
    //iterate the monster's review record
    // NOTE: add filtration - BL_Review_Monster should have filtration
    // -by rating, -by locale, -by date
    foreach ($monster->reviews_all as $review_obj) {
      $result .= "<div class='bl_client_review {$review_obj['dir']}'>";
      //iterate each review object property
      foreach (self::$review_props as $review_prop) {
        //inject its keyname into the classname
        $this_class = "class='bl_client_review_prop {$review_prop}'";
        if (isset($review_obj[$review_prop])) {
          //load $inner_html with corresponding element
          switch($review_prop) {
            case 'author_avatar' :
              $inner_html = "<img {$this_class} src={$review_obj[$review_prop]} />";
              break;
            case 'author' :
            case 'timestamp' :
              $inner_html = "<p {$this_class}>{$review_obj[$review_prop]}</p>";
              break;
            case 'rating' :
              //determine the width coefficient from the aggregate rating
              $coeff = strval(floatval($review_obj[$review_prop]) * 20);
              //poach the footer review snippet inline style rule
              $style_rule = "style='height:25px;width: {$coeff}%;background: url( " .
                site_url() . self::$star_img_path . " ) repeat-x 0 0;background-position: 0 -25px;'";
              //set the outer div to 5-star width
              $minwidth = "style='width:120px;'";
              //nest the elements; inject their inline styles
              $inner_inner_html = "<div class='bl_client gold_stars' $style_rule></div>";
              $inner_html = "<div class='bl_client gold_stars_wrapper' {$minwidth}>{$inner_inner_html}</div>";
              break;
            case 'text' :
              $inner_html = "<p {$this_class}>{$review_obj[$review_prop]}</p>";
              break;
            default:
              $inner_html = '';
          }
        } else {
          $inner_html = "<span {$this_class}>(not set)</span>";
        }
        //append the current review attribute
        $result .= $inner_html;
      }
      //close the review item div
      $result .= "</div>";
    }

    //close the review shrine div
    $result .= "</div>";
    return $result;
  }
  //DEPRECATED SHORTCODE HANDLER - SEPARATES REVIEWS BY DIRECTORY
  /*
  public static function local_reviews_shortcode_handler() {
    //preconfigure the
    $dirs = ['google','facebook'];
    //hit your local options table for recent activity
    $options_arr = get_option('bl_api_client_activity');
    if (count(array_keys($options_arr))) {
      foreach (array_keys($options_arr) as $arr_key) {
          error_log($arr_key);
      }
    } else {
      error_log('bl_api_client_activity array is empty');
    }
    //instantiate the "review monster" active record
    $monster = new BL_Review_Monster($options_arr);
    //isntantiate the review shrine return string value
    $result = "<div id='my_review_shrine' class='bl_client_reviews_widget'>";
    //iterate the monster's review record
    foreach (self::$dirs as $dir) {
      foreach ($monster->reviews[$dir] as $review_obj) {
        $result .= "<div class='bl_client_review {$dir}'>";
        //iterate each review object property
        foreach (self::$review_props as $review_prop) {
          //inject its keyname into the classname
          $this_class = "class='bl_client_review_prop {$review_prop}'";
          if (isset($review_obj[$review_prop])) {
            //load $inner_html with corresponding element
            switch($review_prop) {
              case 'author_avatar' :
                $inner_html = "<img {$this_class} src={$review_obj[$review_prop]} />";
                break;
              case 'author' :
              case 'timestamp' :
                $inner_html = "<p {$this_class}>{$review_obj[$review_prop]}</p>";
                break;
              case 'rating' :
                //determine the width coefficient from the aggregate rating
                $coeff = strval(floatval($review_obj[$review_prop]) * 20);
                //poach the footer review snippet inline style rule
                $style_rule = "style='height:25px;width: {$coeff}%;background: url( " .
                  site_url() . self::$star_img_path . " ) repeat-x 0 0;background-position: 0 -25px;'";
                //set the outer div to 5-star width
                $minwidth = "style='width:120px;'";
                //nest the elements; inject their inline styles
                $inner_inner_html = "<div class='bl_client gold_stars' $style_rule></div>";
                $inner_html = "<div class='bl_client gold_stars_wrapper' {$minwidth}>{$inner_inner_html}</div>";
                break;
              case 'text' :
                $inner_html = "<p {$this_class}>{$review_obj[$review_prop]}</p>";
                break;
              default:
                $inner_html = '';
            }
          } else {
            $inner_html = "<span {$this_class}>(not set)</span>";
          }
          //append the current review attribute
          $result .= $inner_html;
        }
        //close the review item div
        $result .= "</div>";
      }
    }
    //close the review shrine div
    $result .= "</div>";
    return $result;
  }
  */
}
?>
