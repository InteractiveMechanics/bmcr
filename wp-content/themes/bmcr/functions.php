<?php
/**
 * bmcr functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package bmcr
 */

if ( ! function_exists( 'bmcr_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function bmcr_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on bmcr, use a find and replace
		 * to change 'bmcr' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'bmcr', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'menu-1' => esc_html__( 'Primary', 'bmcr' ),
			'menu-2' => esc_html__('Secondary', 'bmcr')
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'bmcr_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );
	}
endif;
add_action( 'after_setup_theme', 'bmcr_setup' );

/**
* register custom navigation walker
*
* github repo: https://github.com/wp-bootstrap/wp-bootstrap-navwalker
*
*/

require_once get_template_directory() . '/class-wp-bootstrap-navwalker.php';




/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function bmcr_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'bmcr_content_width', 640 );
}
add_action( 'after_setup_theme', 'bmcr_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function bmcr_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'bmcr' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'bmcr' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'bmcr_widgets_init' );


function add_custom_post_types_to_loop( $query ) {
	if ( $query->is_front_page() && $query->is_main_query() || $query->is_home() && $query->is_main_query() || is_archive() && $query->is_main_query() && !is_post_type_archive() )
		$query->set( 'post_type', array( 'articles', 'reviews', 'responses' ) );
	return $query;
}
add_action( 'pre_get_posts', 'add_custom_post_types_to_loop' );


add_filter('ninja_forms_render_options', 'my_pre_population_callback', 10, 2);
function my_pre_population_callback($options, $settings) {
  
  // target only the field with this key
  if( $settings['key'] == 'listselect_1534176698515' ) {

    // write the query to fetch the data
    $args = array(
      'post_type' => 'reviews',
      'post_status' => 'title-added'
    );

    $posts = new WP_Query( $args );

    if ( $posts->have_posts() ) {

      $options = array();

      while ( $posts->have_posts() ) {

        $posts->the_post();
        
        if (isset($_GET['bid'])){
	        $bid = $_GET['bid'];
        }
        
        // $options is the variable which contains tha values rendered
        // we will use the post title as label and the ID as value
        if ($bid == get_the_id()) {
	        $options[] = array(
	          'label' => get_the_title(),
	          'value' => get_the_title(),
	          'selected' => true
	        );
	    } else {
		    $options[] = array(
	          'label' => get_the_title(),
	          'value' => get_the_title(),
	          'selected' => false
	        );
	    }

      } // endwhile
    } // endif

    wp_reset_postdata();
  }

  return $options;
}









/*
* Register ACF options pages
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */

if( function_exists('acf_add_options_page') ) {
    
    acf_add_options_page(array(
        'page_title'    => 'Theme General Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts', 
        'redirect'      => false
    ));
    
    acf_add_options_sub_page(array(
        'page_title' => 'Theme Header Settings',
        'menu_title' => 'Header',
        'parent_slug' => 'theme-general-settings',
    ));
    acf_add_options_sub_page(array(
        'page_title'    => 'Theme Footer Settings',
        'menu_title'    => 'Footer',
        'parent_slug'   => 'theme-general-settings',
    ));
    
     acf_add_options_sub_page(array(
        'page_title'    => '404 Page Settings',
        'menu_title'    => '404 Page',
        'parent_slug'   => 'theme-general-settings',
    ));

    
}




// enables relationships field to update between CPTs; that is, if a response is connected to a review, it will automatically update on the review's record and vice versa
function bidirectional_acf_update_value( $value, $post_id, $field  ) {
	
	// vars
	$field_name = $field['name'];
	$field_key = $field['key'];
	$global_name = 'is_updating_' . $field_name;
	
	
	// bail early if this filter was triggered from the update_field() function called within the loop below
	// - this prevents an inifinte loop
	if( !empty($GLOBALS[ $global_name ]) ) return $value;
	
	
	// set global variable to avoid inifite loop
	// - could also remove_filter() then add_filter() again, but this is simpler
	$GLOBALS[ $global_name ] = 1;
	
	
	// loop over selected posts and add this $post_id
	if( is_array($value) ) {
	
		foreach( $value as $post_id2 ) {
			
			// load existing related posts
			$value2 = get_field($field_name, $post_id2, false);
			
			
			// allow for selected posts to not contain a value
			if( empty($value2) ) {
				
				$value2 = array();
				
			}
			
			
			// bail early if the current $post_id is already found in selected post's $value2
			if( in_array($post_id, $value2) ) continue;
			
			
			// append the current $post_id to the selected post's 'related_posts' value
			$value2[] = $post_id;
			
			
			// update the selected post's value (use field's key for performance)
			update_field($field_key, $value2, $post_id2);
			
		}
	
	}
	
	
	// find posts which have been removed
	$old_value = get_field($field_name, $post_id, false);
	
	if( is_array($old_value) ) {
		
		foreach( $old_value as $post_id2 ) {
			
			// bail early if this value has not been removed
			if( is_array($value) && in_array($post_id2, $value) ) continue;
			
			
			// load existing related posts
			$value2 = get_field($field_name, $post_id2, false);
			
			
			// bail early if no value
			if( empty($value2) ) continue;
			
			
			// find the position of $post_id within $value2 so we can remove it
			$pos = array_search($post_id, $value2);
			
			
			// remove
			unset( $value2[ $pos] );
			
			
			// update the un-selected post's value (use field's key for performance)
			update_field($field_key, $value2, $post_id2);
			
		}
		
	}
	
	
	// reset global varibale to allow this filter to function as per normal
	$GLOBALS[ $global_name ] = 0;
	
	
	// return
    return $value;
    
}

add_filter('acf/update_value/name=relationships', 'bidirectional_acf_update_value', 10, 3);



/**
 * Enqueue scripts and styles.
 */
function bmcr_scripts() {	
	wp_enqueue_style( 'bootstrap-style', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
	
	wp_enqueue_style( 'bootstrapselect-style', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.2/css/bootstrap-select.min.css');
	
	wp_enqueue_style( 'bmcr-style', get_stylesheet_uri() );	

	wp_enqueue_script( 'bmcr-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );

    wp_enqueue_script( 'bmcr-notes', get_template_directory_uri() . '/js/notes.js', array('jquery'), null, true );

	wp_enqueue_script( 'bmcr-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );
	
	wp_enqueue_script( 'popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js', array('jquery'), null, true );

	wp_enqueue_script( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js', array('jquery'), null, true );
	
	wp_enqueue_script( 'bootstrapselect', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.2/js/bootstrap-select.min.js', array('jquery', 'bootstrap'), null, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'bmcr_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

remove_filter('sanitize_title', 'sanitize_title_with_dashes');
function sanitize_title_with_dots_and_dashes($title) {
        $title = strip_tags($title);
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
        $title = remove_accents($title);
        if (seems_utf8($title)) {
                if (function_exists('mb_strtolower')) {
                        $title = mb_strtolower($title, 'UTF-8');
                }
                $title = utf8_uri_encode($title);
        }
        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title); // kill entities
        $title = preg_replace('/[^%a-z0-9 ._-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');
        $title = str_replace('-.-', '.', $title);
        $title = str_replace('-.', '.', $title);
        $title = str_replace('.-', '.', $title);
        $title = preg_replace('|([^.])\.$|', '$1', $title);
        $title = trim($title, '-'); // yes, again
        return $title;
}
add_filter('sanitize_title', 'sanitize_title_with_dots_and_dashes');


function filter_posts_where( $where ) {
    if ( isset( $_GET['auth'] )){ 
	    $where = str_replace("meta_key = 'books_$", "meta_key LIKE 'books_%", $where);
    }
    if ( isset( $_GET['reviewer'] )){ 
	    $where = str_replace("meta_key = 'reviewers_$", "meta_key LIKE 'reviewers_%", $where);
    }
	return $where;
}
add_filter('posts_where', 'filter_posts_where');

function post_page_removal() {
    remove_menu_page( 'edit.php' );
}
add_action( 'admin_menu', 'post_page_removal' );

