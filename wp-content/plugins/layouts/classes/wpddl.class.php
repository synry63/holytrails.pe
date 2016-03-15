<?php
class WPDD_Layouts {
	public $header_added = false;
	private $cell_factory = array();
	private $registed_cells = null;
	private $layouts_editor_page = false;
	public $css_manager;
	private $scripts_manager;
	public $post_types_manager;
	public $frameworks_options_manager;
	private $css_framework;
	public $listing_page;
	public $layout_post_loop_cell_manager;
    public $create_layout_for_pages_manager;
    private $registered_cells = array();
    private $rendered_layout_id = null;
    private $where_used_count = 0;
    private $ddl_caps;

    const PARENTS_OPTIONS = 'parents_options';

	function __construct(){

        $this->plugin_localization();

        $this->ddl_caps = WPDD_Layouts_Users_Profiles::getInstance();
	
		$this->registed_cells = new WPDD_registed_cell_types();

		$this->layout_post_loop_cell_manager = new WPDD_layout_post_loop_cell_manager;

		$this->registered_theme_sections = new WPDD_register_layout_theme_section();

		$this->scripts_manager =  WPDDL_scripts_manager::getInstance();

		if ( is_admin() ) {
			
			$this->parents_options = new WPDDL_Options_Manager( self::PARENTS_OPTIONS );
		}

		$this->post_types_manager = new WPDD_Layouts_PostTypesManager();
		
		$this->individual_assignment_manager = new WPDD_Layouts_IndividualAssignmentManager();

		global $wpdd_gui_editor;

		$this->wpddl_init();

		$this->css_manager = WPDD_Layouts_CSSManager::getInstance();

		$this->frameworks_options_manager = WPDD_Layouts_CSSFrameworkOptions::getInstance();

		$this->set_css_framework( $this->frameworks_options_manager->get_current_framework() );

		add_action('wp_ajax_nopriv_'.WPDDL_LAYOUTS_CSS, array(&$this, 'handle_layout_css_from_db_print'), 10 );

        add_action('init', array(&$this, 'init_listing_page' ), 20 );
		
		add_action( 'before_delete_post', array(&$this, 'before_delete_post_action') );
        
		add_action( 'add_attachment', array(&$this, 'add_attachment_action') );
		

        WDDL_ExtraModulesLoader::getInstance();
        WPDDL_Layouts_WPML::getInstance();
        
		if( is_admin()){
            // a little trick to have global $this available in post edit page upon construction
            add_action('init', array(&$this, 'init_create_layout_for_pages' ), 20 );

			$this->fix_up_views_slugs();
			
			if ($this->layouts_editor_page) {
				new WPDD_GUI_DIALOGS();
			}

			global $pagenow;

			$wpdd_gui_editor = new WPDD_GUI_EDITOR();

			/**/
            if( class_exists('WPDDL_Admin_Pages') ){
                WPDDL_Admin_Pages::getInstance();
            } else if ( class_exists('WPDDL_Admin_Pages_Embedded') ){
                WPDDL_Admin_Pages_Embedded::getInstance();
            }


			if ( $pagenow == 'plugins.php' ) {
                add_action('admin_enqueue_scripts', array($this, 'plugin_page_styles'));
			}


			if (isset( $_GET['page'] ) && ( $_GET['page']==WPDDL_LAYOUTS_POST_TYPE ||
					$_GET['page']=='dd_layout_theme_export' ||
					//$_GET['page'] == WPDDL_LAYOUTS_SETTINGS ||
					$_GET['page'] == 'dd_layouts_edit' )) {

				add_action('admin_enqueue_scripts', array($this, 'preload_styles'));
				add_action('admin_enqueue_scripts', array($this, 'preload_scripts'));
				
			}
			if( isset( $_GET['page'] ) && ( $_GET['page']=='dd_tutorial_videos') )
			{
				add_action('admin_enqueue_scripts', array($this, 'help_page_scripts'));
			}
			
			if (isset( $_GET['in-iframe-for-layout']) &&
					$_GET['in-iframe-for-layout'] == 1 &&
					defined('CRED_FORMS_CUSTOM_POST_NAME') &&
					$pagenow == 'post.php' &&
					isset($_GET['post'])) {
				
				$post_id = $_GET['post'];
				$post = get_post($post_id);
				if ($post->post_type == CRED_FORMS_CUSTOM_POST_NAME) {
					add_action('admin_enqueue_scripts', array($this, 'cred_in_iframe_scripts'));
				}
			}

			if( isset( $_GET['in-iframe-for-layout']) && $_GET['in-iframe-for-layout'] == 1 &&
				isset( $_GET['page'] ) && (('views-editor' == $_GET['page']) ||
										   ('views-embedded' == $_GET['page']) ||
										   ('view-archives-embedded' == $_GET['page']) ||
										   ('view-archives-editor' == $_GET['page']) )) {
				add_action('admin_enqueue_scripts', array($this, 'views_in_iframe_scripts'));
			}

            if (
                isset( $_GET['in-iframe-for-layout'] )
                && '1' == $_GET['in-iframe-for-layout']
            ) {
                wp_deregister_script('heartbeat');
                remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
                remove_action( 'wp_print_styles', 'print_emoji_styles' );
            }
			
			add_action('wp_ajax_ddl_create_layout', array($this, 'create_layout_callback') );
			add_action('wp_ajax_ddl_dismiss_template_message', array($this, 'ddl_dismiss_template_message') );

            /**
             * add extra debug information
             */
            add_filter('icl_get_extra_debug_info', array($this, 'add_extra_debug_information'));

			add_action('wpml_register_string_packages', array($this, 'register_all_strings_for_translation'), 10, 0);
            
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_toolset_common_styles' ) );
            
		} else {
		   // add_action('wp_head', array(&$this,'handle_layout_css_fe'));

			if(isset($_GET['ddl_style'])){
				header('Content-Type:text/css');
				$this->wpddl_frontent_styles($_GET['ddl_style']);
				die();
			}
            // Assign post layout for post create from front-end

            
			add_action('wp_enqueue_scripts', array($this, 'load_frontend_js'));
			add_action('wp_enqueue_scripts', array($this, 'load_frontend_css'));
            //place layout edit link in last position
            add_action( 'get_layout_id_for_render', array( &$this, 'get_layout_id_for_render_callback' ), 888, 2 );
            // @see class-toolset-admin-bar-menu.php
            //add_action( 'admin_bar_menu', array($this, 'print_layout_edit_link'), 999 );

            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_toolset_common_styles' ) );

            /**
			 * get cache singleton
			 */
			WPDD_Layouts_Cache_Singleton::getInstance();
        }
	}
	function __destruct(){

	}

    public static function views_available(){
        return class_exists('WP_Views');
    }

    public static function  set_toolset_edit_last(  $layout_id, $force = true ){
        if( false === $force ){
            return;
        }

        $now = time();
        $last = self::get_toolset_edit_last( $layout_id );

        if( $last >= $now ){
            return;
        }

        return update_post_meta( $layout_id, TOOLSET_EDIT_LAST, $now, $last );
    }

    public static function  reset_toolset_edit_last(  $layout_id ){
            delete_post_meta( $layout_id, TOOLSET_EDIT_LAST, self::get_toolset_edit_last( $layout_id ) );
    }

    public static function get_toolset_edit_last( $layout_id ){
        return intval( get_post_meta($layout_id, TOOLSET_EDIT_LAST, true ) );
    }

    public static function get_toolset_edit_last_in_readable_format( $layout_id ){
        $last_edit = self::get_toolset_edit_last( $layout_id );
        return date(DATE_COOKIE, $last_edit);
    }

    public function is_embedded(){
        return defined('WPDDL_EMBEDDED');
    }

    public function get_layout_id_for_render_callback($id, $args){
        $this->rendered_layout_id = $id;
        return $id;
    }

	public function is_rendered_by_layout(){
			return $this->rendered_layout_id !== null;
	}

    public function init_create_layout_for_pages()
    {
        $this->create_layout_for_pages_manager = WPDD_PostEditPageManager::getInstance();
    }

    public function init_listing_page(){
        $this->listing_page = WPDD_LayoutsListing::getInstance(  );
    }

    // Localization
    function plugin_localization(){
        $locale = get_locale();
        load_textdomain( 'ddl-layouts', WPDDL_ABSPATH . '/locale/layouts-' . $locale . '.mo');
    }
	

	function set_css_framework( $framework )
	{
		$this->css_framework = $framework;
	}

	function get_css_framework()
	{
		return $this->css_framework;
	}

	public function enqueue_scripts ( $handles ) {
		$this->scripts_manager->enqueue_scripts( $handles );
	}

	public function enqueue_styles ( $handles ) {
		$this->scripts_manager->enqueue_styles ( $handles );
	}

	public function deregister_styles ( $handles ) {
		$this->scripts_manager->deregister_styles ( $handles );
	}

	public function deregister_scripts ( $handles ) {
		$this->scripts_manager->deregister_styles ( $handles );
	}

	public function localize_script ( $handle, $object, $args ) {
		$this->scripts_manager->localize_script( $handle, $object, $args );
	}

	function preload_styles(){

			$this->enqueue_styles( array (
					'toolset-select2-css',
					'ddl-dialogs-forms-css',
					'ddl-dialogs-general-css',
					'ddl-dialogs-css',
					'wp-layouts-pages',
					'toolset-font-awesome',
					'toolset-colorbox',
					'toolset-common',
					'toolset-notifications-css',
					'views-admin-dialogs-css'
				)
		);
	}

	function help_page_scripts()
	{

		$this->enqueue_styles(array(
			'toolset-select2-css',
			'ddl-dialogs-forms-css',
			'ddl-dialogs-general-css',
			'ddl-dialogs-css',
			'wp-layouts-pages',
			'toolset-font-awesome',
			'toolset-colorbox',
			'toolset-common',
			'wp-mediaelement'
		));

		$this->enqueue_scripts(array(
			'select2',
			'wp-layouts-colorbox-script',
			'wp-layouts-dialogs-script',
			'wp-mediaelement',
			'ddl_common_scripts',
			'wp-layouts-video-js',
			'wp-layouts-help-js'
		));

		$this->localize_script('wp-layouts-help-js', 'DDLayout_settings', array(
				'DDL_JS' => array(
					'res_path' => WPDDL_RES_RELPATH,
					'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
					'editor_lib_path' => WPDDL_GUI_RELPATH."editor/js/",
					'dialogs_lib_path' => WPDDL_GUI_RELPATH."dialogs/js/",
					'DEBUG' => WPDDL_DEBUG,
				)
			)
		);
	}
	
	function views_in_iframe_scripts () {
		$this->enqueue_scripts(array(
            'suggest',
			'ddl-layouts-views-support'
		));
		$this->enqueue_styles(array(
			'ddl-dialogs-forms-css'
		));
	}

	function cred_in_iframe_scripts () {
		
		$this->enqueue_scripts(array(
			'ddl-layouts-cred-support'
		));
		
		$data = array(
            'DDL_JS' => array(
                    'cred_help_header' => __('Building your form', 'ddl-layouts'),
					'new_form' => isset($_GET['new_layouts_form']) && $_GET['new_layouts_form']
            ));
		if ($data['DDL_JS']['new_form']) {
			$data['DDL_JS']['new_form_help'] = __('Build the form using HTML and CRED shortcodes. Use the Add Post Fields button to add fields that belong to this post type, or Add Generic Fields to add any other inputs.', 'ddl-layouts');
		}
		
        $this->localize_script('ddl-layouts-cred-support', 'DDLayout_cred_settings', $data);
		
		$this->enqueue_styles(array(
			'ddl-dialogs-forms-css'
		));
		
		
	}

	function enqueue_cell_styles() {

		foreach( $this->cell_factory as $factory ) {

			if ( method_exists( $factory, 'enqueue_editor_styles') ) {
				$factory->enqueue_editor_styles();
			}
		}
		$this->registed_cells->enqueue_cell_styles();
	}

	function preload_scripts(){

		$this->enqueue_scripts( array (
									   'select2',
									   'wp-layouts-colorbox-script',
									   'toolset-utils',
										'ddl_common_scripts',
										'wp-layouts-dialogs-script'
									  )
							  );
	}

	function layout_get_templates_options_object( )
	{
		// Determine which templates support layouts.

		$ret = new stdClass();

        $template_option = $this->get_option('templates');
        if ($template_option) {
            foreach($template_option as $file => $layout) {
                $layout_templates[] = $file;
            }
        }

		$templates = wp_get_theme()->get_page_templates();

		$ret->layout_templates = $this->templates_have_layout( $templates );
		$ret->template_option = $template_option;

		return $ret;
	}

	function templates_have_layout( $templates )
	{

		$layout_templates = array();
		$file_data = false;
		foreach ($templates as $file => $name) {

			if (!in_array($file, $layout_templates)) {
				if( file_exists( get_template_directory() . '/' . $file ) ){
					$file_data = file_get_contents(get_template_directory() . '/' . $file);
				}

				if ( self::is_child_theme() ) {
					// try child theme.
					if( file_exists( get_stylesheet_directory() . '/' . $file ) ){
						$file_data = file_get_contents(get_stylesheet_directory() . '/' . $file);
					}
				}
				if ($file_data !== false) {
					if (strpos($file_data, 'the_ddlayout') !== false) {
						$layout_templates[] = $file;
					}
				}
			}
		}

		return $layout_templates;
	}

    public static function is_child_theme(){
        return get_stylesheet_directory() !== get_template_directory();
    }


    function template_have_layout( $file )
	{

		$bool = false;

		$file_abs = get_template_directory() . '/' . $file;

		if ( file_exists( $file_abs ) ) {
			$file_data = @file_get_contents( $file_abs );
			if ($file_data === false) {
				// try child theme.
				$file_data = @file_get_contents(get_stylesheet_directory() . '/' . $file);
			}
			if ($file_data !== false) {
				if (strpos($file_data, 'the_ddlayout') !== false) {
					$bool = true;
				}
			}
		}

		return $bool;
	}

	function plugin_page_styles() {
		$this->enqueue_styles( array ('toolset-common') );
	}

	function enqueue_cell_scripts() {

		foreach( $this->cell_factory as $factory ) {

			if ( method_exists( $factory, 'enqueue_editor_scripts') ) {
				$factory->enqueue_editor_scripts();
			}
		}

		$this->registed_cells->enqueue_cell_scripts();
	}
	/*
	 * this registers and enqueue those scripts to be used everywhere
	 */
	function register_and_enqueue_global_scripts()
	{
		if( is_admin() )
		{
			$this->enqueue_scripts( array (
					'headjs',
					'ddl_common_scripts'
				)
			);
		}
		$this->enqueue_scripts( 'jquery' );
	}

	function wpddl_init(){

		//check if css options are already set and set defaults if necessary
		// $this->css_settings_init();

		// Check for editor page.
		$this->layouts_editor_page = false;
		if (isset($_GET['page']) and $_GET['page']=='dd_layouts_edit') {
			if(isset($_GET['layout_id']) and $_GET['layout_id']>0){
				$this->layouts_editor_page = true;
			}
		}

		$this->wpddl_register_post_type_for_layouts();

		$this->register_and_enqueue_global_scripts();

		$this->cell_factory = apply_filters('dd_layouts_register_cell_factory', array());
		$this->cell_factory = apply_filters('dd_layouts_de_register_cell_factory', $this->cell_factory);
	}

	function register_dd_layout_cell_type($cell_type, $data) {
		return $this->registed_cells->register_dd_layout_cell_type($cell_type, $data);
	}

	function register_dd_layout_theme_section($theme_section, $args){
		$this->registered_theme_sections->register_dd_layout_theme_section($theme_section, $args);
	}

	function has_theme_sections()
	{
		return sizeof( $this->registered_theme_sections->get_theme_sections() ) > 0;
	}

	function get_current_cell_info() {
		return $this->registed_cells->get_current_cell_info();
	}

	function create_cell($cell_type, $name, $width, $css_class_name = '', $content = null, $cssId = '', $tag = 'div', $unique_id = '') {
		if (isset($this->cell_factory[$cell_type])) {
			return $this->cell_factory[$cell_type]->build($name, $width, $css_class_name, $content, $cssId, $tag, $unique_id);
		}

		return $this->registed_cells->create_cell($cell_type, $name, $width, $css_class_name, $content, $cssId, $tag, $unique_id);
	}

	function get_cell_templates() {
		$templates = '';

		foreach ($this->cell_factory as $cell_type => $factory) {
			$templates .= '<script type="text/html" id="' . $cell_type . '-template">'."\n";
			$templates .= $factory->get_editor_cell_template()."\n";
			$templates .= '</script>'."\n";
		}

		$templates .= $this->registed_cells->get_cell_templates();

		return $templates;
	}

	function get_cell_types() {
		
		global $wpddl_features;
		
		$cell_types = array_keys($this->cell_factory);
		$cell_types = array_merge($cell_types, $this->registed_cells->get_cell_types());
		
		foreach ($cell_types as $index => $cell_type) {
			if ($cell_type == 'cell-post-content' && !$wpddl_features->is_feature('post-content-cell')) {
				unset($cell_types[$index]);
			}
			if ($cell_type == 'post-loop-cell' && !$wpddl_features->is_feature('post-loop-cell')) {
				unset($cell_types[$index]);
			}

		}
		return $cell_types;
	}

	function get_cell_info($cell_type) {
		static $cell_info_cache = array();

		if (!isset($cell_info_cache[$cell_type])) {
			$template['cell-image-url'] = '';
			$template['name'] = '';
			$template['description'] = '';
			$template['button-text'] = '';
			$template['dialog-template'] = '';
			$template['allow-multiple'] = true;

			if (isset($this->cell_factory[$cell_type])) {
				$cell_info_cache[$cell_type] = $this->cell_factory[$cell_type]->get_cell_info($template);
			} else {
				$cell_info_cache[$cell_type] = $this->registed_cells->get_cell_info($cell_type);
			}

			if (!isset($cell_info_cache[$cell_type]['category'])) {
				$cell_info_cache[$cell_type]['category'] = __('Text and media', 'ddl-layouts');
			}

			if (!isset($cell_info_cache[$cell_type]['icon-url'])) {
				$cell_info_cache[$cell_type]['icon-url'] = '';
			}

			if (!$cell_info_cache[$cell_type]['cell-image-url'] && !$cell_info_cache[$cell_type]['icon-url']) {
				$cell_info_cache[$cell_type]['cell-image-url'] = 'icon-circle-blank';
			}

		}

		return $cell_info_cache[$cell_type];
	}

	function get_cell_categories() {
		$categories = array();

		foreach ($this->get_cell_types() as $cell_type) {
			$cell_info = $this->get_cell_info($cell_type);

			$categories[$cell_info['category']] = array('name' => $cell_info['category']);
		}

		return $categories;
	}


	function wpddl_frontent_styles($post_id){
		$styles = get_post_meta($post_id, 'dd_layouts_styles', true);
		echo $styles;
	}


	function dd_layouts_list(){
		$this->listing_page->init();
	}

	function dd_layouts_edit(){
		new WPDD_EDITOR();
	}

	//function dd_layouts_settings(){
	//	include WPDDL_GUI_ABSPATH . 'templates/layout_settings.tpl.php';
	//}

    function theme_has_page_templates(){
        return count( wp_get_theme()->get_page_templates() ) > 0;
    }

    public static function get_theme_name(){
        $current_theme = wp_get_theme();
        $current_theme_name = $current_theme->get( 'Name' );
        return $current_theme_name;
    }

	function wpddl_register_post_type_for_layouts() {
		$labels = array(
			'name' => _x('Layouts', 'post type general name'),
			'singular_name' => _x('Layout', 'post type singular name'),
			'add_new' => _x('Add New', 'book'),
			'add_new_item' => __('Add new layout', 'ddl-layouts'),
			'edit_item' => __('Edit layout', 'ddl-layouts'),
			'new_item' => __('New layout', 'ddl-layouts'),
			'view_item' => __('View layouts', 'ddl-layouts'),
			'search_items' => __('Search layouts', 'ddl-layouts'),
			'not_found' =>  __('No layouts found', 'ddl-layouts'),
			'not_found_in_trash' => __('No layouts found in Trash', 'ddl-layouts'),
			'parent_item_colon' => '',
			'menu_name' => 'Layouts'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => false,
			'show_in_menu' => false,
			'query_var' => false,
			'rewrite' => false,
			'can_export' => false,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => 90,
			'supports' => array('title')
		);
		register_post_type(WPDDL_LAYOUTS_POST_TYPE,$args);
	}

	function does_layout_with_this_name_exist($layout_name) {
		$post_id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_name);
		return $post_id > 0;
	}

	function create_layout_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wp_nonce_create_layout' ) ) {
			$result = array('error' => 'error',
				'error_message' => __('Security check failed', 'ddl-layouts'));
		} else {
			
			// Check for duplicate layout name.

			$layout_name = str_replace('\\\\', '##DDL-SLASH##', $_POST['title']);
			$layout_name = stripslashes_deep($layout_name);
			$layout_name = str_replace('##DDL-SLASH##', '\\\\', $layout_name);
			if ($this->does_layout_with_this_name_exist($layout_name)) {
				$result = array('error' => 'error',
					'error_message' => __('A layout with this name already exists. Please use a different name.', 'ddl-layouts'));

			} else {

                if( isset( $_POST['extras'] ) )
                {
                    $extras = $_POST['extras'];
                    $extras_action = $extras['action'];

                    if( $extras_action === 'one' )
                    {
                        $assign_post = $extras['post_id'];
                    }
                    elseif( $extras_action === 'all' && isset( $extras['post_types'] ) ){
                        $types_to_batch = $extras['post_types'];
                    }
                }

				$parent_post_name = '';
				if ($_POST['layout_parent']) {
					global $wpdb;
					$parent_post_name = WPDD_Layouts_Cache_Singleton::get_name_by_id($_POST['layout_parent']);
				}
				
				if (isset( $_POST['layout_preset']) && $_POST['layout_preset'] ) {
					$layout = $this->load_layout($_POST['layout_preset'], $_POST['layout_type']);
				} else {
					if ( $_POST['layout_parent'] > 0 ){
						if ( $this->get_css_framework() == 'bootstrap-3' || ($this->get_css_framework() == 'bootstrap-2' && $_POST['layout_type'] == 'fluid') ){
							$_POST['columns'] = 12;	
						}
					}
					$layout = $this->create_layout($_POST['columns'], $_POST['layout_type']);
				}
				//When create child layout, get width from parent cell
				if ( $_POST['layout_parent'] > 0 && isset( $_POST['layout_preset']) && $_POST['layout_preset'] ){
					
					$parent_layout = $this->get_layout_from_id( $_POST['layout_parent'] );
					$parent_layout_settings = $this->get_layout_settings($_POST['layout_parent'], true);
					$width = $parent_layout->get_width_of_child_layout_cell();					
					if ( $this->get_css_framework() == 'bootstrap-3' || ($this->get_css_framework() == 'bootstrap-2' && $_POST['layout_type'] == 'fluid') ){
						$width = 12;	
					}					
					if (isset( $_POST['layout_preset']) && $_POST['layout_preset'] ) {
						$layout = $this->load_layout($_POST['layout_preset'], $_POST['layout_type']);
					} else {
						$layout = $this->create_layout($width, $_POST['layout_type']);
					}
					if ($this->get_css_framework() == 'bootstrap-2' && $_POST['layout_type'] == 'fluid'){
						$layout['width'] = 12;	
					}
				}
				
				$layout['type'] = $_POST['layout_type'];
				$layout['cssframework'] = $this->get_css_framework();
				$layout['template'] = '';
				$layout['parent'] = $parent_post_name;
				$layout['name'] = $layout_name;

				$postarr = array(
					'post_title'	=> $layout_name,
					'post_content'	=> '',
					'post_status'	=> 'publish',
					'post_type'	=> WPDDL_LAYOUTS_POST_TYPE
				);
				$post_id = wp_insert_post($postarr);
                // force layout object to take right ID
                $layout_post = get_post( $post_id );
                $layout['id'] = $post_id;
                $layout['slug'] = $layout_post->post_name;

                if( isset($extras_action) && $extras_action === 'all' && isset($types_to_batch) === false )
                {
					if( isset( $_POST['post_types'] ) ){
						$post_types = count( $_POST['post_types'] ) === 0 ? array() : array_unique( $_POST['post_types'] );
					} else{
						$post_types = array();
					}

                    $this->post_types_manager->handle_post_type_data_save( $post_id, $post_types );
                }
                else if( isset($extras_action) && $extras_action === 'all' && isset($types_to_batch) )
                {
                    $post_types = count( $_POST['post_types'] ) === 0 ? array() : array_unique( $_POST['post_types'] );

                    $this->post_types_manager->handle_set_option_and_bulk_at_once( $post_id, $post_types, $types_to_batch );

                }
                else if( isset($extras_action) && $extras_action === 'one' && isset($assign_post) )
                {
                    $this->post_types_manager->update_post_meta_for_post_type( array($assign_post), $post_id );
                }
				
				//Set default parent
				if( isset($_POST['save_parent']) && $_POST['save_parent'] == 1 )
				{
                    $this->parents_options->update_options( self::PARENTS_OPTIONS, $_POST['layout_parent'] );
				}
				


                WPDD_Layouts::save_layout_settings( $post_id, $layout );

				$result['id'] = $post_id;
			}

		}

        die( wp_json_encode( $result ) );
    }

	function create_layout($width, $type) {
		$layout = new WPDD_layout($width);
		$row = new WPDD_layout_row('1', '', '', $type);
		for ($i = 0; $i < $width; $i++) {
			$cell = new WPDD_layout_spacer('', 1);
			$row->add_cell($cell);
		}
		$layout->add_row($row);

		$layout = $layout->get_as_array();
		$layout['width'] = $width;

		return $layout;
	}

	function ddl_dismiss_template_message() {
		$nonce = $_POST["wpnonce"];

		if ( wp_verify_nonce( $nonce, 'wp_nonce_ddl_dismiss' ) ) {
			$this->save_option(array('dismiss_layout_message' => true));
		}

		die();
	}

	public function load_layout($preset_file, $layout_type = null)
	{

		$layout_json = file_get_contents($preset_file);

		$layout = json_decode(str_replace('\\\"', '\"' , $layout_json), true);

		if ($layout_type) {
			$layout['type'] = $layout_type;
			for ($i = 0; $i < sizeof($layout['Rows']); $i++) {
				$layout['Rows'][$i]['layout_type'] = $layout_type;
				$layout['Rows'][$i]['cssClass'] = 'row-' . $layout_type;

			}
		}

		return $layout;

	}

	function load_frontend_js() {
		$this->enqueue_scripts('ddl-layouts-frontend');
		$this->localize_script('ddl-layouts-frontend', 'DDLayout_fe_settings', array(
				'DDL_JS' => array(
					'css_framework' => $this->get_css_framework(),
					'DEBUG' => WPDDL_DEBUG,
				)
			)
		);
	}

	function load_frontend_css()
	{
		$this->enqueue_styles('menu-cells-front-end');
        $this->enqueue_styles('ddl-front-end');
	}



	public static function get_layout_settings( $post_id, $as_array = false, $clear_cache = false ) {
		static $layouts_raw = array();
		static $layouts_decoded = array();

		if (!isset($layouts_raw[$post_id]) || $clear_cache) {
			$layouts_raw[$post_id] = get_post_meta($post_id, WPDDL_LAYOUTS_SETTINGS, true);
		}

		if ($as_array && (!isset($layouts_decoded[$post_id]) || $clear_cache) ) {

			if ($layouts_raw[$post_id]) {
				$layouts_decoded[$post_id] = json_decode(  $layouts_raw[$post_id]  );
			} else {
				$layouts_decoded[$post_id] = null;
			}
		}

		if ($as_array) {
			return $layouts_decoded[$post_id];
		} else {
			return $layouts_raw[$post_id];
		}
	}

    // I added this 'cause in ajax calls after saving the static property of self::get_layout_settings
    // is not updated so the settings you get are outdated
    public static function get_layout_settings_raw_not_cached( $layout_id, $as_array = true ) {
        $settings = get_post_meta($layout_id, WPDDL_LAYOUTS_SETTINGS, $as_array);
        if( $as_array ) {
            return json_decode( $settings );
        }else{
            return $settings;
        }
    }

    public static function get_layout_json_settings_encoded_64( $post_id, $cached = true )
    {
        $json = $cached ? self::get_layout_settings($post_id, false ) : self::get_layout_settings_raw_not_cached($post_id, false);

        return base64_encode( $json );
    }

	public static function save_layout_settings( $post_id, $settings )
	{
		if (is_string($settings)) {
			$settings = wp_json_encode( json_decode($settings, true) );
		} else if (is_array($settings) || is_object($settings)) {
			$settings = wp_json_encode( (array)$settings );
		}
		
		$layouts_raw = WPDD_Layouts::get_layout_settings( $post_id, false );

		$result = update_post_meta( $post_id, WPDDL_LAYOUTS_SETTINGS, addslashes( $settings ), $layouts_raw );

		// clear the cache.
		$layouts_raw = WPDD_Layouts::get_layout_settings( $post_id, false, true );
		
		return $result;
	}

	public static function get_layout_parent($id, $layout_search = false )
	{
		global $wpdb;

		$layout = $layout_search ? $layout_search : self::get_layout_settings($id, true);
		$parent = $layout->parent;

		if (!empty( $parent )) {
			$parent = WPDD_Layouts_Cache_Singleton::get_id_by_name($parent);
		}
		if (!$parent) {
			$parent = 0;
		}

		return $parent;
	}

	function get_layout ( $layout_name ) {
		global $wpdb;

		$layout = $result = null;

		if ( empty($layout_name) ) {
			return $layout_name;
		}

		$id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_name);

		if ( $id ) {
			$result = new stdClass();
			$result->ID = $id;
			$result->post_name = $layout_name;
		}

		if ($result) {
			$layout_json = self::get_layout_settings($result->ID);

			$json_parser = new WPDD_json2layout();
			$layout = $json_parser->json_decode($layout_json);
			$layout->set_post_id($result->ID);
			$layout->set_post_slug($result->post_name);
		}

		return $layout;
	}

	function get_layout_from_id ( $id ) {
		global $wpdb;

		$layout = WPDD_Layouts_Cache_Singleton::get_name_by_id($id);
		$result = null;

		if ($layout) {
			$result = new stdClass();
			$result->ID = $id;
			$result->post_name = $layout;
		}
		if ($result) {
			$layout_json = self::get_layout_settings($result->ID);

			$json_parser = new WPDD_json2layout();
			$layout = $json_parser->json_decode($layout_json);
			$layout->set_post_id($result->ID);
			$layout->set_post_slug($result->post_name);
		}

		return $layout;

	}

    function get_layout_slug_for_post_object( $post_id )
    {
        $meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

        if( !$meta  ) return null;

        return $meta;
    }

	function get_available_parent_layouts () {
		static $layouts = null;

		if ($layouts === null) {
			global $wpdb;

			$layouts = array();
			$post_ids =  $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish'", WPDDL_LAYOUTS_POST_TYPE));
			foreach ($post_ids as $post_id) {
				$layout = self::get_layout_settings_raw_not_cached($post_id, true);
				if ($layout && isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {
					$parent = $this->get_layout_parent($post_id);
					if (!isset($layouts[$parent])) {
						$layouts[$parent] = array();
					}
					$layouts[$parent][] = $post_id;
				}
			}
		}

		return $layouts;
	}
	
	function get_layout_list() {
		global $wpdb;
		
		$results =  $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s", WPDDL_LAYOUTS_POST_TYPE));
		
		return $results;
	}

	function save_option($option) {
		$options = get_option('ddlayout_settings');
		if (!$options) {
			$options = array();
		}
		$options = array_merge($options, $option);
		update_option('ddlayout_settings', $options);
	}

	function get_option($option, $default = false) {
		$options = get_option('ddlayout_settings');
		if ($options && isset($options[$option])) {
			return $options[$option];
		} else {
			return $default;
		}
	}


	function record_render_error($data) {
        WPDD_Layouts_RenderManager::getInstance()->record_render_error( $data );
	}


	function layout_type_selector($name) {
		global $wpddl_features;

		?>


		<?php

	}

    public static function get_layout_id_by_slug( $slug )
    {
        return WPDD_Layouts_Cache_Singleton::get_id_by_name($slug);
    }

	function get_layout_css()
	{
		return $this->css_manager->get_layouts_css();
	}

    function get_where_used( $layout_id,
                             $slug = false,
                             $group = false,
                             $posts_per_page = -1,
                             $post_status = array('publish'),
                             $output = 'default',
                             $post_types = 'any',
                             $suppress_filters = true
                            )
    {
        // Get layout php object if $slug parameter is not provided
        if( $slug === false )
        {
            $layout = $this->get_layout_from_id( $layout_id );
            // if we're not able to get layout php object stop execution
            if( is_object( $layout ) === false && method_exists($layout,'get_post_slug') === false ){
                return null;
            }
            $slug = $layout->get_post_slug();
        }

        // We now include attachments
        /* */
        // don't include attachments
        $a = get_post_type_object( 'attachment' );
        $a->exclude_from_search = true;
        /* */
        
        $post_type = !$post_types ? 'any' : $post_types;

        $args = array(
            'posts_per_page' => $posts_per_page,
            'post_type' => $post_type,
            // get only published posts
            'post_status' => $post_status,
            //don't perform found posts query
            'no_found_rows' => false,
            // leave the terms alone we don't need them
            'update_post_term_cache' => false,
            // leave the meta alone we don't need them
            'update_post_meta_cache' => false,
            // don't cache results
            'cache_results'          => false,
            'suppress_filters' => $suppress_filters,
            'meta_query' => array (
                array (
                    'key' => WPDDL_LAYOUTS_META_KEY,
                    'value' => $slug,
                    'compare' => '=',
                )
            ) );

        // set the output type, by default all posts, only ids in case we want to save memory
        $args['fields'] = $output;

        $new_query = new WP_Query( $args );

        if( $group === true )
        {
            add_filter('posts_orderby', array(&$this, 'order_by_post_type'), 10, 2);
            $new_query->group_posts_by_type = $group;
        }

        $posts = $new_query->posts;
        $this->set_where_used_count( $new_query->found_posts );
        // remove any reference to the query
        $new_query = null;
        
        // This is not needed anymore
        /* */
        // reset attachments to original status
        $a->exclude_from_search = false;
        /* */
        
        // flush cache to release memory
        wp_cache_flush();

        return $posts;
    }

    function set_where_used_count( $count ){
    	$this->where_used_count = intval($count);
    }

    function get_where_used_count(){
    	return $this->where_used_count;
    }

    function order_by_post_type($orderby, $query) {
        global $wpdb;
        if ( property_exists($query, 'group_posts_by_type') &&  $query->group_posts_by_type === true) {
            unset( $query->group_posts_by_type );
            $orderby = $wpdb->posts . '.post_type ASC';
        }
        // provide a default fallback return if the above condition is not true
        return $orderby;
    }

	public static function get_layout_children($id)
	{
		global $wpdb;

		if (!$id) return null;

		$layout = self::get_layout_settings($_GET['layout_id'], true);

		$children = array();

		if ($layout && isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {
			$layout_slug = $layout->slug;

			$post_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s", WPDDL_LAYOUTS_POST_TYPE));
			foreach ($post_ids as $post_id) {
				$layout = self::get_layout_settings($post_id, true);
				if ($layout) {
					if ( property_exists ( $layout , 'parent' ) && $layout->parent == $layout_slug) {
						$children[] = $post_id;
					}
				}
			}
		}

		return $children;
	}

    // maybe these are not needed
    public function get_layout_content_for_render( $layout, $args ){
            return WPDD_Layouts_RenderManager::getInstance( )->get_layout_content_for_render( $layout, $args );
    }

    public function get_queried_object(){
        return WPDD_Layouts_RenderManager::getInstance()->get_queried_object();
    }

    public function get_query_post_if_any( $queried_object){
        return WPDD_Layouts_RenderManager::getInstance()->get_query_post_if_any( $queried_object);
    }

    public static function flattenArray( $array ){
        $ret_array = array();

        if( is_array($array) )
        {
            foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $value)
            {
                $ret_array[] = $value;
            }
        }
        else
        {
            $ret_array = array('error' => __( sprintf('Argument should be an array %s', __METHOD__), 'wpv-views') );
        }

        return $ret_array;
    }
	
	private function fix_up_views_slugs () {
		global $wpdb;
		
		$fixed = $this->get_option('views_and_template_slugs_fixed_0.9.2');
		
		if (!$fixed) {
			
			// From 0.9.2 we're using the View ID instead of the slug
			// We need to check all layouts and update them as required.
			$layout_tempates_available = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM {$wpdb->posts} WHERE post_type=%s", WPDDL_LAYOUTS_POST_TYPE ));
			foreach ($layout_tempates_available as $template) {

				$layout = self::get_layout_settings($template->ID);
				$found = false;
				
				if (preg_match_all('/"ddl_layout_view_slug":"(.*?)"/', $layout, $matches)) {
					$found = true;
					for ($i = 0; $i < sizeof($matches[0]); $i++) {
						$slug = $matches[1][$i];
						$id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = '%s' AND post_type='view'", $slug) );
						if ($id > 0) {
							$new = '"ddl_layout_view_id":"' . $id . '"';
							$layout = str_replace($matches[0][$i], $new, $layout);
						}
					}
					
				}

				if (preg_match_all('/"view_template":"(.*?)"/', $layout, $matches)) {
					$found = true;
					for ($i = 0; $i < sizeof($matches[0]); $i++) {
						$slug = $matches[1][$i];
						$id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = '%s' AND post_type='view-template'", $slug) );
						if ($id > 0) {
							$new = '"ddl_view_template_id":"' . $id . '"';
							$layout = str_replace($matches[0][$i], $new, $layout);
						}
					}
					
				}
				
				if ($found) {
					self::save_layout_settings($template->ID, $layout);
				}
				
			}
			
			$this->save_option(array('views_and_template_slugs_fixed_0.9.2' => true));
			
		}
		
	}

    public static function get_post_ID_by_slug( $slug, $post_type = 'post' )
    {
        global $wpdb;

        if( !$slug ) {
            return null;
        }

        if ( WPDDL_LAYOUTS_POST_TYPE == $post_type ) {
            return WPDD_Layouts_Cache_Singleton::get_id_by_name($slug);
        }

        return $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s", $post_type, $slug) );
    }


    public static function get_post_property_from_ID( $id, $property = 'post_name' )
    {
        if( is_nan($id) || !$id ) return null;

        $post = get_post($id);

        if( is_object($post) === false ) return null;

        if( get_class( $post ) !== 'WP_Post' ) return null;

        return $post->{$property};
    }
	
	/**
     * add types configuration to debug
     */

    function add_extra_debug_information($extra_debug)
    {
        $extra_debug['layouts'] = $this->frameworks_options_manager->get_options();
        return $extra_debug;
    }

	function register_strings_for_translation($layout_id) {
		
		$layout = $this->get_layout_from_id($layout_id);
		$layout->register_strings_for_translation();
		
	}
	
	function register_all_strings_for_translation( ) {
		global $wpdb;
		
		$layouts = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE));
		foreach ($layouts as $layout_id) {
			$this->register_strings_for_translation($layout_id);
		}
	}

    /**
     * @deprecated since version 1.2
     */
    function print_layout_edit_link( $wp_admin_bar )
    {
        if( $this->rendered_layout_id === null ) return;

        $layout = $this->get_layout_from_id( $this->rendered_layout_id );

        if( !$layout ) return;

        if( current_user_can( 'activate_plugins' ) === false ) return;

        $href = sprintf('%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), $layout->get_post_id()  );

        $args = array(
            'id'    => 'edit_layout',
            'title' => __('Edit layout', 'ddl-layouts'),
            'href' => $href,
            'meta' => array( 'class' => 'layouts-edit-link' ),
        );
        $wp_admin_bar->add_menu( $args );
    }

	public static function get_available_parents(){
		
		$defaults = array(
			'post_type' => WPDDL_LAYOUTS_POST_TYPE,
			'suppress_filters' => true,
			'order' => 'DESC',
			'orderby' => 'post_title',
			'post_status' => 'publish',
			'posts_per_page' => -1
		);
		$layouts = get_posts( $defaults );
		$parents = array();
		foreach ( $layouts as $layout_post ):
			$layout_attr = self::get_layout_settings( $layout_post->ID, true);
			if ( is_object($layout_attr) && property_exists($layout_attr, 'has_child') && $layout_attr->has_child ){
				$layout_post->layout_attr = $layout_attr;
				$parents[] = $layout_post;
			}
		endforeach;
		
		return $parents;	
	}

    public function set_registered_cells( $cell ){
        $bool = true;

        foreach( $this->registered_cells as $existing )
        {
            if( $existing->get_cell_type() === $cell->get_cell_type() ){
                $bool = false;
                break;
            }
        }

        if( $bool ){
            $this->registered_cells[] = $cell;
        }
    }

    public function get_registered_cells(){
        return $this->registered_cells;
    }

    public function get_rendered_layout_id(){
        return $this->rendered_layout_id;
    }

	function set_up_cell_fields_by_id( $cell_id, $layout_id, $args = array() ){

		$layout = WPDD_Layouts_RenderManager::getInstance()->get_rendered_layout( $layout_id );

		if( $layout instanceof WPDD_layout == false ) return;

		$cell = $layout->get_cell_by_id( $cell_id );

		if( $cell instanceof WPDD_layout_element === false ) return;

		$target = WPDD_Layouts_RenderManager::getInstance()->get_layout_renderer( $layout, $args );

		if( $target instanceof WPDD_layout_render === false ) return;

		$content = $cell->get_translated_content( $target->get_context() );

		global $ddl_fields_api;
		$ddl_fields_api->set_current_cell_content($content);
	}
	
	function is_layout ( $post_id ) {
		global $wpdb;
		
		return $wpdb->get_var( $wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", $post_id)) == WPDDL_LAYOUTS_POST_TYPE;
	}
	
    function before_delete_post_action( $post_id ) {
		if ($this->is_layout($post_id)) {
            $package_name = $post_id;
            $package_kind = 'layout';
            do_action( 'wpml_delete_package', $package_name, $package_kind );
        }
    }
    
    /**
     * Assign the attachment post type layout to new attachment post
     * @param string $attachment_id
     */
    function add_attachment_action( $attachment_id ) {
        
        // Extract attachment post type layout
        $layout_object = $this->post_types_manager->get_layout_to_type_object( 'attachment' );
        if ( null === $layout_object ) {
            return;
        }
        $layout_id = $layout_object->layout_id;
        
        // WPDD_Layouts_PostTypesManager::set_layout_for_post_type_meta_callback() alike
        $posts = array( $attachment_id );
        $this->post_types_manager->update_post_meta_for_post_type( $posts, $layout_id );
        $this->post_types_manager->track_batched_post_types( 'attachment', $layout_id );
    }

    public function import_layouts_from_theme( $source_dir, $overwrite_assignment = false ){
        return WPDD_Layouts_Theme::getInstance()->import_layouts_from_theme( $source_dir, $overwrite_assignment );
    }
    
    public function enqueue_toolset_common_styles() {
        $this->enqueue_styles( array( 'toolset-common' ) );
    }
}