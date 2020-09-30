<?php
// ALL STATIC - for UX - strictly stylesheets and shortcode handlers
// shortcodes are public methods that instantiate Review Monsters for live data
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
  // NOTE: update this to current CR-snippetX!
  public static function aggregate_rating_shortcode_handler($atts) {
    $result = '(not set)';
    //hit your local options table for recent activity
    $options_arr = get_option('bl_api_client_activity');
    $permissions = get_option('bl_api_client_permissions');
    $geoblock_options = get_option( 'og_geo_options' );
    //instantiate the "review monster" active record
    $monster = new BL_Review_Monster($options_arr['reviews']);
    $avg_obj = $monster->get_weighted_aggregate();
    $agg_class = 'class="hreview-aggregate"';
    $business_name = ($permissions['verified']) ? $permissions['verify'] : '';
    $coeff = strval(floatval($avg_obj->rating) * 20);
    $a = shortcode_atts(
      array('title' => 'Our Reviews'),
      $atts
    );
    $button_one = isset($geoblock_options['og_geo_button_text_one']) ?  $geoblock_options['og_geo_button_text_one'] : '';
    $button_one_link = isset($geoblock_options['og_geo_button_link_one']) ?  $geoblock_options['og_geo_button_link_one'] : '';
    $button_two = isset($geoblock_options['og_geo_button_text_two']) ?  $geoblock_options['og_geo_button_text_two'] : '';
    $button_two_link = isset($geoblock_options['og_geo_button_link_two']) ?  $geoblock_options['og_geo_button_link_two'] : '';

    $snippet_html = '<h3 class="card-title">' . $a['title'] . '</h3>';
    $snippet_html .= '<div id="cr-review-blockx" ' . $agg_class .'>';
    $snippet_html .= '<b class="item"><span class="fn">'. $business_name . '</span></b>';
    $snippet_html .= '<div>';
    $snippet_html .= '<div class="rating-container">';
    $snippet_html .= '<div class="r-stars">';
    $snippet_html .= '<div class="r-stars-inner" style="width: ' . $coeff . '%; background: transparent url(' . site_url() . self::$star_img_path . ' ) repeat-x 0 0;background-position: 0 -25px;"> ';
    $snippet_html .= '</div><div>';
    $snippet_html .= '<p class="aggRatings">Rated <span class="rating">' . strval($avg_obj->rating) . '</span>/<span>5</span> Based on <span class="count">' . strval($avg_obj->count) . '</span> Verified Ratings</p>';
    $snippet_html .= '<p><a class="aggReview-button" href="' . $button_one_link . '">' . $button_one . '</a><br>';
    $snippet_html .= '<a class="aggReview-button" href="' . $button_two_link . '">' . $button_two . '</a></p>';
    $snippet_html .= '</div></div></div></div></div></div>';
    return $snippet_html;
  }

  public static function local_reviews_shortcode_handler() {
    //Integrates all directories, sorts by date
    //hit your local options table for recent activity
    $reviews = BL_Review_Monster::get_bl_client_reviews();
    //$options_arr = get_option('bl_api_client_activity');
    //instantiate the "review monster" active record
    $monster = new BL_Review_Monster($reviews);
    //$monster = new BL_Review_Monster($options_arr['reviews']);
    //isntantiate the review shrine return string value
    $result = "<div id='my_review_shrine' class='bl_client_reviews_widget'>";
    //iterate the monster's review record
    // NOTE: add filtration - BL_Review_Monster should have filtration
    // -by rating, -by locale, -by date
    foreach ($monster->reviews_all as $review_obj) {
      $result .= "<div class='bl_client_review {$review_obj['listing_directory']}' itemprop='review' itemscope='' itemtype='http://schema.org/Review'>";
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
              $meta_prop = ($review_prop==='author') ? "itemprop='$review_prop'" : '';
              $inner_html = "<p $meta_prop {$this_class}>{$review_obj[$review_prop]}</p>";
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
              $inner_html .= '<div class="cr-hidden"  itemprop="reviewRating" itemscope="" itemtype="http://schema.org/Rating">';
              $inner_html .= '<meta itemprop="bestRating" content="5">';
              $inner_html .= '<meta itemprop="worstRating" content="1">';
              $inner_html .= '<meta itemprop="ratingValue" content="' . strval(floatval($review_obj[$review_prop])) . '">';
              $inner_html .= '</div>';
              break;
            case 'text' :
              $inner_html = "<p itemprop='reviewBody' {$this_class}>{$review_obj[$review_prop]}</p>";
              break;
            default:
              $inner_html = '';
          }
        } else {
          $inner_html = "<span itemprop='reviewBody' {$this_class}>(not set)</span>";
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

}
