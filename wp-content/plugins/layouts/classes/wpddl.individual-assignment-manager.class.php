<?php
class WPDD_Layouts_IndividualAssignmentManager
{

    const INDIVIDUAL_POST_ASSIGN_CHECKBOXES_NAME = 'individual_posts_assign';
	private $current_layout;

	public function __construct() {
		add_action('wp_ajax_ddl_fetch_post_for_layout', array($this, 'fetch_posts_used_by_layout'));
		add_action('wp_ajax_ddl_remove_layout_from_post', array($this, 'remove_layout_from_post'));
		add_action('wp_ajax_ddl_assign_layout_to_posts', array($this, 'assign_layout_to_posts'));
		add_action('wp_ajax_ddl_get_individual_post_checkboxes', array($this, 'get_post_checkboxes_callback'));
	}
	
	
	public function fetch_posts_used_by_layout () {
		global $wpddlayout;
		
		$this->_check_nonce();

		$result = array();
		
		$result['posts'] = $this->return_assigned_layout_list_html( $_POST['layout_id'] );
		
		echo wp_json_encode( array( 'Data' => $result ) );
		
		die();
	}

    public function return_assigned_layout_list_html( $id ){
        global $wpddlayout;

        $this->current_layout = $id;
        $layout_id = $this->current_layout;
        $amount = isset( $_POST['single_amount_to_show_in_dialog'] ) ? $_POST['single_amount_to_show_in_dialog'] : 5;
        $post_types = $this->get_post_types( $this->current_layout );

		$post_types_query = array_diff( $wpddlayout->post_types_manager->get_post_types_from_wp( 'names' ), $post_types );

		$posts = $wpddlayout->get_where_used( $this->current_layout, false, false, $amount, array('publish','draft','pending'), 'default', $post_types_query);
		$found_posts = $wpddlayout->get_where_used_count();

        ob_start();

        include WPDDL_GUI_ABSPATH . 'editor/templates/individual-assigned-posts.box.tpl.php';

        return ob_get_clean();
    }

    public function get_post_types( $layout_id )
    {
        global $wpddlayout;

        $post_types = $wpddlayout->post_types_manager->get_layout_post_types_object( $layout_id );

        if( $post_types === false ) return array();

        foreach ($post_types as $key => $type) {
            $post_types[$key] = $type['post_type'];
        }

        return $post_types;
    }
	
	public function remove_layout_from_post () {
		$this->_check_nonce();
        if ( isset($_POST['post_ids']) ){
            $ids = json_decode( stripslashes($_POST['post_ids']) );
            foreach ($ids as $key => $value) {
                $this->remove_layout_from_post_db( $key );
            }  
            //$this->remove_layout_from_post_db( $_POST['post_id'] );
            global $wpddlayout;
			$this->current_layout = $_POST['layout_id'];
            $send = $wpddlayout->listing_page->get_send( 'publish', $_POST['html'], $this->current_layout, '', $_POST );
            die( $send );
        }
	}

    public function remove_layout_from_post_db( $post_id )
    {
        $meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );
        WPDD_Utils::remove_layout_assignment_to_post_object( $post_id, $meta, true );
    }
	
	public function assign_layout_to_posts () {
		global $wpddlayout;

		$this->_check_nonce();
		
		if (isset($_POST['posts']) && isset($_POST['layout_id'])) {
			$this->current_layout = $_POST['layout_id'];

            $wpddlayout->post_types_manager->update_post_meta_for_post_type( $_POST['posts'], $this->current_layout );
            $send = $wpddlayout->listing_page->get_send( 'publish', $_POST['html'], $this->current_layout, '', $_POST  );
            die( $send );
		}
	}

	private function _check_nonce () {
        if( WPDD_Utils::user_not_admin() ){
            die( 'You don\'t have permission to perform this action' );
        }
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
							'wp_nonce_individual-pages-assigned')) {
			die('verification failed');
		}
	}
	
	public function get_post_checkboxes_callback() {
		$this->_check_nonce();
		
		$search = '';
		if (isset($_POST['search'])) {
			$search = $_POST['search'];
		}
		
		$sort = true;
		
		if (isset($_POST['sort'])) {
			$sort = $_POST['sort'] == 'true' ? true : false;
		}

		$this->current_layout = $_POST['layout_id'];

		echo $this->get_posts_checkboxes($_POST['post_type'], $_POST['count'], $search, $sort);
		die();
	}
	
	public function filter_query_fields ($fields) {
		global $wpdb;
		
		$fields = $wpdb->posts . '.ID,' . $wpdb->posts . '.post_title';
		return $fields;
	}
	
	public function get_posts_checkboxes($post_type, $count = -1, $search = '', $sort = true) {
        global $wpddlayout;

        $this->current_layout = isset( $_POST['layout_id'] ) ? $_POST['layout_id'] : $this->current_layout;

        if( $post_type === 'any' )
        {
            $post_type = $wpddlayout->post_types_manager->get_post_types_with_templates( );
        }
        else
        {
            if( $wpddlayout->post_types_manager->check_layout_template_page_exists( get_post_type_object( $post_type ) ) === false ) return '';
        }

        $layout = get_post($this->current_layout);

        $recent_args = array(
            'post_type' => $post_type,
            'posts_per_page' => $count,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => WPDDL_LAYOUTS_META_KEY,
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => WPDDL_LAYOUTS_META_KEY,
                    'value' => $layout->post_name,
                    'compare' => '!=',
                )
            ),
            'suppress_filters' => true
        );
		
		if ($sort) {
			$recent_args = array_merge( $recent_args, array( 'orderby' => 'date', 'order' => 'DESC') );
		} else {
			$recent_args = array_merge( $recent_args, array( 'orderby' => 'title', 'order' => 'ASC') );
		}
		
		if ($search) {
			$recent_args['s'] = $search;
		}
//		add_filter('posts_fields_request', array($this, 'filter_query_fields'));
        $get_posts = new WP_Query($recent_args);
		$most_recent = $get_posts->posts;

//		remove_filter('posts_fields_request', array($this, 'filter_query_fields'));
		ob_start();
		?>
			<ul class="ddl-posts-check-list">
				<?php foreach ($most_recent as $recent): ?>
					<li><label><input name="<?php echo self::INDIVIDUAL_POST_ASSIGN_CHECKBOXES_NAME; ?>" class="js-ddl-individual-posts" type="checkbox" value="<?php echo $recent->ID; ?>" data-title="<?php echo $this->encode_title($recent->post_title); ?>" /><?php echo $this->encode_title($recent->post_title); ?></label></li>
				<?php endforeach; ?>
			</ul>
		<?php
		
		if ($search && sizeof($most_recent) == 0) {
			_e('No results found', 'ddl-layouts');
		}

        wp_reset_query();
        $get_posts = null;
		return ob_get_clean();
	}
	
	public function encode_title($title) {
		if (!defined('ENT_HTML401')) {
			define('ENT_HTML401', 0);
		}
		return htmlentities( trim( $title ) ? $title : __( '(no title)', 'ddl-layouts' ), ENT_COMPAT | ENT_HTML401, 'UTF-8' );
	}

    public function fetch_layout_posts( $layout_slug ){

        global $wpdb;

        $post_types = get_post_types( array( 'exclude_from_search' => false ), 'names' );
        /* *
        $post_types = array_diff( $post_types, array('attachment') );
        /* */
        $post_types = implode("', '", $post_types);

        $query = $wpdb->prepare("SELECT  $wpdb->posts.ID, $wpdb->posts.post_name, $wpdb->posts.post_type FROM $wpdb->posts  INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
INNER JOIN $wpdb->postmeta AS mt1 ON ($wpdb->posts.ID = mt1.post_id) WHERE 1=1  AND $wpdb->posts.post_type IN (%s) AND (($wpdb->posts.post_status <> 'trash' AND $wpdb->posts.post_status <> 'auto-draft')) AND ($wpdb->postmeta.meta_key = '_layouts_template'
AND  (mt1.meta_key = '_layouts_template' AND CAST(mt1.meta_value AS CHAR) = %s) ) GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC", $post_types, $layout_slug);

        $query = stripslashes( $query );

        // I am not using WP_Query since we want only post_name field to save memory
        $posts = $wpdb->get_results($query);

        if( $wpdb->num_rows === 0 ) return null;

        $ret = array( );

	    $page_templates = $this->get_pages_templates( $posts );

        foreach( $posts as $post ){
            if( $post->post_type === 'page' ){
                $ret[] = (object) array(
                    'post_name' => $post->post_name,
                    'post_type' => $post->post_type,
                    '_wp_page_template' => $page_templates && isset( $page_templates[$post->ID] ) ? $page_templates[$post->ID] : 'default'
                );
            } else {
                $ret[] = (object) array(
                    'post_name' => $post->post_name,
                    'post_type' => $post->post_type
                );
            }
        }
        return $ret;
    }

	private function get_pages_templates($posts){
		global $wpdb;

		//get ids only
		$posts = array_map( array($this, 'filter_ids') , array_filter( $posts, array($this, 'filter_pages') ) );

		if( count( $posts ) === 0 ) return null;

		$posts = implode("', '", $posts);

		$query = $wpdb->prepare("SELECT $wpdb->postmeta.meta_value, $wpdb->postmeta.post_id FROM $wpdb->postmeta WHERE 1=1 AND $wpdb->postmeta.post_id IN (%s) AND $wpdb->postmeta.meta_key = '_wp_page_template'", $posts);

		$query = stripslashes( $query );

		$metas = $wpdb->get_results($query);

		if( $wpdb->num_rows === 0 ) return null;

		// make an array with ID as key and page template as value
		$ret = array_combine( array_map( array($this, 'map_id_keys'), $metas ), array_map( array($this, 'map_meta_values'), $metas ) );

		return $ret;
	}

	function filter_pages( $p ) {
		return $p->post_type === 'page';
	}

	function filter_ids( $p ){
		return $p->ID;
	}

	function map_id_keys( $m ){
		return $m->post_id;
	}

	function map_meta_values( $m ){
		return $m->meta_value;
	}

    public function fetch_posts_by_slug( $slugs ){
        global $wpdb;
        $posts_list = implode("', '",  $slugs);
        $query = $wpdb->prepare("SELECT  $wpdb->posts.ID, $wpdb->posts.post_name, $wpdb->posts.post_type FROM $wpdb->posts  WHERE 1=1  AND $wpdb->posts.post_name IN (%s) AND (($wpdb->posts.post_status <> 'trash' AND $wpdb->posts.post_status <> 'auto-draft')) GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC", $posts_list);
        $query = stripslashes( $query );
        $posts = $wpdb->get_results($query);

        if( $wpdb->num_rows === 0 ) return null;

        $ret = array( );

        foreach( $posts as $post ){
	        $ret[$post->post_name] = new stdClass();
            $ret[$post->post_name]->ID = $post->ID;
	        $ret[$post->post_name]->post_type = $post->post_type;
        }

        return $ret;
    }

    public function get_page_template( $post_id ){
        return get_post_meta( $post_id, '_wp_page_template', true );
    }
}