<?php
//
// Recommended way to include parent theme styles.
//  (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
//  
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style')
    );
}
//
// Your code goes below
//
// remove website field from comment form
/*add_filter('comment_form_default_fields', 'url_filtered',10);
function url_filtered($fields)
{
    if(isset($fields['url']))
        unset($fields['url']);
    return $fields;
}*/
add_filter ('ddl_comment_cell_style', 'ddl_comment_change_cell_style');
function ddl_comment_change_cell_style( $var){
    return 'div'; // possible options are ul (default), ol and div
}

add_action( 'template_redirect', 'redirect_to_specific_page' );

function redirect_to_specific_page() {

    /*if ( is_page('home') && $_SERVER['REMOTE_ADDR']!="190.237.8.170" && $_SERVER['REMOTE_ADDR']!="190.42.113.121" ) {

        wp_redirect( 'holytrails.pe/coming', 302 );
        exit;
    }*/
}
add_action('wp_print_styles', 'load_fonts',10);
add_filter('wp_enqueue_scripts', 'enqueue_my_scripts', 20);
function load_fonts() {
    wp_register_style('googleRaleway', 'http://fonts.googleapis.com/css?family=Raleway');
    //wp_register_style('googleFonts', 'http://fonts.googleapis.com/css?family=PT+Sans');
    wp_enqueue_style( 'googleRaleway');
}
function enqueue_my_scripts() {
    //var_dump('my id '. get_the_ID());
    wp_enqueue_style('child', get_stylesheet_directory_uri() . '/child.css',array('googleRaleway','bootstrap_css'));

    //slick
    //if(get_the_ID()==18 || get_the_ID()==25){ // only home page and about
        wp_enqueue_style( 'slick-css',get_stylesheet_directory_uri() . '/libs/slick/slick.css',array('child'));
        wp_enqueue_style( 'slick-theme-css',get_stylesheet_directory_uri() . '/libs/slick/slick-theme.css',array('child'));
        wp_enqueue_script( 'slick-js', get_stylesheet_directory_uri() . '/libs/slick/slick.min.js', array('jquery'), '1.0.0', true );
    //}
    if(get_the_ID()==288 || get_the_ID()==315 || get_the_ID()==313){// only the rides pages
        // tab responsive
        wp_enqueue_style( 'tabresponsive-css',get_stylesheet_directory_uri() . '/libs/Responsive-Tabs/responsive-tabs.css');
        wp_enqueue_style( 'responsivetab-style-css',get_stylesheet_directory_uri() . '/libs/Responsive-Tabs/responsivetab-style.css');
        wp_enqueue_script( 'tabresponsive-js', get_stylesheet_directory_uri() . '/libs/Responsive-Tabs/jquery.responsiveTabs.min.js', array('jquery'), '1.0.0', true );
    }

    else if(get_the_ID()==343){ // only the build your trip page
        //date picker
        wp_enqueue_style( 'picker-main-css',get_stylesheet_directory_uri() . '/libs/date-picker/default.css');
        wp_enqueue_style( 'picker-date-css',get_stylesheet_directory_uri() . '/libs/date-picker/default.date.css');
        wp_enqueue_script( 'picker-main-js', get_stylesheet_directory_uri() . '/libs/date-picker/picker.js', array('jquery'), '1.0.0', true );
        wp_enqueue_script( 'picker-date-js', get_stylesheet_directory_uri() . '/libs/date-picker/picker.date.js', array('jquery','picker-main-js'), '1.0.0', true );
    }

    // noty
    wp_enqueue_script( 'noty-js', get_stylesheet_directory_uri() . '/libs/noty/jquery.noty.packaged.js', array('jquery'), '1.0.0', true );
    // 2d transform
   // wp_enqueue_script( 'jquery-transform-js', get_stylesheet_directory_uri() . '/libs/jquery.transform/jquery.transform2d.js', array('jquery'), '1.0.0', true );

     //main init
    wp_enqueue_script( 'main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), '1.0.0', true );
}

/* ADD CUSTOM POST automaticly
 *
 *
add_filter('wp_get_nav_menu_items', 'add_my_menu_items', 10, 3);
function add_my_menu_items($items, $menu, $args) {

    if (!is_admin() && $menu->name == 'main menu') {

        $posts = query_posts('post_type=ride');
        foreach ($posts as $i => &$post) {

            $post->menu_item_parent = 33;
            $post->classes = array();
            $post->type = 'post_type';
            $post->object = $post->post_type;
            $post->object_id = $post->ID;
            $post->menu_order = sizeof($items) + 1;
            $post->url = get_permalink($post);
            $post->title = $post->post_title;
            $items = array_merge($items, array($post));
        }
    }
    return $items;
}*/
// Change return to shop link, send to homepage instead

function change_return_shop_url() {
    return home_url();
}
add_filter( 'woocommerce_return_to_shop_redirect', 'change_return_shop_url' );

function _remove_script_version( $src ){
    $parts = explode( '?ver', $src );
    return $parts[0];
}
// remove version on static files
add_filter( 'script_loader_src', '_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', '_remove_script_version', 15, 1 );

// remove some metas
remove_action( 'wp_head', 'feed_links_extra', 3 ); // Display the links to the extra feeds such as category feeds
remove_action( 'wp_head', 'feed_links', 2 ); // Display the links to the general feeds: Post and Comment Feed
remove_action( 'wp_head', 'rsd_link' ); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action( 'wp_head', 'wlwmanifest_link' ); // Display the link to the Windows Live Writer manifest file.
remove_action( 'wp_head', 'index_rel_link' ); // index link
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); // prev link
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); // start link
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 ); // Display relational links for the posts adjacent to the current post.
remove_action( 'wp_head', 'wp_generator' ); // Display the XHTML generator that is generated on the wp_head hook, WP version

// remove a menu from admin panel
function custom_menu_page_removing() {
    //remove_menu_page( 'wpcf' ); //wplivechat-menu = chat // wpcf = type
}
add_action( 'admin_menu', 'custom_menu_page_removing' );


