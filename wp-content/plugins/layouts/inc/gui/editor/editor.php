<?php

class WPDD_GUI_EDITOR{


	private $layout_id = null;
	private $post = null;
    private $removed_cells = null;

	const AMOUNT_OF_POSTS_TO_SHOW = 5;

	function __construct(){

		$this->layout_id = isset( $_GET['layout_id'] ) ? $_GET['layout_id'] : null;

		global $post;

		$post = $post ? $post : get_post( $this->layout_id );
		$this->post = $post;

		if (isset($_GET['page']) and $_GET['page']=='dd_layouts_edit') {

            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );

			if( $this->post === null ){
				add_action('admin_enqueue_scripts', array($this, 'preload_styles'));
				add_action('wpddl_layout_not_found', array($this,'layout_not_found'), 10 );
				return;
			}

            if( null !== $this->layout_id ){
                $clean_up = new WPDDL_LayoutsCleaner(
                    $this->layout_id
                );
                $this->removed_cells = $clean_up->remove_orphaned_ct_cells('cell-content-template', 'ddl_view_template_id');
            }

			add_action('wpddl_pre_render_editor', array($this,'pre_render_editor'), 10, 1);
			add_action('wpddl_render_editor', array($this,'render_editor'), 10, 1);
			add_action('wpddl_after_render_editor', array($this,'after_render_editor'), 10, 1);


			//add_action('wpddl_after_render_editor', array($this,'print_where_used_links'), 11, 1);
			add_action('wpddl_after_render_editor', array($this,'add_empty_where_used_ui'), 11, 1);
			add_action('wpddl_after_render_editor', array($this,'add_video_toolbar'), 11, 1);
			add_action('wpddl_after_render_editor', array($this,'add_wpml_ui'), 12, 1);


			add_action('wpddl_layout_actions', array($this,'layout_actions'));

			add_action('admin_enqueue_scripts', array($this, 'preload_styles'));
			add_action('admin_enqueue_scripts', array($this, 'preload_scripts'));

			add_action('admin_init', array($this,'init_editor'));
			//add_action('admin_enqueue_scripts', array($this, 'load_latest_backbone'), -1000);

			do_action('wpddl_layout_actions');
		}


		//leave wp_ajax out of the **** otherwise it won't be fired
		add_action('wp_ajax_get_layout_data', array(&$this, 'get_layout_data_callback') );
		add_action('wp_ajax_save_layout_data', array(&$this, 'save_layout_data_callback') );
		add_action('wp_ajax_get_layout_parents', array(&$this, 'get_layout_parents_callback') );
		add_action('wp_ajax_check_for_parents_loop', array(&$this, 'check_for_parents_loop_callback') );
		add_action('wp_ajax_check_for_parent_child_layout_width', array(&$this, 'check_for_parent_child_layout_width_callback') );
		add_action('wp_ajax_view_layout_from_editor', array(&$this, 'view_layout_from_editor_callback') );
		add_action('wp_ajax_show_all_posts', array(&$this, 'show_all_posts_callback') );

		add_action('wp_ajax_ddl_get_where_used_ui', array(&$this, 'get_where_used_ui_callback') );

		add_action('wp_ajax_edit_layout_slug', array(&$this, 'edit_layout_slug_callback') );

		add_action('wp_ajax_remove_all_layout_associations', array(&$this, 'remove_all_layout_associations_callback') );

		add_action('wp_ajax_ddl_update_wpml_state', array(&$this, 'update_wpml_state') );
		add_action('wp_ajax_ddl_load_assign_dialog_editor', array(&$this, 'load_assign_dialog_callback') );

		add_action('wp_ajax_ddl_compact_display_mode', array(&$this, 'compact_display_callback') );

	}

	function __destruct(){
	}

	function init_editor(){
		$this->list_where_used = $this->get_where_used_lists( $this->layout_id );
	}

	function layout_not_found(){
		include_once 'templates/editor-layout-does-not-exist.tpl.php';
	}

	public function compact_display_callback() {

		if( $_POST && wp_verify_nonce( $_POST['compact_display_nonce'], 'compact_display_nonce' ) ) {
			global $wpddlayout;

			$wpddlayout->save_option(array('compact_display' => $_POST['mode'] == 'true' ? true : false));
		}

		die();
	}

	public function edit_layout_slug_callback()
	{
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['edit_layout_slug_nonce'], 'edit_layout_slug_nonce' ) )
		{
			$slug = get_sample_permalink( $_POST['layout_id'], get_the_title( $_POST['layout_id'] ), $_POST['slug'] );
			$send = wp_json_encode( array( 'Data' =>  array( 'slug' => $slug[1] ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	public function remove_all_layout_associations_callback()
	{

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['ddl_remove_all_layout_association_nonce'], 'ddl_remove_all_layout_association_nonce' ) )
		{
			$send = wp_json_encode( array( 'Data' => $this->editor_purge_all_associations($_POST['layout_id']) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	private function editor_purge_all_associations($layout_id)
	{
		global $wpddlayout;
		$associations = $this->get_where_used_lists( $_POST['layout_id'] );

		$loops = is_object($associations) && property_exists($associations, 'loops') ? $associations->loops : false;
		$types = is_object($associations) && property_exists($associations, 'types') ? $associations->post_types : false;
		$posts = is_object($associations) && property_exists($associations, 'posts') ? $associations->posts : false;

		if( $loops && count($loops) > 0 )
		{
			$loops_manager = $wpddlayout->layout_post_loop_cell_manager;
			$remove = array();

			foreach( $loops as  $loop )
			{
				$loop = (object) $loop;
				$remove[] = $loop->name;
			}
			$loops_manager->remove_archives_association( $remove, $layout_id );
		}

		if( ( $posts && count($posts) > 0 ) || ( $types && count($types) > 0 ) )
		{

			$wpddlayout->post_types_manager->purge_layout_post_type_data( $layout_id );
		}

		return $associations;
	}

	public function add_empty_where_used_ui() {

		?>

		<div class="where-used-ui js-where-used-ui">
			<?php $this->add_select_post_types(); ?>
		</div>

	<?php

	}

	public function get_where_used_ui_callback() {

        if( user_can_assign_layouts() === false ){
            die( __("You don't have permission to perform this action!", 'ddl-layouts') );
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
				'ddl_layout_view_nonce')) {
			die('verification failed');
		}

		echo $this->get_where_used_output( $_POST['layout_id'] );
		die();
	}

	function get_where_used_output( $layout_id )
	{
		ob_start();

		$this->layout_id = $layout_id;
		$this->list_where_used = $this->get_where_used_lists( $this->layout_id );
		$this->add_select_post_types();
		$output = ob_get_clean();
		return $output;
	}

	function add_wpml_ui () {

		global $wpddlayout;

		$post = get_post($_GET['layout_id']);

		$layout = $wpddlayout->get_layout_from_id($post->ID);
		ob_start();
		do_action('wpml_show_package_language_ui', $layout->get_string_context());
		$lang_selector = ob_get_clean();

		?>

		<div id="js-dd-layouts-lang-wrap" class="dd-layouts-wrap" <?php if (!$lang_selector) { echo ' style="display:none"'; } ?>>
			<div class="dd-layouts-lang-wrap">
				<?php echo $lang_selector; ?>
			</div>
		</div>

	<?php

	}

	public function get_layout_data_callback()
	{
		echo WPDD_Layouts::get_layout_settings($_POST['layout_id'], false);
		die(  );
	}
	private function slug_exists( $slug, $layout_id )
	{
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s AND ID != %d", WPDDL_LAYOUTS_POST_TYPE, $slug, $layout_id) );

		if ( !empty( $id ) ) return true;

		return false;
	}
	public function save_layout_data_callback()
	{
		global $wpddlayout;

        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['save_layout_nonce'], 'save_layout_nonce' ) )
		{
			if( $_POST['layout_model'] && $_POST['layout_id'] )
			{
				$raw = stripslashes( $_POST['layout_model'] );
				$json = json_decode( $raw, true );
				$children_to_delete = $json['children_to_delete'];
				$child_delete_mode = $json['child_delete_mode'];

				unset($json['children_to_delete']);
				unset($json['child_delete_mode']);

				$post = get_post( $_POST['layout_id'] );

				$msg = array( "Data" => array() );

				if( $post->post_title != $json['name'] || $post->post_name != $json['slug'] )
				{

					if( $this->slug_exists( $json['slug'], $_POST['layout_id'] ) )
					{
						echo wp_json_encode(array( "Data" => array( 'error' =>  __( sprintf('The layout %s cannot be saved, the post name  %s is already taken. Please try with a different name.', $json['name'], $json['slug'] ), 'wpv-views') ) ) );

						die();
					}
					else
					{
						if( $post->post_name != $json['slug'] )
						{
							$slug = get_sample_permalink( $_POST['layout_id'], $json['name'], $json['slug'] );
							$slug = $slug[1];
						}
						else{
							$slug = $json['slug'];
						}

						$postarr = apply_filters('ddl_layout_post_save',array(
							'ID' => $_POST['layout_id'],
							'post_title' => $json['name'],
							'post_name' => $slug
						), $json, $raw );

						$updated_id = wp_update_post($postarr);
						//TODO: we probably can remove this call to get_post to save memory
						$updated_post = get_post( $updated_id );

						$json['slug'] = $updated_post->post_name;

						if( $this->normalize_layout_slug_if_changed( $_POST['layout_id'],  $json, $post->post_name ) )
						{
							$msg['Data']['slug'] = urldecode( $updated_post->post_name );
						}

					}

				}
				if ( $raw === WPDD_Layouts::get_layout_settings( $_POST['layout_id'] ) ) {
					// no need to save as it hasn't changed.
					$up = false;
				} else {


					$up = WPDD_Layouts::save_layout_settings(
                        $_POST['layout_id'],
                        apply_filters('ddl_layout_settings_save',$json, $post, $raw )
                    );
				}


				// I commented out !empty( $_POST['layout_css'] ) to allow users to erase css entirely
				if( isset( $_POST['layout_css'] ) /*&& !empty( $_POST['layout_css'] )*/ )
				{
                    $css = $this->handle_layout_css( stripslashes($_POST['layout_css']) );
                    if( $css !== false ){
                        $msg['Data']['css_saved'] = $css;
                    } else {
                        $msg['Data']['css_saved'] = 'no';
                    }
				}

				if( $children_to_delete && !empty($children_to_delete) )
				{
					$delete_children = $this->purge_layout_children( $children_to_delete, $child_delete_mode );
					if( $delete_children ) {
						$msg['Data']['layout_children_deleted'] = $delete_children;
					}
				}

				$wpddlayout->register_strings_for_translation($_POST['layout_id']);

				$msg['message']['layout_changed'] = $up;

				if (isset($_POST['silent']) && $_POST['silent'] == true) {
					$msg['message']['silent'] = true;
				} else {
					$msg['message']['silent'] = false;
				}

				$send = wp_json_encode( $msg );
                WPDD_Layouts::set_toolset_edit_last( $_POST['layout_id'], $up );
                
                // Update Visual Editor (Text Cell) preferred editor for new cells
                if( isset( $_POST['preferred_editor'] ) && preg_match( '/^(codemirror|tinymce)$/', $_POST['preferred_editor'] ) ) {
                    update_user_option( get_current_user_id(), 'ddl_preferred_editor', $_POST['preferred_editor'] );
                }
			}
		}
		else
		{
			$send = wp_json_encode(array( "Data" => array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) ) );
		}
		echo $send;
		die();
	}

	private function handle_archives_data_save($archives, $layout_id)
	{
		global $wpddlayout;
		$check = $wpddlayout->layout_post_loop_cell_manager->get_layout_loops( $layout_id );

		if( $archives !== $check )
		{
			$wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $archives, $layout_id );
		}
	}

	private function normalize_layout_slug_if_changed( $layout_id, $layout_data, $previous_slug)
	{

		$current = (object) $layout_data;

		if( $current->slug === $previous_slug ) return false;

		$this->normalize_posts_where_used_data_on_slug_change( $current->slug, $previous_slug );

		if( property_exists($current, 'has_child') && $current->has_child === true )
		{
			$this->normalize_children_on_slug_change( $current, $current->slug, $previous_slug );
		}

		return true;
	}

	private function normalize_posts_where_used_data_on_slug_change( $slug, $previous_slug )
	{
		global $wpdb;

		$sql = $wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_key = %s AND meta_value = %s", sanitize_text_field($slug), WPDD_Layouts_PostTypesManager::META_KEY, $previous_slug  );

		$wpdb->query( $sql );
	}

	private function normalize_children_on_slug_change( $layout, $slug, $previous_slug )
	{
		global $wpddlayout;

		$defaults = array(
			'posts_per_page' => -1,
			'post_type' => WPDDL_LAYOUTS_POST_TYPE,
			'suppress_filters' => true,
			'post_status' => 'publish',
			'posts_per_page' => -1
		);

		$query = new WP_Query($defaults);

		$list = $query->posts;

		$children = DDL_GroupedLayouts::get_children( $layout, $list, $previous_slug);

		if( !is_array($children) || sizeof($children) === 0 ) return;

		if( is_array($children) && sizeof($children) > 0 )
		{
			foreach( $children as $child )
			{
				$current = WPDD_Layouts::get_layout_settings( $child, true );
				$current->parent = $slug;
				WPDD_Layouts::save_layout_settings( $child, $current );
			}
		}
	}

	private function purge_layout_children( $children, $action )
	{
		global $wpddlayout;

		if( !is_array( $children ) ) return false;

		$ret = array();

		foreach( $children as $child )
		{
			$id = intval($child);
			$layout = WPDD_Layouts::get_layout_settings($id, true);
			$layout->parent = '';
			WPDD_Layouts::save_layout_settings( $id, $layout );

			if( $action === 'delete' ) {
				// We also need to delete grandchildren
				$layout = $wpddlayout->get_layout_from_id($id);
				$grand_children = $layout->get_children();
				$this->purge_layout_children($grand_children, $action);
				$wpddlayout->post_types_manager->purge_layout_post_type_data( $id );
				$ret[] = wp_trash_post( $id );
			}
		}

		return true;
	}

	private function handle_layout_css( $css )
	{
		global $wpddlayout;
		return $wpddlayout->css_manager->handle_layout_css_save( $css );
	}
	//TODO:this function is depracated
	private function handle_post_type_data_save( $post_types, $layout_id )
	{
		global $wpddlayout;

		$save = $post_types['layout_'.$layout_id];
		$check = $wpddlayout->post_types_manager->get_layout_post_types( $layout_id );

		if( $save === $check || $post_types === null || !$post_types )
		{
			return false;
		}

		return $wpddlayout->post_types_manager->handle_post_type_data_save( $layout_id, $post_types );
	}

	public function get_layout_parents_callback() {
		global $wpddlayout;

		$parents = array();

		$layout = $wpddlayout->get_layout( $_POST['layout_name'] );

		if ($layout) {
			$parent_layout = $layout->get_parent_layout();


			while ($parent_layout) {
				$parents[] = $parent_layout->get_post_slug();

				$parent_layout = $parent_layout->get_parent_layout();
			}
		}

		echo wp_json_encode($parents);

		die();
	}

	public function check_for_parents_loop_callback () {
		global $wpddlayout;

		$loop_found = false;

		$layout = $wpddlayout->get_layout( $_POST['new_parent_layout_name'] );

		if ($layout) {
			$parent_layout = $layout->get_parent_layout();

			while ($parent_layout) {
				if ($_POST['layout_name'] == $parent_layout->get_name()) {
					$loop_found = true;
					break;
				}

				$parent_layout = $parent_layout->get_parent_layout();
			}
		}

		if ($loop_found) {
			echo wp_json_encode(array('error' => sprintf(__("You can't use %s as a parent layout as it or one of its parents has the current layout as a parent.", 'ddl-layouts'), '<strong>' . $_POST['new_parent_layout_name'] . '</strong>') ) );
		} else {
			echo wp_json_encode(array('error' => ''));
		}

		die();

	}

	public function check_for_parent_child_layout_width_callback () {
		global $wpddlayout;

		$layout = $wpddlayout->get_layout( $_POST['parent_layout_name'] );

		$result = wp_json_encode(array('error' => ''));

		if ($layout) {
			$child_layout_width = $layout->get_width_of_child_layout_cell();

			if ($child_layout_width != $_POST['width']) {
				$result = wp_json_encode(array('error' => sprintf(__("This layout width is %d and the child layout width in %s is %d. This layout may not display correctly.", 'ddl-layouts'), $_POST['width'], '<strong>' . $_POST['parent_layout_title'] . '</strong>', $child_layout_width) ) );
			}
		}

		echo $result;

		die();
	}

	function preload_styles(){
		global $wpddlayout;

		$wpddlayout->enqueue_styles(
			array(
				'progress-bar-css' ,
				'toolset-font-awesome',
				'toolset-notifications-css',
				'jq-snippet-css',
				'wp-jquery-ui-dialog',
				'wp-editor-layouts-css',
				'toolset-colorbox',
				'toolset-common',
				'ddl-dialogs-css',
				'wp-pointer' ,
				'toolset-select2-css',
				'layouts-select2-overrides-css',
				'wp-mediaelement',
			)
		);

		$wpddlayout->enqueue_cell_styles();
	}

	function preload_scripts(){
		global $wpddlayout;

		//speed up ajax calls sensibly
		wp_deregister_script('heartbeat');
		wp_register_script('heartbeat', false);

		$wpddlayout->enqueue_scripts(
			array(
				'jquery-ui-cell-sortable',
				'jquery-ui-custom-sortable',
				'jquery-ui-resizable',
				'jquery-ui-tabs',
				'wp-pointer',
				'backbone',
				'select2',
				'toolset-utils',
				'wp-pointer',
				'wp-mediaelement',
				'ddl-sanitize-html',
				'ddl-sanitize-helper',
				'ddl-post-types',
				//'ddl-individual-assignment-manager',
				'ddl-editor-main',
				'media_uploader_js',
				'icl_media-manager-js',
				//'ddl-post-type-options-script'
			)
		);

		$wpddlayout->localize_script('ddl-editor-main', 'icl_media_manager', array(
				'only_img_allowed_here' => __( "You can only use an image file here", 'ddl-layouts' )
			)
		);

		$wpddlayout->localize_script('ddl-editor-main', 'DDLayout_settings', array(
				'DDL_JS' => array(
					'available_cell_types' => $wpddlayout->get_cell_types(),
					'res_path' => WPDDL_RES_RELPATH,
					'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
					'editor_lib_path' => WPDDL_GUI_RELPATH."editor/js/",
					'common_rel_path' => WPDDL_TOOLSET_COMMON_RELPATH,
					'dialogs_lib_path' => WPDDL_GUI_RELPATH."dialogs/js/",
					'layout_id' => $this->layout_id,
					'create_layout_nonce' => wp_create_nonce('create_layout_nonce'),
					'save_layout_nonce' => wp_create_nonce('save_layout_nonce'),
					'ddl-view-layout-nonce' => wp_create_nonce('ddl-view-layout-nonce'),
					'ddl_show_all_posts_nonce' => wp_create_nonce('ddl_show_all_posts_nonce'),
					'edit_layout_slug_nonce' => wp_create_nonce('edit_layout_slug_nonce'),
					'compact_display_nonce' => wp_create_nonce('compact_display_nonce'),
					'compact_display_mode' => $wpddlayout->get_option('compact_display'),
					'DEBUG' => WPDDL_DEBUG,
					'strings' => $this->get_editor_js_strings(),
					'has_theme_sections' => $wpddlayout->has_theme_sections(),
					'AMOUNT_OF_POSTS_TO_SHOW' => self::AMOUNT_OF_POSTS_TO_SHOW,
					'is_css_enabled' => $wpddlayout->css_manager->is_css_possible()
				, 'current_framework' => $wpddlayout->frameworks_options_manager->get_current_framework()
				, 'cred_layout_css_text' => __('Layouts cell styling', 'ddl-layouts')
                , 'removed_cells' => $this->removed_cells
				),
                'DDL_OPN' => WPDD_LayoutsListing::change_layout_dialog_options_name()
			)
		);

		$wpddlayout->enqueue_cell_scripts();

	}

	function load_latest_backbone() {
		// load our own version of backbone for the editor.
		wp_dequeue_script('backbone');
		wp_deregister_script('backbone');
		wp_register_script('backbone', WPDDL_RES_RELPATH . '/js/external_libraries/backbone-min.js', array('underscore','jquery'), '1.1.0');
		wp_enqueue_script('backbone');

	}

	function pre_render_editor($inline) {

        ?>

		<div class="wrap" id="js-dd-layout-editor">
        <?php if( WPDDL_DEBUG ): ?>
        Last edited at: <?php echo WPDD_Layouts::get_toolset_edit_last_in_readable_format($this->layout_id) ?>
            <?php endif; ?>
		<?php

		$post = $this->post;

		if (!$inline) {
			include_once 'templates/editor_header_box.tpl.php';
		}

	}

	function render_editor($inline){
		$this->ddl_render_editor($inline);
	}

    function ddl_render_editor($inline){
        global $wpddlayout;
        // Get layout
        if ($inline) {
            $post = get_post($_GET['post']);
            $layout_json = WPDD_Layouts::get_layout_json_settings_encoded_64($post->ID);
            if (!$layout_json) {
                // This post doesn't have a layout so create an empty one
                $preset_dir = WPDDL_RES_ABSPATH . '/preset-layouts/';
                $layout = $wpddlayout->load_layout($preset_dir . '4-evenly-spaced-columns.ddl');

                // Force fluid when using in post editor.
                $layout['type'] = 'fluid';
                for ($i = 0; $i < sizeof($layout['Rows']); $i++) {
                    $layout['Rows'][$i]['layout_type'] = 'fluid';
                }
                $layout_json = wp_json_encode($layout);
            }
        } else {
            $post = get_post($_GET['layout_id']);
            // $layout_json_not_decoded = WPDD_Layouts::get_layout_settings($post->ID);
            $layout_json = WPDD_Layouts::get_layout_json_settings_encoded_64($post->ID);
        }

        ob_start();
        WPDD_GUI_EDITOR::load_js_templates('/js/templates');
        include_once 'templates/editor_box.tpl.php';
        echo ob_get_clean();

    }

	function after_render_editor() {

		?>
		</div> <!-- .wrap -->

	<?php
	}

	function layout_actions(){
		if(isset($_REQUEST['action'])){
			switch ($_REQUEST['action']) {
				case 'trash':
					$this->delete_layout($_REQUEST['post']);
					break;
				default:
					break;
			}
		}
	}

	function delete_layout($layout_id){
		$post_id = $layout_id;
		wp_delete_post($post_id, true);
		delete_post_meta($post_id, WPDDL_LAYOUTS_SETTINGS);
		delete_post_meta($post_id, 'dd_layouts_header');
		delete_post_meta($post_id, 'dd_layouts_styles');
		$url = home_url( 'wp-admin').'/admin.php?page=dd_layouts';
		header("Location: $url", true, 302);
		die();
	}

	public static function load_js_templates( $tpls_dir )
	{
		global $wpddlayout;

		WPDD_FileManager::include_files_from_dir( dirname(__FILE__), $tpls_dir );

		echo apply_filters("ddl_print_cells_templates_in_editor_page", $wpddlayout->get_cell_templates() );
	}

	function get_editor_js_strings () {
		return array(
			'only_one_cell' => __("You can't insert another cell of this type. Only one cell of this type is allowed per layout.", 'ddl-layouts'),
			'save_required' => __('This layout has changed', 'ddl-layouts'),
			'page_leave_warning' => __('This layout has changed. Are you sure you want to leave this page?', 'ddl-layouts'),
			'save_before_edit_parent' => __('Do you want to save the current layout before editing the parent layout?', 'ddl-layouts'),
			'save_required_edit_child' => __('Switching to the child layout', 'ddl-layouts'),
			'save_before_edit_child' => __('Do you want to save the current layout before editing the child layout?', 'ddl-layouts'),
			'save_layout_yes' => __('Save layout', 'ddl-layouts'),
			'save_layout_no' => __('Discard changes', 'ddl-layouts'),
			'save_required_new_child' => __('Creating a new child layout', 'ddl-layouts'),
			'save_before_creating_new_child' => __('Do you want to save the current layout before creating a new child layout?', 'ddl-layouts'),
			'no_parent' => __('No parent set', 'ddl-layouts'),
			'content_template' => __('Content Template', 'ddl-layouts'),
			'save_complete' => __('The layout has been saved.', 'ddl-layouts'),
			'one_column' => __('1 Column', 'ddl-layouts'),
			'columns' => __('Columns', 'ddl-layouts'),
			'at_least_class_or_id' => __('You should define either an ID or one class for this cell to style its CSS', 'ddl-layouts'),
            'ajax_error' => __('There was an error during the ajax request, make sure the data you send are in json format.', 'ddl-layouts'),
            'select_range_one_column' => __('Move the mouse to resize, click again to create.', 'ddl-layouts'),
			'select_range_one_column_short' => __('1 column', 'ddl-layouts'),
			'select_range_more_columns' => __('%d columns - click again to create', 'ddl-layouts'),
			'select_range_more_columns_short' => __('%d columns', 'ddl-layouts'),
			'dialog_yes' => __('Yes', 'ddl-layouts'),
			'dialog_no' => __('No', 'ddl-layouts'),
			'dialog_cancel' => __('Cancel', 'ddl-layouts'),
			'slug_unwanted_character' => __("The slug should contain only lower case letters", 'ddl-layouts' ),
			'save_and_also_save_css' => __('The layout has been saved. Layouts CSS has been updated.', 'ddl-layouts'),
			'save_and_save_css_problem' => __('The layout has been saved. Layouts CSS has NOT been updated. Please retry or check write permissions for uploads directory.', 'ddl-layouts'),
			'invalid_slug' => __("The entered value for layout slug shouldn't be an empty string.",'ddl-layouts'),
			'title_not_empty_string' => __("The title shouldn't be an empty string.", 'ddl-layouts'),
			'more_than_4_rows' => __('If you need more than 4 rows you can add them later in the editor', 'ddl-layouts'),
			'id_duplicate' => __("This id is already used in the current layout, please select a unique id for this element", 'ddl-layouts'),
			'edit_cell' => __('Edit cell', 'ddl-layouts'),
			'remove_cell' => __('Remove cell', 'ddl-layouts'),
			'set_cell_type' => __('Select cell type', 'ddl-layouts'),
			'show_grid_edit' => __('Show grid edit', 'ddl-layouts'),
			'hide_grid_edit' => __('Hide grid edit', 'ddl-layouts'),
			'css_file_loading_problem' => __('It is not possible to handle CSS loading in the front end. You should either make your uploads directory writable by the server, or use permalinks.', 'ddl-layouts'),
			'save_required_open_view' => __('Switching to the View', 'ddl-layouts'),
			'save_before_open_view' => __('The layout has changed. Do you want to save the current layout before switching to the View?', 'ddl-layouts'),
			'close_view_iframe' => __('Close this view and return to the layout', 'ddl-layouts'),
			'save_and_close_view_iframe' => __('Save and Close this view and return to the layout', 'ddl-layouts'),
			'close_view_iframe_without_save' => __('Close this view and discard the changes', 'ddl-layouts'),
			'video_message_text' => __( 'Please enter a valid YouTube video URL.', 'ddl-layouts' ),
			'title_one_comment_text' => __( 'The text for one comment title is missing', 'ddl-layouts' ),
			'title_multi_comments_text' => __( 'The text for multiple comments title missing', 'ddl-layouts' ),
			'this_field_is_required' => __( 'This field cannot be empty', 'ddl-layouts' ),
			'no_changes_nothing_to_save' => __( 'No changes were made, nothing to save to the server.', 'ddl-layouts' ),
			'no_drop_title' => __('You cannot drag to here', 'ddl-layouts'),
			'no_drop_content' => __("You cannot drag the cell into the target row because the cell is %NN% columns wide and the target row has only %MM% free columns. %OO%To move this cell, first resize it and make it at most %MM% columns wide.", 'ddl-layouts'),
			'no_drop_content_wider' => __("The target row's columns are wider, so the space appears sufficient, but there is not enough room for the cell." . ' ', 'ddl-layouts'),
			'no_more_pages' => __("This layout is already assigned to all pages.", 'ddl-layouts'),
			'no_more_posts' => __("This layout is already assigned to all posts items.", 'ddl-layouts'),
			'new_ct_message_title' => __("Content Template", 'ddl-layouts'),
			'new_ct_message' => __("Insert fields to display parts of the content and add HTML around them for styling.", 'ddl-layouts'),
			'this_is_a_parent_layout' => __('This layout has children. You should assign one of its children to content and not this parent layout.', 'ddl-layouts'),
            'switch_editor_warning_message' => __('You are about to switch editing modes. Please note that this may change the content of the cell. Are you sure?', 'ddl-layouts'),
		    'content_template_should_have_name' => __('A Content Template should have a name please provide one.', 'ddl-layouts'),
            'removed_cells_message' => sprintf( __('%d orphaned Content Template cell(s): %s have been deleted from this Layout since the associated Views Content Template was deleted outside the Layouts editor', 'ddl-layouts'), count($this->removed_cells), $this->removed_cells && count($this->removed_cells) > 0 ? implode(', ', $this->removed_cells) : '' )
            );
	}

	public static function print_layouts_css()
	{
		global $wpddlayout;
		echo $wpddlayout->get_layout_css();
	}

	public function add_where_used_links( $layout_id = false, $all = false ) {

		return '';

		global $wpddlayout;

		$get = $layout_id ? $layout_id :$_GET['layout_id'];

		$post_types = $wpddlayout->post_types_manager->get_post_types_from_wp();

		$items = $this->get_where_used_x_amount_of_posts( $get, $all );

		$posts = $items->posts;

		ob_start();

		include_once WPDDL_GUI_ABSPATH.'editor/templates/list-layouts-where_used.box.tpl.php';

		return ob_get_clean();
	}

	public function show_all_posts_callback()
	{
        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['ddl_show_all_posts_nonce'], 'ddl_show_all_posts_nonce' ) )
		{
			$amount = $_POST['amount'] == 'all' ? true : false;
			$send = wp_json_encode( array( 'Data' => array( 'where_used_html' => $this->add_where_used_links( $_POST['layout_id'], $amount ) ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	public function print_where_used_links()
	{
		echo '<div id="js-print_where_used_links dd-layouts-wrap">' . $this->add_where_used_links() . '</div>';
	}

	public function get_where_used_x_amount_of_posts( $layout_id, $all = false, $amount = self::AMOUNT_OF_POSTS_TO_SHOW )
	{
		global $wpddlayout;

		$ret = new stdClass();
		$ret->posts = array();
		$temp = array();

		$post_types = $wpddlayout->individual_assignment_manager->get_post_types( $layout_id );
		$post_types_query = array_diff( $wpddlayout->post_types_manager->get_post_types_from_wp( 'names' ), $post_types );

		$posts = $wpddlayout->get_where_used( $layout_id, false, true, $amount, array('publish', 'draft'), 'default', $post_types_query, true );

		$ret->found_posts = $wpddlayout->get_where_used_count();
		$ret->shown_posts = 0;

		if( $all === true ) $amount = count( $posts );

		foreach( $posts as $post )
		{
			if( !isset($temp[$post->post_type]) )
			{
				$temp[$post->post_type] = array();
			}

			$len = count( $temp[$post->post_type] );

			if( $len < $amount )
			{
				$item = new stdClass();
				$item->post_title = $post->post_title;
				$item->ID = $post->ID;
				$item->post_name = $post->post_name;
				$item->post_type = $post->post_type;
				$item->edit_link = get_edit_post_link( $post->ID);
				$item->permalink = get_permalink( $post->ID );
				$ret->posts[] = $item;
				$ret->shown_posts++;
			}

			$temp[$post->post_type][] = $post->ID;
		}

		$keys = array_keys($temp);

		foreach( $keys as $key )
		{
			$ret->{$key} = count($temp[$key]);
		}


		return $ret;
	}

	public function view_layout_from_editor_callback( )
	{
		global $wpddlayout;

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['ddl-view-layout-nonce'], 'ddl-view-layout-nonce' ) )
		{

			$layout = WPDD_Layouts::get_layout_settings($_POST['layout_id'], true);
			if ($layout && isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {
				$send = wp_json_encode( array( 'message' =>  __( "This layout contains a child layout and can't be viewed directly.", 'ddl-layouts') .
					'<br />'.
					__( "You'll need to switch to one of the child layouts and view it.", 'ddl-layouts')
				) );
			} else {

				$items = $this->get_where_used_x_amount_of_posts( $_POST['layout_id'], false, 3 );
				$posts = $items->posts;
				$layout_post_types = $wpddlayout->post_types_manager->get_layout_post_types( $_POST['layout_id'] );


				$loops = $wpddlayout->layout_post_loop_cell_manager->get_layout_loops( $_POST['layout_id'] );

				if( count($posts) === 0 && count($loops) === 0 && count($layout_post_types) === 0 )
				{
					$send = wp_json_encode( array( 'message' =>  __( sprintf("This layout is not assigned to any content. %sFirst, assign it to content and then you can view it on the site's front-end. %sYou can assign this layout to content at the bottom of the layout editor.", '<br>', '<br>' ), 'ddl-layouts') ) );
				}
				else
				{
					$items = array();

					foreach( $layout_post_types as $post_type ){
						$push = $this->get_x_posts_of_type($post_type, $_POST['layout_id'], 1);
						if( is_array( $push ) ){
							$posts = array_merge( $posts, $push );
						}
					}

					foreach( $posts as $post )
					{
						$post_types = $wpddlayout->post_types_manager->get_post_types_from_wp();
						$label = $post_types[$post->post_type]->labels->singular_name;
						$labels = $post_types[$post->post_type]->labels->name;
						$item = array( 'href' => get_permalink( $post->ID ), 'title' => $post->post_title, 'type' => $label, 'types' => $labels  );
						if( in_array( $item, $items ) === false ){
							$items[] = $item;
						}
					}


					foreach( $loops as $loop )
					{
						$push = $wpddlayout->layout_post_loop_cell_manager->get_loop_display_object( $loop );
						if( null !== $push  )
							array_push( $items, $push );
					}

					$send = wp_json_encode( array(
							'Data' =>  $items,
							'message' =>  __( sprintf("This layout is not assigned to any content. %sFirst, assign it to content and then you can view it on the site's front-end. %sYou can assign this layout to content at the bottom of the layout editor.", '<br>', '<br>' ), 'ddl-layouts'),
                            'no_preview_message' =>  __( 'No previews available', 'ddl-layouts')
						)
					);

				}
			}
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);

	}

	public function add_video_toolbar()
	{
		include_once WPDDL_GUI_ABSPATH.'editor/templates/tutorial-video-bar.box.tpl.php';
	}

	private function get_where_used_lists( $layout_id = null )
	{
		global $wpddlayout;

		$id = $layout_id ? $layout_id : $this->layout_id;

		$post_types = $wpddlayout->post_types_manager->get_layout_post_types_object( $id );
		//	$post_types_assigned = $wpddlayout->individual_assignment_manager->get_post_types( $layout_id );
		$amount = self::AMOUNT_OF_POSTS_TO_SHOW;

		$items = $this->get_where_used_x_amount_of_posts( $id, true, $amount );

		$posts = $items->posts;

		$loops = $wpddlayout->layout_post_loop_cell_manager->get_layout_loops( $id );

		if( (!$post_types || count( $post_types ) === 0) && count( $posts  ) === 0 && count( $loops ) === 0 )
		{
			return null;
		}

		$ret = new stdClass();

		if( count( $posts  ) > 0 )
		{
			$ret->posts = $posts;
		}

		if( $post_types && count( $post_types ) )
		{
			$ret->post_types = $post_types;
		}

		if( count( $loops ) > 0 )
		{
			$loops_display = array();

			foreach( $loops as $loop )
			{
				$push = $wpddlayout->layout_post_loop_cell_manager->get_loop_display_object( $loop );

				if( null !== $push  )
					$push['name'] =  $loop;
				array_push( $loops_display, $push );
			}

			$ret->loops = $loops_display;
		}

		return $ret;
	}

	public function get_x_posts_of_type( $post_type, $layout_id, $amount = self::AMOUNT_OF_POSTS_TO_SHOW  )
	{

		global $wpddlayout;

		$layout = $wpddlayout->get_layout_from_id( $layout_id );

		$args = array(
			'posts_per_page' => $amount,
			'post_type' => $post_type,
            'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'inherit' ),
			'meta_query' => array (
				array (
					'key' => WPDDL_LAYOUTS_META_KEY,
					'value' => $layout->get_post_slug(),
					'compare' => '=',
				)
			) );

		$new_query = new WP_Query( $args );

		$posts = $new_query->posts;

		return count( $posts ) > 0 && isset( $posts[0] ) ? $posts : null;
	}

	function ddl_get_post_type_batched_preview_permalink($post_type, $post_id){
		global $wpddlayout;
		$id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type);

		if( $id && $id == $this->layout_id  )
		{
			$loop = (object) $wpddlayout->layout_post_loop_cell_manager->get_loop_display_object(WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type);
			return $loop->href;
		} else {
			return get_permalink( $post_id );
		}
	}

	public function add_select_post_types( )
	{
		global $wpddlayout;
		$this->layout_id = $this->layout_id ? $this->layout_id : $_GET['layout_id'];
		$lists = $this->list_where_used;
		$current = $this->layout_id;
		?>
		<div class="dd-layouts-wrap">
			<div class="dd-layouts-where-used">
				<?php $title_display = $lists === null ? __('This layout is not used for any content.', 'ddl-layout' ) : __('This layout is used for:', 'ddl-layouts' );
				include WPDDL_GUI_ABSPATH . 'editor/templates/layout-content-assignment.box.tpl.php';
				?>
			</div>
		</div><!-- .dd-layouts-wrap -->

		<div class="ddl-dialog hidden layout-content-assignment-dialog js-layout-content-assignment-dialog ddl-change-layout-use-for-post-types-box-wrapper">
		</div>
	<?php
	}

	private function load_assign_dialog( $layout_id ){
		global $wpddlayout;

		$this->layout_id = $this->layout_id ? $this->layout_id : $layout_id;
		ob_start();
		?>

		<div class="js-selected-post-types-in-layout-div">

			<div class="ddl-dialog-header">
				<h2 class="js-dialog-title"><?php _e('Assign to content', 'ddl-layout'); ?></h2>
				<i class="icon-remove js-edit-dialog-close js-remove-video"></i>
			</div>

			<div class="ddl-dialog-content js-ddl-dialog-content">
				<?php
				$html = $wpddlayout->listing_page->print_dialog_checkboxes($this->layout_id, false, '', false);
				echo $html;
				?>
			</div>


			<div class="ddl-dialog-footer js-dialog-footer">
				<div class="dialog-change-use-messages" data-text="<?php echo WPDD_LayoutsListing::$OPTIONS_ALERT_TEXT; ?>"></div>
				<input type="button" class="button js-edit-dialog-close close-change-use"
					   value="<?php _e('Close', 'ddl-layouts'); ?>">
			</div>
		</div>
		<?php wp_nonce_field('layout-set-change-post-types-nonce', 'layout-set-change-post-types-nonce'); ?>
		<?php wp_nonce_field('ddl_layout_view_nonce', 'ddl_layout_view_nonce'); ?>

		<?php
		return ob_get_clean();
	}

	public function load_assign_dialog_callback(){
		if( $_POST && wp_verify_nonce( $_POST['load-assign-dialog-nonce'], 'load-assign-dialog-nonce' ) )
		{
			$send = wp_json_encode( array( 'Data' =>  $this->load_assign_dialog( $_POST['layout_id'] ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	function update_wpml_state ( ) {

        if( user_can_edit_layouts() === false ){
            die( __("You don't have permission to perform this action!", 'ddl-layouts') );
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
				'ddl_layout_view_nonce')) {
			die();
		}

		global $wpddlayout;

		$layout = $wpddlayout->get_layout_from_id($_POST['layout_id']);
		if ($_POST['register_strings'] == 'true') {
			$layout->register_strings_for_translation();
		}
		do_action('WPML_show_package_language_ui', $layout->get_string_context());

		die();
	}
}