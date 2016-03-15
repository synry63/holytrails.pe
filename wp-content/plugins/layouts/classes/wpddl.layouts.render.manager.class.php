<?php
class WPDD_Layouts_RenderManager{
    private $main;
    private static $instance;
    private $render_errors;
    private $attachment_markup = '';

    private function __construct(  ){
        global $wpddlayout;

        $this->main = $wpddlayout;
        $this->render_errors = array();

        if( !is_admin() ){
            add_action('wp_head', array($this,'wpddl_frontend_header_init'));
            add_action('wpddl_before_header', array($this, 'before_header_hook'));
            add_filter('ddl_render_cell_content', array(&$this,'fix_attachment_body'), 10, 3 );
            add_filter('prepend_attachment', array(&$this, 'attachment_handler'), 999);
        }
    }

    function is_wp_post_object( $post ){
        return 'object' === gettype( $post ) && get_class( $post ) === 'WP_Post';
    }

    function fix_attachment_body( $content, $cell, $renderer ){
        global $post;

        // Do not render attachment post type posts' bodies automatically
        if( $this->is_wp_post_object( $post ) && $post->post_type === 'attachment' && $this->attachment_markup ){
            $content = WPDD_Utils::str_replace_once( $this->attachment_markup , '', $content);

        }
        return $content;
    }

    function attachment_handler($html){
        $this->attachment_markup = $html;
        return $html;
    }

    function get_layout_renderer( $layout, $args )
    {
        $manager = new WPDD_layout_render_manager($layout );
        $renderer = $manager->get_renderer( );
        // set properties  and callbacks dynamically to current renderer
        if( is_array($args) && count($args) > 0 )
        {
            $renderer->set_layout_arguments( $args );
        }
        return $renderer;
    }

    function get_query_post_if_any( $queried_object)
    {
        return 'object' === gettype( $queried_object ) && get_class( $queried_object ) === 'WP_Post' ? $queried_object : null;
    }

    function get_queried_object()
    {
        global $wp_query;
        $queried_object = $wp_query->get_queried_object();
        return $queried_object;
    }


    function get_layout_id_for_render( $layout, $args = null )
    {
        $options = is_null( $args ) === false && is_array( $args ) === true ? (object) $args : false;

        $allow_overrides = $options && property_exists( $options, 'allow_overrides' ) ? $options->allow_overrides : true;

        $id = 0;

        if ($layout) {
            $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout);
        }

        if( $allow_overrides === true ){

            // when blog is front
            if( is_front_page() && is_home() && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG) )
            {
                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG);

            // when blog is not front
            } elseif ((is_home()) && (!(is_front_page())) && (!(is_page())) && ($this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG))) {
                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG);
            } elseif ( is_post_type_archive() ) {

                $post_type_object = $this->get_queried_object();

                if ( $post_type_object && property_exists( $post_type_object, 'public' ) && $post_type_object->public && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type_object->name) ) {
                    $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type_object->name);
                }
            }
            elseif ( is_archive() && ( is_tax() || is_category() || is_tag() ) ) {

                $term =  $this->get_queried_object();
                if ( $term && property_exists( $term, 'taxonomy' ) && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy) ) {
                    $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy);
                }

            }
            // Check other archives
            elseif ( is_search()  && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH) ) {

                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH);
            }
            elseif ( is_author() && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR ) ) {
                $author = WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR;
                $id = $this->main->layout_post_loop_cell_manager->get_option( $author );
            }
            elseif ( is_year() && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_YEAR) ) {

                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_YEAR);
            }
            elseif ( is_month() && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_MONTH) ) {

                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_MONTH);
            }
            elseif ( is_day() && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_DAY) ) {

                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_DAY);
            }
            elseif( is_404() && $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_404 ) )
            {

                $id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_404 );
            }
            else{

                global $post;

                if( $post !== null )
                {
                    $post_id = $post->ID;

                    $layout_selected = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

                    if ($layout_selected) {

                        $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);

                        $option = $this->main->post_types_manager->get_layout_to_type_object($post->post_type);

                        if( is_object( $option ) && property_exists( $option, 'layout_id') && (int) $option->layout_id === (int) $id )
                        {
                            $id = $option->layout_id;
                        }
                    }
                }
            }
        }

        return apply_filters('get_layout_id_for_render', (int) $id, $layout );
    }

    function get_layout_content_for_render( $layout, $args )
    {
        $id = $this->get_layout_id_for_render( $layout, $args );

        $content = '';

        if ($id) {

            // Check for preview mode
            $layout = $this->get_rendered_layout($id);

            if ($layout) {
                $content = $this->get_rendered_layout_content( $layout, $args );
            }
        } else {
            if (!$layout) {
                $content = '<p>' . __('You need to select a layout for this page. The layout selection is available in the page editor.', 'ddl-layouts') . '</p>';
            }
        }

        return apply_filters('get_layout_content_for_render', $content, $this, $layout, $args );
    }

    private function get_rendered_layout_content( $layout, $args ){
        $renderer = $this->get_layout_renderer( $layout, $args );
        //$renderer = new WPDD_layout_render($layout);
        $content = $renderer->render( );

        $render_errors = $this->get_render_errors();
        if (sizeof($render_errors)) {
            $content .= '<p class="alert alert-error"><strong>' . __('There were errors while rendering this layout.', 'ddl-layouts') . '</strong></p>';
            foreach($render_errors as $error) {
                $content .= '<p class="alert alert-error">' . $error . '</p>';
            }
        }
        return $content;
    }

    public function get_rendered_layout( $id ){
        $layout = null;
        $old_id = $id;
        if (isset($_GET['layout_id'])) {
            $id = $_GET['layout_id'];
        }

        if( isset( $_POST['layout_preview'] ) && $_POST['layout_preview'] ){

            $json_parser = new WPDD_json2layout();
            $layout = $json_parser->json_decode( stripslashes( $_POST['layout_preview'] ) );
        }
        else{
            $layout = $this->main->get_layout_from_id($id);
            if (!$layout && isset($_GET['layout_id'])) {
                if ($id != $old_id) {
                    $layout = $this->main->get_layout_from_id($old_id);
                }
            }
        }
        return $layout;
    }

    function wpddl_frontend_header_init(){
        $this->main->header_added = TRUE;

        $queried_object = $this->get_queried_object();
        $post = $this->get_query_post_if_any( $queried_object);


        if( null === $post ) return;
        // if there is a css enqueue it here
        $post_id = $post->ID;

        $layout_selected = get_post_meta($post_id, WPDDL_LAYOUTS_META_KEY, true);

        if( $layout_selected ){
            $id = $this->main->get_post_ID_by_slug( $layout_selected, WPDDL_LAYOUTS_POST_TYPE );
            $header_content = get_post_meta($id, 'dd_layouts_header');
            echo isset($header_content[0]) ? $header_content[0] : '';
        }
    }

    function before_header_hook(){
        if (isset($_GET['layout_id'])) {
            $layout_selected = $_GET['layout_id'];
        } else {
            $post_id = get_the_ID();
            $layout_selected = WPDD_Layouts::get_layout_settings( $post_id, false );
        }
        if($layout_selected>0){
            //$layout_content = get_post_meta($layout_selected, WPDDL_LAYOUTS_SETTINGS);

            $layout_content =  WPDD_Layouts::get_layout_settings_raw_not_cached( $layout_selected, false );

            if (sizeof($layout_content) > 0) {
                $test = new WPDD_json2layout();
                $layout = $test->json_decode($layout_content[0]);
                $manager = new WPDD_layout_render_manager($layout);
                $renderer = $manager->get_renderer( );
                $html = $renderer->render_to_html();

                echo $html;
            }
        }
    }

    function record_render_error($data) {
        if ( !in_array($data, $this->render_errors) ) {
            $this->render_errors[] = $data;
        }
    }

    function get_render_errors() {
        return $this->render_errors;
    }

    public function item_has_ddlayout_assigned()
    {
        if (is_front_page() && is_home() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_BLOG)) {
            return true;
        } elseif (is_post_type_archive()) {

            $post_type_object = $this->get_queried_object();

            if ($post_type_object && property_exists($post_type_object, 'public') && $post_type_object->public && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX . $post_type_object->name)) {
                return true;
            }
        } elseif (is_archive() && (is_tax() || is_category() || is_tag())) {

            $term = $this->get_queried_object();
            if ($term && property_exists($term, 'taxonomy') && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $term->taxonomy)) {
                return true;
            }

        } // Check other archives
        elseif (is_search() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_SEARCH)) {

            return true;
        } elseif (is_author() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR)) {
            return true;
        } elseif (is_year() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_YEAR)) {

            return true;
        } elseif (is_month() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_MONTH)) {

            return true;
        } elseif (is_day() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_DAY)) {

            return true;
        } elseif (is_404() && $this->main->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_404)) {

            return true;
        } else {
            global $post;

            if( $this->is_wp_post_object( $post ) ){
                $assigned_template = get_post_meta($post->ID, WPDDL_LAYOUTS_META_KEY, true);

                if (!$assigned_template) {
                    return false;
                }

                return $assigned_template !== 'none';
            }
        }
        return false;
    }


    public static function getInstance(  )
    {
        if (!self::$instance)
        {
            self::$instance = new WPDD_Layouts_RenderManager(  );
        }

        return self::$instance;
    }
}