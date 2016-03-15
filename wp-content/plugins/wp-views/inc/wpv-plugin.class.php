<?php

require WPV_PATH_EMBEDDED . '/inc/wpv.class.php';
require_once( WPV_PATH . '/inc/filters/editor-addon-parametric.class.php');

class WP_Views_plugin extends WP_Views {

    function init() {
        add_filter( 'custom_menu_order', array($this, 'enable_custom_menu_order' ));
        add_filter( 'menu_order', array($this, 'custom_menu_order' )); // @todo I really feel this is not used anymore

        global $wp_version;
        if (version_compare($wp_version, '3.3', '>=')) {
            // ct-editor-deprecated
            add_action('admin_head-edit.php', array($this, 'admin_add_help')); // TODO review if this is needed here anymore, maybe for Content Templates
            add_action('admin_head-post.php', array($this, 'admin_add_help')); // TODO review if this is needed here anymore, maybe for Content Templates
            add_action('admin_head-post-new.php', array($this, 'admin_add_help')); // TODO review if this is needed here anymore, maybe for Content Templates

            add_action('admin_head', array($this, 'admin_add_help')); // TODO check in what page we are inside that method
        }
        // ct-editor-deprecated
        add_action('admin_head-post.php', array($this, 'admin_add_errors')); // TODO review if this is needed here anymore, maybe for Content Templates
        add_action('admin_head-post-new.php', array($this, 'admin_add_errors')); // TODO review if this is needed here anymore, maybe for Content Templates

        parent::init();

        add_action('wp_ajax_wpv_get_types_field_name', array($this, 'wpv_ajax_wpv_get_types_field_name')); // Maybe deprecated
        add_action('wp_ajax_wpv_get_taxonomy_name', array($this, 'wpv_ajax_wpv_get_taxonomy_name')); // Maybe deprecated
		
		// Actions to display buttons in edit screen textareas
		add_action( 'wpv_views_fields_button', array( $this, 'add_views_fields_button' ), 10, 2 );
		add_action( 'wpv_cred_forms_button', array( $this, 'add_cred_forms_button' ) );

        if(is_admin()){
			add_action( 'admin_enqueue_scripts', array( $this,'wpv_admin_enqueue_scripts' ) );
			$this->view_parametric_create();
		}
        
        /**
        * Add hooks for backend Module Manager Integration
        */
        if ( defined( 'MODMAN_PLUGIN_NAME' ) ) {
			// Keep the part about registering elements in the plugin version
            add_filter( 'wpmodules_register_items_' . _VIEWS_MODULE_MANAGER_KEY_, array( $this, 'register_modules_views_items' ), 30, 1 );
            add_filter( 'wpmodules_register_items_' . _VIEW_TEMPLATES_MODULE_MANAGER_KEY_, array( $this, 'register_modules_view_templates_items' ), 20, 1 );
			// Add the section to Views and WPA edit pages
            add_action( 'view-editor-section-extra', array( $this, 'add_view_module_manager_section' ), 20, 2 );
        }

        /**
         * add some debug information
         */
        add_filter( 'icl_get_extra_debug_info', array( $this, 'add_config_to_toolset_extra_debug' ) );
    }

    /**
     * add extra debug information
     */
    public function add_config_to_toolset_extra_debug( $extra_debug ) {
        global $WPV_settings;
        $extra_debug['views'] = $WPV_settings->get();
        return $extra_debug;
    }

    function register_modules_views_items( $items ) {
        $views = $this->get_views();
        foreach ( $views as $view ) {
			$summary = '';
			$view_settings = $this->get_view_settings( $view->ID );
			if ( ! isset( $view_settings['view-query-mode'] ) ) { // Old views may not have this setting
				$view_settings['view-query-mode'] = 'normal';
			}
			switch ( $view_settings['view-query-mode'] ) {
				case 'normal':
					$summary .= '<h5>' . __('Content to load', 'wpv-views') . '</h5><p>' . apply_filters('wpv-view-get-content-summary', $summary, $view->ID, $view_settings) .'</p>';
					$summary .= '<h5>' . __('Filter', 'wpv-views') . '</h5>';
					$summary .= wpv_create_summary_for_listing( $view->ID );
					break;
				case 'archive':
				case 'layout-loop':
					$summary .= '<h5>' . __('Content to load', 'wpv-views') . '</h5><p>'. __('This View displays results for an <strong>existing WordPress query</strong>', 'wpv-views') . '</p>';
					break;
			}
            $items[] = array(
                'id' => _VIEWS_MODULE_MANAGER_KEY_ . $view->ID,
                'title' => $view->post_title,
                'details' => '<div style="padding:0 5px 5px;">' . $summary . '</div>'
            );
        }
        return $items;
    }

    function register_modules_view_templates_items( $items ) {
        global $WPV_settings;
        $viewtemplates = $this->get_view_templates();
        foreach ( $viewtemplates as $view ) {
			$summary = '';
			$used_as = wpv_get_view_template_defaults( $WPV_settings, $view->ID );
			if ($used_as != '<div class="view_template_default_box"></div>') {
				$summary .= '<h5>' . __('How this Content Template is used', 'wpv-views') . '</h5><p>' . $used_as . '</p>';
			}
			$fields_used = wpv_get_view_template_fields_list( $view->ID );
			if ($fields_used != '<div class="view_template_fields_box"></div>') {
				$summary .= '<h5>' . __('Fields used', 'wpv-views') . '</h5><p>' . $fields_used . '</p>';
			}
			if ( '' == $summary ) {
				$summary = '<p>' . __('Content template', 'wpv-views') . '</p>';
			}
            $items[] = array(
                'id' => _VIEW_TEMPLATES_MODULE_MANAGER_KEY_ . $view->ID,
                'title' => $view->post_title,
                'details' => '<div style="padding:0 5px 5px;">' . $summary . '</div>'
            );
        }
        return $items;
    }

	/**
     * view_parametric_create function.
     *
     * @access public
     * @return void
     */
    function view_parametric_create() {

		$this->add_parametric = new Editor_addon_parametric (
            'parametric_filter_create',
			__('New filter', 'wpv-views'),
			WPV_URL . '/res/js/redisign/views_parametric.js',
			false,
            false,
            'icon-filter-mod'
        );

	    $this->edit_parametric = new Editor_addon_parametric (
            'parametric_filter_edit',
	    	__('Edit filter', 'wpv-views'),
	    	WPV_URL . '/res/js/redisign/views_parametric.js',
	    	false,
            false,
            'icon-edit'
        );

    }

    /**
     * Creates the Views and Fields button for edit pages textareas.
     *
     * @param $textarea (string)
	 * @param $menus (array) Optional. Allows for custom set the menus that will be available. Since 1.9
     *
     * @since 1.7
     */
	function add_views_fields_button( $textarea, $menus = array() ) {
		echo '<li class="wpv-vicon-codemirror-button">';
		wpv_add_v_icon_to_codemirror( $textarea, $menus );
		echo '</li>';
	}

	/**
	* add_cred_forms_button
	*
	* Creates a button for CRED forms when needd, in edit pages textareas
	*
	* @param $textarea (string)
	*
	* @return string Echo button
	*
	* @since 1.7
	*/
	function add_cred_forms_button( $textarea ) {
		$return = '';
		// This filter is only used by CRED to generate its button HTML
		$button = apply_filters( 'wpv_meta_html_add_form_button', '', '#' . $textarea );
		if ( ! empty( $button ) ) {
			$return .= '<li>' . $button . '</li>';
		}
		echo $return;
	}

    function enable_custom_menu_order($menu_ord) {
        return true;
    }

    function custom_menu_order( $menu_ord ) {
        $types_index = array_search('wpcf', $menu_ord);
        $views_index = array_search('edit.php?post_type=view', $menu_ord);

        if ($types_index !== false && $views_index !== false) {
            // put the types menu above the views menu.
            unset($menu_ord[$types_index]);
            $menu_ord = array_values($menu_ord);
            array_splice($menu_ord, $views_index, 0, 'wpcf');
        }

        return $menu_ord;
    }

    function is_embedded() {
        return false;
    }


    function admin_menu() {
		parent::admin_menu();
		global $pagenow;
		$cap = 'manage_options';
        $page = wpv_getget( 'page' );

		add_menu_page(__('Views', 'wpv-views'), __('Views', 'wpv-views'), $cap, 'views', 'wpv_admin_menu_views_listing_page', 'none');

        add_submenu_page( 'views', __('Views', 'wpv-views'), __('Views', 'wpv-views'), $cap, 'views', 'wpv_admin_menu_views_listing_page');

        if ( 'views-editor' == $page ) {
			add_submenu_page( 'views', __( 'Edit View', 'wpv-views' ), __( 'Edit View', 'wpv-views' ), $cap, 'views-editor', 'views_redesign_html');
			add_filter( 'screen_options_show_screen', '__return_false', 99 );
		}

        add_submenu_page( 'views', __('Content Templates', 'wpv-views'), __('Content Templates', 'wpv-views'), $cap, 'view-templates', 'wpv_admin_menu_content_templates_listing_page');

        // Add New Content Template menu when we're about to show this page.
        // ct-editor-deprecate
        if ( ( 'post-new.php' == $pagenow ) && ( wpv_getget( 'post_type' ) == 'view-template' ) ) {
			add_submenu_page( 'views',
                __( 'New Content Template', 'wpv-views'),
                __( 'New Content Template', 'wpv-views'),
                $cap,
                'post-new.php?post_type=view-template');
		}


        // Edit Content Template page
        if( ( 'admin.php' == $pagenow ) && ( WPV_CT_EDITOR_PAGE_NAME == $page ) ) {
            $edit_content_template_label = __( 'Edit Content Template', 'wpv-views' );
            add_submenu_page( 'views', $edit_content_template_label, $edit_content_template_label, $cap,
                WPV_CT_EDITOR_PAGE_NAME, 'wpv_ct_editor_page'
            );
			add_filter( 'screen_options_show_screen', '__return_false', 99 );
        }


		add_submenu_page( 'views', __('WordPress Archives', 'wpv-views'), __('WordPress Archives', 'wpv-views'), $cap, 'view-archives', 'wpv_admin_archive_listing_page');

        if ( 'view-archives-editor' == $page ) {
			add_submenu_page( 'views', __( 'Edit WordPress Archive', 'wpv-views' ), __( 'Edit WordPress Archive', 'wpv-views' ), $cap, 'view-archives-editor', 'views_archive_redesign_html');
			add_filter( 'screen_options_show_screen', '__return_false', 99 );
		}

        global $WPV_settings;
		add_submenu_page( 'views', __( 'Settings', 'wpv-views' ), __( 'Settings', 'wpv-views' ), $cap, 'views-settings', array( $WPV_settings, 'wpv_settings_admin' ) );
        add_submenu_page( 'views', __('Import/Export', 'wpv-views'), __('Import/Export', 'wpv-views'), $cap, 'views-import-export', 'wpv_admin_menu_import_export');
		add_submenu_page( 'views', __('Help', 'wpv-views'), __('Help', 'wpv-views'), $cap, WPV_FOLDER . '/menu/help.php', null );

		if ( 'views-debug-information' == $page ) {
			add_submenu_page( 'views', __( 'Debug information', 'wpv-views' ), __( 'Debug information', 'wpv-views' ), $cap, 'views-debug-information', array( $this, 'debug_page' ) );
		}

		// create a new submenu for specific update routines
		if ( 'views-update-help' == $page && function_exists( 'views_update_help_wpv_if' ) ) {
			add_submenu_page( 'views', __( 'Update changes', 'wpv-views' ), __( 'Update changes', 'wpv-views' ), $cap, 'views-update-help', 'views_update_help');
		}

        // Fake menu. Toolbar create a new X link
        $this->add_views_admin_create_ct_or_wpa_auto();
    }
    
    public function add_views_admin_create_ct_or_wpa_auto() {
        $parent_slug = 'options.php'; // Invisible. See WordPress documentation. todo add link
        $page_title = __( 'Create a new Template', 'wpv-views' );
        $menu_title = __( 'Create a new Template', 'wpv-views' );
        $capability = 'manage_options';
        $menu_slug = 'views_create_auto';
        $function = array( $this, 'create_ct_or_wpa_auto' );
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    }
    
    /**
     * Creates a Content Template for WordPress Archive for a Post Type, 
     * Taxonomy or WordPress Page with default settings and assigns it to the
     * right item.
     * 
     * Used by Toolset_Admin_Bar_Menu.
     * 
     * Expected $_GET parameters
     * type: post type, taxonomy or special wordpress archive
     * class: is it an archive page or a content template page
     * post: post_id or empty
     * 
     */
    public function create_ct_or_wpa_auto() {
        
        // verify permissions
        if( ! current_user_can( 'manage_options' ) ) {
            die( __( 'Untrusted user', 'wpv-views' ) );
        }
        
        // verify nonce
        check_admin_referer( 'create_auto' );
        
        // validate parameters
        $b_type = isset( $_GET['type'] ) && preg_match( '/^([-a-z0-9_]+)$/', $_GET['type'] );
        $b_class = isset( $_GET['class'] ) && preg_match( '/^(archive|page)$/', $_GET['class'] );
        $b_post_id = isset( $_GET['post'] ) && (int) $_GET['post'] >= 0;

        // validate request
        if( ! ( $b_type && $b_class && $b_post_id ) ) {
            die( __( 'Invalid parameters', 'wpv-views' ) );
        }
        
        // get parameters
        $type = $_GET['type'];
        $class = $_GET['class'];
        $post_id = (int) $_GET['post'];
        
        // enforce rules
        $b_page_archive = 'page' === $type && 'archive' === $class;
        $b_404 = '404' === $type;
        if( $b_page_archive || $b_404 ) {
            die( __( 'Not allowed', 'wpv-views' ) );
        }
        
        // prepare processing
        if( $post_id === 0 ) {
            $post_id = null;
        }
        
        $wpa_id = 0;
        $ct_id = 0;
        
        global $WPV_settings;
        global $toolset_admin_bar_menu;
        $post_title = $toolset_admin_bar_menu->get_name_auto( 'views', $type, $class, $post_id );
        $title = sanitize_text_field( $post_title );
        
        $taxonomy = get_taxonomy( $type );
        $is_tax = $taxonomy !== false;

        $post_type_object = get_post_type_object( $type );
        $is_cpt = $post_type_object != null;
        
        // route request
        if( 'archive' === $class ) {
            
            // Create a new WordPress Archive
            global $wpdb, $WPV_view_archive_loop;
            
            // Is there another WordPress Archive with the same name?
            $already_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                    WHERE ( post_title = %s OR post_name = %s ) 
                    AND post_type = 'view' 
                    LIMIT 1",
                    $title,
                    $title
                )
            );
            if( $already_exists ) {
                die( __( 'A WordPress Archive with that name already exists. Please use another name.', 'wpv-views' ) );
            }
            
            $args = array(
                'post_title'    => $title,
                'post_type'      => 'view',
                'post_content'  => "[wpv-layout-meta-html]",
                'post_status'   => 'publish',
                'post_author'   => get_current_user_id(),
                'comment_status' => 'closed'
            );
            $wpa_id = wp_insert_post( $args );
            $wpa_type = '';
            
            if( in_array( $type, Toolset_Admin_Bar_Menu::$default_wordpress_archives )  ) {
                
                // Create a new WordPress Archive for X archives
                
                /* assign WordPress Archive to X archives */
                $wpa_type = sprintf( 'wpv-view-loop-%s-page', $type );
                
            } else if( $is_tax ) {
                
                // Create a new WordPress Archive for Ys
                
                /* assign WordPress Archive to Y */
                $wpa_type = sprintf( 'wpv-view-taxonomy-loop-%s', $type );
                
            } else if( $is_cpt ) {
                
                // Create a new WordPress Archive for Zs
                
                /* assign WordPress Archive to Z */
                $wpa_type = sprintf( 'wpv-view-loop-cpt_%s', $type );
                
            } else {
                die( __( 'An unexpected error happened.', 'wpv-views' ) );
            }
            
            $archive_defaults = wpv_wordpress_archives_defaults( 'view_settings' );
            $archive_layout_defaults = wpv_wordpress_archives_defaults( 'view_layout_settings' );
            update_post_meta( $wpa_id, '_wpv_settings', $archive_defaults );
            update_post_meta( $wpa_id, '_wpv_layout_settings', $archive_layout_defaults );
            
            $data = array( $wpa_type => 'on' );
            $WPV_view_archive_loop->update_view_archive_settings( $wpa_id, $data );
            
        } else if( 'page' === $class ) {
            
            // Create a new Content Template
            $create_template = wpv_create_content_template( $title, '', true, '' );
            if ( isset( $create_template['error'] ) ) {
                die( __( 'A Content Template with that name already exists. Please use another name.', 'wpv-views' ) );
            }
            
            if( ! isset( $create_template['success'] ) || (int) $create_template['success'] == 0 ) {
                die( __( 'An unexpected error happened.', 'wpv-views' ) );
            }
            
            $ct_id = $create_template['success'];
            $ct_type = '';
            
            if( 'page' === $type ) {
                
                // Create a new Content Template for 'Page Title'
                
                /* assign Content Template to Page */
                update_post_meta( $post_id, '_views_template', $ct_id );
                
            } else if( $is_cpt ) {
                
                // Create a new Content Template for Ys
                
                /* assign Content Template to Y */
                $ct_type = sanitize_text_field( sprintf( 'views_template_for_%s', $type ) );
                $WPV_settings[$ct_type] = $ct_id;
                
            } else {
                die( __( 'An unexpected error happened.', 'wpv-views' ) );
            }
            
        }
        
        // update changes
        $WPV_settings->save();
        
        // redirect to editor or die
        $template_id = max( array( $wpa_id, $ct_id ) );
        
        if( $template_id === 0 ) {
            die( __( 'Unexpected error. Nothing was changed.', 'wpv-views' ) );
        }
        
        // redirect to editor (headers already sent)
        $edit_link = $toolset_admin_bar_menu->get_edit_link( 'views', false, $type, $class, $template_id );
        $exit_string = '<script type="text/javascript">'.'window.location = "' . $edit_link . '";'.'</script>';
        exit( $exit_string );
    }

    /**
     * debug page
     */
    public function debug_page()
    {
        require_once WPV_PATH_EMBEDDED . '/common/debug/debug-information.php';
    }

    function settings_box_load(){
    // DEPRECATED, check Module Manager, maybe needed in Content Templates
    // ct-editor-deprecated
        
		global $pagenow;
        if (defined('MODMAN_PLUGIN_NAME') && 'post-new.php'!=$pagenow)
        {
            // module manager sidebar meta box
            add_meta_box('wpv_modulemanager_box',__('Module Manager','wpv-views'),array($this, 'modulemanager_views_box'),'view','side','high');  // this might be DEPRECATED
            add_meta_box('wpv_modulemanager_box',__('Module Manager','wpv-views'),array($this, 'modulemanager_view_templates_box'),'view-template','side','high');
        }
        if ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == WPV_FOLDER . '/menu/main.php') {
            $this->include_admin_css();
        }
        if ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'wpv-import-theme') {
            $this->include_admin_css();
        }
	
    }

   function modulemanager_views_box($post)
   {
        $element=array('id'=>_VIEWS_MODULE_MANAGER_KEY_.$post->ID, 'title'=>$post->post_title, 'section'=>_VIEWS_MODULE_MANAGER_KEY_);
        do_action('wpmodules_inline_element_gui',$element);
   }

   function add_view_module_manager_section( $view_settings, $view_id ) {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'module_manager' );
		?>
		<div class="wpv-setting-container wpv-setting-container-module-manager js-wpv-settings-content">

			<div class="wpv-settings-header">
				<h3>
					<?php _e( 'Module Manager', 'wpv-views' ) ?>
					<i class="icon-question-sign js-display-tooltip" 
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>" 
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>" >
					</i>
				</h3>
			</div>

			<div class="wpv-setting wpv-setting-module-manager">
				<?php
				$element = array(
					'id' => _VIEWS_MODULE_MANAGER_KEY_ . $view_id, 
					'title' => get_the_title( $view_id ), 
					'section' => _VIEWS_MODULE_MANAGER_KEY_
				);
				do_action( 'wpmodules_inline_element_gui', $element );
				?>
			</div>

		</div>
	   <?php
	}

   function modulemanager_view_templates_box( $post ) {
        $element = array(
			'id' => _VIEW_TEMPLATES_MODULE_MANAGER_KEY_ . $post->ID, 
			'title' => $post->post_title, 
			'section' => _VIEW_TEMPLATES_MODULE_MANAGER_KEY_
		);
        do_action( 'wpmodules_inline_element_gui', $element );
   }

    /**
     * save the view settings.
     * Called from a post_save action
     *
     */

    function save_view_settings($post_id){ // DEPRECATED, keep it because it might be fired when creating a new View
        global $wpdb, $sitepress;

        list($post_type, $post_status) = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT post_type, post_status FROM {$wpdb->posts} 
				WHERE ID = %d 
				LIMIT 1",
				$post_id
			), 
			ARRAY_N
		);

        if ($post_type == 'view') {

            if(isset($_POST['_wpv_settings'])){
                $_POST['_wpv_settings'] = apply_filters('wpv_view_settings_save', $_POST['_wpv_settings']);
                update_post_meta($post_id, '_wpv_settings', $_POST['_wpv_settings']);
            }
            // DEPRECATED// This should save nothing, as we are not passing anything like the needed data at all
			// Commented out in 1.7
			//save_view_layout_settings($post_id);


            if (isset($sitepress)) {
                if (isset($_POST['icl_trid'])) {
                    // save the post from the edit screen.
                    if (isset($_POST['wpv_duplicate_view'])) {
                        update_post_meta($post_id, '_wpv_view_sync', intval($_POST['wpv_duplicate_view']));
                    } else {
                        update_post_meta($post_id, '_wpv_view_sync', "0");
                    }

                    $icl_trid = $_POST['icl_trid'];
                } else {
                    // get trid from database.
                    $icl_trid = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT trid FROM {$wpdb->prefix}icl_translations 
							WHERE element_id = %d
							AND element_type = %s 
							LIMIT 1",
							$post_id,
							'post_' . $post_type
						)
					);
                }

                if (isset($_POST['wpv_duplicate_source_id'])) {
                    $source_id = $_POST['wpv_duplicate_source_id'];
                    $target_id = $post_id;
                } else {
                    // this is the source
                    $source_id = $post_id;
                    $target_id = null;
                }

                if ($icl_trid) {
                    $this->duplicate_view($source_id, $target_id, $icl_trid);
                }
            }
            if(isset($_POST['full_view'])) {
				$blogtime = current_time('timestamp');
				$last_saved = date('dmYHi',current_time('timestamp'));
				update_post_meta($post_id, '_wpv_last_modified', $last_saved);
			}
        }
    }
	
	/**
	* after_save_item
	*
	* Action fired on wpv_action_wpv_save_item to update a postmeta flag storing the last modified time
	*
	* @param $item_id (integer)
	*
	* @since 1.8.0
	*/
	
	function after_save_item( $item_id ) {
		if (
			! is_numeric( $item_id )
			|| intval( $item_id ) < 1
		) {
			return;
		}
		$now = time();
        $last = intval( get_post_meta( $item_id, '_toolset_edit_last', true ) );
		if ( $last >= $now ) {
            return;
        }
        update_post_meta( $item_id, '_toolset_edit_last', $now, $last );
	}

    function duplicate_view($source_id, $target_id, $icl_trid) { // DEPRECATED,check how to translate Views

        global $wpdb;

        if ($target_id) {
            // we're saving a translation
            // see if we should copy from the original
            $duplicate = get_post_meta($target_id, '_wpv_view_sync', true);
            if ($duplicate === "") {
                // check the original state
                $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
            }
            if ($duplicate) {
                $view_settings = get_post_meta($source_id, '_wpv_settings', true);
                update_post_meta($target_id, '_wpv_settings', $view_settings);

                $view_layout_settings = get_post_meta($source_id, '_wpv_layout_settings', true);
                update_post_meta($target_id, '_wpv_layout_settings', $view_layout_settings);
            }
        } else {
            // We're saving the original
            // see if we should copy to translations.
            $translations = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT element_id FROM {$wpdb->prefix}icl_translations 
					WHERE trid = %s 
					LIMIT 1",
					$icl_trid
				)
			);

            foreach ($translations as $translation_id) {
                if ($translation_id != $source_id) {
                    $this->duplicate_view($source_id, $translation_id, $icl_trid);
                }
            }
        }

    }

	/**
	 * If the post has a view
	 * add an view edit link to post.
	 */

	function edit_post_link($link, $post_id) {
		
		if ( !current_user_can( 'manage_options' ) )
			return $link;
		
        global $WPV_settings;
		if ( $WPV_settings->wpv_show_edit_view_link == 1 ){
			if ($this->current_view) {
				$view_link = '<a href="'. admin_url() .'admin.php?page=views-editor&view_id='. $this->current_view .'" title="'.__('Edit view', 'wpv-views').'">'.__('Edit view', 'wpv-views').' "'.get_the_title($this->current_view).'"</a>';
				$view_link = apply_filters( 'wpv_edit_view_link', $view_link );
				if ( isset( $view_link ) && !empty( $view_link ) ) {
					$link = $link . ' ' . $view_link;
				}
			}
		}
		return $link;
	}

    function admin_add_help() {
        global $pagenow;
        $screen = get_current_screen();

        $help = $this->admin_plugin_help('', $screen->id, $screen);

        if ($help) {
            $screen->add_help_tab(array(
                                    'id' => 'views-help',
                                    'title' => __('Views', 'wpv-views'),
                                    'content' => $help,
                                    ));
        }
    }

    /**
    * Adds help on admin pages.
    *
    * @param type $contextual_help
    * @param type $screen_id
    * @param type $screen
    * @return type
    */
    function admin_plugin_help($contextual_help, $screen_id, $screen) { // TODO review texts for contextual help
        $help = '';
        switch ($screen_id) {
            case 'views_page_view-templates':
                $help = '<p>'.__("Use <strong>Content Templates</strong> to design single pages in your site. ",'wpv-views').'</p>';
                $help .= '<p>'.__("This page lists the <strong>Content Templates</strong> that you have created and allows you to create new ones.",'wpv-views').'</p>';
                $help .= '<p>'.__("The ‘arrange by’ line, at the top of the page, lets you list the <strong>Content Templates</strong> by their name or by how they are used in the site.",'wpv-views').'</p>';
                $help .= '<p>'.__("Click on the name of a Content Template to edit it or create new <strong>Content Templates</strong> using the ‘Add’ buttons.",'wpv-views').'</p>';
                $help .= '<p><a href="http://wp-types.com/documentation/user-guides/view-templates/?utm_source=viewsplugin&utm_campaign=views&utm_medium=view-content-template-header-help&utm_term=Content Templates online help" target="_blank">'.__("Content Templates online help", 'wpv-views').'</a></p>';
                break;

            case 'view-template':
                $help = '<p>'.__("<strong>Content Templates</strong> let you design single pages in your site.", 'wpv-views').'</p>';
		        $help .= '<p>'.__("To Create a <strong>Content Template</strong>:", 'wpv-views').'</p>';
                $help .= '<ol><li>'.__("Set the title.", 'wpv-views').'</li>';
                $help .= '<li>'.__("Add fields to the body. Use the V icon to insert basic fields, custom fields, taxonomy and Views. If you are using CRED, use the C icon to insert CRED forms.", 'wpv-views').'</li>';
                $help .= '<li>'.__("Style the output by adding HTML around shortcodes.", 'wpv-views').'</li>';
                $help .= '</ol>';
                $help .= '<p><a href="http://wp-types.com/documentation/user-guides/view-templates/?utm_source=viewsplugin&utm_campaign=views&utm_medium=content-tempalte-header-page&utm_term=Content Templates online help" target="_blank">'.__("Content Templates online help", 'wpv-views').'</a></p>';
                break;

            case 'toplevel_page_views':
                $help = '<p>'.__("Use <strong>Views</strong> to load content from the database and display it anyway you choose.",'wpv-views').'</p>';
                $help .= '<p>'.__("This page lists the <strong>Views</strong> in your site. Under ‘actions’, you will find ‘duplicate’ and ‘delete’ for Views.", 'wpv-views').'</p>';
                $help .= '<p>'.__("Click on a <strong>Views</strong> name to edit it or create new Views.", 'wpv-views').'</p>';
                $help .= '<p><a href="http://wp-types.com/documentation/user-guides/views/?utm_source=viewsplugin&utm_campaign=views&utm_medium=views-listing-header&utm_term=Views online help" target="_blank">'.__("Views online help", 'wpv-views') .'</a></p>';
                break;

            case 'views_page_view-archives':
                $help = '<p>'.__("Use <strong>WordPress Archives</strong> to style and design standard listing and archive pages.",'wpv-views').'</p>';
                $help .= '<p>'.__("This page lists the <strong>WordPress Archives</strong> in your site.", 'wpv-views').'</p>';
                $help .= '<p>'.__("The ‘arrange by’ line, at the top of the page, lets you list the <strong>WordPress Archives</strong> by their name or by how they are used in the site.", 'wpv-views').'</p>';
                $help .= '<p>'.__("Click on the name of a <strong>WordPress Archives</strong> to edit it or create new WordPress Archives using the ‘Add’ buttons.", 'wpv-views').'</p>';
                $help .= '<p><a href="http://wp-types.com/documentation/user-guides/normal-vs-archive-views/?utm_source=viewsplugin&utm_campaign=views&utm_medium=archive-listing-header&utm_term=WordPress Archives online help" target="_blank">'.__("WordPress Archives online help", 'wpv-views') .'</a></p>';
                break;

            case 'edit-view':
                $help = '<p>'.__("Use <strong>Views</strong> to filter and display lists in complex and interesting ways. Read more about Views in our user guide:",'wpv-views');
                $help .= '<br /><a href="http://wp-types.com/user-guides/views/?utm_source=viewsplugin&utm_campaign=views&utm_medium=edit-view&utm_term=http://wp-types.com/user-guides/views/" target="_blank">http://wp-types.com/user-guides/views/ &raquo;</a></p>';
                $help .= '<p>'.__("This page gives you an overview of the Views you have created.", 'wpv-views').'</p>';
                $help .= '<p>'.__("It has the following options:", 'wpv-views').'</p>';
                $help .= '<ul><li>'.__("<strong>Add New</strong>: Add a New View", 'wpv-views').'</li></ul>';
                $help .= '<p>'.__("If you hover over a View's name you also have these options:", 'wpv-views').'</p>';
                $help .= '<ul><li>'.__("<strong>Edit</strong>: Click to edit the View<br />\n", 'wpv-views').'</li>';
                $help .= '<li>'.__("<strong>Quick Edit</strong>: click to get quick editing options for the View, such as title, slug and date", 'wpv-views').'</li>';
                $help .= '<li>'.__("<strong>Trash</strong>: Move the View to Trash", 'wpv-views').'</li></ul>';
                $help .= '<p>'.sprintf(__("If you need additional help with Content Templates you can visit our <a href='%s' target='_blank'>support forum &raquo;</a>.", 'wpv-views'), WPV_SUPPORT_LINK).'</p>';
                break;

            case 'views_page_view-archives-editor':
                $help = '<p>'.__("<strong>WordPress Archives</strong> let you style and design standard listing and archive pages.",'wpv-views').'</p>';
                $help .= '<p>'.__("To create a <strong>WordPress Archive</strong>:", 'wpv-views').'</p>';
                $help .= '<ol><li>'.__("Set the title", 'wpv-views').'</li>';
                $help .= '<li>'.__("Select on which listing pages it displays", 'wpv-views').'</li>';
                $help .= '<li>'.__("Design the output for the View by inserting fields and styling with HTML", 'wpv-views').'</li></ol>';
                $help .= '<p><a href="http://wp-types.com/documentation/user-guides/normal-vs-archive-views/?utm_source=viewsplugin&utm_campaign=views&utm_medium=archive-editor&utm_term=WordPress Archives online help" target="_blank">'.__("WordPress Archives online help", 'wpv-views') .'</a></p>';
                break;

            case 'view':
            case 'views_page_views-editor':
                $help = '<p>'.__("<strong>Views</strong> load content from the database and display it anyway you choose.",'wpv-views').'</p>';
                $help .= '<p>'.__("To make it easier to use Views, we’ve created different preset usage modes for <strong>Views</strong>. Each usage mode emphasizes the features that you need and hides the ones that are not needed.",'wpv-views').'</p>';
                $help .= '<p>'.__("You can switch between the different <strong>Views</strong> usage mode by opening the ‘Screen options’ tab.",'wpv-views').'</p>';
                $help .= '<p>'.__("To create a <strong>View</strong>:", 'wpv-views').'</p>';
                $help .= '<ol><li>'.__("Set the title", 'wpv-views').'</li>';
                $help .= '<li>'.__("Select the content to load", 'wpv-views').'</li>';
                $help .= '<li>'.__("Optionally, apply a filter to the query", 'wpv-views').'</li>';
                $help .= '<li>'.__("If needed, enable pagination and front-end filters", 'wpv-views').'</li>';
                $help .= '<li>'.__("Design the output for the View by inserting fields and styling with HTML", 'wpv-views').'</li></ol>';
                $help .= '<p>'.__("When you are done, remember to add the <strong>View</strong> to your content. You can do that by inserting the <strong>View</strong> as a shortcode to content or displaying it as a widget.",'wpv-views').'</p>';
                $help .= '<p><a href="http://wp-types.com/documentation/user-guides/views/?utm_source=viewsplugin&utm_campaign=views&utm_medium=view-editor&utm_term=Views online help" target="_blank">'.__("Views online help", 'wpv-views') .'</a></p>';
                break;

        }

        if ($help != '') {
            return $help;
        } else {
            return $contextual_help;
        }
    }

    // Add important errors right after the View name

    function admin_add_errors() { // TODO check how to add errors now, if needed
    global $post;
    if (empty($post->ID)) {
	return;
    }
    $post_type = $post->post_type;
    if( 'view' != $post_type ) {
	return;
    }
    $last_saved = get_the_modified_time( 'dmYHi' );
    $last_modified = get_post_meta( $post->ID, '_wpv_last_modified', true );
    $view_not_complete = '<div class="wpv_form_errors" style="width:98.7%;">' . sprintf(  esc_js(__( 'This View was not saved correctly. You may need to increase the number of post variables allowed in PHP. <a href="%s">How to increase max_post_vars setting</a>.', 'wpv-views' )), 'http://wp-types.com/faq/why-do-i-get-a-500-server-error-when-editing-a-view/?utm_source=viewsplugin&utm_campaign=views&utm_medium=add-view-error&utm_term=How to increase max_post_vars setting' ) . '</div>';
    ?>
	<script type="text/javascript">
	jQuery(document).ready(function(){
		var last_saved = <?php echo $last_saved; ?>;
		var last_modified = <?php echo ('' != $last_modified) ? $last_modified : '""'; ?>;
		if (!jQuery('#full_view').length || (last_modified.length && last_saved != last_modified)) {
			jQuery('#titlediv').after('<?php echo $view_not_complete; ?>');
		}
	});
	</script>

    <?php
    }

	// Add WPML sync options.

	function language_options() { // DEPRECATED, check how to traslate Views

		global $sitepress, $post;

        if ($post->post_type == 'view') {
            list($translation, $source_id, $translated_id) = $sitepress->icl_get_metabox_states();

            echo '<br /><br /><strong>' . __('Views sync', 'wpv-views') . '</strong>';

            $checked = '';
            if ($translation) {
                if ($translated_id) {
                    $duplicate = get_post_meta($translated_id, '_wpv_view_sync', true);
                    if ($duplicate === "") {
                        // check the original state
                        $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
                    }
                } else {
                    // This is a new translation.
                    $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
                }

                if ($duplicate) {
                    $checked = ' checked="checked"';
                }
                echo '<br /><label><input class="wpv_duplicate_from_original" name="wpv_duplicate_view" type="checkbox" value="1" '.$checked . '/>' . __('Duplicate view from original', 'wpml-media') . '</label>';
                echo '<input name="wpv_duplicate_source_id" value="' . $source_id . '" type="hidden" />';
            } else {

                $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
                if ($duplicate) {
                    $checked = ' checked="checked"';
                }
                echo '<br /><label><input name="wpv_duplicate_view" type="checkbox" value="1" '.$checked . '/>' . __('Duplicate view to translations', 'wpv-views') . '</label>';
            }
        }
	}

    /**
	 * Get the available View in a select box
	 *
	 */

	function get_view_select_box($row, $page_selected, $archives_only = false) {
		global $wpdb, $sitepress;

		static $views_available = null;

		if (!$views_available) {
			$views_available = $wpdb->get_results(
				"SELECT ID, post_title, post_name FROM {$wpdb->posts} 
				WHERE post_type = 'view' 
				AND post_status = 'publish'"
			);

            if ($archives_only) {
                foreach ($views_available as $index => $view) {
                    $view_settings = $this->get_view_settings($view->ID);
                    if ($view_settings['view-query-mode'] != 'archive') {
                        unset($views_available[$index]);
                    }
                }
            }

			// Add a "None" type to the list.
			$none = new stdClass();
			$none->ID = '0';
			$none->post_title = __('None', 'wpv-views');
			$none->post_content = '';
			array_unshift($views_available, $none);
		}

        $view_box = '';
		if ($row === '') {
			$view_box .= '<select class="view_select" name="view" id="view">';
		} else {
			$view_box .= '<select class="view_select" name="view_' . $row . '" id="view_' . $row . '">';
		}

        if (isset($sitepress) && function_exists('icl_object_id')) {
            $page_selected = icl_object_id($page_selected, 'view', true);
        }

        foreach($views_available as $view) {

			if (isset($sitepress)) { // TODO maybe DEPRECATED check how to translate Views
				// See if we should only display the one for the correct lanuage.
				$lang_details = $sitepress->get_element_language_details($view->ID, 'post_view');
				if ($lang_details) {
					$translations = $sitepress->get_element_translations($lang_details->trid, 'post_view');
					if (count($translations) > 1) {
						$lang = $sitepress->get_current_language();
						if (isset($translations[$lang])) {
							// Only display the one in this language.
							if ($view->ID != $translations[$lang]->element_id) {
								continue;
							}
						}
					}
				}
			}

            if ($page_selected == $view->ID)
                $selected = ' selected="selected"';
            else
                $selected = '';

			if ($view->post_title) {
				$post_name = $view->post_title;
			} else {
				$post_name = $view->post_name;
			}

			$view_box .= '<option value="' . $view->ID . '"' . $selected . '>' . $post_name . '</option>';

        }
        $view_box .= '</select>';

        return $view_box;
	}

	function wpv_ajax_wpv_get_types_field_name() { // TODO check where this is used, maybe create a function to handle it past wpnonce verification
		if (wp_verify_nonce($_POST['wpv_nonce'], 'wpv_get_types_field_name_nonce')) {
			if (!defined('WPCF_VERSION')) {
				echo json_encode(array('found' => false,
									   'name' => $_POST['field']));
			} else {
			    if (defined('WPCF_INC_ABSPATH')) {
					require_once WPCF_INC_ABSPATH . '/fields.php';
				}

				if (function_exists('wpcf_admin_fields_get_fields')) {
					$fields = wpcf_admin_fields_get_fields();
				} else {
					$fields = array();
				}

				$found = false;
				foreach ($fields as $field) {
					if ($_POST['field'] == wpcf_types_get_meta_prefix($field) . $field['slug']) {
						echo json_encode(array('found' => true,
											   'name' => $field['name']));
						$found = true;
						break;
					}
				}

				if (!$found) {
					echo json_encode(array('found' => false,
										   'name' => $_POST['field']));
				}

			}
		}
		die();
	}

	function wpv_ajax_wpv_get_taxonomy_name() { // TODO check where this is used, maybe create a function to handle it past wpnonce verification
		if (wp_verify_nonce($_POST['wpv_nonce'], 'wpv_get_types_field_name_nonce')) {

			$taxonomies = get_taxonomies('', 'objects');
			if (isset($taxonomies[$_POST['taxonomy']])) {
				echo json_encode(array('found' => false,
								   'name' => $taxonomies[$_POST['taxonomy']]->labels->name));
			} else {
				echo json_encode(array('found' => false,
								   'name' => $_POST['taxonomy']));
			}
		}
		die();
	}

	function wpv_admin_enqueue_scripts( $hook ) {// echo $hook; TODO this function needs a lot of love
		/**
		* Registering all scripts
		*/

		/**
		* Register scripts and styles needed in the embedded version too:
		*
		* Scripts:
		* toolset-colorbox
		* select2
		* views-utils-script
		* CodeMirror
		* Pointers
		*
		* Styles:
		* toolset-font-awesome
		* toolset-colorbox
		* views-notifications-css
		* views-admin-dialogs-css
		* select2
		* views-codemirror-css
		*/
		parent::wpv_admin_enqueue_scripts( $hook );

		// Views, WPA and CT edit screens JS
		// @todo on a future revision, once common is spread, make **_editor.js depend on icl_editor-script and remove fallbacks

		wp_register_script( 'views-editor-js', ( WPV_URL . "/res/js/redesign/views_editor.js" ), array( 'jquery', 'wp-pointer', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tooltip', 'views-codemirror-conf-script', 'underscore', 'views-utils-script', 'quicktags', 'wplink'), WPV_VERSION, true );
		$views_editor_translations = array(
			'frontend_events_dialog_title' => __("Insert Views frontend event handler", 'wpv-views')
		);
		wp_localize_script( 'views-editor-js', 'wpv_views_editor_texts', $views_editor_translations );
		wp_register_script( 'views-filters-js', ( WPV_URL . "/res/js/redesign/views_section_filters.js" ), array( 'views-editor-js'), WPV_VERSION, true );
		wp_register_script( 'views-pagination-js', ( WPV_URL . "/res/js/redesign/views_section_pagination.js" ), array( 'views-editor-js'), WPV_VERSION, true );
		wp_register_script( 'views-update-js', ( WPV_URL . "/res/js/redesign/views_sections_update.js" ), array( 'views-editor-js'), WPV_VERSION, true );

		wp_register_script( 'views-archive-editor-js', ( WPV_URL . "/res/js/redesign/views_archive_editor.js" ), array( 'jquery', 'wp-pointer', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-draggable', 'views-codemirror-conf-script', 'underscore', 'views-utils-script', 'quicktags', 'wplink'), WPV_VERSION, true );
		wp_register_script( 'views-archive-update-js', ( WPV_URL . "/res/js/redesign/views_archives_sections_update.js" ), array( 'views-archive-editor-js'), WPV_VERSION, true );

		wp_register_script( 'views-layout-template-js', ( WPV_URL . "/res/js/redesign/views_section_layout_template.js" ), array( 'jquery'), WPV_VERSION, true );
		wp_register_script( 'views-redesign-media-manager-js', ( WPV_URL . "/res/js/redesign/views_media_manager.js" ), array( 'jquery'), WPV_VERSION, true );
		wp_register_script( 'views-layout-wizard-script' , WPV_URL . '/res/js/redesign/views_layout_edit_wizard.js', array('jquery', 'views-shortcodes-gui-script'), WPV_VERSION, true);

		wp_register_script( 'views-content-template-js' , WPV_URL . '/res/js/redesign/views_content_template.js', array( 'jquery', 'wp-pointer', 'jquery-ui-sortable', 'jquery-ui-draggable', 'views-codemirror-conf-script', 'underscore', 'views-utils-script', 'quicktags', 'wplink'), WPV_VERSION, true);

        // Suggestion Script for Views edit screen
		wp_register_script( 'views-suggestion_script', ( WPV_URL . "/res/js/redesign/suggestion_script.js" ), array(), WPV_VERSION, true );
		wp_register_style( 'views_suggestion_style', WPV_URL . '/res/css/token-input.css', array(), WPV_VERSION );
		wp_register_style( 'views_suggestion_style2', WPV_URL . '/res/css/token-input-wpv-theme.css', array(), WPV_VERSION );

		// Listing JS

		wp_register_script( 'views-listing-common-script' , WPV_URL . '/res/js/redesign/wpv_listing_common.js', array( 'jquery' ), WPV_VERSION, true);
		wp_register_script( 'views-listing-script' , WPV_URL . '/res/js/redesign/views_listing_page.js', array( 'jquery', 'views-listing-common-script' ), WPV_VERSION, true);
		wp_register_script( 'views-archive-listing-script' , WPV_URL . '/res/js/redesign/views_wordpress_archive_listing_page.js', array( 'jquery', 'views-listing-common-script' ), WPV_VERSION, true);
		wp_register_script( 'views-content-template-listing-script' , WPV_URL . '/res/js/redesign/wpv_content_template_listing.js', array('jquery', 'views-listing-common-script' ), WPV_VERSION, true);
		
		// Update help
		
		wp_register_script( 'views-update-help-js', WPV_URL . '/res/js/views_admin_update_help.js', array( 'jquery' ), WPV_VERSION, true );

		// NOTE knockout (2.2.1) and parametric.js files need to be enqueued at admin_header time for some reason, registered and enqueued in editor-addon-parametric-class.php in common

        /* Knockout.js 3.3.0
         *
         * If WP_DEBUG is defined (and true), debug version of the script will be registered. Otherwise we will use a minified one.
         *
         * Please add a note if you enqueue this script somewhere. This may change to just 'knockout'
         * when the old version (knockout 2.2.1) is thrown away. So we'd like to know what to replace.
         *
         * - wpv_ct_editor_enqueue()
         */
        if( defined( 'WP_DEBUG' ) && true == WP_DEBUG ) {
            wp_register_script('knockout3', WPV_URL . '/res/js/lib/knockout-3.3.0.debug.js', array(), '3.3.0');
        } else {
            wp_register_script('knockout3', WPV_URL . '/res/js/lib/knockout-3.3.0.js', array(), '3.3.0');
        }

        /**
		* Enqueue actions
		*/

        $page = wpv_getget( 'page' );

        // Basic WordPress scripts & styles

		wp_enqueue_script('wp-pointer');
		wp_enqueue_style('wp-pointer');
		wp_enqueue_script('thickbox'); // TODO maybe DEPRECATED
		wp_enqueue_style('thickbox'); // TODO maybe DEPRECATED

		// External libraries scripts

		if ( ! empty( $page )
            && ( in_array( $page, array(
                    'views', 'view-archives', 'view-templates', 'views-editor',
                    'view-archives-editor', WPV_CT_EDITOR_PAGE_NAME )
                )
				|| strpos( $_SERVER['QUERY_STRING'], 'help.php') !== false ) )
        {

			if ( $_GET['page'] == 'views-editor' ) {// TODO WTF is it doing here?
				delete_transient('wpv_layout_wizard_save_settings');
			}

			wp_enqueue_script( 'views-utils-script');
			$help_box_translations = array(
				'wpv_dont_show_it_again' => __("Got it! Don't show this message again", 'wpv-views'),
				'wpv_close' => __("Close", 'wpv-views')
			);
			wp_localize_script( 'views-utils-script', 'wpv_help_box_texts', $help_box_translations );
			
			// Views shortcodes GUI script - already localized in parent class.
			wp_enqueue_script('views-shortcodes-gui-script');
			
			if ( !wp_style_is( 'views-admin-dialogs-css' ) ) {
				wp_enqueue_style( 'views-admin-dialogs-css' );
			}

			// CodeMirror NOTE add to Views and Content Templates edit screen only

			if ( in_array( $page, array( 'views-editor', 'view-archives-editor', WPV_CT_EDITOR_PAGE_NAME ) ) ) {
				wp_enqueue_script('views-codemirror-script');
				wp_enqueue_script('views-codemirror-overlay-script');
				wp_enqueue_script('views-codemirror-xml-script');
				wp_enqueue_script('views-codemirror-css-script');
				wp_enqueue_script('views-codemirror-js-script');
				wp_enqueue_script('views-codemirror-addon-searchcursor-script');
				wp_enqueue_script('views-codemirror-addon-panel-script');
				wp_enqueue_script('views-codemirror-conf-script');
				wp_enqueue_style('views-codemirror-css');
				
				//Codemirror:Quicktags:Add link popup styles and html
				wp_enqueue_style('editor-buttons');
			}

			// Shared ToolSet CSS

			wp_enqueue_style( 'toolset-font-awesome' );

			// General Views CSS

			wp_enqueue_style( 'views-admin-css' );

		}
     
		// Views screens - import/export and help

		if ( 
			$hook == 'views_page_views-import-export'
			|| $hook == 'wp-views/menu/help.php'
		) {
			wp_enqueue_style( 'views-admin-css' );
		}

		// Views listing page

		if (isset($_GET['page']) && $_GET['page'] == 'views') {
			wp_enqueue_script( 'views-listing-script' );
		}

		if (isset($_GET['page']) && $_GET['page'] == 'view-archives') {
			wp_enqueue_script( 'views-archive-listing-script' );
		}

		if (isset($_GET['page']) && $_GET['page'] == 'view-templates') {
			wp_enqueue_script( 'views-content-template-listing-script' );
		}


		// Views and WPA editors
		// To Juan: check this code after 2-3 versions
		$media_manager_translations = array(
			'only_img_allowed_here' => __( "You can only use an image file here", 'wpv-views' )
		);
		
		$editor_translations = array(
			'meta_html_extra_css_open' => __( 'Open CSS editor', 'wpv-views' ),
			'meta_html_extra_css_close' => __( 'Close CSS editor', 'wpv-views'  ),
			'meta_html_extra_js_open' => __( 'Open JS editor', 'wpv-views'  ),
			'meta_html_extra_js_close' => __( 'Close JS editor', 'wpv-views'  )
		);
		
		$inline_content_templates_translations = array(
            'new_template_name_in_use' => __( 'A Content Template with that name already exists. Please try with another name.', 'wpv-views' ),
			'pointer_close' => __( 'Close', 'wpv-views' ),
			'pointer_scroll_to_template' => __( 'Scroll to the Content Template', 'wpv-views' )
		);
		
		$layout_wizard_translations = array(
			'button_next' => __( 'Next', 'wpv-views' ),
			'button_insert' => __( 'Finish', 'wpv-views' ),
			'unknown_error' => __( 'Something wrong happened, please try again', 'wpv-views' ),
            'bootstrap_not_set' => __( 'You need to set the Bootstrap version used in your theme.', 'wpv-views' ) . ' ' .
                sprintf(
                    __("<a href='%s' target='_blank'>Go to the Settings page &raquo;</a>", 'wpv-views'),
                    add_query_arg( array( 'page' => 'views-settings', 'tab' => 'compatibility' ), admin_url( 'admin.php' ) )
                ),
            'bootstrap_2' => __( 'This site is using Bootstrap 2.0', 'wpv-views' ),
            'bootstrap_3' => __( 'This site is using Bootstrap 3.0', 'wpv-views' ),
            'bootstrap_not_used' => __( 'This site is not using Bootstrap CSS.', 'wpv-views' ),
		);

		if ( 'views-editor' == $page ) {
			wp_enqueue_script('suggest'); // for author filter, although it is declared as dependency
			wp_enqueue_script( 'views-editor-js' ); // general js file
			wp_localize_script( 'views-editor-js', 'wpv_editor_strings', $editor_translations );
			
			wp_enqueue_script( 'views-filters-js' ); // general js file for filters, each filter registers and enqueues its own scripts depending on this
			$filters_strings = array(
				'select_empty' => __( "Please select an option", 'wpv-views' ),
				'param_missing' => __("This field can not be empty", 'wpv-views'),
				'param_url_ilegal' => __("Only lowercase letters, numbers, hyphens and underscores allowed as URL parameters", 'wpv-views'),
				'param_shortcode_ilegal' => __("Only lowercase letters and numbers allowed as shortcode attributes", 'wpv-views'),
				'param_year_ilegal' => __( 'Years can only be a four digits number', 'wpv-views'  ),
				'param_month_ilegal' => __( 'Months can only be a number between 1 and 12', 'wpv-views' ),
				'param_week_ilegal' => __( 'Weeks can only be numbers between 1 and 53', 'wpv-views' ),
				'param_day_ilegal' => __( 'Days can only be a number between 1 and 31', 'wpv-views' ),
				'param_hour_ilegal' => __( 'Hours can only be numbers between 0 and 23', 'wpv-views' ),
				'param_minute_ilegal' => __( 'Minutes can only be numbers between 0 and 59', 'wpv-views' ),
				'param_second_ilegal' => __( 'Seconds can only be numbers between 0 and 59', 'wpv-views' ),
				'param_dayofyear_ilegal' => __( 'Days of the year can only be numbers between 1 and 366', 'wpv-views' ),
				'param_dayofweek_ilegal' => __( 'Days of the week can only be numbers between 1 and 7', 'wpv-views' ),
				'param_numeric_natural_ilegal' => __( 'This needs to be a non-negative number', 'wpv-views' ),
				'param_forbidden_wordpress' => __("This is a word reserved by WordPress", 'wpv-views'),
				'param_forbidden_toolset' => __("This is a word reserved by any of the ToolSet plugins", 'wpv-views'),
				'param_forbidden_toolset_attr' => __("This is an attribute reserved by any of the ToolSet plugins", 'wpv-views'),
				'param_forbidden_post_type' => __("There is a post type named like that", 'wpv-views'),
				'param_forbidden_taxonomy' => __("There is a taxonomy named like that", 'wpv-views'),
				'parent_type_not_hierarchical' => __("The posts you want to display are not hierarchical, so this filter will not work", 'wpv-views'),
				'taxonomy_parent_changed' => __("The taxonomy you want to display has changed, so this filter needs some action", 'wpv-views'),
				'taxonomy_term_changed' => __("The taxonomy you want to display has changed, so this filter needs some action", 'wpv-views')
			);
			wp_localize_script( 'views-filters-js', 'wpv_filters_strings', $filters_strings );
			
			wp_enqueue_script( 'views-pagination-js' );
			$pagination_translation = array(
				'close' => __( 'Close', 'wpv-views' ),
				'wpv_page_pagination_shortcode_definition'	=> __('This is an optional placeholder to wrap the pagination shortcodes. The content of this shortcode will only be displayed if there is more than one page of results.', 'wpv-views'),
				'wpv_page_num_shortcode_definition'		=> __('Displays the current page number', 'wpv-views'),
				'wpv_page_total_shortcode_definition'		=> __('Displays the maximum number of pages found by the Views Query.', 'wpv-views'),
				'wpv_page_selector_shortcode_definition'	=> __('Displays a pager with the current page selected. Depending on the value of the <em>style</em> parameter it displays a list of links to the other pages or a drop-down list to select another page.', 'wpv-views'),
				'wpv_page_pre_shortcode_definition'		=> __('Display a <em>Previous</em> link to move to the previous page.', 'wpv-views'),
				'wpv_page_next_shortcode_definition'		=> __('Display a <em>Next</em> link to move to the next page.', 'wpv-views')
			);
			wp_localize_script( 'views-pagination-js', 'wpv_pagination_texts', $pagination_translation );

			//Enqueue suggestion script
			wp_enqueue_script( 'views-suggestion_script' );
			wp_enqueue_style ('views_suggestion_style');
			wp_enqueue_style ('views_suggestion_style2');

			wp_enqueue_script( 'views-update-js' );
			
			wp_enqueue_script( 'views-layout-template-js' );
			wp_localize_script( 'views-layout-template-js', 'wpv_inline_templates_strings', $inline_content_templates_translations );
			
			wp_enqueue_script( 'views-layout-wizard-script' );
			wp_localize_script( 'views-layout-wizard-script', 'wpv_layout_wizard_strings', $layout_wizard_translations );
			
			if( function_exists( 'wp_enqueue_media' ) && !wp_script_is( 'icl_media-manager-js', 'enqueued') ) {
				wp_enqueue_media();
				wp_enqueue_script( 'views-redesign-media-manager-js' );
				wp_localize_script( 'views-redesign-media-manager-js', 'wpv_media_manager', $media_manager_translations );
			}
            
        }

		if ( 'view-archives-editor' == $page ) {
            wp_enqueue_script('suggest');
			wp_enqueue_script( 'views-archive-editor-js' ); // general js file
			wp_localize_script( 'views-archive-editor-js', 'wpv_editor_strings', $editor_translations );
			
			wp_enqueue_script( 'views-archive-update-js' );
			
			wp_enqueue_script( 'views-layout-template-js' );
			wp_localize_script( 'views-layout-template-js', 'wpv_inline_templates_strings', $inline_content_templates_translations );
			
			wp_enqueue_script( 'views-layout-wizard-script' );
			wp_localize_script( 'views-layout-wizard-script', 'wpv_layout_wizard_strings', $layout_wizard_translations );
			
			if( function_exists( 'wp_enqueue_media' ) && !wp_script_is( 'icl_media-manager-js', 'enqueued') ) {
				wp_enqueue_media();
				wp_enqueue_script( 'views-redesign-media-manager-js' );
				wp_localize_script( 'views-redesign-media-manager-js', 'wpv_media_manager', $media_manager_translations );
			}
		}
		
		// Update help screen
		
		if ( isset($_GET['page'] ) && $_GET['page']=='views-update-help' ) {
			wp_enqueue_script( 'views-update-help-js' );
		}

	}
}

