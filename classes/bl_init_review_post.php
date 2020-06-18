<?php
   class BL_Init_Review_Post {

  // Create the custom post type for Reviews
  public static function review_custom_post_type(){
       $labels = array(
  		'name'               =>  'CR Customer Reviews' ,
  		'singular_name'      =>  'Review' ,
  		'menu_name'          =>  'Reviews' ,
  		'name_admin_bar'     =>  'CR Reviews' ,
  		'add_new'            =>  'Add Review' ,
  		'add_new_item'       =>  'Add Review' ,
  		'new_item'           =>  'New Review' ,
  		'edit_item'          =>  'Edit Review' ,
  		'view_item'          =>  'View Review',
  		'all_items'          =>  'All Reviewss',
  		'search_items'       =>  'Search Reviews',
  		'parent_item_colon'  =>  'Parent Review',
  		'not_found'          =>  'No Reviews found.',
  		'not_found_in_trash' =>  'No Reviews Found in Trash.',
  	);

  	$args = array(
  		'labels'             => $labels,
  		'public'             => false,
  		'publicly_queryable' => true,
  		'show_ui'            => true,
  		'show_in_menu'       => false,
      'menu_position'      => 1,
      'menu_icon'          => 'dashicons-share-alt2',
  		'query_var'          => true,
  		'rewrite'            => array( 'slug' => 'crs_review' ),
  		'capability_type'    => 'page',
  		'has_archive'        => false,
  		'hierarchical'       => false,
      'show_in_rest'       => true,
  		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'revisions', 'custom-fields' )
  	);

    register_post_type( 'crs_review' , $args);
  }

  public static function review_rewrite_flush() {
       // First, we "add" the custom post type via the above written function.
      // Note: "add" is written with quotes, as CPTs don't get added to the DB,
      // They are only referenced in the post_type column with a post entry,
      // when you add a post of this CPT.
      self::review_custom_post_type();

      // ATTENTION: This is *only* done during plugin activation hook in this example!
      // You should *NEVER EVER* do this on every page load!!
      flush_rewrite_rules();
  }
  /**
   * [crs_review_star_tax description]
   * @return [type] [description]
   */
  public static function crs_review_star_tax() {
    $labels = array(
      'name'              => _x( 'Ratings', 'taxonomy general name', 'cr-suite'),
      'singular_name'     => _x( 'Rating', 'taxonomy singular name', 'cr-suite' ),
  		'search_items'      => __( 'Search Ratings', 'cr-suite' ),
  		'all_items'         => __( 'All Ratings', 'cr-suite' ),
   		'edit_item'         => __( 'Edit Rating', 'cr-suite' ),
  		'update_item'       => __( 'Update Rating', 'cr-suite' ),
  		'add_new_item'      => __( 'Add New Rating', 'cr-suite' ),
  		'new_item_name'     => __( 'New Rating Name', 'cr-suite' ),
  		'menu_name'         => __( 'Rating', 'cr-suite' ),
    );

    $args = array(
		  'hierarchical'      => true,
      'public'            => false,
		  'labels'            => $labels,
		  'show_ui'           => true,
      'show_in_menu'      => true,
		  'show_admin_column' => true,
		  'query_var'         => true,
		  'rewrite'           => array( 'slug' => 'rating' ),
	  );

    register_taxonomy(
      'rating',
      'crs_review',
      $args
    );

  }

  public static function crs_review_star_numbers() {
    $parent_term = term_exists( 'rating', 'crs_review' ); // array is returned if taxonomy is given
    $parent_term_id = $parent_term['term_id']; // get numeric term id
    wp_insert_term(
      '5 Stars', // the term
      'rating', // the taxonomy
      array(
        'description'=> '5 Stars',
        'slug' => '5-stars',
        'parent'=> $parent_term_id
      )
    );
    wp_insert_term(
      '4 Stars', // the term
      'rating', // the taxonomy
      array(
        'description'=> '4 Stars',
        'slug' => '4-stars',
        'parent'=> $parent_term_id
      )
    );
    wp_insert_term(
      '3 Stars', // the term
      'rating', // the taxonomy
      array(
        'description'=> '3 Stars',
        'slug' => '3-stars',
        'parent'=> $parent_term_id
      )
    );
    wp_insert_term(
      '2 Stars', // the term
      'rating', // the taxonomy
      array(
        'description'=> '2 Stars',
        'slug' => '2-stars',
        'parent'=> $parent_term_id
      )
    );
    wp_insert_term(
      '1 Star', // the term
      'rating', // the taxonomy
      array(
        'description'=> '1 Star',
        'slug' => '1-star',
        'parent'=> $parent_term_id
      )
    );
  }

}
