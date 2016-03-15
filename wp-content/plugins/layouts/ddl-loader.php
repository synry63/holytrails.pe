<?php
if( defined('WPDDL_VERSION') ) return;
define('WPDDL_VERSION', '1.2');

define('WPDDL_RELPATH', plugins_url() . '/' . basename(dirname(__FILE__)));
define( 'WPDDL_ABSPATH', dirname( __FILE__ ) );
define( 'WPDDL_ONTHEGO_RESOURCES', WPDDL_ABSPATH . '/onthego-resources/');
define( 'WPDDL_INC_ABSPATH', WPDDL_ABSPATH . '/inc' );
define( 'WPDDL_INC_RELPATH', WPDDL_RELPATH . '/inc' );
define( 'WPDDL_CLASSES_ABSPATH', WPDDL_ABSPATH . '/classes' );
define( 'WPDDL_CLASSES_RELPATH', WPDDL_RELPATH . '/classes' );
define( 'WPDDL_RES_ABSPATH', WPDDL_ABSPATH . '/resources' );
define( 'WPDDL_RES_RELPATH', WPDDL_RELPATH . '/resources' );
define( 'WPDDL_GUI_ABSPATH', WPDDL_ABSPATH . '/inc/gui/' );
define( 'WPDDL_GUI_RELPATH', WPDDL_RELPATH . '/inc/gui/' );
define( 'WPDDL_SUPPORT_THEME_PATH', WPDDL_ABSPATH . '/theme/' );


define( 'WPDDL_TOOLSET_COMMON_ABSPATH', WPDDL_ABSPATH  . '/toolset-common' );
define( 'WPDDL_TOOLSET_COMMON_RELPATH', WPDDL_RELPATH  . '/toolset-common' );


if( !defined('WPDDL_DEBUG') ) define('WPDDL_DEBUG', false);

//TODO: this is used for archives / loops it is better to use it only for this data. Should we rename it not to get confused..
define('WPDDL_GENERAL_OPTIONS', 'ddlayouts_options');
define('WPDDL_CSS_OPTIONS', 'layout_css_settings');
define('WPDDL_LAYOUTS_CSS', 'layout_css_styles');
define('WPDDL_LAYOUTS_META_KEY', '_layouts_template');
define('WPDDL_LAYOUTS_POST_TYPE', 'dd_layouts');
define('WPDDL_LAYOUTS_SETTINGS', 'dd_layouts_settings');
define('WPDDL_LAYOUTS_EXTRA_MODULES', WPDDL_ABSPATH. '/extra');

if( !defined('TOOLSET_EDIT_LAST') ){
    define('TOOLSET_EDIT_LAST', '_toolset_edit_last');
}



define('DDL_ITEMS_PER_PAGE', 10 );

require WPDDL_ONTHEGO_RESOURCES . 'loader.php';
onthego_initialize(WPDDL_ONTHEGO_RESOURCES, WPDDL_RELPATH . '/onthego-resources/');


require_once WPDDL_INC_ABSPATH . '/constants.php';

if ( file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-embedded.class.php') && defined('WPDDL_EMBEDDED') && ( defined('WPDDL_DEVELOPMENT') === false && defined('WPDDL_PRODUCTION') === false ) ) {

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-embedded.class.php';

} else if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-plugin.class.php') && ( defined('WPDDL_DEVELOPMENT') || defined('WPDDL_PRODUCTION') ) ) {

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-plugin.class.php';
}

require_once WPDDL_INC_ABSPATH . '/help_links.php';

require_once WPDDL_TOOLSET_COMMON_ABSPATH . '/utility/utils.php';
require_once WPDDL_TOOLSET_COMMON_ABSPATH . '/WPML/wpml-string-shortcode.php';

require_once WPDDL_CLASSES_ABSPATH . '/wpddl.user-profiles.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.utils.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.layout.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.json2layout.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.layout-render.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.registered_cell.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.registered_layout_theme_section.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.cache-singleton.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.layouts.render.manager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.editor.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.file-manager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.cssmanager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.optionsmanager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.scripts.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.archives.manager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.post-types-manager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.individual-assignment-manager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.cssframerwork.options.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.layouts-listing.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.views-support.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl-common-messages-class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.post-edit-page-manager.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/wpddl.PluginLayouts-helper.class.php';
require_once WPDDL_LAYOUTS_EXTRA_MODULES . '/wddl.extra-loader.class.php';

require_once WPDDL_GUI_ABSPATH . '/dialogs/dialogs.php';
require_once WPDDL_GUI_ABSPATH . '/dialogs/wpddl.create-cell-dialog.class.php';
require_once WPDDL_GUI_ABSPATH . '/editor/editor.php';

require_once WPDDL_INC_ABSPATH . '/api/ddl-fields-api.php';

require_once WPDDL_INC_ABSPATH . '/api/ddl-theme-api.php';

require_once WPDDL_INC_ABSPATH . '/api/ddl-features-api.php';

include_once WPDDL_RES_ABSPATH. '/log_console.php';

include_once WPDDL_TOOLSET_COMMON_ABSPATH . DIRECTORY_SEPARATOR . 'classes/class-toolset-admin-bar-menu.php';

// Add theme export menu.
require_once WPDDL_ABSPATH . '/theme/wpddl.theme-support.class.php';

if ( file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.layouts-helper.class.php') && defined('WPDDL_EMBEDDED') === false && ( defined('WPDDL_DEVELOPMENT') === true || defined('WPDDL_PRODUCTION') === true ) ) {

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.layouts-helper.class.php';

}


add_action( 'init', 'init_layouts_plugin', 9 );

    function init_layouts_plugin()
    {
        global $wpddlayout;
        $wpddlayout = new WPDD_Layouts();
    }