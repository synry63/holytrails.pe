<?php
define("DDL_CREATE", "ddl_create_layout" );
define("DDL_ASSIGN", "ddl_assign_layout_to_content" );
define("DDL_EDIT", "ddl_edit_layout" );
define("DDL_DELETE", "ddl_delete_layout" );

class WPDD_Layouts_Users_Profiles{

    private static $instance = null;
    private $ddl_users_settings;

    protected static $perms_to_pages = array(
        'admin.php?page=dd_layouts&amp;new_layout=true' => DDL_CREATE,
        'dd_layouts_edit' => DDL_EDIT,
        'dd_layouts' => DDL_EDIT,
        'dd_layouts_debug' => DDL_EDIT,
        'dd_tutorial_videos' => DDL_EDIT,
        'dd_layouts_troubleshoot' => DDL_EDIT
    );

    private function __construct(){
        $this->ddl_users_settings = new WPDDL_Options_Manager( 'users_options' );

        // clean up the database when deactivate plugin
        //register_activation_hook( WPDDL_ABSPATH . DIRECTORY_SEPARATOR . 'dd-layouts.php', array(&$this, 'add_caps') );
        add_action('init', array(&$this, 'add_caps'), 99 );
        register_deactivation_hook( WPDDL_ABSPATH . DIRECTORY_SEPARATOR . 'dd-layouts.php', array(&$this, 'disable_all_caps') );

    }

    public static final function ddl_get_capabilities(){
            return array(
                DDL_CREATE => "Create layouts",
                DDL_ASSIGN => "Assign layouts to content",
                DDL_EDIT => "Edit layouts",
                DDL_DELETE => "Delete layouts"
            );
    }


    public static function get_cap_for_page( $page ){
        return self::$perms_to_pages[$page] ? self::$perms_to_pages[$page] : DDL_EDIT;
    }

    public function add_caps(){
        global $wp_roles;

        if( $this->ddl_users_settings->get_options('updated_profiles') === true ){
            return;
        }

        if ( ! isset( $wp_roles ) || ! is_object( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        $ddl_capabilities = array_keys( self::ddl_get_capabilities() );

        $roles = $wp_roles->get_names();
        foreach ( $roles as $current_role => $role_name ) {
            $capability_can = apply_filters( 'ddl_capability_can', 'manage_options' );
            if ( isset( $wp_roles->roles[ $current_role ][ 'capabilities' ][ $capability_can ] ) ) {
                $role = get_role( $current_role );
                if ( isset( $role ) && is_object( $role ) ) {
                    for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {

                        if ( ! isset( $wp_roles->roles[ $current_role ][ 'capabilities' ][ $ddl_capabilities[ $i ] ] ) ) {
                            $role->add_cap( $ddl_capabilities[ $i ] );

                        }
                    }
                }

            }
        }

        //Set new caps for all Super Admins
        $super_admins = get_super_admins();
        foreach ( $super_admins as $admin ) {
            $updated_current_user = new WP_User( $admin );
            for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {
                $updated_current_user->add_cap( $ddl_capabilities[ $i ] );
            }
        }
        
        // We need to refresh $current_user caps to display the entire Layouts menu
                
        // If $current_user has not been updated yet with the new capabilities,
        global $current_user;
        if ( isset( $current_user ) && isset( $current_user->ID ) ) {
            
            // Insert the capabilities for the current execution
            $updated_current_user = new WP_User( $current_user->ID );

            for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {
                if ( $updated_current_user->has_cap($ddl_capabilities[$i]) ) {
                    $current_user->add_cap($ddl_capabilities[$i]);
                }
            }
            
            // Refresh $current_user->allcaps
            $current_user->get_role_caps();
        }


        $this->ddl_users_settings->update_options( 'updated_profiles', true, true );
    }

    public function disable_all_caps(){
        global $wp_roles;

        if ( ! isset( $wp_roles ) || ! is_object( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        $ddl_capabilities = array_keys( self::ddl_get_capabilities() );

        foreach ( $ddl_capabilities as $cap ) {
            foreach (array_keys($wp_roles->roles) as $role) {
                $wp_roles->remove_cap($role, $cap);
            }
        }

        //Remove caps for all Super Admins
        $super_admins = get_super_admins();
        foreach ( $super_admins as $admin ) {
            $user = new WP_User( $admin );
            for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {
                $user->remove_cap( $ddl_capabilities[ $i ] );
            }
        }

        $this->ddl_users_settings->update_options( 'updated_profiles', false, true );

    }

    public static function user_can_create(){
        return current_user_can( DDL_CREATE );
    }

    public static function user_can_assign(){
        return current_user_can( DDL_ASSIGN );
    }

    public static function user_can_edit(){
        return current_user_can( DDL_EDIT );
    }

    public static function user_can_delete(){
        return current_user_can( DDL_DELETE );
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDD_Layouts_Users_Profiles();
        }

        return self::$instance;
    }
}