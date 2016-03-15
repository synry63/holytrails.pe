<?php
/**********************************************************************
 *          Define textdomain
 ********************************************************************/
define( "THEMETD", "toolset_starter" );
load_theme_textdomain( "toolset_starter", get_template_directory() . '/languages' );


/**********************************************************************
 *            Load Bootstrap functions and Theme Customization
 ********************************************************************/
require_once( get_template_directory() . '/functions/bootstrap-wordpress.php' );
require_once( get_template_directory() . '/functions/theme-customizer.php' );

/******************************************************************************************
 * Enqueue styles and scripts
 *****************************************************************************************/

// used in different places
define( 'THEME_CSS', get_template_directory_uri() . '/css/theme.css' );
define( 'THEME_CSS_WOO', get_template_directory_uri() . '/css/woocommerce.css' );
define( 'THEME_CSS_BOOTSTRAP', get_template_directory_uri() . '/bootstrap/css/bootstrap.min.css' );

if ( ! function_exists( 'ref_register_scripts' ) ) {

	function ref_register_scripts() {
		if ( ! is_admin() ) {

			// Register  CSS
			wp_register_style( 'bootstrap_css', THEME_CSS_BOOTSTRAP , array(), null );
			wp_register_style( 'main', get_stylesheet_uri(), array(), null );
			wp_register_style( 'theme', THEME_CSS, array(), null );
			wp_register_style( 'ref_woocommerce', THEME_CSS_WOO, array(), null );
			wp_register_style( 'font_awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', array(), null );

			// Enqueue CSS
			wp_enqueue_style( 'main' );

			if(get_theme_mod( 'ref_theme_styles',1) == 1 ) {
				wp_enqueue_style( 'theme' );
			} else {
				wp_enqueue_style( 'bootstrap_css' );
			}

			if(get_theme_mod( 'ref_wc_styles',1) == 1 ) {
				wp_enqueue_style( 'ref_woocommerce' );
			}

			wp_enqueue_style( 'font_awesome' );

			// Register  JS
			wp_register_script( 'wpbootstrap_bootstrap_js', get_template_directory_uri() . '/bootstrap/js/bootstrap.js', array( 'jquery' ), null, true );
			wp_register_script( 'theme_js', get_template_directory_uri() . '/js/theme.min.js', array( 'jquery' ), null, true );

			// Enqueue JS // MOI
            //$useragent=$_SERVER['HTTP_USER_AGENT'];
            //if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
                wp_enqueue_script( 'wpbootstrap_bootstrap_js' );
            //}
            // END MOI

			wp_enqueue_script( 'theme_js' );


			if ( is_single() && comments_open() && get_option( 'thread_comments' ) ) {
				wp_enqueue_script( 'comment-reply' );
			}
		}
	}

	add_action( 'wp_enqueue_scripts', 'ref_register_scripts' );
}


/******************************************************************************************
 * Theme support
 *****************************************************************************************/
add_theme_support( 'woocommerce' );
add_theme_support( "title-tag" );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'nav-menus' );
register_nav_menus( array(
	'header-menu' => __( 'Header Menu', THEMETD ),
) );


add_theme_support( 'html5', array(
	'search-form',
	'comment-form',
	'comment-list',
	'gallery',
	'caption',
	'video'
) );

/**********************************************************
 * The Archive Title Filter
 ********************************************************/
add_filter( 'get_the_archive_title', 'ref_custom_archive_title');

function ref_custom_archive_title ($title) {

	if ( is_post_type_archive() ) {

		$title = post_type_archive_title( '', false );

	}
	return $title;
};
/******************************************************************************************
 * Add Open Sans font variants for admin and front-end
 *****************************************************************************************/

if ( ! function_exists( 'replace_open_sans' ) ) {

	function replace_open_sans() {
		wp_deregister_style( 'open-sans' );
		wp_register_style( 'open-sans', '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800&subset=latin,latin-ext' );
		//wp_register_style( 'open-sans', '//fonts.googleapis.com/css?family=Open+Sans' ); // MOI
		wp_enqueue_style( 'open-sans' );
	}

	add_action( 'wp_enqueue_scripts', 'replace_open_sans' );
}


/**********************************************************************
 *            Add image sizes
 ********************************************************************/

add_image_size( 'product-thumbnail', 260, 330, true );

/**********************************************************************
 *            Register sidebars
 ********************************************************************/

function wpbootstrap_register_widget_areas() {
	register_sidebar( array(
		'name'          => __( 'Widgets in Footer', THEMETD ),
		'id'            => 'sidebar-footer',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widgettitle">',
		'after_title'   => '</h3>'
	) );

	register_sidebar( array(
		'name'          => __( 'Widgets in Header', THEMETD ),
		'id'            => 'sidebar-header',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widgettitle">',
		'after_title'   => '</h3>'
	) );

	register_sidebar( array(
		'name'          => __( 'Default Sidebar', THEMETD ),
		'id'            => 'sidebar-default',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widgettitle">',
		'after_title'   => '</h3>'
	) );


}

add_action( 'widgets_init', 'wpbootstrap_register_widget_areas' );

/**************************************************
 * Load custom cells types for Layouts plugin from the /dd-layouts-cells/ directory
 **************************************************/
if ( class_exists( 'WPDD_Layouts' ) && ! function_exists( 'include_ddl_layouts' ) ) {

	function include_ddl_layouts( $tpls_dir = '' ) {
		$dir_str = dirname( __FILE__ ) . $tpls_dir;
		$dir     = opendir( $dir_str );
		while ( ( $currentFile = readdir( $dir ) ) !== false ) {
			if ( is_file( $dir_str . $currentFile ) && pathinfo($dir_str . $currentFile, PATHINFO_EXTENSION) === 'php' ) {
				include $dir_str . $currentFile;
			}
		}
		closedir( $dir );
	}

	include_ddl_layouts( '/dd-layouts-cells/' );
}


/**************************************************
 * Allow to Import/Export Layouts
 **************************************************/

if ( is_admin() &&
     class_exists( 'WP_Views' ) &&
     function_exists( 'wpv_admin_import_data' ) &&
     class_exists( 'WPDD_Layouts' ) &&
     ! function_exists( 'include_ddl_layouts' )
) {

	if ( ! get_option( 'bs3min_upload_options' ) ) {
		add_action( 'init', 'wpcf_bs3theme_import', 99 );
	}

	//Main theme import
	function wpcf_bs3theme_import() {

		if ( defined( 'WPDDL_VERSION' ) ) {
			require_once WPDDL_ABSPATH . '/ddl-theme.php';
		}

		if ( function_exists( 'ddl_import_layouts_from_theme_dir' ) ) {
			wpcf_bs3theme_import_import_layouts();
		}
	}

	//Import Layouts
	function wpcf_bs3theme_import_import_layouts() {
		ddl_import_layouts_from_theme_dir();
		update_option( 'bs3min_upload_options', 'yes' );
	}
}


/**********************************************************************
 *            Page Slug Body Class
 ********************************************************************/

function add_slug_body_class( $classes ) {
	global $post;

	if ( isset( $post ) ) {
		$classes[] = $post->post_type . '-' . $post->post_name;
	}

	return $classes;
}

add_filter( 'body_class', 'add_slug_body_class' );

add_filter('get_layout_id_for_render', 'toolset_base_theme_fix_attachment', 2, 99);
function toolset_base_theme_fix_attachment( $layout_id, $layout ){
    // if the page is rendering with layouts fix attachment if that's the case
    if( $layout_id !== 0 ){
        add_filter('the_content', 'prepend_attachment', 1, 999);
    }
    return $layout_id;
}

function revslider_scripts_cleanup() {
//DeRegister jquery.themepunch.tools.min
    wp_deregister_script( 'jquery.themepunch.tools.min' );
//DeRegister jquery.themepunch.revolution.min
    wp_deregister_script( 'jquery.themepunch.revolution.min' );

//Enqueue js files in footer
    wp_enqueue_script('jquery.themepunch.tools.min', '/wp-content/plugins/revslider/rs-plugin/js/jquery.themepunch.tools.min.js', array(), '',  true);
    wp_enqueue_script('jquery.themepunch.revolution.min', '/wp-content/plugins/revslider/rs-plugin/js/jquery.themepunch.revolution.min.js', array(), '',  true);
}

add_action( 'wp_enqueue_scripts', 'revslider_scripts_cleanup' );
?>
