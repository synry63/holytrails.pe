<?php
class WPDD_PostEditPageManager
{

    private static $instance;
    private $create_object = null;
    private $post_type = null;
    private $post_id = null;
    private $has_post_content_cell = false;
    private $current_layout = null;
    private $post_title = '';

    private static $WHITE_LIST = array(
        'cred-form',
        'nav_menu_item',
        'view-template',
        'attachment',
        'wp-types-group',
        'view',
        'dd_layouts',
        'product_variation',
        'shop_order',
        'shop_coupon',
        'refunded',
        'failed',
        'revoked',
        'abandoned',
        'active',
        'inactive',
        'edd_discount',
        'edd_payment',
        'download',
        'product_variation',
        'shop_order',
        'shop_coupon',
        'shop_email',
        'wpsc_log',
        'wpsc-product-file',
        'wpsc-product'
    );

    /**
     *
     */
    private function __construct( )
    {

        if( class_exists( 'WooCommerce' ) || defined('JIGOSHOP_VERSION') ){
            array_push( self::$WHITE_LIST, 'product' );
        }

        //theme_has_page_templates()
        global $pagenow;

        if( is_admin() ){
            if( $pagenow == 'post.php' && isset($_GET['action']) && $_GET['action'] === 'edit' )
            {
                $this->post_id = $_GET['post'];
                $this->__init();
            }
            elseif( $pagenow == 'post-new.php' ){
                add_action( 'admin_print_scripts', array(&$this, '__init_on_post_create'), 1 );
            }

            /*Actions at admin page for layout edit and layout*/
            if ($pagenow == 'post.php' || $pagenow == 'post-new.php' || $pagenow == 'admin-ajax.php') {
                add_action('admin_head', array($this,'wpddl_edit_template_options'));
                add_action('admin_enqueue_scripts', array($this, 'page_edit_scripts'));
                add_action('admin_enqueue_scripts', array($this, 'page_edit_styles'));
                /*Saving layout settings at post/page edit page*/
                add_action('save_post', array($this,'wpddl_save_post'), 10, 2);
            }

            add_filter('screen_options_show_screen', array(&$this, 'remove_screen_options'), 10, 2);

        } else{
            add_action('save_post', array($this,'wpddl_save_post'), 10, 2);
        }

        //TODO: this is depracated at the moment we're using update post to save changes here
        //add_action('wp_ajax_ddl_switch_layout_from_post', array(&$this, 'ddl_switch_layout_from_post_callback') );
    }

    private function __init(){
        $post_object = get_post( $this->post_id );
        $this->post_type = &$post_object->post_type;
        $this->post_title = &$post_object->post_title;

        // if we are in forbidden post types edit page don't do anything
        if(
            ddl_has_feature('warn-missing-post-loop-cell') === false ||
            in_array( $this->post_type , self::$WHITE_LIST ) ) {
            return;
        }

        if( $this->post_type == 'page' && self::page_templates_have_layout() === false ) {
            return;
        } else if( $this->post_type !== 'page' && self::post_type_template_have_layout($this->post_type) === false ){
            return;
        }

        $this->show_hide_content_editor_in_post_edit_page();
        $this->add_create_layout_support( $post_object );
    }

    /**
     * @param $display_boolean
     * @param $wp_screen_object
     * @return bool
     *  Avoid to show visibility option for Layouts metabox for page post type
     */
    function remove_screen_options( $display_boolean, $wp_screen_object ){
        global $wp_meta_boxes;

        if( $wp_screen_object->post_type === 'page' ){
            $meta_box = $wp_meta_boxes[$wp_screen_object->id]['side']['high']['wpddl_template'];
            unset( $wp_meta_boxes[$wp_screen_object->id]['side']['high']['wpddl_template'] );
            $wp_screen_object->render_screen_options();
            $wp_meta_boxes[$wp_screen_object->id]['side']['high']['wpddl_template'] = $meta_box;
        }
        return $display_boolean;
    }

    function wpddl_edit_template_options(){
        global $post;

        if( !is_object($post) ) return;

        $post_object = get_post_type_object($post->post_type);

        if ( ( $post_object->publicly_queryable || $post_object->public) ) {
            add_meta_box('wpddl_template', __('Layout', 'wpdd-layout'), array($this,'meta_box'), $post->post_type, 'side', 'high');
        }
    }

    public static function this_page_template_have_layout( $post_id ){
        global $wpddlayout;
        $current_template = get_post_meta( $post_id, '_wp_page_template', true );

        return $wpddlayout->template_have_layout( $current_template );
    }

    public static function page_templates_have_layout( ){
        global $wpddlayout;
        $bool = false;
        if( !function_exists('get_page_templates') ){
            include_once ABSPATH . 'wp-admin/includes/theme.php';
        }
        $tpls = get_page_templates();

        foreach( $tpls as $tpl ){
            $check = $wpddlayout->template_have_layout( $tpl );
            if( $check ){
                $bool = true;
                break;
            }
        }

        return $bool;
    }

    public static function post_type_template_have_layout( $post_type ){
        global $wpddlayout;

        $bool = false;
        $tpls = $wpddlayout->post_types_manager->get_single_template( $post_type );

        foreach( $tpls as $tpl ){
            $check = $wpddlayout->template_have_layout( $tpl );
            if( $check ){
                $bool = true;
                break;
            }
        }

        return $bool;
    }

    public function __init_on_post_create(){
        global $post;
        $this->post_id = $post->ID;
        $this->__init();
    }

    private function add_create_layout_support( $post )
    {
        global $wpddlayout;

        if( $wpddlayout->theme_has_page_templates() ){
            $this->create_object = new WPDD_CreateLayoutForSinglePage( $post );
            add_action('ddl_add_create_layout_button', array(&$this, 'add_create_button') );
            add_action( 'ddl-create-layout-from-page-extra-fields', array(&$this, 'add_create_extra_fields') );
        }
    }

    public function show_hide_content_editor_in_post_edit_page(  )
    {
        $this->has_post_content_cell = $this->has_layout_with_post_content_cell( $this->post_id, $this->post_type );

            add_action( 'edit_form_after_title', array(&$this, 'include_overlay_template') );
            add_action( 'admin_print_scripts', array(&$this, 'ddl_post_editor_overrides_scripts' ), 110 );
            add_action( 'edit_form_after_editor', array(&$this, 'print_alternate_content_in_place_of_editor') );
    }

    //TODO: this is currently deprecated since the update happens when post is saved
    public function ddl_switch_layout_from_post_callback(){

        if( WPDD_Utils::user_not_admin() ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if( wp_verify_nonce( $_POST['ddl_switch_layout_from_post_nonce'], 'ddl_switch_layout_from_post_nonce' ) )
        {
            $this->post_id = $_POST['post_id'];
            $meta = $this->update_layout_for_page( $_POST['layout_slug'], $_POST['post_id'] );
            $send = wp_json_encode( array( 'message' => array( 'meta' => $meta, 'current' => $_POST['layout_id'], 'post_id' => $this->post_id, 'key' => WPDDL_LAYOUTS_META_KEY ) ) );
        }
        else
        {
            $send = WPDD_Utils::ajax_nonce_fail(__METHOD__);
        }

        die( $send );
    }

    private function update_layout_for_page( $layout_slug, $post_id ){
        return WPDD_Utils::assign_layout_to_post_object( $post_id, $layout_slug, null );
    }

    public function include_overlay_template()
    {
        $additional_cells =  $this->get_display_post_content_cells();
        include_once WPDDL_GUI_ABSPATH . 'templates/layout-post-edit-page-post-content-cell-overlay.tpl.php';
    }

    private function get_display_post_content_cells(){
        global $wpddlayout;

        $post_content_cells = array();
        $cells = $wpddlayout->get_registered_cells();

        foreach( $cells as $cell ){
            $data = $cell->get_cell_data();
            if( isset( $data['displays-post-content'] ) &&  $data['displays-post-content'] === true ){
                $post_content_cells[] = sprintf( __('or a "%s" cell', 'ddl-layouts'), $data['name']);

            }
        }

        if( count($post_content_cells) === 0 ) return '';

        return implode( $post_content_cells );
    }

    public function print_alternate_content_in_place_of_editor()
    {
        ob_start()?>
            <div class="ddl-post-content-message-in-post-editor js-ddl-post-content-message-in-post-editor toolset-alert">
            </div>
        <?php
        echo ob_get_clean();
    }

    /**
     *
     */
    public function ddl_post_editor_overrides_scripts()
    {
        global $wpddlayout;

        $layouts = $this->get_eligible_layouts_for_assignation();

        $this->has_post_content_cell = $this->current_layout ? $this->current_layout->has_post_content_cell : false;

        $wpddlayout->enqueue_styles(
            array(
                'toolset-notifications-css',
                'toolset-colorbox',
                'ddl-dialogs-forms-css',
                'wpt-toolset-backend',
                'ddl-dialogs-general-css',
            )
        );

        $wpddlayout->enqueue_scripts(
            array(
                'ddl-post-editor-overrides'
            )
        );

        $wpddlayout->localize_script(
            'ddl-post-editor-overrides',
            'DDLayout_settings',
            array(
                'strings' => array(

                ),
                'DDL_JS' => array(
                    'post' => array(
                        'ID' => $this->post_id,
                        'post_type' => $this->post_type,
                        'post_title' => $this->post_title,
                        'has_post_content_cell' => $this->has_post_content_cell

                    ),
                    'post_edit_page' => true,
                    'layout' => $this->current_layout,
                    'layouts' => $layouts,
                    'ddl_switch_layout_from_post_nonce' => wp_create_nonce( 'ddl_switch_layout_from_post_nonce' ),
                    'message_same' => sprintf( __('The selected layout is already assigned to %s'), '' ),
                    'current_template' => get_page_template_slug( $this->post_id )
                )
            )
        );
    }

    public function get_eligible_layouts_for_assignation()
    {
        $ret = array();

        $layouts = DDL_GroupedLayouts::get_all_layouts_as_posts(
            'publish',
            'title',
            'ids',
            false,
            true,
            false,
            false,
            true,
            'ASC'
        );

        foreach ($layouts as $layout) {
	        $clone = WPDD_Layouts::get_layout_settings($layout, true);

	        if( is_object($clone) === false ){
		        continue;
	        }

            $opts = clone $clone;

            if (is_object($opts) && ( property_exists($opts, 'has_child') === false || property_exists($opts, 'has_child')  && $opts->has_child === false) ) {
                $opts = $this->get_post_content_cell($opts);
                $ret[] = self::_filter_fields_to_keep($opts);
            }
        }
        return $ret;
    }

    private function get_post_content_cell($opts){

        $test = new WPDD_json2layout();
        $layout = $test->json_decode( wp_json_encode($opts) );

        if( property_exists($opts, 'has_post_content_cell') && $opts->has_post_content_cell === true )
        {
            $cell_post_content = $layout->has_cell_of_type( 'cell-post-content' );
            $cell_content_template = $layout->get_all_cells_of_type( 'cell-content-template' );

            if( $cell_post_content )
            {
                $opts->cell_post_content_type = 'cell-post-content';

            } elseif ( count( $cell_content_template ) > 0  )
            {
               if(  $this->content_template_cell_has_body_tag( $cell_content_template ) ){
                   $opts->cell_post_content_type = 'cell-content-template';
               } else {
                   $opts->cell_post_content_type = 'cell-content-template-no-body';
                   $opts->has_post_content_cell = false;
               }

            }
        }

        if( property_exists($opts, 'has_post_content_cell') && $opts->has_post_content_cell === true ){
            return $opts;
        } else{
            $cell_visual_editor = $layout->get_all_cells_of_type( 'cell-text' );

            if( count($cell_visual_editor) > 0 ){
                $opts->cell_post_content_type = $this->visual_editor_cell_has_wpvbody_tag( $cell_visual_editor );
                if( $opts->cell_post_content_type !== '' ){
                    $opts->has_post_content_cell = true;
                }
            } else {
                if( property_exists($opts, 'cell_post_content_type') === false ){
                    $opts->cell_post_content_type = '';
                }
            }
        }

        return $opts;
    }

    private function content_template_cell_has_body_tag( $cells ){

        if( !is_array($cells) || count($cells) === 0 ) return '';

        $ret = '';

        foreach( $cells as $cell ){
                if( $cell->check_if_content_template_has_body_tag( ) ){
                    $ret = 'cell-content-template';
                    break;
                } else {
                    $ret = '';
                }

        }

        return $ret;
    }

    public function visual_editor_cell_has_wpvbody_tag( $cells ){
        if( !is_array($cells) || count($cells) === 0 ) return '';

        $ret = '';

        foreach( $cells as $cell ){
            $content = $cell->get_content();

            if( !$content ) {
                $ret = '';
            } else {
                $content = (object) $content;
                if( $this->content_content_has_views_tag( $content ) ){
                    $ret = 'cell-content-template';
                    break;
                } else {
                    $ret = '';
                }
            }
        }

        return $ret;

    }

    private function content_content_has_views_tag( $content ){
        return property_exists(  $content, 'content' ) && strpos(  $content->content, 'wpv-post-body' ) !== false;
    }

    public static function _filter_fields_to_keep($obj)
    {
        $preserve = array(
            'id',
            'slug',
            'name',
            'has_post_content_cell',
            'cell_post_content_type',
            'post_content_icon'
        );

        foreach( $obj as $key => $val )
        {
            if( in_array($key, $preserve ) === false )
            {
                unset( $obj->{$key} );

            }
        }

        if( property_exists($obj, 'has_post_content_cell') === false )
        {
            $obj->has_post_content_cell = false;
        }

        return $obj;
    }

    public function has_layout_with_post_content_cell( $post_id, $post_type  )
    {
        $layout_id = $this->get_layout_id( $post_id, $post_type );

        if( $layout_id === null ) return false;

        $this->current_layout = $this->set_layout_id_for_json( $layout_id );

        if( !$this->current_layout ) return false;

       return property_exists( $this->current_layout, 'has_post_content_cell') ? $this->current_layout->has_post_content_cell : false;
    }

    private function get_layout_id( $post_id, $post_type ){
        global $wpddlayout, $pagenow;
        if( $pagenow == 'post-new.php' ){

            $layout = $wpddlayout->post_types_manager->get_layout_to_type_object( $post_type );

            if( null === $layout ) return null;

            return $layout->layout_id;
        } else {

            $layout_slug = self::page_has_layout( $post_id );

            if( $layout_slug === false ) return null;

            return WPDD_Layouts::get_layout_id_by_slug( $layout_slug );
        }
    }

    private function set_layout_id_for_json( $layout_id ){

        $settings = WPDD_Layouts::get_layout_settings( $layout_id, true );

        if( is_object($settings) === false ) {
            return null;
        }

        $ret = clone $settings;

        $ret = $this->get_post_content_cell( $ret );

        $ret = $this->_filter_fields_to_keep( $ret );

        return $ret;
    }

    public function add_create_button()
    {
        if( is_null( $this->create_object )  ) return;

        $this->create_object->add_button();
    }

    public function add_create_extra_fields()
    {
        if( is_null( $this->create_object )  ) return;

        $this->create_object->add_create_extra_fields();
    }

    public static function page_has_layout( $post_id )
    {
        $meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

        if( $meta === '' ) {
            $ret = false;
        }
        elseif( $meta == '0' ){
            $ret = false;
        }
        else{
            $ret = $meta;
        }
        return $ret;
    }

    public static function getInstance(  )
    {
        if (!self::$instance)
        {
            self::$instance = new WPDD_PostEditPageManager;
        }

        return self::$instance;
    }

    public static function page_template_has_layout( $post_id )
    {
        global $wpddlayout;
        $template = get_post_meta($post_id, '_wp_page_template', true);

        return $wpddlayout->template_have_layout( $template );
    }

    function meta_box($post) {
        global $wpddlayout;

        if( $wpddlayout->theme_has_page_templates() === false ){

            ?>
            <p class="toolset-alert toolset-alert-warning js-layout-support-warning line-height-16">
                <?php echo sprintf(__("A template file that supports layouts is not available.", 'ddl-layouts'),'') ?><br>
                <?php printf(__('Please check %shere%s to see how to set one up.', 'ddl-layouts'), '<a href="' . WPDLL_LEARN_ABOUT_SETTING_UP_TEMPLATE . '" target="_blank">', '</a>'); ?>
            </p>
            <?php
            return;
        }

        global $wpddl_features;

        $layout_tempates_available = WPDD_Layouts_Cache_Singleton::get_published_layouts();

        if (isset($_GET['post'])) {
            $template_selected = get_post_meta($_GET['post'], WPDDL_LAYOUTS_META_KEY, true);
        } else {
            $template_selected = '';
        }

        if ($post->post_type != 'page') {
            $none = new stdClass();
            $none->ID = 0;
            $none->post_name = 0;
            $none->post_title = __('None', 'ddl-layouts');

            array_unshift($layout_tempates_available, $none);
        }

        $post_type_obj = get_post_type_object( $post->post_type );

        ?>

        <div class="js-dd-layout-selector">

            <script type="text/javascript">
                var ddl_old_template_text = "<?php _e('Template', 'ddl-layouts'); ?>";
            </script>


            <?php if($post->post_type == 'page'): ?>
                <p>
                    <i class="icon-layouts-logo ont-icon-22 ont-color-orange"></i> <strong><?php _e('Template and Layout', 'ddl-layouts') ?></strong>
                </p>
            <?php endif; ?>
            <p>


                <?php

                $template_selected = $this->wpml_layout_for_post_edit($template_selected);

                $template_option = $wpddlayout->get_option( 'templates' );

                $post_type_theme = $wpddlayout->post_types_manager->get_layout_template_for_post_type( $post->post_type );

                $theme_template = $post_type_theme == 'default' ? basename( get_page_template() ) : $post_type_theme;

                $post_type_layout = $wpddlayout->post_types_manager->get_layout_to_type_object($post->post_type);
                
                
                ?>

                <input type="hidden" name="ddl-namespace-post-type-tpl" value="<?php echo $post_type_theme == 'default' ? 'default' : $theme_template;?>" class="js-ddl-namespace-post-type-tpl" />
                <select name="layouts_template" id="js-layout-template-name" <?php disabled( $post->post_type == 'attachment' ); /* cannot assign layouts to attachment post type posts individually */ ?> >
                    <?php
                    if (isset($template_option[$theme_template])) {
                        $theme_default_layout = $template_option[$theme_template];
                    } else {
                        $theme_default_layout = '';
                    }


                    foreach ($layout_tempates_available as $template) {

                        $layout = WPDD_Layouts::get_layout_settings($template->ID, true);
                        $has_loop = is_object($layout) && property_exists($layout, 'has_loop') ? $layout->has_loop : false;

                        $supported = true;
                        $warning = '';
                        if ($layout && property_exists( $layout, 'type') && $layout->type == 'fixed' && !$wpddl_features->is_feature('fixed-layout')) {
                            $warning = __("This layout is a fixed layout. The current theme doesn't support fixed layouts and might not display correctly", 'ddl-layouts');
                        }
                        if ($layout && property_exists( $layout, 'type') && $layout->type == 'fluid' && !$wpddl_features->is_feature('fluid-layout')) {
                            $warning = __("This layout is a fluid layout. The current theme doesn't support fluid layouts and might not display correctly", 'ddl-layouts');
                        }
                        if ( $layout && $template_selected != $layout->slug && isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true) ) {

                            $supported = false;
                        }

                        if ($supported) {

                            $force_layout = ' data-force-layout="false"';

                            if ($template_selected == $template->post_name){

                                $selected = ' selected="selected"';
                            }
                            // for new posts let's assign the Layout if there's one
                            elseif( !isset( $_GET['post'] ) && is_object( $post_type_layout ) && property_exists( $post_type_layout, 'layout_id') && (int)$template->ID === (int)$post_type_layout->layout_id ) {

                                $selected = ' selected="selected"';
                                $force_layout = ' data-force-layout="true"';
                            } else{

                                $selected = '';
                            }

                            $title = $template->post_title;
                            if ($title == '') {
                                $title = $template->post_name;
                            }
                            
                            if ( $template->post_name == $theme_default_layout && $post->post_type == 'page' ) {
                                $title .= __(' - Template default', 'ddl-layouts');
                            }

                            $data_object = array(
                                'layout_has_loop' => $has_loop,
                                'post_type' => $post->post_type,
                                'is_really_selected' => $selected !== ''
                            ) ;
                            $data_object = $data_object;
                            ?>
                            <option data-object="<?php echo htmlspecialchars( wp_json_encode( $data_object ) ); ?>" value="<?php echo $template->post_name; ?>"<?php echo $selected . $force_layout; ?> data-id="<?php echo $template->ID; ?>" data-ddl-warning="<?php echo $warning; ?>"><?php echo $title; ?></option>
                        <?php
                        }
                    }
                    
                    ?>

                </select>
                <input type="hidden" class="js-wpddl-default-template-message" value="<?php echo basename(get_page_template( 'page'))?>" data-message="<?php _e('Show all templates', 'ddl-layouts')?>" />
                <?php if($post->post_type == 'page'): ?>
                    <select name="combined_layouts_template" id="js-combined-layout-template-name">

                    </select>


                <?php endif; ?>
            <p>
                <a data-href="<?php echo admin_url() . 'admin.php?page=dd_layouts_edit&amp;action=edit&layout_id='; ?>" class="edit-layout-template js-edit-layout-template"><?php _e('Edit this layout', 'ddl-layouts'); ?></a>
            </p>
            </p>

            <?php do_action('ddl_add_create_layout_button'); ?>

            <div class="display_errors js-display-errors"></div>

            <p class="toolset-alert toolset-alert-warning js-layout-support-warning" style="display:none">
            </p>


            <?php wp_nonce_field('wp_nonce_ddl_dismiss', 'wp_nonce_ddl_dismiss'); ?>
        </div>



        <?php $woocommerce_support_message = $wpddlayout->post_types_manager->check_layout_template_for_woocommerce( $post_type_obj ); ?>
        <?php if ( $woocommerce_support_message ): ?>

            <p class=" toolset-alert toolset-alert-warning js-layout-support-missing">
                <?php echo $woocommerce_support_message; ?>
            </p>
        <?php elseif ( $wpddlayout->post_types_manager->check_layout_template_page_exists( $post_type_obj ) === false ): ?>

            <p class=" toolset-alert toolset-alert-warning js-layout-support-missing">
                <?php echo sprintf(__("A template file that supports layouts is not available.", 'ddl-layouts'), '<strong>"' . $post_type_obj->labels->singular_name . '"</strong>') ?><br>
                <?php printf(__('Please check %shere%s to see how to set one up.', 'ddl-layouts'), '<a href="' . WPDLL_LEARN_ABOUT_SETTING_UP_TEMPLATE . '" target="_blank">', '</a>'); ?>
            </p>
        <?php endif; ?>


    <?php

    }

    function page_edit_scripts() {
        global $wpddlayout;
        if( $wpddlayout->theme_has_page_templates() ){
            $wpddlayout->enqueue_scripts( array (
                    'select2',
                    'ddl_post_edit_page',
                )
            );

            $opts = $wpddlayout->layout_get_templates_options_object( );

            $wpddlayout->localize_script('ddl_post_edit_page', 'DDLayout_settings_editor', array(
                'strings' => array(
                    'content_template_diabled' => __('Since this page uses a layout, styling with a Content Template is disabled.', 'ddl-layouts'),
                    'layout_has_loop_cell' => __('This layout has a WordPress Archive cell and shouldn\'t be used for single posts of this post type.', 'ddl-layouts')
                ),
                'layout_templates' => $opts->layout_templates,
                'layout_template_defaults' => $opts->template_option,
            ) );
        }
    }

    function page_edit_styles() {
        global $wpddlayout;
        $wpddlayout->enqueue_styles( array ('toolset-notifications-css', 'toolset-select2-css','ddl_post_edit_page_css') );
    }

    private function wpml_layout_for_post_edit ($template_selected) {
        $source_post_id = apply_filters ( 'wpml_new_post_source_id', null);

        if ($source_post_id) {
            $template_selected = get_post_meta($source_post_id, WPDDL_LAYOUTS_META_KEY, true);
        }

        return $template_selected;
    }

    function wpddl_save_post($pidd){
        global $wpddlayout;

        if( user_can_assign_layouts() === false ) return;

        if ($_POST && isset($_POST['action']) && $_POST['action'] != 'inline-save') { // Don't save in quick edit mode.

            $layout_data = $wpddlayout->post_types_manager->get_layout_to_type_object( get_post_type( $pidd ) );

            if( isset($_POST['layouts_template']) ){

                $layout_selected = $_POST['layouts_template'];

                if( ( isset($_POST['page_template']) && $wpddlayout->template_have_layout($_POST['page_template'] ) === false ) || $layout_selected == '0' )
                {
                    $wpddlayout->individual_assignment_manager->remove_layout_from_post_db( $pidd );
                }
                else
                {
                    WPDD_Utils::assign_layout_to_post_object( $pidd,  $layout_selected, null );
                }

            }
            elseif ( !empty($layout_data->layout_id) && is_null($_POST['layouts_template']) ){

                WPDD_Utils::remove_layout_assignment_to_post_object( $pidd, '', true );
            }
            else
            {
                // when we set a non-layout template after a layout has been set
                $meta = get_post_meta($pidd, WPDDL_LAYOUTS_META_KEY, true);

                if( $meta )
                {
                    WPDD_Utils::remove_layout_assignment_to_post_object( $pidd, $meta, false );
                }
            }
        }
    }

}

class WPDD_CreateLayoutForSinglePage{

        private $post_id = 0;
        private $post = null;
        private $post_type = null;

        public function __construct( &$post )
        {
            $this->post = &$post;
            $this->post_id = $post->ID;
            $this->post_type = $post->post_type;
            add_action( 'admin_print_scripts', array(&$this, 'handle_scripts' ), 99 );
        }

        public function add_button()
        {
           ob_start();
        ?>
            <div class="create-layout-for-page-wrap hidden">
                <?php if( $this->post_type === 'page' || $this->post_type === 'post'):
                do_action('ddl_create_layout_for_this_page');
                else:
                do_action('ddl_create_layout_for_this_cpt');
                endif;?>
            </div>
        <?php
            $this->include_creation_php();
            echo ob_get_clean();
        }

        public function add_create_extra_fields()
        {
            ob_start(); ?>
                <input type="hidden" name="associate-post-upon-creation" id="js-associate-post-upon-creation" value="<?php echo $this->post_id;?>" />
            <?php
            echo ob_get_clean();
        }

        public function include_creation_php()
        {
            if( class_exists( 'WPDDL_Admin_Pages') ){
                WPDDL_Admin_Pages::getInstance()->include_creation_box();
            }
        }

        public function handle_scripts(){
            global $wpddlayout;

            if( $wpddlayout->is_embedded() ){
                return;
            }

            $wpddlayout->enqueue_styles(
                array(
                    'toolset-notifications-css',
                    'toolset-colorbox',
                    'ddl-dialogs-forms-css',
                    'wpt-toolset-backend',
                    'ddl-dialogs-general-css'
                )
            );

            $wpddlayout->enqueue_scripts(
                array(
                    /*'layouts-prototypes',*/
                    'toolset-utils',
                    'wp-layouts-dialogs-script',
                    'ddl-create-for-pages',
                    'ddl_create_new_layout'
                )
            );

            $post_type_obj = get_post_type_object( $this->post_type );

            $wpddlayout->localize_script(
                'ddl-create-for-pages',
                'DDLayout_settings_create',
                array(
                    'strings' => array(

                    ),
                    'DDL_JS' => array(
                        'post' => array(
                            'post_title' => $this->post->post_title,
                            'post_id' => $this->post_id,
                            'post_type' => $this->post_type,
                            'post_name' => $this->post->post_name,
                            'post_type_label' => $post_type_obj->label
                        ),
                        'new_layout_title_text' => sprintf(__('Layout for %s'), $this->post->post_title)
                    )
                )
            );
        }
}