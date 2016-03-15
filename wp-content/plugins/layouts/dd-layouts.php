<?php
/*
Plugin Name:Layouts
Plugin URI: http://wp-types.com/
Description: Design entire WordPress sites using a drag-and-drop interface. Layouts 1.2 <a href="https://wp-types.com/documentation/user-guides/#layouts">release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version:1.2
*/


/**
 * WPDDL_DEVELOPMENT -> default development, loads production files and leave embedded files alone
 *
 * WPDDL_EMBEDDED -> loads embedded files.
 *
 * WPDDL_PRODUCTION -> loads production files (not to be set manually)
 */
define('WPDDL_PRODUCTION', 'Layouts');

if (
    file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-embedded.class.php') &&
    defined('WPDDL_EMBEDDED') &&
    (defined('WPDDL_DEVELOPMENT') === false && defined('WPDDL_PRODUCTION') === false)
) {

    define('WPDDL_EMBEDDED_PATH', plugin_basename(__FILE__));
    define('WPDDL_EMBEDDED_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ddl-embedded-loader.php';

} else if (
    file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-plugin.class.php') &&
    (defined('WPDDL_DEVELOPMENT') || defined('WPDDL_PRODUCTION')) &&
    !function_exists('ddl_layouts_plugin_loader')
) {
    add_action('plugins_loaded', 'ddl_layouts_plugin_loader', 2);

    function ddl_layouts_plugin_loader()
    {
        if (!defined('WPDDL_IN_THEME_MODE')) { // This check is only needed when the plugin is being activated while the bootstrap theme is in use.
            require_once dirname(__FILE__) . '/ddl-loader.php';
        }
    }
}


if( !function_exists('wpddl_disable_wpml_admin_lang_switcher') ){

    function wpddl_disable_wpml_admin_lang_switcher($state)
    {
        global $pagenow;

        if ($pagenow == 'admin.php' && isset($_GET['page']) &&
            ($_GET['page'] == 'dd_layouts' ||
                $_GET['page'] == 'dd_layouts_edit')
        ) {

            $state = false;
        } elseif ($pagenow == 'admin-ajax.php' && isset($_POST['action']) &&
            ($_POST['action'] == 'ddl_load_assign_dialog_editor' ||
                $_POST['action'] == 'ddl_get_individual_post_checkboxes')
        ) {
            $state = false;
        }

        return $state;
    }
    add_filter('wpml_show_admin_language_switcher', 'wpddl_disable_wpml_admin_lang_switcher');
}

function wpddl_plugin_activate_new_release() {
    global $pagenow, $current_user ;
    $user_id = $current_user->ID;

    $plugin = plugin_basename(__FILE__);

    if ( $pagenow === 'plugins.php' && is_plugin_active($plugin) && WPDDL_VERSION === '1.2' && !get_user_meta( $user_id, WPDDL_Messages::$release_option_name ) ) {
        add_action( 'admin_notices', array( 'WPDDL_Messages', 'release_message' ) );
    }
}
add_action( 'admin_init', 'wpddl_plugin_activate_new_release' );


register_deactivation_hook( __FILE__, 'wpddl_layout_deactivate_plugin' );
function wpddl_layout_deactivate_plugin(){
    global $current_user ;
    $user_id = $current_user->ID;
    delete_user_meta( $user_id, WPDDL_Messages::$release_option_name );
}