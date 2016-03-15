<?php

add_action( 'init', 'init_layouts_theme_support', 9 );

function init_layouts_theme_support()
{
	global $wpddlayout_theme;
	$wpddlayout_theme =  WPDD_Layouts_Theme::getInstance();
}

class WPDD_Layouts_Theme {

    private $messages = array();
    private $layouts_saved = 0;
    private $css_saved = 0;
    private $layouts_deleted = 0;
    private $layouts_overwritten = 0;
    private $existing_layout = null;
    // keep track of the posts successfully assigned to imported layout
  //  private $posts_assigned = array();
    // keep track of the posts not present in the new DB which cannot be assigned
  //  private $post_not_assigned = array();

    private $imported_layouts = array();
    private static $instance;

	function __construct(){

		$this->file_manager_export = new WPDD_FileManager('/theme-dd-layouts', 'wp_nonce_export_layouts_to_theme');

        if ( is_admin() ) {
			if (isset($_GET['page']) && $_GET['page']=='dd_layout_theme_export') {
				add_action('wp_loaded', array($this, 'export_and_download_layouts'));
                add_action( 'wp_loaded', array($this, 'import_layouts') );
			}
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDD_Layouts_Theme();
        }

        return self::$instance;
    }

    function import_layouts()
    {
        if( $_POST )
        {
            if ( isset( $_POST['ddl-import'] ) && $_POST['ddl-import'] == __( 'Import', 'ddl-layouts' ) && isset( $_POST['layouts-import-nonce'] ) && wp_verify_nonce( $_POST['layouts-import-nonce'], 'layouts-import-nonce' ) ) {

                $overwrite = isset( $_POST['layouts-overwrite'] ) && $_POST['layouts-overwrite'] == 'on' ? true : false;
                $delete = isset( $_POST['layouts-delete'] ) && $_POST['layouts-delete'] == 'on' ? true : false;
                $overwrite_assignment = isset( $_POST['overwrite-layouts-assignment'] ) && $_POST['overwrite-layouts-assignment'] == 'on' ? true : false;

                $import = $this->manage_manual_import( $_FILES, $overwrite, $delete, $overwrite_assignment );

                add_action( 'admin_notices', array(&$this, 'import_upload_message'));
            }
            else if( isset( $_POST['ddl-import'] ) && $_POST['ddl-import'] == __( 'Import', 'ddl-layouts' ) && isset( $_POST['layouts-import-nonce'] ) && wp_verify_nonce( $_POST['layouts-import-nonce'], 'layouts-import-nonce' ) === false )
            {
                add_action( 'admin_notices', array(&$this, 'nonce_control_failed') );
            }
        }
    }

    function nonce_control_failed()
    { ?>
        <div class="message error"><p><?php _e('There was a security check issue while uploading the file. Nonce check failed.', 'ddl-layouts'); ?></p></div>
    <?php
    }

    function import_upload_message( )
    {
        foreach( $this->messages as $message ):
        ?>
        <div class="message <?php echo $message->result; ?>"><p><?php echo $message->message; ?></p></div>
        <?php
        endforeach;
    }

    private function manage_manual_import( $files, $overwrite, $delete, $overwrite_assignment )
    {
        if ( isset( $files['import-file'] ) ) {
            $file = $files['import-file'];
        } else {

            $this->messages[] = (object) array(
                'result' => 'error',
                'message' => __('There was a problem uploading the file. Check the file and try again', 'ddl-layouts' )
            );

            return false;
        }

        $info = pathinfo( $file['name'] );

        if( $info['extension'] == 'zip' )
        {
            $result = $this->handle_zip_file( $file, $overwrite, $delete, $overwrite_assignment );

            if( $delete )
            {
                $this->handle_layouts_to_be_deleted();
            }

            $this->handle_messages( $result, $overwrite || $overwrite_assignment , $delete, true, $info['extension'] );

        }
        else if( $info['extension'] == 'ddl' ){

            $result = $this->handle_single_layout( $file, $info, $overwrite, $delete, $overwrite_assignment );

            if( $delete )
            {
                $this->handle_layouts_to_be_deleted();
            }

            $this->handle_messages( $result, $overwrite || $overwrite_assignment, $delete, true, $info['extension'] );
        }
        else if( $info['extension'] == 'css' ){
                $result = $this->handle_single_css( $file, $overwrite );

                if( $result === false )
                {
                    $this->messages[] = (object) array(
                        'result' => 'error',
                        'message' => __('There was a problem saving the CSS.', 'ddl-layouts' )
                    );
                }
                else
                {
                    if( $overwrite === false )
                    {
                        $css_message =  __('The Layouts CSS was created.', 'ddl-layouts');
                    }
                    else{
                        $css_message = __('The Layouts CSS was overwritten.', 'ddl-layouts');
                    }

                    $this->messages[] = (object) array(
                        'result' => 'updated',
                        'message' => $css_message
                    );
                }

                return true;
        }
        else{
            $this->messages[] = (object) array(
                'result' => 'error',
                'message' => __('The file type is not compatible with layouts. The imported files should be a single .ddl file, a single .css file or a .zip archive of .ddl and .css files.', 'ddl-layouts' )
            );
            return false;
        }

        return  true;
    }

    private function handle_single_layout( $file, $info, $overwrite = false, $delete = false, $overwrite_assignment = false )
    {

        $layout_name = $info['filename'];

        $layout_json = file_get_contents( $file['tmp_name'] );

        $ret = $this->layout_handle_save( $layout_json, $layout_name, $overwrite, $delete, $overwrite_assignment );

        return $ret === 0 ? false : true;
    }

    private function handle_single_css( $file,  $overwrite = false )
    {
        $data = file_get_contents( $file['tmp_name'] );

        $ret = $this->save_css( $data, $overwrite );

        return $ret;
    }

    private function handle_messages( $result, $overwrite, $delete, $extension  )
    {

        $plural = __('layouts were', 'ddl-layouts');
        $singular = __('layout was', 'ddl-layouts');

        if( $this->layouts_saved !== 1 )
        {
            $saved = $plural;
        }
        else
        {
            $saved = $singular;
        }

        if( $this->layouts_overwritten !== 1 )
        {
            $overwritten = $plural;
        }
        else
        {
            $overwritten = $singular;
        }

        if( $this->layouts_deleted !== 1 )
        {
            $deleted = $plural;
        }
        else
        {
            $deleted = $singular;
        }

        if( $result === false )
        {
            $this->messages[] = (object) array(
                'result' => 'error',
                'message' => __( sprintf('Unable to open %s file.', $extension), 'ddl-layouts' )
            );

            return false;
        }
        else
        {
            if( $overwrite === false )
            {
                $css_message = $this->css_saved === 0 ? '' : __('The Layouts CSS was created.', 'ddl-layouts');
            }
            else{
                $css_message = $this->css_saved === 0 ? '' : __('The Layouts CSS was overwritten.', 'ddl-layouts');
            }


            if( $overwrite === false && $delete === false )
            {
                $this->messages[] = (object) array(
                    'result' => 'updated',
                    'message' => __( sprintf('%d %s imported. %s', $this->layouts_saved, $saved, $css_message), 'ddl-layouts' )
                );
            }
            elseif( $overwrite === true && $delete === false )
            {
                $this->messages[] = (object) array(
                    'result' => 'updated',
                    'message' => __( sprintf('%d %s imported, %d %s overwritten. %s', $this->layouts_saved,$saved, $this->layouts_overwritten, $overwritten, $css_message), 'ddl-layouts' )
                );
            }
            elseif( $overwrite === false && $delete === true )
            {
                $this->messages[] = (object) array(
                    'result' => 'updated',
                    'message' => __( sprintf('%d %s imported, %d %s deleted. %s', $this->layouts_saved, $saved, $this->layouts_deleted, $deleted, $css_message), 'ddl-layouts' )
                );
            }
            elseif( $overwrite === true && $delete === true )
            {
                $this->messages[] = (object) array(
                    'result' => 'updated',
                    'message' => __( sprintf('%d %s imported, %d %s overwritten, %s %s deleted. %s', $this->layouts_saved, $saved, $this->layouts_overwritten, $overwritten, $this->layouts_deleted, $deleted, $css_message), 'ddl-layouts' )
                );
            }
        }
    }

    private function handle_layouts_to_be_deleted()
    {
          if( is_array( $this->imported_layouts ) && count( $this->imported_layouts ) > 0  )
          {
              $posts = get_posts(
                    array(
                        'post_type' => WPDDL_LAYOUTS_POST_TYPE,
                        'post__not_in' => $this->imported_layouts,
                        'posts_per_page'=> -1
                    )
              );

              if( is_array( $posts ) && count( $posts ) > 0 )
              {
                  foreach( $posts as $post )
                  {
                      $ret = wp_delete_post( $post->ID, true );

                      if( $ret )
                      {
                          $this->layouts_deleted++;
                      }
                  }
              }
          }
    }

    function handle_zip_file( $file, $overwrite, $delete, $overwrite_assignment )
    {
        $zip = zip_open( urldecode( $file['tmp_name'] ) );
        if ( is_resource( $zip ) ) {
            while ( ( $zip_entry = zip_read( $zip ) ) !== false ) {
                if( self::get_extension(  zip_entry_name( $zip_entry ) ) === 'ddl' )
                {
                    $data = @zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );
                    $name = self::get_file_nicename( zip_entry_name( $zip_entry ) );
                    $this->layout_handle_save( $data, $name, $overwrite, $delete, $overwrite_assignment );

                }
                elseif( self::get_extension(  zip_entry_name( $zip_entry ) ) === 'css' )
                {
                    $data = @zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );
                    $this->save_css( $data, $overwrite );
                }
            }

            return true;

        } else {
            return false;
        }

        return false;
    }

    private function save_css( $data, $overwrite )
    {
        global $wpddlayout;
        $save = $wpddlayout->css_manager->import_css( $data, $overwrite );
        if( $save ) $this->css_saved++;
        return $save;
    }

    public function layout_handle_save( $layout_json, $layout_name, $overwrite = false, $delete = false, $overwrite_assignment = false )
    {
        if( is_object($layout_json) || is_array($layout_json) ){
            $layout = $layout_json;
        } else {
            $layout = json_decode(str_replace('\\\"', '\"', $layout_json));
        }


        if ( is_null($layout) === false ) {

	        try{
		        $layout_json = wp_json_encode(self::fix_toolset_association_on_import_export( $layout, 'import' ) );
	        } catch(Exception $e){
		        printf("Error: %s in %s at %d", $e->getMessage(), $e->getFile(), $e->getLine() );
	        }


            $this->existing_layout = self::layout_exists( $layout_name );

            if ( $overwrite === false ) {

                if( $this->existing_layout === null )
                {

                    $ret = $this->save_layout( $layout_name, $layout_json, $layout, $overwrite_assignment );
                    if( $ret !== 0 ){
                        if( $delete ) $this->imported_layouts[] = $ret;
                        $this->layouts_saved++;
                    }

                    return $ret;

                } elseif( $this->existing_layout !== null )  {

                        $ret = $this->manage_assignments( $this->existing_layout, $layout, $overwrite_assignment );
                        if( $ret ){
                            $this->layouts_overwritten++;
                            return $ret;
                        }
                }
            }
            elseif( $overwrite === true )
            {
                if( $this->existing_layout === null )
                {
                    $ret = $this->save_layout( $layout_name, $layout_json, $layout, $overwrite_assignment );
                    if( $ret !== 0 ){
                        if( $delete ) $this->imported_layouts[] = $ret;
                        $this->layouts_saved++;
                    }
                    return $ret;
                }
                else{
                    $ret = $this->update_layout( $this->existing_layout, $layout_name, $layout_json, $layout, $overwrite_assignment );
                    if( $ret !== 0 ){
                        if( $delete ) $this->imported_layouts[] = $ret;
                        $this->layouts_overwritten++;
                    }
                    return $ret;
                }
            }
        }

        return false;
    }

    private function save_layout( $layout_name, $layout_json, $layout, $overwrite_assignment = false )
    {
        $postarr = array(
            'post_title' => is_object($layout) ? $layout->name : $layout['name'],
            'post_name' => $layout_name,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => WPDDL_LAYOUTS_POST_TYPE
        );

        $post_id = wp_insert_post($postarr);

        $layout_array = json_decode( $layout_json, true );

        $layout_array['id'] = $post_id;

        $this->manage_assignments( $post_id, $layout, $overwrite_assignment );

		$translations = $this->get_translations($layout_array);

        WPDD_Layouts::save_layout_settings( $post_id, $this->clean_up_import_data($layout_array) );

		$this->set_translations($translations, $post_id);

        return $post_id;
    }

	private function clean_up_import_data($layout_array){
		unset( $layout_array['archives'] );
		unset( $layout_array['posts'] );
		unset( $layout_array['post_types'] );
		return $layout_array;
	}

    // TODO: an option can be added to preserve status
    private function update_layout( $id, $layout_name, $layout_json, $layout, $overwrite_assignment )
    {
        $postarr = array(
            'ID'           => $id,
            'post_title' => is_object($layout) ? $layout->name : $layout['name'],
            'post_name' => $layout_name
        );

        $post_id = wp_update_post($postarr);

        $this->manage_assignments( $post_id, $layout, $overwrite_assignment );

        $layout_array = json_decode($layout_json, true );
        $translations = $this->get_translations( $layout_array );

        WPDD_Layouts::save_layout_settings($id, $this->clean_up_import_data($layout_array) );

        $this->set_translations($translations, $id);

        return $post_id;
    }


    private function manage_assignments( $post_id, $layout, $overwrite_assignment ){
            $archives = $this->write_archive_assignments($post_id, $layout, $overwrite_assignment);
            $posts = $this->write_single_posts_assignments($post_id, $layout, $overwrite_assignment);
	        $post_types = $this->write_post_types_assignments($post_id, $layout, $overwrite_assignment);
            return $archives || $posts || $post_types;
    }

    private function write_archive_assignments($post_id, $layout, $overwrite_assignment){

        if( !isset($layout->archives) || count( $layout->archives ) === 0 ) return false;

        global $wpddlayout;

        $options = $wpddlayout->layout_post_loop_cell_manager->get_options_general();

        if(
            ( $overwrite_assignment === true && $layout->archives && count( $layout->archives ) > 0 ) ||
            ( $options === false || count( $options ) === 0 )
        )
        {
            $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $layout->archives, $post_id );
            return $post_id;
        }

        return false;
    }

    private function write_single_posts_assignments($post_id, $layout, $overwrite_assignment){

        if( !isset($layout->posts) || is_array($layout->posts)  === false || count($layout->posts) === 0 ) return false;

        if(
            ( $overwrite_assignment === true && count( $layout->posts ) > 0 )
        )
        {
            $posts = $this->get_receiving_database_posts_id( $layout->posts );
            $ret = $this->assign_layout_to_single_pages( $layout, $posts, $overwrite_assignment );
            if( $ret ){
                return $post_id;
            }
        }

        return false;
    }

    private function get_receiving_database_posts_id( $posts_data ){

            $post_names_array = array();

            foreach( $posts_data as $post_data ){
                $post_names_array[] = $post_data->post_name;
            }

        global $wpddlayout;

        $posts_ids = $wpddlayout->individual_assignment_manager->fetch_posts_by_slug( $post_names_array );

        if( $posts_ids === null ) return null;

        foreach( $posts_data as $post_data ){
                if( isset($posts_ids[$post_data->post_name]) &&
                    is_object($posts_ids[$post_data->post_name]) &&
                    $posts_ids[$post_data->post_name]->post_type === $post_data->post_type
                ){
                    $post_data->ID = $posts_ids[$post_data->post_name]->ID;
                } else{
                    $post_data->ID = false;
                }
        }

        return $posts_data;
    }

    private function assign_layout_to_single_pages( $layout, $posts, $overwrite_assignment ){

        if( !$posts || is_array($posts) === false || count($posts) === 0 ) return false;

        $ret = array();

        if( $overwrite_assignment && $posts && count($posts) > 0 ){

            global $wpddlayout;

            foreach( $posts as $post ){
                $slug = $layout->slug;
                $post_id = $post->ID;
                $post_type = $post->post_type;
                $template = property_exists($post, '_wp_page_template') ? $post->_wp_page_template : false;
                if( $post_id ){
                  //  $this->posts_assigned[] = $post->post_name;
                    $ret[] = $wpddlayout->post_types_manager->update_single_post_layout( $slug, $post_id, $post_type, $template );
                } else {
                 //   $this->post_not_assigned[] = $post->post_name;
                }
            }
        }

        return count( $ret ) > 0;
    }

	private function write_post_types_assignments( $post_id, $layout, $overwrite_assignment ){

		if( $overwrite_assignment === false ) return $overwrite_assignment;

		if( !isset($layout->post_types) || is_object($layout->post_types) === false || count( (array) $layout->post_types ) === 0 ) return false;

		global $wpddlayout;

		$to_set = array();
		$to_bulk = array();

		foreach( $layout->post_types as $post_type => $bulk ){
            if( post_type_exists($post_type) ){

                if( $bulk ){
                    $to_bulk[] = $post_type;
                }

                $to_set[] = $post_type;
            }

		}

		$wpddlayout->post_types_manager->handle_set_option_and_bulk_at_once( $post_id, $to_set, $to_bulk, false );

		return true;
	}

	private function get_translations (&$layout_array) {
		$translations = null;
		if ( isset($layout_array['translations']) ) {
			$translations = $layout_array['translations'];
			unset($layout_array['translations']);
		}

		return $translations;
	}

	private function set_translations ($translations, $post_id) {
		global $wpddlayout;

		if ($translations) {
			$wpddlayout->register_strings_for_translation($post_id);
			do_action('wpml_set_translated_strings',
										  $translations,
										  array(
												'kind' => 'Layout',
												'name' => $post_id
												)
										 );

		}

	}


    public static function layout_exists( $layout_name )
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s", WPDDL_LAYOUTS_POST_TYPE, $layout_name));
    }

    public static function get_extension( $file_name )
    {
        $last = strrpos( $file_name, '.' );

        if( $last === false ) return null;

        $extension = substr( $file_name, $last+1 );

        return  $extension;
    }

    public static function get_file_nicename( $file_name )
    {
        $last = strrpos( $file_name, '/' );

        $full_name = substr($file_name, $last );

        $last_dot = strrpos( $full_name, '.' );

        $name = substr($full_name, 1, $last_dot-1 );

        return $name;
    }

	function export_layouts_to_theme($target_dir) {
		global $wpdb, $wpddlayout;

		$results = array();

		$layouts =  $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM {$wpdb->posts} WHERE post_type=%s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE));

		foreach ($layouts as $layout) {
           
            $layout_array = WPDD_Layouts::get_layout_settings( $layout->ID, true );

            if ( is_null($layout_array) === false )
            {

	            try{
		            $layout_json = wp_json_encode( self::fix_toolset_association_on_import_export( $layout_array, 'export' ) );
	            } catch(Exception $e){
		            printf("Error: %s in %s at %d", $e->getMessage(), $e->getFile(), $e->getLine() );
                }

                $post_types = $this->set_post_types_export_data( $layout->ID);

				$archives = $wpddlayout->layout_post_loop_cell_manager->get_layout_loops( $layout->ID );

	            $posts = $this->set_layouts_associated_posts_for_export($layout->post_name, $post_types);

	            $layout_array->post_types = $post_types;
                $layout_array->archives = $archives;
                $layout_array->posts = $posts;

				$translations = apply_filters('WPML_get_translated_strings',
											  array(),
											  array(
													'kind' => 'Layout',
										            'name' => $layout->ID
													)
											 );

				if ( !empty($translations)) {
					$layout_array->translations = $translations;
				}

				$layout_json = wp_json_encode( $layout_array );

                $results[] = $this->file_manager_export->save_file( $layout->post_name, '.ddl', $layout_json, array('title' => $layout->post_title), true );

            }

		}

		$css = $this->get_layout_css();

		if( $css )
		{
			$results[] = $this->file_manager_export->save_file( 'layouts', '.css', $css, array('title' => 'Layouts CSS'), true );
		}

		return $results;

	}

	function get_layout_css()
	{
		global $wpddlayout;
		return $wpddlayout->get_layout_css();
	}

	/*
	 * Set post type data for export. Get post type slug and track if batched.
	 */
	private function set_post_types_export_data($layout_id){
		global $wpddlayout;

		$post_types = $wpddlayout->post_types_manager->get_layout_post_types( $layout_id );

		if( count($post_types) === 0 ) return null;

		$ret = array();

		foreach($post_types as $post_type){
			$ret[$post_type] = $wpddlayout->post_types_manager->get_post_type_was_batched( $layout_id, $post_type );
		}

		return $ret;
	}

	/*
	 * Filters posts assigned with entire post type.
	 * If the type is not there or not batched keep, otherwise leave it remove it from collection
	 */
	private function set_layouts_associated_posts_for_export( $layout_slug, $post_types_data ) {
		global $wpddlayout;
		$posts = $wpddlayout->individual_assignment_manager->fetch_layout_posts( $layout_slug );

		if ( is_null( $posts ) ) {
			return null;
		}

		$ret = array();

		foreach ( $posts as $post ) {
			if ( is_null($post_types_data) || array_key_exists( $post->post_type, $post_types_data ) && $post_types_data[ $post->post_type ] === false ||
			     array_key_exists( $post->post_type, $post_types_data ) === false
			) {
				$ret[] = $post;
			}
		}

		return $ret;
	}

	function export_for_download() {
		global $wpdb, $wpddlayout;

		$results = array();

		$layouts =  $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM {$wpdb->posts} WHERE post_type=%s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE));

		foreach ($layouts as $layout) {

           $layout_array = WPDD_Layouts::get_layout_settings( $layout->ID, true );

            if ( is_null($layout_array) === false )
            {
	            try{
		            $layout_array = self::fix_toolset_association_on_import_export($layout_array, 'export');
	            } catch(Exception $e){
		            printf("Error: %s in %s at %d", $e->getMessage(), $e->getFile(), $e->getLine() );
	            }

	            $post_types = $this->set_post_types_export_data( $layout->ID);

				$archives = $wpddlayout->layout_post_loop_cell_manager->get_layout_loops( $layout->ID );

	            $posts = $this->set_layouts_associated_posts_for_export($layout->post_name, $post_types);

	            $layout_array->post_types = $post_types;
                $layout_array->archives = $archives;
                $layout_array->posts = $posts;

				$translations = apply_filters('wpml_get_translated_strings',
											  array(),
											  array(
													'kind' => 'Layout',
										            'name' => $layout->ID
													)
											 );

				if ( !empty($translations)) {
					$layout_array->translations = $translations;
				}

				$layout_json = wp_json_encode( $layout_array );

                $file_name = $layout->post_name . '.ddl';

                $results[] = array(
                    'file_data' => $layout_json,
                    'file_name' => $file_name,
                    'title' => $layout->post_title,
                );
            }
		}



		$css = $this->get_layout_css();

		if( $css )
		{
			$results[] = array(
				'file_data' => $this->get_layout_css(),
				'file_name' => 'layouts.css',
				'title' => 'Layouts CSS',
			);
		}

		return $results;
	}

	function export_and_download_layouts() {
		if (isset($_POST['export_and_download'])) {

			$nonce = $_POST["wp_nonce_export_layouts"];

            if( WPDD_Utils::user_not_admin() ){
                die( __("You don't have permission to perform this action!", 'ddl-layouts') );
            }

			if ( wp_verify_nonce( $nonce, 'wp_nonce_export_layouts' ) ) {
				$results = $this->export_for_download();

				$sitename = sanitize_key(get_bloginfo('name'));
				if (!empty($sitename)) {
					$sitename .= '.';
				}

                require_once WPDDL_TOOLSET_COMMON_ABSPATH . '/Zip.php';

				if (class_exists('Zip')) {
                    $dirname = $sitename . 'dd-layouts.' . date('Y-m-d');
                    $zipName = $dirname . '.zip';
					$zip = new Zip();
                    $zip->addDirectory( $dirname );

					foreach ($results as $file_data) {
                        $zip->addFile( $file_data['file_data'], $dirname .'/'.$file_data['file_name'] );
					}

                    $zip->sendZip( $zipName );
				}
			}
			die();
		}
	}

	public static function fix_toolset_association_on_import_export(
		$layout, $action = 'export', $args = array(
			array(
				'property'  => 'ddl_view_template_id',
				'post_type' => 'view-template',
				'db_field'  => 'post_name'
			),
			array(
				'property'  => 'view',
				'post_type' => 'view',
				'db_field'  => 'post_name'
			),
            array(
				'property'  => 'ddl_layout_view_id',
				'post_type' => 'view',
				'db_field'  => 'post_name'
			), 
			array(
				'property'  => 'ddl_layout_cred_id',
				'post_type' => 'cred-form',
				'db_field'  => 'post_name'
			),
            array(
				'property'  => 'target_id',
				'post_type' => '!unknown', // we will get correct post type inside function
				'db_field'  => 'post_name'
			)            
		)
	)
    {
        if( null === $layout )
        {
            throw new Exception( __( sprintf("Layout parameter should be an object, %s given.", gettype($layout) ), 'ddl-layouts') );
        }

        if( !is_array($args) || sizeof( $args ) === 0 )
        {
            throw new Exception( __( sprintf("Third argument should be an array containing at least one object with 'property', 'post_type' and 'db_field' properties. Argument has size of %d instead.", sizeof( $args ) ), 'ddl-layouts') );
        }

        foreach( $layout as $key => $val )
        {
            if( is_object($val) || is_array($val) )
            {
                foreach( $args as $arg )
                {
                    $arg = (object) $arg;

                    if( is_object($val) && property_exists($val, $arg->property) )
                    {
                        if( 'export' === $action )
                        {
                            $value = WPDD_Layouts::get_post_property_from_ID( (int) $val->{$arg->property}, $arg->db_field );
                            if ( $arg->property == 'target_id' ){
                                $value = $value.';'.WPDD_Layouts::get_post_property_from_ID( (int) $val->{$arg->property}, 'post_type' );
                            }
                        }
                        elseif( 'import' === $action )
                        {
                            if ( $arg->property == 'target_id' ){
                                $temp = explode(';', $val->{$arg->property});
                                if ( isset($temp[0]) && isset($temp[1]) ){
                                    $val->{$arg->property} = $temp[0];
                                    $arg->post_type = $temp[1];
                                }
                            }
                            $value = WPDD_Layouts::get_post_ID_by_slug( $val->{$arg->property}, $arg->post_type );
                        }

                        if( $value && is_null($value) === false )
                        {
                            $val->{$arg->property} = $value;
                        }
                    }
                }

	            try{
		            self::fix_toolset_association_on_import_export( $val, $action, $args  );
	            }
                catch( Exception $e ){
	                printf("Error: %s in %s at %d", $e->getMessage(), $e->getFile(), $e->getLine() );
                }
            }

            if( is_object($layout) )
            {
                $layout->{$key} = $val;
            }
            elseif( is_array($layout) )
            {
                $layout[$key] = $val;
            }
        }
        return $layout;
    }
    function import_layouts_from_theme($source_dir, $overwrite_assignment = false )
    {
        global $wpddlayout;

        if (is_dir($source_dir)) {

            $layouts = glob($source_dir . '/*.ddl');

            foreach( $layouts as $layout ){
                $file_details = pathinfo($layout);
                $layout_name = $file_details['filename'];
                $layout_json = file_get_contents($layout);
                $layout = json_decode(str_replace('\\\"', '\"', $layout_json));
                $layout->file_name = $layout_name;
                $layouts_array[] = $layout;
            }

            usort( $layouts_array, array($this, 'sortLayoutsFromFile') );

            foreach ($layouts_array as $layout) {

                $layout_name = $layout->file_name;

                unset( $layout->file_name );

                if (is_null($layout) === false) {

                    $layout_array = WPDD_Layouts_Theme::fix_toolset_association_on_import_export( $layout, 'import' );
                    // make sure we have the right data type
                    $layout_array = (array) $layout_array;

                    $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_name);

                    if (!$id) {

                        $postarr = array(
                            'post_title' => is_object($layout) ? $layout->name : $layout['name'],
                            'post_name' => $layout_name,
                            'post_content' => '',
                            'post_status' => 'publish',
                            'post_type' => WPDDL_LAYOUTS_POST_TYPE
                        );

                        $post_id = wp_insert_post($postarr);

                        $layout_array['id'] = $post_id;

                        WPDD_Layouts::save_layout_settings( $post_id, $layout_array );

                        if ( $overwrite_assignment ){

                            //Archives
                            if(  isset($layout_array['archives']) && count( $layout_array['archives'] ) > 0 ){
                                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $layout_array['archives'], $post_id );
                                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $layout_array['archives'], $post_id );
                            }

                            //Post Types
                            if(  isset($layout_array['post_types']) && count( $layout_array['post_types'] ) > 0 ){
                                $to_set = array();
                                $to_bulk = array();

                                foreach( $layout->post_types as $post_type => $bulk ){
                                    if( post_type_exists($post_type) ){

                                        if( $bulk ){
                                            $to_bulk[] = $post_type;
                                        }

                                        $to_set[] = $post_type;
                                    }

                                }

                                $wpddlayout->post_types_manager->handle_set_option_and_bulk_at_once( $post_id, $to_set, $to_bulk, false );
                            } if ( isset($layout_array['posts']) && count( $layout_array['posts'] ) > 0 ){
                                    $this->write_single_posts_assignments($post_id, $layout, $overwrite_assignment);
                            }
                        }

                    }

                }

            }

            $wpddlayout->css_manager->import_css_from_theme($source_dir);
        }

    }

    function sortLayoutsFromFile( $a, $b ){
        if( ( isset($b->posts) && count($b->posts) > 0 ) && ( isset($a->posts) && count($a->posts) > 0 ) ){
                return 0;
        }
        if( ( isset($b->posts) && count($b->posts) > 0 ) && ( !isset($a->posts) || count($a->posts) === 0 ) ){
            return -1;
        } else if( ( !isset($b->posts) || count($b->posts) === 0 ) && ( isset($a->posts) && count($a->posts) > 0 ) ){
            return 1;
        } else {
            return -1;
        }
    }

    public function update_layouts( $path, $args ){
        global $wpddlayout;

        if ( is_dir($path) && is_array( $args ) && count($args) > 0 ) {

            $layouts = glob($path . '/*.ddl');

            foreach ($layouts as $layout) {
                $file_details = pathinfo($layout);

                $layout_json = file_get_contents($layout);

                $filtered = $this->filter_import($file_details['filename'], json_decode(str_replace('\\\"', '\"', $layout_json) ), $args) ;

                $layout = $filtered->layout;
                $layout_name = $filtered->name;
                $action = $filtered->do;

                if (is_null($layout) === false) {

                    $id = $this->layout_handle_save( $layout, $layout_name, true, false, false );

                    if( $action === 'overwrite' && $id ){
                        WPDD_Layouts::reset_toolset_edit_last( $id );
                    } else if( $action === 'duplicate' && $this->existing_layout ){
                        WPDD_Layouts::reset_toolset_edit_last( $this->existing_layout );
                    }
                }
            }
            $wpddlayout->css_manager->import_css_from_theme($path);
        }
    }

    private function filter_import( $name, $layout, $filter ){
            $ret = new stdClass();
            $ret->name = $name;
            $ret->layout = $layout;
            $ret->do = null;

            if( count( $filter ) === 0 ){
                return $ret;
            }

         /*   $me = array_filter($filter, function($item) use ($name){
                    return in_array( $name, array_values($item) );
            });*/

        // PHP < 5.3
        $me = array_filter($filter, array(new FilterByProperty(null, $name), 'value_in_array' ) );

        if( empty( $me ) ){
            return $ret;
        }

        $switch = array_keys($me);
        $ret->do = $switch[0];

        switch( $ret->do ){
            case 'skip':
                $ret->layout = null;
                break;
            case 'overwrite':
                $ret->layout = $layout;
                break;
            case 'duplicate':
                $ret->name = $name . '_' . time();
                $layout->name = $layout->name . ' ' . date( DATE_COOKIE, time() );
                $layout->slug = $ret->name;
                $ret->layout = $layout;
                break;
            default:
                $ret->layout = $layout;
                break;
        }

        return $ret;
    }
}