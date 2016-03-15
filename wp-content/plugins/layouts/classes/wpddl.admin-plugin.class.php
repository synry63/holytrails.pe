<?php

class WPDDL_Admin_Pages
{
    private static $instance = null;
    private $layouts_editor_page = false;

    private function __construct()
    {
        if( is_admin() ){

            $this->admin_init();
            add_action('admin_menu', array($this, 'add_layouts_admin_menu'));
            add_action('admin_menu', array($this, 'add_layouts_import_export_admin_menu'), 11);
            add_action('admin_menu', array($this, 'add_layouts_admin_create_layout_auto'), 12); // Fake menu for Toolbar link
            add_action('ddl_create_layout_button', array(&$this, 'create_layout_button'));
            add_action('ddl_create_layout_for_this_page', array(&$this, 'create_layout_for_this_page'));
            add_action('ddl_create_layout_for_this_cpt', array(&$this, 'create_layout_for_this_cpt'));
            add_action('wpddl_render_editor', array($this,'render_editor'), 10, 1);
            if (isset( $_GET['page'] ) && ( $_GET['page']==WPDDL_LAYOUTS_POST_TYPE ||
                    $_GET['page']=='dd_layout_theme_export' ||
                    $_GET['page'] == 'dd_layouts_edit' )) {

                add_action('admin_enqueue_scripts', array($this, 'preload_scripts'));

            }
            if (isset($_GET['page']) && $_GET['page']=='dd_layout_theme_export') {
                add_action('admin_enqueue_scripts', array($this, 'import_export_enqueue_script'));
            }
            add_action('ddl_include_creation_box', array(&$this, 'include_creation_box' ));
			
			add_action('wp_ajax_ddl_remove_layouts_loop_pagination_links', array($this,'remove_layouts_loop_pagination_links'));
        }
        // loads admin helper (duplicates layouts)
        if( class_exists('WPDDL_Plugin_Layouts_Helper') ){
            $this->helper = new WPDDL_Plugin_Layouts_Helper();
        }
    }

    function import_export_enqueue_script()
    {
        global $wpddlayout;
        $wpddlayout->enqueue_scripts('dd-layout-theme-import-export');

        $wpddlayout->localize_script('dd-layout-theme-import-export', 'DDLayout_settings', array(
            'DDL_JS' => array(
                'no_file_selected' => __('No file selected. Please select one file to import Layouts data from.', 'ddl-layouts')
            )
        ));
    }

    public function include_creation_box()
    {
        if( file_exists( WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php' ) ){
            include WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php';
        }
    }

    public function render_editor(){
        if( file_exists( WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php' ) ){
            include WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php';
        }
    }

    public function preload_scripts(){
        global $wpddlayout;

        $wpddlayout->enqueue_scripts(
            array(
                'ddl_create_new_layout'
            )
        );
        $wpddlayout->localize_script('ddl_create_new_layout', 'DDLayout_settings_editor', array(
            'user_can_create' => user_can_create_layouts(),
            'strings' => array(
                'associate_layout_to_page' => __('To create an association between this Layout and a single page open....', 'ddl-layouts')
            )
        ) );
    }

    public function create_layout_for_this_page()
    {
        global $post;
        if( user_can_create_layouts() ):
        ?>
        <a href="#" class="add-new-h2 js-create-layout-for-page create-layout-for-page"><?php printf(__('Create a new layout for this %s', 'ddl_layout'), rtrim($post->post_type, 's') );?></a>
        <?php

    else: ?>
        <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl_layout'), rtrim($post->post_type, 's') );?></button><br>
        <?php
        endif;
    }

    public function create_layout_for_this_cpt()
    {
        global $post;
        if( user_can_create_layouts() ):
        ?>
        <a href="#" class="add-new-h2 js-create-layout-for-post-custom create-layout-for-page"><?php printf(__('Create a new layout for this %s', 'ddl_layout'), rtrim($post->post_type, 's') );?></a>
        <?php

    else: ?>
        <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl_layout'), rtrim($post->post_type, 's') );?></button><br>
        <?php
        endif;
    }


    public function add_layouts_admin_menu()
    {
        $pages = array(
            WPDDL_LAYOUTS_POST_TYPE => array(
                'title' => __('Layouts', 'ddl-layouts'),
                'function' => array($this, 'dd_layouts_list'),
                'subpages' => $this->add_sub_pages()
            ),
        );
        if (!$this->layouts_editor_page) {
            unset($pages[WPDDL_LAYOUTS_POST_TYPE]['subpages']['dd_layouts_edit']);
        }
        $this->add_to_menu($pages);
    }
    
    public function add_layouts_admin_create_layout_auto() {
        $parent_slug = 'options.php'; // Invisible
        $page_title = __( 'Create a new Layout', 'toolset' );
        $menu_title = __( 'Create a new Layout', 'toolset' );
        $capability = DDL_CREATE;
        $menu_slug = 'dd_layouts_create_auto';
        $function = array( $this, 'create_layout_auto' );
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    }

    public function create_layout_auto() {
        
        // verify permissions
        if( ! current_user_can( 'manage_options' ) && WPDD_Layouts_Users_Profiles::user_can_create() && WPDD_Layouts_Users_Profiles::user_can_assign() ) {
            die( __( 'Untrusted user', 'ddl-layouts' ) );
        }
        
        // verify nonce
        check_admin_referer( 'create_auto' );
        
        // validate parameters
        $b_type = isset( $_GET['type'] ) && preg_match( '/^([-a-z0-9_]+)$/', $_GET['type'] );
        $b_class = isset( $_GET['class'] ) && preg_match( '/^(archive|page)$/', $_GET['class'] );
        $b_post_id = isset( $_GET['post'] ) && (int) $_GET['post'] >= 0;

        // validate request
        if( ! ( $b_type && $b_class && $b_post_id ) ) {
            die( __( 'Invalid parameters', 'ddl-layouts' ) );
        }
        
        // get parameters
        $type = $_GET['type'];
        $class = $_GET['class'];
        $post_id = (int) $_GET['post'];
        
        // enforce rules
        $b_page_archive = 'page' === $type && 'archive' === $class;
        if( $b_page_archive ) {
            die( __( 'Not allowed', 'ddl-layouts' ) );
        }
        
        // prepare processing
        if( $post_id === 0 ) {
            $post_id = null;
        }
        
        $layout = null;
        $layout_id = 0;
        
        global $toolset_admin_bar_menu;
        $post_title = $toolset_admin_bar_menu->get_name_auto( 'layouts', $type, $class, $post_id );
        $title = sanitize_text_field( stripslashes_deep( $post_title ) );
        
        $taxonomy = get_taxonomy( $type );
        $is_tax = $taxonomy !== false;

        $post_type_object = get_post_type_object( $type );
        $is_cpt = $post_type_object != null;
        
        
        /* Create a new Layout */
        global $wpddlayout;
        
        // Is there another Layout with the same name?
        $already_exists = $wpddlayout->does_layout_with_this_name_exist( $title );
        if( $already_exists ) {
            die( __( 'A layout with this name already exists. Please use a different name.', 'ddl-layouts' ) );
        }
        
        // Create a empty layout. No preset.
        // TODO: Pick the preset best suited (and check if Views is installed)
        $layout = $wpddlayout->create_layout( 12 /* colums */, 'fluid' /* layout_type */ );
        
        // Define layout parameters
        $layout['type'] = 'fluid'; // layout_type
        $layout['cssframework'] = $wpddlayout->get_css_framework();
        $layout['template'] = '';
        $layout['parent'] = '';
        $layout['name'] = $title;
        
        $args = array(
            'post_title'	=> $title,
            'post_content'	=> '',
            'post_status'	=> 'publish',
            'post_type'     => WPDDL_LAYOUTS_POST_TYPE
        );
        $layout_id = wp_insert_post( $args );

        // force layout object to take right ID
        // @see WPDD_Layouts::create_layout_callback() @ wpddl.class.php
        $layout_post = get_post( $layout_id );
        $layout['id'] = $layout_id;
        $layout['slug'] = $layout_post->post_name;
        
        // assign layout
        if( 'archive' === $class ) {
            
            if( preg_match( '/^(home-blog|search|author|year|month|day)$/', $type ) ) {
                
                // Create a new Layout for X archives
                
                /* assign Layout to X archives */
                $layouts_wordpress_loop = sprintf( 'layouts_%s-page', $type );
                $wordpress_archive_loops = array( $layouts_wordpress_loop );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );
                
            } else if( $is_tax ) {
                
                // Create a new Layout for Y archives
                
                /* assign Layout to Y archives */
                $layouts_taxonomy_loop = sprintf( 'layouts_taxonomy_loop_%s', $type );
                $wordpress_archive_loops = array( $layouts_taxonomy_loop );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );
                 
                
            } else if( $is_cpt ) {
                
                // Create a new Layout for Z archives
                
                /* assign Layout to Z archives */
                $layouts_cpt = sprintf( 'layouts_cpt_%s', $type );
                $wordpress_archive_loops = array( $layouts_cpt );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );
                
            } else {
                die( __( 'An unexpected error happened.', 'ddl-layouts' ) );
            }
            
        } else if( 'page' === $class ) {
            
            if( '404' === $type ) {
                
                // Create a new Layout for Error 404 page
                
                /* assign Layout to 404 page */
                $wordpress_others_section = array( 'layouts_404_page' );
                $wpddlayout->layout_post_loop_cell_manager->handle_others_data_save( $wordpress_others_section, $layout_id );
                
            } else if( 'page' === $type ) {
                
                // Create a new Layout for 'Page Title'
                
                /* assign Layout to Page */
                $posts = array( $post_id );
                $wpddlayout->post_types_manager->update_post_meta_for_post_type( $posts, $layout_id );
                
            } else if( $is_cpt ) {
                
                // Create a new Layout for Ys
                
                /* assign Layout to Y */
                $post_types = array( $type );
                $wpddlayout->post_types_manager->handle_post_type_data_save( $layout_id, $post_types, $post_types );
                //$wpddlayout->post_types_manager->handle_set_option_and_bulk_at_once( $layout_id, $post_types, $post_types );
                
            } else {
                die( __( 'An unexpected error happened.', 'ddl-layouts' ) );
            }
            
        }
        
        // update changes
        WPDD_Layouts::save_layout_settings( $layout_id, $layout );
        
        // redirect to editor (headers already sent)
        $edit_link = $toolset_admin_bar_menu->get_edit_link( 'layouts', false, $type, $class, $layout_id );
        $exit_string = '<script type="text/javascript">'.'window.location = "' . $edit_link . '";'.'</script>';
        exit( $exit_string );
        
    }
    
    public function create_layout_button()
    {
        if( user_can_create_layouts() ):
            ?>
            <a href="#" class="add-new-h2 js-layout-add-new-top"><?php _e('Add new layout', 'ddl-layouts');?></a>
        <?php

        else: ?>
            <button disabled class="add-new-disabled"><?php _e('Add new layout', 'ddl-layouts');?></button>
        <?php
        endif;
    }

    private function add_layout_menu()
    {
        if( user_can_create_layouts() === false || user_can_edit_layouts() === false  ){
            return array();
        }
        return array('admin.php?page=dd_layouts&amp;new_layout=true' => array(
            'title' => __('Add new layout', 'ddl-layouts'),
        )
        );
    }

    private function add_edit_menu()
    {
         if( user_can_edit_layouts() === false ){
             return array();
         }
        return array('dd_layouts_edit' => array(
            'title' => __('Edit layout', 'ddl-layouts'),
            'function' => array($this, 'dd_layouts_edit'),
        ));
    }

    private function add_tutorial_video()
    {
        return array('dd_tutorial_videos' => array(
            'title' => __('Help', 'ddl-layouts'),
            'function' => array($this, 'dd_layouts_help'),
            'subpages' => array(
                'dd_layouts_debug' => array(
                    'title' => __('Debug information', 'ddl-layouts'),
                    'function' => array(__CLASS__, 'dd_layouts_debug')
                ),
            ),
        ),);
    }
	
	private function add_troubleshoot_menu()
	{
		if( isset( $_GET['page'] ) && 'dd_layouts_troubleshoot' == $_GET['page'] ){
			return array('dd_layouts_troubleshoot' => array(
                'title' => __('Troubleshoot', 'ddl-layouts'),
                'function' => array(__CLASS__, 'dd_layouts_troubleshoot'),
            ));
		}
		return array();
	}

    private function add_sub_pages()
    {
        $menus = array_merge(
            array(),
            $this->add_layout_menu(),
            $this->add_edit_menu(),
            $this->add_tutorial_video(),
			$this->add_troubleshoot_menu()
        );

        return $menus;
    }

    /**
     * Adds items to admin menu.
     *
     * @param array $menu array of menu items
     * @param string $parent_slug menu slug, if exist item is added as submenu
     *
     * @return void function do not return anything
     *
     */
    public function add_to_menu($menu, $parent_slug = null)
    {
        foreach ($menu as $menu_slug => $data) {
            $slug = null;
            if (empty($parent_slug)) {
                $slug = add_menu_page(
                    $data['title'],
                    isset($data['menu']) ? $data['menu'] : $data['title'],
                    WPDD_Layouts_Users_Profiles::get_cap_for_page( $menu_slug ),
                    $menu_slug,
                    isset($data['function']) ? $data['function'] : null
                );
            } else {
                $slug = add_submenu_page(
                    $parent_slug,
                    $data['title'],
                    isset($data['menu']) ? $data['menu'] : $data['title'],
                    WPDD_Layouts_Users_Profiles::get_cap_for_page( $menu_slug ),
                    $menu_slug,
                    isset($data['function']) ? $data['function'] : null
                );
            }
            /**
             * add load hook if is defined
             */
            if (!empty($slug) && isset($data['load_hook'])) {
                add_action('load-' . $slug, $data['load_hook']);
            }
            /**
             * add subpages
             */
            if (isset($data['subpages'])) {
                $this->add_to_menu($data['subpages'], $menu_slug);
            }
        }
    }

    function admin_init()
    {
        if (isset($_GET['page']) and $_GET['page'] == 'dd_layouts_edit') {
            if (isset($_GET['layout_id']) and $_GET['layout_id'] > 0) {
                $this->layouts_editor_page = true;
            }
        }
    }

    function dd_layouts_help(){
        include WPDDL_GUI_ABSPATH . 'templates/layout_help.tpl.php';
        include WPDDL_GUI_ABSPATH . 'dialogs/dialog_video_player.tpl.php';
    }

    function dd_layouts_list()
    {
        global $wpddlayout;
        $wpddlayout->listing_page->init();
    }

    function dd_layouts_edit()
    {
        global $wpddlayout;
        $wpddlayout->dd_layouts_edit();
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Admin_Pages();
        }

        return self::$instance;
    }

    function dd_layouts_theme_export(){
        include WPDDL_SUPPORT_THEME_PATH . 'templates/layout_theme_export.tpl.php';
    }

    function add_layouts_import_export_admin_menu() {

        add_submenu_page(WPDDL_LAYOUTS_POST_TYPE, __('Import/Export', 'ddl-layouts'), __('Import/Export', 'ddl-layouts'), DDL_ASSIGN, 'dd_layout_theme_export', array($this, 'dd_layouts_theme_export'));
    }
    /**
     * debug page render hook.
     */
    public static function dd_layouts_debug()
    {
        include_once WPDDL_TOOLSET_COMMON_ABSPATH . DIRECTORY_SEPARATOR.'debug/debug-information.php';
    }
	/**
	* troubleshoot page render hook
	*/
	public static function dd_layouts_troubleshoot()
	{
		include WPDDL_GUI_ABSPATH . 'templates/layout_troubleshoot.tpl.php';
	}
	
	function remove_layouts_loop_pagination_links()
	{
		if( user_can_create_layouts() === false ){
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'ddl-layouts' )
			);
			wp_send_json_error($data);
		}
		if(	!isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'ddl_remove_layouts_loop_pagination_links') ){
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'ddl-layouts' )
			);
			wp_send_json_error($data);
		}
		if( function_exists('wpv_check_views_exists') ){
			$ddl_archive_loop_ids = wpv_check_views_exists( 'layouts-loop' );
			if( $ddl_archive_loop_ids ){
				$ddl_archive_loop_ids = array_map('esc_attr', $ddl_archive_loop_ids);
				$ddl_archive_loop_ids = array_map('trim', $ddl_archive_loop_ids);
				$ddl_archive_loop_ids = array_filter($ddl_archive_loop_ids, 'is_numeric');
				$ddl_archive_loop_ids = array_map('intval', $ddl_archive_loop_ids);
				if( count($ddl_archive_loop_ids) ){
					global $wpdb;
					$final_post_content = "[wpv-filter-meta-html]\n[wpv-layout-meta-html]";
					$wpdb->query( 
						$wpdb->prepare( 
							"UPDATE {$wpdb->posts} 
							SET post_content = %s 
							WHERE ID IN ('" . implode("','", $ddl_archive_loop_ids) . "')",
							$final_post_content 
						) 
					);
				}
			}
			$data = array(
				'message' => __( 'Pagination links deleted.', 'ddl-layouts' )
			);
			wp_send_json_success( $data );
		} else {
			$data = array(
				'type' => 'missing',
				'message' => __( 'You need Views to perform this action.', 'ddl-layouts' )
			);
			wp_send_json_error($data);
		}
	}

}