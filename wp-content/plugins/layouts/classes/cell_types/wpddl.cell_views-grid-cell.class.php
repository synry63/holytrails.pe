<?php
/*
 * Theme Views content grid cell type.
 * Displays current theme basic footer with two credits area.
 *
 */

/*
 * Render preview for view
 */
add_action('wp_ajax_ddl_views_content_grid_preview', 'ddl_views_content_grid_preview');
function ddl_views_content_grid_preview(){

    if( WPDD_Utils::user_not_admin() ){
        die( __("You don't have permission to perform this action!", 'ddl-layouts') );
    }

    if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
            'ddl_layout_view_nonce')) {
        die('verification failed');
    }

    global $wpdb;

    if ( isset($_POST['view_id']) ){
        $view_id = $_POST['view_id'];
    }else{
        return __('View not set','ddl-layouts');
    }
    $layout_style = array(
        'unformatted' => __('Unformatted','ddl-layouts'),
        'bootstrap-grid' => __('Unformatted','ddl-layouts'),
        'table' => __('Table-based grid','ddl-layouts'),
        'table_of_fields' => __('Table','ddl-layouts'),
        'un_ordered_list' => __('Unordered list','ddl-layouts'),
        'ordered_list' => __('Ordered list','ddl-layouts')
    );
    $view = $wpdb->get_results( $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE ID = %d AND post_type='view'",$view_id) );
    if ( isset($view[0]) ){
        $post_title = $view[0]->post_title;
        $id = $view[0]->ID;
        $view_settings = get_post_meta($id,'_wpv_settings',true);
        $meta = get_post_meta($id,'_wpv_layout_settings',true);
        if ( ! isset( $meta['style'] ) ) {
            $meta['style'] = 'unformatted';
        }

        $view_purpose = '';

        if ( isset($view_settings['view-query-mode']) && $view_settings['view-query-mode'] == 'normal') {
            $view_output = get_view_query_results($id);
            if ( ! isset( $view_settings['view_purpose'] ) ) {
                $view_settings['view_purpose'] = 'full';
            }
            switch ($view_settings['view_purpose']) {
                case 'all':
                    $view_purpose = __('Display all results','ddl-layouts');
                    break;

                case 'pagination':
                    $view_purpose = __('Display the results with pagination','ddl-layouts');
                    break;

                case 'slider':
                    $view_purpose = __('Display the results as a slider','ddl-layouts');
                    break;

                case 'parametric':
                    $content = $_POST['content'];
                    switch ($content['parametric_mode']) {
                        case 'full':
                            ?>
                            <div class="ddl-parametric-search-preview">
                                <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/both-search-form-and-results.png'; ?>" height="204px">
                            </div>
                            <?php
                            die();
                            break;

                        case 'form':

                            if ($content['parametric_mode_target'] == 'self' && $_POST['target_found'] != 'true') {
                                ?>
                                <div class="ddl-parametric-search-preview">
                                    <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-form-only-results-missing.png'; ?>" height="204px">
                                </div>
                                <?php
                                die();
                            }

                            if ($content['parametric_mode_target'] == 'other') {
                                ?>
                                <div class="ddl-parametric-search-preview">
                                    <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-form-only-results-in-a-different-page.png'; ?>" height="204px">
                                </div>
                                <?php
                                die();
                            }

                            ?>
                            <div class="ddl-parametric-search-preview">
                                <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-form-only.png'; ?>" height="100px">
                            </div>
                            <?php
                            die();
                            break;

                        case 'results':
                            ?>
                            <div class="ddl-parametric-search-preview">
                                <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-results-only.png'; ?>" height="100px">
                            </div>
                            <?php
                            die();
                            break;
                    }
                    break;

                case 'full':
                    $view_purpose = __('Displays a fully customized display','ddl-layouts');
                    break;
            }

            echo $view_purpose;
            echo '<br />';

        } else {
            $view_output = array();

            if ($meta['style'] == 'bootstrap-grid' || $meta['style'] == 'table') {
                if ($meta['style'] == 'bootstrap-grid') {
                    $col_number = $meta['bootstrap_grid_cols'];
                } else {
                    $col_number = $meta['table_cols'];
                }

                // add 2 rows of items.
                for ($i = 1; $i <= 2 * $col_number; $i++) {
                    $item = new stdClass();
                    $item->post_title = sprintf(__('Post %d', 'ddl-layouts'), $i);
                    $view_output[] = $item;
                }

            } else {
                // just add 3 items
                for ($i = 1; $i <= 3; $i++) {
                    $item = new stdClass();
                    $item->post_title = sprintf(__('Post %d', 'ddl-layouts'), $i);
                    $view_output[] = $item;
                }
            }

        }
        ddl_views_generate_cell_preview( $post_title, $id, $meta, $view_output );
    }

    die();

}

/*
 * Create new view and output view info
 * $id, $slug, $title
 */
add_action('wp_ajax_ddl_create_new_view', 'ddl_create_new_view');
function ddl_create_new_view(){
    global $wpdb;

    if( WPDD_Utils::user_not_admin() ){
        die( __("You don't have permission to perform this action!", 'ddl-layouts') );
    }
    if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
            'ddl_layout_view_nonce')) {
        die('verification failed');
    }

    $view_type = 'normal';
    if (isset($_POST['is_archive'])) {
        $view_type = 'layouts-loop';
    }
    $view_purpose = 'full';
    if (isset($_POST['purpose'])){
        $view_purpose = $_POST['purpose'];
    }

    $name = $original_name = $_POST['cell_name'];
    $i = 0;
    $name_in_use = true;
    while( $name_in_use ){
        $i++;
        $postid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
			WHERE ( post_title = %s OR post_name = %s )
			AND post_type = 'view'
			LIMIT 1",
                $name,
                strtolower( $name )
            )
        );
        if ( $postid ) {
            $name = $original_name . ' ' . $i;
        }
        else{
            $name_in_use = false;
        }
    }
    $args = array(
        'title' => $name,
        'settings' => array('view_purpose' => $view_purpose,// This is not a purpose but a layout
            'view-query-mode' => $view_type)
    );
    $view_id = wpv_create_view( $args );
    if ( isset( $view_id['success']) ){

        $id = $view_id['success'];

        // set it to filter posts by default.
        $view_settings = get_post_meta($id, '_wpv_settings', true);
        $view_settings['post_type'] = array('post');
        if ( isset( $_POST['purpose'] ) ) {
            $view_settings['view_purpose'] = $_POST['purpose'];
        } else {
            $view_settings['view_purpose'] = 'full';
        }

        if ( $view_type == 'layouts-loop' ) {
            // show the content section for pagination.
            unset( $view_settings['sections-show-hide']['content'] );
        }

        update_post_meta($id, '_wpv_settings', $view_settings);

        $res = $wpdb->get_results( "SELECT post_name FROM $wpdb->posts WHERE ID = '" . $id . "' AND post_type='view'" );
        $post_name = $res[0]->post_name;
        $output = wp_json_encode(array( 'id'=>$id, 'post_name' => $post_name, 'post_title'=> $name));
        //print wp_json_encode(array( 'id'=>$id, 'post_name' => $post_name, 'post_title'=> $name));

    } else {
        $output = wp_json_encode( array( 'error'=>$view_id, 'message' => $view_id ) );
    }

    die( $output );
}

add_shortcode('ddl-pager-prev-page', 'ddl_pagination_previous_shortcode');

function ddl_pagination_previous_shortcode($atts, $value) {

    return get_next_posts_link(do_shortcode($value));
}

add_shortcode('ddl-pager-next-page', 'ddl_pagination_next_shortcode');

function ddl_pagination_next_shortcode($atts, $value) {

    return get_previous_posts_link(do_shortcode($value));
}

/*
 * Get settings about the View
 * $id, $slug, $title
 *
 * DEPRECATED
 * Not sure if in use anymore
 * We do not get the View settings anymore when selecting one
 */
add_action('wp_ajax_ddl_get_settings_for_view', 'ddl_get_settings_for_view');
function ddl_get_settings_for_view(){
    global $wpdb;

    if( WPDD_Utils::user_not_admin() ){
        die( __("You don't have permission to perform this action!", 'ddl-layouts') );
    }
    if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
            'ddl_layout_view_nonce')) {
        die('verification failed');
    }

    $result = array();

    if ( isset($_POST['view_id']) ){
        $view_id = $_POST['view_id'];
        $view = $wpdb->get_results( $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE ID = %d AND post_type='view'",$view_id) );
        if ( isset($view[0]) ){
            $id = $view[0]->ID;
            $meta = get_post_meta($id,'_wpv_layout_settings',true);
            if (ddl_confirm_ok_to_change_grid_cols($meta)) {
                //$result['grid_settings'] = $meta['bootstrap_grid_cols'];
            }
            $result['title'] = $view[0]->post_title;

        }
    }

    print wp_json_encode($result);

    die();
}

/*
 * Save the View settings for columns
 *
 * DEPRECATED
 */

add_action('wp_ajax_ddl_save_view_columns', 'ddl_save_view_columns');
function ddl_save_view_columns(){
    global $wpdb;

    if( WPDD_Utils::user_not_admin() ){
        die( __("You don't have permission to perform this action!", 'ddl-layouts') );
    }
    if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
            'ddl_layout_view_nonce')) {
        die('verification failed');
    }

    $result = array();

    print wp_json_encode($result);

    die();
}

// DEPRECATED

function ddl_confirm_ok_to_change_grid_cols ($meta) {
    $ok_to_update = false;

    if (isset($meta['style']) && $meta['style'] == 'bootstrap-grid') {
        if (function_exists('wpv_create_bootstrap_meta_html')) {
            $meta_html_current = $meta['layout_meta_html'];
            // find the content template used
            $match = array();

            if (preg_match('/\[wpv-post-body view_template="(.*?)\"\]/', $meta_html_current, $match)) {
                $template = $match[1];
                $old_test = wpv_create_bootstrap_meta_html( $meta['bootstrap_grid_cols'],
                    $template,
                    $meta_html_current);

                if (preg_replace('/\s+/', '', $old_test) == preg_replace('/\s+/', '', $meta_html_current)) {
                    $ok_to_update = true;
                }
            }
        } else {
            // set it to true so that the column select or shown.
            $ok_to_update = true;
        }
    }

    return $ok_to_update;

}


if ( ! function_exists('register_views_content_grid_cell_init') ) {

    function register_views_content_grid_cell_init() {
        if ( function_exists('register_dd_layout_cell_type') ) {
            register_dd_layout_cell_type('views-content-grid-cell',
                array(
                    'name'						=> __('View (Content lists, parametric searches, custom sliders)', 'ddl-layouts'),
                    'description'				=> __('Load content and display it with your styling. A View is used for any custom content display, including parametric searches, tables, grids, sliders and content lists.', 'ddl-layouts'),
                    'category'					=> __('Content display', 'ddl-layouts'),
                    'button-text'				=> __('Assign View', 'ddl-layouts'),
                    'dialog-title-create'		=> __('Create a new View', 'ddl-layouts'),
                    'dialog-title-edit'			=> __('Edit View', 'ddl-layouts'),
                    'dialog-template-callback'	=> 'views_content_grid_dialog_template_callback',
                    'cell-content-callback'		=> 'views_content_grid_content_callback',
                    'cell-template-callback'	=> 'views_content_grid_template_callback',
                    'preview-image-url'			=>  DDL_ICONS_PNG_REL_PATH . 'views-content-grid_expand-image2.png',
                    'cell-image-url' 			=> DDL_ICONS_SVG_REL_PATH.'views-grid-01.svg',
                    'has_settings' => false,
                    'register-scripts'		   	=> array(
                        array( 'ddl_views_content_grid_js', WPDDL_RELPATH . '/inc/gui/dialogs/js/views-grid-cell.js', array( 'jquery' ), WPDDL_VERSION, true ),
                    ),
                )
            );
        }
    }
    add_action( 'init', 'register_views_content_grid_cell_init' );


    function views_content_grid_dialog_template_callback() {
        global $WP_Views;
        ob_start();
        ?>
        <div class="ddl-form">
            <?php
            echo wp_nonce_field('ddl_layout_view_nonce', 'ddl_layout_view_nonce', true, false);
            if ( class_exists('WP_Views') ) {
                if ( defined('WPV_VERSION') ) {
                    if ( version_compare( WPV_VERSION, '1.6.1', '<=' ) ) {
                        ?>
                        <input type="hidden" value="0" class="js-views-content-is_views_installed" />
                        <input type="hidden" value="0" class="js-views-content-is_views_embedded" />
                        <div class="toolset-alert toolset-alert-info">
                            <p>
                                <i class="icon-views-logo ont-color-orange ont-icon-24"></i>
                                <?php _e('This cell requires Views plugin above version 1.6.1 to create custom content-driven cells.', 'ddl-layouts'); ?>



                                &nbsp;&nbsp;
                                <a class="fieldset-inputs" href="http://wp-types.com/home/views-create-elegant-displays-for-your-content?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-views" target="_blank">
                                    <?php _e('About Views', 'ddl-layouts');?>
                                </a>

                            </p>
                        </div>
                    <?php
                    } else {
                        ?>
                        <input type="hidden" value="1" class="js-views-content-is_views_installed" />
                        <?php
                        if ( $WP_Views->is_embedded() ) {
                            ?>
                            <input type="hidden" value="1" class="js-views-content-is_views_embedded" />
                        <?php
                        } else {
                            ?>
                            <input type="hidden" value="0" class="js-views-content-is_views_embedded" />
                        <?php
                        }
                        if ( version_compare( WPV_VERSION, '1.7', '>=' ) ) {
                            ?>
                            <input type="hidden" value="1" class="js-views-content-is_views_above_oneseven" />
                        <?php
                        } else {
                            ?>
                            <input type="hidden" value="0" class="js-views-content-is_views_above_oneseven" />
                        <?php
                        }
                        ?>

                        <fieldset class="js-view-result-missing" style="display:none; margin-top:-20px">
                            <div class="fields-group">
                                <div class="toolset-alert toolset-alert-warning">
                                    <p><i class="icon-warning-sign"></i> <?php _e('This layout has a search form, but is missing the results.', 'ddl-layout'); ?></p>
                                    <div>
                                        <ul>
                                            <li>
                                                <label class="radio">
                                                    <input type="radio" class="js-ddl-complete-search" value="complete" data-cell-name-text="<?php _e('%CELL_NAME% search results', 'ddl-layouts'); ?>" />
                                                    <?php _e('Complete the parametric search setup by inserting a cell for the results', 'ddl-layouts');?>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="radio">
                                                    <input type="radio" class="js-ddl-different-view" value="cancel" />
                                                    <?php _e('Insert a different View, not related to the parametric search results', 'ddl-layouts');?>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </fieldset>


                        <fieldset class="js-view-result-ok">
                            <legend><?php _e('View:', 'ddl-layouts'); ?></legend>
                            <div class="fields-group">
                                <?php if ( $WP_Views->is_embedded() ) { ?>
                                    <p style="display: none;">
                                        <label class="radio">
                                            <input type="radio" name="view-grid-view-action" class="js-ddl-views-grid-create" value="new_layout" />
                                            <?php _e('Create new View', 'ddl-layouts');?>
                                        </label>
                                    </p>
                                    <p style="display:none;">
                                        <label class="radio">
                                            <input type="radio" name="view-grid-view-action" class="js-ddl-views-grid-existing" value="existing_layout" checked="checked" />
                                            <?php _e('Use an existing View', 'ddl-layouts');?>
                                        </label>
                                    </p>
                                <?php } else { ?>
                                    <p>
                                        <label class="radio">
                                            <input type="radio" name="view-grid-view-action" class="js-ddl-views-dialog-mode js-ddl-views-grid-create" <?php checked( get_ddl_field('ddl_layout_view_id'), '' ); ?> value="new_layout" />
                                            <?php _e('Create new View', 'ddl-layouts');?>
                                        </label>
                                    </p>
                                    <p>
                                        <label class="radio">
                                            <?php $checked = ( get_ddl_field('ddl_layout_view_id') != '' ) ? ' checked="checked" ' : '';?>
                                            <input type="radio" name="view-grid-view-action" class="js-ddl-views-dialog-mode js-ddl-views-grid-existing" value="existing_layout" <?php echo $checked?> />
                                            <?php _e('Use an existing View', 'ddl-layouts');?>
                                        </label>
                                    </p>
                                <?php }
                                $hidden = '';
                                if ( get_ddl_field('ddl_layout_view_id') == '' ) {
                                    $hidden = ' style="display:none;" ';
                                }
                                ?>
                                <p class="js-ddl-select-existing-view"<?php echo $hidden; ?>>
                                    <?php
                                    $i = 0;
                                    $wpv_args = array( // array of WP_Query parameters
                                        'post_type' => 'view',
                                        'posts_per_page' => -1,
                                        'order' => 'ASC',
                                        'orderby' => 'title',
                                        'post_status' => 'publish'
                                    );
                                    $wpv_query = get_posts( $wpv_args );
                                    $wpv_count_posts = count( $wpv_query );
                                    $views_as_options = '';
                                    $wpv_total_archives = 0;
                                    $wpv_total_views = 0;
                                    if ( $wpv_count_posts > 0 ) {
                                        foreach ( $wpv_query as $post ) {
                                            $view_settings = $WP_Views->get_view_settings( $post->ID );
                                            if ( isset($view_settings['view-query-mode']) && $view_settings['view-query-mode'] == 'archive' || $view_settings['view-query-mode'] == 'layouts-loop' ){
                                                $wpv_total_archives++;
                                            }else{
                                                $wpv_total_views++;
                                            }
                                            if ( $WP_Views->does_view_have_form_controls( $post->ID ) ) {
                                                $purpose = 'parametric';
                                            } else {
                                                $purpose = isset( $view_settings['view_purpose'] ) ? $view_settings['view_purpose'] : 'full';
                                                if ( $purpose == 'bootstrap-grid' ) {
                                                    $purpose = 'full';
                                                }
                                            }
                                            $i++;
                                            $views_query_mode = isset( $view_settings['view-query-mode'] ) ? $view_settings['view-query-mode'] : 'normal';
                                            $views_as_options .= '<option data-id="' . $post->ID .'" value="' . $post->ID . '" data-mode="' . $views_query_mode . '" data-purpose="' . $purpose . '">' . $post->post_title . '</option>';
                                        }
                                    }
                                    ?>
                                    <input type="hidden" value="<?php echo $wpv_total_archives?>" class="js-wpv-total-archives" />
                                    <input type="hidden" value="<?php echo $wpv_total_views?>" class="js-wpv-total-views" />
                                    <?php
                                    if ( $i > 0 ) {
                                    ?>
                                    <select name="<?php the_ddl_name_attr('ddl_layout_view_id'); ?>" class="ddl-view-select js-ddl-view-select">
                                        <option value="" data-mode="both"><?php _e('--- Select View ---','ddl-layouts');?></option>
                                        <?php echo $views_as_options; ?>
                                    </select>
                                    <?php
                                    if ( $WP_Views->is_embedded() ) {
                                    ?>
                                <div class="toolset-alert toolset-alert-info">
                                    <?php _e('You are using the embedded version of Views. Install and activate the full version of Views and you will be able to create custom content-driven grids.', 'ddl-layouts'); ?>

                                    &nbsp;&nbsp;
                                    <a class="fieldset-inputs" href="http://wp-types.com/home/views-create-elegant-displays-for-your-content?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-views" target="_blank">
                                        <?php _e('About Views', 'ddl-layouts');?>
                                    </a>
                                </div>
                            <?php
                            }
                            } else {
                                if ( $WP_Views->is_embedded() ) {
                                    ?>
                                    <div class="toolset-alert toolset-alert-info js-data-embedded_no_views_at_all" data-embedded_no_views_at_all="yes">
                                        <?php _e('You are using the embedded version of the Views Plugin and there are no Views available.', 'ddl-layouts'); ?>
                                        <!--<br />
                                        <?php _e('You can download pre-built modules using the Module Manager plugin.', 'ddl-layouts'); ?>
                                        <br />
                                        <br />
                                        <?php if (defined( 'MODMAN_CAPABILITY' )) { ?>
                                            <a class="fieldset-inputs button button-primary-toolset" href="<?php echo admin_url('admin.php?page=ModuleManager_Modules&amp;tab=library&amp;module_cat=layouts'); ?>" target="_blank">
                                                <i class="icon-download-alt"></i> <?php _e('Download Modules', 'ddl-layouts');?>
                                            </a>
                                        <?php } else { ?>
                                            <a class="fieldset-inputs button button-primary-toolset" href="http://wp-types.com/home/module-manager?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-module-manager" target="_blank">
                                                <?php _e('Get Module Manager plugin', 'ddl-layouts');?>
                                            </a>
                                        <?php } ?>
                                        -->
                                    </div>
                                <?php
                                } else {
                                    ?>
                                    <select name="<?php the_ddl_name_attr('ddl_layout_view_id'); ?>" class="ddl-view-select js-ddl-view-select" style="display:none;">
                                        <option value="" data-mode="both"><?php _e('None','ddl-layouts');?></option>
                                    </select>
                                    <?php /*<div class="toolset-alert toolset-alert-info">
											<?php _e('There are no Views available.', 'ddl-layouts'); ?>
											<br />
											<?php _e('You can create one or download pre-built modules using the Module Manager plugin.', 'ddl-layouts'); ?>
											<br />
											<br />
											<?php if (defined( 'MODMAN_CAPABILITY' )) { ?>
												<a class="fieldset-inputs button button-primary-toolset" href="<?php echo admin_url('admin.php?page=ModuleManager_Modules&amp;tab=library&amp;module_cat=layouts'); ?>" target="_blank">
													<i class="icon-download-alt"></i> <?php _e('Download Modules', 'ddl-layouts');?>
												</a>
											<?php } else { ?>
												<a class="fieldset-inputs button button-primary-toolset" href="http://wp-types.com/home/module-manager?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-module-manager" target="_blank">
													<?php _e('Get Module Manager plugin', 'ddl-layouts');?>
												</a>
											<?php } ?>
										</div>
										<?php
										*/
                                }
                            }
                            ?>
                                <div class="toolset-alert toolset-alert-info js-no-views-message" style="display: none;">
                                    <span class="js-no-views-message-views" style="display: none;"><?php _e('There are no Views available.', 'ddl-layouts'); ?></span>
                                    <span class="js-no-views-message-archives" style="display: none;"><?php _e('There are no Archive Views available.', 'ddl-layouts'); ?></span>
                                    <!--<br />
                                    <?php _e('You can create one or download pre-built modules using the Module Manager plugin.', 'ddl-layouts'); ?>
                                    <br />
                                    <br />
                                    <?php if (defined( 'MODMAN_CAPABILITY' )) { ?>
                                        <a class="fieldset-inputs button button-primary-toolset" href="<?php echo admin_url('admin.php?page=ModuleManager_Modules&amp;tab=library&amp;module_cat=layouts'); ?>" target="_blank">
                                            <i class="icon-download-alt"></i> <?php _e('Download Modules', 'ddl-layouts');?>
                                        </a>
                                    <?php } else { ?>
                                        <a class="fieldset-inputs button button-primary-toolset" href="http://wp-types.com/home/module-manager?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-module-manager" target="_blank">
                                            <?php _e('Get Module Manager plugin', 'ddl-layouts');?>
                                        </a>
                                    <?php } ?>
                                    -->
                                </div>
                                </p>
                            </div>
                        </fieldset>
                        <?php
                        $hidden = '';
                        if ( $WP_Views->is_embedded() ) {
                            $hidden = ' style="display:none;" ';
                        }
                        ?>
                        <fieldset class="js-ddl-set-view-purpose"<?php echo $hidden; ?>>
                            <legend><?php _e('View purpose:', 'ddl-layouts'); ?></legend>
                            <div class="fields-group ddl-form-indent" style="background:#f0f0f0;padding:1px 10px 10px">
                                <ul>
                                    <li>
                                        <label>
                                            <input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_all" value="all" />
                                            <?php _e('Display all results','ddl-layouts'); ?>
                                        </label>
                                        <span class="desc"><?php _e('The View will output all the results returned from the query section.', 'ddl-layouts'); ?></span>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_pagination" value="pagination" />
                                            <?php _e('Display the results with pagination','ddl-layouts'); ?>
                                        </label>
                                        <span class="desc"><?php _e('The View will display the query results in pages.', 'ddl-layouts'); ?></span>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_slider" value="slider" />
                                            <?php _e('Display the results as a slider','ddl-layouts'); ?>
                                        </label>
                                        <span class="desc"><?php _e('The View will display the query results as slides.', 'ddl-layouts'); ?></span>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_parametric" value="parametric" />
                                            <?php _e('Display the results as a parametric search','ddl-layouts'); ?>
                                        </label>
                                        <span class="desc"><?php _e('Visitors will be able to search through your content using different search criteria.', 'ddl-layouts'); ?></span>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_full" value="full" checked="checked" />
                                            <?php _e('Full custom display mode','ddl-layouts'); ?>
                                        </label>
                                        <span class="desc"><?php _e('See all the View controls open and set up things manually.', 'ddl-layouts'); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </fieldset>
                        <fieldset class="js-ddl-view-main-buttons">

                            <button data-close="no" class="button button-primary js-ddl-create-edit-view ddl-toolset-cell-button">
                                <?php _e('Create', 'ddl-layouts'); ?>
                            </button>
                        </fieldset>
                        <fieldset class="js-wpv-settings-views-layouts-parametric-extra" style="display:none">
                            <div id="views-layouts-parametric-div"
                                 class="wpv-setting-container js-wpv-display-for-purpose js-wpv-display-for-purpose-parametric"
                                 data-notice-1="<?php _e('Since you are only displaying the %NNN% in this cell, the %MMM% section is disabled. A parametric search should have the %NNN% and %MMM%. To display the %MMM% you need to:','ddl-layouts'); ?>"
                                 data-notice-2="<?php _e('Create a different Layout cell and display this View.','ddl-layouts'); ?>"
                                 data-notice-3="<?php _e('Choose to display the %MMM%','ddl-layouts'); ?>"
                                 data-notice-form="<?php _e('search form','ddl-layouts'); ?>"
                                 data-notice-results="<?php _e('search results','ddl-layouts'); ?>"
                                >
                                <div class="wpv-setting js-wpv-setting">
                                    <h3><?php _e('What do you want to display in this cell?', 'ddl-layouts'); ?></h3>
                                    <ul>
                                        <li>
                                            <input type="radio" id="wpv-ddl-parametric-mode-full" class="js-wpv-ddl-parametric-mode" name="<?php the_ddl_name_attr('parametric_mode'); ?>" value="full" />
                                            <label for="wpv-ddl-parametric-mode-full"><?php _e('The search form and the results', 'ddl-layouts'); ?></label>
                                        </li>
                                        <li>
                                            <input type="radio" id="wpv-ddl-parametric-mode-form" class="js-wpv-ddl-parametric-mode" name="<?php the_ddl_name_attr('parametric_mode'); ?>" value="form" />
                                            <label for="wpv-ddl-parametric-mode-form"><?php _e('Only the search form', 'ddl-layouts'); ?></label>
                                            <div class="js-wpv-ddl-parametric-mode-form-settings wpv-advanced-setting" style="margin:10px 0 10px 20px;">
                                                <p>
                                                    <?php _e( 'Where do you want to display the results?', 'ddl-layouts' ); ?>
                                                </p>
                                                <ul>
                                                    <li>
                                                        <input id="wpv-filter-form-target-self" value="self" type="radio" name="<?php the_ddl_name_attr('parametric_mode_target'); ?>" class="js-wpv-ddl-parametric-target" />
                                                        <label for="wpv-filter-form-target-self"><?php _e('In other place on this same page', 'ddl-layouts'); ?></label>
                                                    </li>
                                                    <li>
                                                        <input id="wpv-filter-form-target-other" value="other" type="radio" name="<?php the_ddl_name_attr('parametric_mode_target'); ?>" class="js-wpv-ddl-parametric-target" />
                                                        <label for="wpv-filter-form-target-other"><?php _e('On another page', 'ddl-layouts'); ?></label>
													<span class="toolset-alert" id="wpv-ddl-target-other-forbidden" style="display:none;">
														<?php _e( 'If you want to display the results on a different page, the View form must contain a search button', 'ddl-layouts' ); ?>
													</span>
                                                    </li>
                                                </ul>
                                                <div class="js-wpv-ddl-parametric-target-other-div" style="margin:0 20px 10px;">
                                                    <p>
                                                        <label for="wpv-ddl-parametric-mode-form-target-title"><?php _e('Target page to show the results:', 'ddl-layouts'); ?></label>
                                                        <input type="text" id="wpv-ddl-parametric-mode-form-target-title" name="<?php the_ddl_name_attr('parametric_target_title'); ?>" class="widefat js-wpv-widget-form-target-suggest-title" placeholder="<?php echo esc_attr( __( 'Please type', 'ddl-layouts' ) ); ?>" />
                                                        <input type="hidden" id="wpv-ddl-parametric-mode-form-target-id" name="<?php the_ddl_name_attr('parametric_target_id'); ?>" class="widefat js-wpv-widget-form-target-id" />
                                                    </p>
                                                    <div class="js-wpv-check-target-setup-box" style="display:none;">
                                                        <?php _e( 'Be sure to complete the setup:', 'ddl-layouts' ); ?><br />
                                                        <a href="#" target="_blank" class="button-primary js-wpv-ddl-insert-view-form-target-set-existing-link" data-editurl="<?php echo admin_url( 'post.php' ); ?>?post="><?php _e( 'Add the search results to this page', 'ddl-layouts' ); ?></a>
                                                        <a href="#" class="button-secondary js-wpv-ddl-discard-target-setup-link"><?php _e( 'Not now', 'ddl-layouts' ); ?></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <input type="radio" id="wpv-ddl-parametric-mode-results" class="js-wpv-ddl-parametric-mode" name="<?php the_ddl_name_attr('parametric_mode'); ?>" value="results" />
                                            <label for="wpv-ddl-parametric-mode-results"><?php _e('Only the results', 'ddl-layouts'); ?></label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </fieldset>
                    <?php
                    }
                } else {
                    ?>
                    <input type="hidden" value="0" class="js-views-content-is_views_installed" />
                    <div class="toolset-alert toolset-alert-info">
                        <p>
                            <i class="icon-views-logo ont-color-orange ont-icon-24"></i>
                            <?php _e('This cell requires Views plugin. Install and activate Views and you will be able to create custom content-driven cells.', 'ddl-layouts'); ?>

                            &nbsp;&nbsp;
                            <a class="fieldset-inputs" href="http://wp-types.com/home/views-create-elegant-displays-for-your-content?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-views" target="_blank">
                                <?php _e('About Views', 'ddl-layouts');?>
                            </a>
                        </p>
                    </div>
                <?php
                }
            } else {
                ?>
                <input type="hidden" value="0" class="js-views-content-is_views_installed" />
                <div class="toolset-alert toolset-alert-info">
                    <p>
                        <i class="icon-views-logo ont-color-orange ont-icon-24"></i>
                        <?php _e('This cell requires Views plugin. Install and activate Views and you will be able to create custom content-driven cells.', 'ddl-layouts'); ?>

                        &nbsp;&nbsp;
                        <a class="fieldset-inputs" href="http://wp-types.com/home/views-create-elegant-displays-for-your-content?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-content-grid-cell&utm_term=get-views" target="_blank">
                            <?php _e('About Views', 'ddl-layouts');?>
                        </a>
                    </p>
                </div>
            <?php
            }
            ?>
            <div class="js-views-content-grid-help">
                <?php ddl_add_help_link_to_dialog(WPDLL_VIEWS_CONTENT_GRID_CELL, __('Learn about the Views cell', 'ddl-layouts')); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();

    }

    function views_content_grid_template_callback() {
        global $WP_Views;
        if( class_exists('WP_Views') ){

            ob_start();

            ?> <div class="cell-content">
                <p class="cell-name">{{ name }}</p>
                <div class="cell-preview">
                    <#
                        if (content) {
                        var preview = DDLayout.views_preview.get_preview( name,
                        content,
                        '<?php _e('Updating', 'ddl-layouts'); ?>...',
                        '<?php _e('Loading', 'ddl-layouts'); ?>...',
                        '<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/views-content-grid.svg'; ?>'
                        );
                        print( preview );
                        }
                        #>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
    }



    function views_content_grid_content_callback() {
        //Render View
        if( function_exists('render_view') )
        {
            $mode = get_ddl_field('parametric_mode');
            $target = get_ddl_field('parametric_mode_target');
            $target_id = get_ddl_field('parametric_target_id');

            if ( $target == 'self' ) {
                $target_id = 'self';
            }

            if ( $mode == 'form' && ! empty( $target_id ) ) {
                return render_view( array( 'id' => get_ddl_field('ddl_layout_view_id'), 'target_id' => $target_id ) );
            } else if ( $mode == 'results' ) {
                return render_view( array( 'id' => get_ddl_field('ddl_layout_view_id'), 'view_display' => 'layout' ) );
            } else {
                return render_view( array( 'id' => get_ddl_field('ddl_layout_view_id') ) );
            }
        }
        else
        {
            return WPDDL_Messages::views_missing_message();
        }

    }

}
function ddl_views_generate_cell_preview( $post_title, $id, $meta, $view_output ){
    $count_view_output = count($view_output);
    //Generate preview for bootstrap grid and table based grid
    if ( !isset($meta['style']) ){
        $meta['style'] = 'unformatted';
    }
    if ( $meta['style'] == 'bootstrap-grid'  ):
        $col_number = $meta['bootstrap_grid_cols'];
        $i=$k=0;
        $col_width = 12/$col_number;
        ?>
		<i class="icon-th-large ddl-view-layout-icon"></i><?php _e('Bootstrap grid', 'ddl-layouts'); ?>
		<br />
		<div class="presets-list fields-group">
		<?php
        $total_rows = 0;

        if ( $count_view_output > 0 ){
            for ($j = 0, $limit=$count_view_output; $j < $limit; $j++){
                $view_post = $view_output[$j];
                $cell_content = ddl_view_content_grid_get_title( $view_post );
                $i++;
                if ($i == 1){
                    $total_rows++;
                    if ( $total_rows > 3){
                        $j = $count_view_output+1;
                        $hidden_items_count = $limit-$k;
                        $hidden_rows = ceil($hidden_items_count/$col_number);
                        ?>
                        <div class="row-fluid">
                            <div class="span-preset12 views-cell-preview views-cell-preview-more">
                                <?php echo sprintf(__('Plus %s more rows - %s items in total', 'ddl-layouts'), $hidden_rows, $limit); ?>
                            </div>
                        </div>
                        <?php
                        continue;
                    }
                    ?>
                    <div class="row-fluid">
                <?php
                }
                ?>
                <div class="span-preset<?php echo $col_width; ?> views-cell-preview" ><?php echo $cell_content; ?></div>
                <?php
                if ( $i == $col_number){
                    $i=0;
                    ?></div><?php
                }
                $k++;
            }
            if ( $i != 0 ){
                ?></div><?php
            }
        } else {
            //Show empty grid when no posts
            ?>
            <div class="row-fluid">
                <?php
                for( $i=0; $i<$col_number; $i++){
                    ?>
                    <div class="span-preset<?php echo $col_width;?> views-cell-preview" ></div>
                <?php
                }
                ?>
            </div>
            <div class="row-fluid">
                <div class="span-preset12 views-cell-preview views-cell-preview-more">
                    <?php _e('No items were returned by the View', 'ddl-layouts'); ?>
                </div>
            </div>
        <?php
        }
        ?></div><?php
    elseif ( $meta['style'] == 'table' ):
        $col_number = $meta['table_cols'];
        $i=$k=0;
        $col_width = round(100/$col_number, 2)-2;
        $total_rows = 0;
        ?>
		<i class="icon-th ddl-view-layout-icon"></i><?php _e('Table-based grid', 'ddl-layouts'); ?>
		<br />
		<?php
        if ( $count_view_output > 0 ){
            $total_rows = 0;
            for ($j = 0, $limit=$count_view_output; $j < $limit; $j++){
                $view_post = $view_output[$j];
                $cell_content = ddl_view_content_grid_get_title( $view_post );
                $i++;
                if ( $i == 1){
                    $total_rows++;
                    if ( $total_rows > 3){
                        $j = $count_view_output+1;
                        $hidden_items_count = $limit-$k;
                        $hidden_rows = ceil($hidden_items_count/$col_number);
                        ?>
                        <div class="row-fluid row">
                            <div class="views-cell-table-preview views-cell-preview views-cell-preview-more views-cell-table-preview-more" style="width:100%;">
                                <?php echo sprintf(__('Plus %s more rows - %s items in total', 'ddl-layouts'), $hidden_rows, $limit); ?>
                            </div>
                        </div>
                        <?php
                        continue;
                    }
                    ?>
                    <div class="row-fluid">
                <?php }	?>
                <div class="views-cell-preview views-cell-table-preview" style="width:<?php echo $col_width?>%;"><?php echo $cell_content;?></div>
                <?php
                if ( $i == $col_number ){
                    $i = 0;
                    ?>
                    </div>
                <?php }

            }
            if ( $i != 0 ){
                ?></div><?php
            }
        } else {
            //If table 0 posts
            ?>
            <div class="row-fluid">
                <?php
                for( $i=0; $i<$col_number; $i++){
                    ?>
                    <div class="views-cell-preview views-cell-table-preview" style="width:<?php echo $col_width?>%;"></div>
                <?php
                }
                ?>
            </div>
            <div class="row-fluid row">
                <div class="views-cell-table-preview views-cell-preview views-cell-preview-more views-cell-table-preview-more" style="width:100%;">
                    <?php _e('No items were returned by the View', 'ddl-layouts'); ?>
                </div>
            </div>
        <?php
        }
    elseif ( $meta['style'] == 'unformatted' ||  $meta['style'] == 'un_ordered_list' || $meta['style'] == 'ordered_list' ):
        switch ($meta['style']) {
            case 'unformatted':
                $style_icon = 'icon-code';
                $style_name = __('Unformatted', 'ddl-layouts');
                break;

            case 'un_ordered_list':
                $style_icon = 'icon-list-ul';
                $style_name = __('Unordered list', 'ddl-layouts');
                break;

            case 'ordered_list':
                $style_icon = 'icon-list-ol';
                $style_name = __('Ordered list', 'ddl-layouts');
                break;

        }
        ?>
    <i class="<?php echo $style_icon; ?> ddl-view-layout-icon"></i><?php echo $style_name; ?>
        <br />
        <div class="presets-list fields-group">
            <?php
            for ( $i=0; $i<3; $i++ ){
                if (isset($view_output[$i])) {
                    $view_post = $view_output[$i];
                    $cell_content = ddl_view_content_grid_get_title( $view_post );
                } else {
                    $cell_content = '';
                }
                ?>
                <div class="row-fluid row">
                    <?php if ( $meta['style'] == 'unformatted' ){?>
                        <div class="span-preset12 views-cell-preview" >
                            <?php echo $cell_content;?>
                        </div>
                    <?php }elseif(  $meta['style'] == 'un_ordered_list' || $meta['style'] == 'ordered_list' ){
                        $list = '&#149;';
                        if ( $meta['style'] == 'ordered_list' ){
                            $list = $i+1;
                        }
                        ?>
                        <div class="views-cell-preview views-cell-table-preview views-cell-table-preview-no-border" style="width:8%;">
                            <?php echo $list;?>
                        </div>
                        <div class="views-cell-preview views-cell-table-preview" style="width:85%;">
                            <?php echo $cell_content;?>
                        </div>
                    <?php }?>
                </div>
            <?php
            }
            if ($count_view_output) {
                $cell_message = '';
                $limit = $count_view_output;
                if ( $limit > 3 ){
                    $limit -= 3;
                    $cell_message = sprintf(__('Plus %s more items', 'ddl-layouts'), $limit);
                }
            } else {
                $cell_message = __('No items were returned by the View', 'ddl-layouts');
            }
            ?>
            <?php if ($cell_message): ?>
                <div class="row-fluid">
                    <div class="span-preset12 views-cell-preview views-cell-preview-more">
                        <?php echo $cell_message ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php
    elseif ( $meta['style'] == 'table_of_fields' ):
        $col_number = (count($meta['fields'])+1)/5-1;
        $i=$k=0;
        $col_width = round(100/$col_number, 2)-2;
        $total_rows = 0;
        ?>
        <i class="icon-table ddl-view-layout-icon"></i><?php _e('Table', 'ddl-layouts'); ?>
        <br />
        <div class="presets-list fields-group">
        <table class="ddl-view-table-preview" width="100%">
            <thead>
            <tr>

                <?php
                for ( $i=0,$limit=$col_number; $i<$limit; $i++ ){
                    $col_title = __('Column ', 'ddl-layouts').' '.$i;
                    if ( isset($meta['fields']['row_title_'.$i]) && !empty($meta['fields']['row_title_'.$i]) ){
                        $col_title = $meta['fields']['row_title_'.$i];
                    }
                    ?>
                    <td width="<?php echo 100/count($meta['fields']); ?>%"><?php echo $col_title;?></td>
                <?php
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="<?php echo count($meta['fields']); ?>">

                    <?php
                    for ( $i=0; $i<3; $i++ ){
                        if (isset($view_output[$i])) {
                            $view_post = $view_output[$i];
                            $cell_content = ddl_view_content_grid_get_title( $view_post );
                        } else {
                            $cell_content = '';
                        }
                        ?>
                        <div class="row-fluid row">
                            <div class="span-preset12 views-cell-preview" >
                                <?php echo $cell_content;?>
                            </div>
                        </div>
                    <?php
                    }
                    $cell_message = __('No items were returned by the View', 'ddl-layouts');
                    $limit = $count_view_output;
                    if ( $limit > 3 ){
                        $limit -= 3;
                        $cell_message = sprintf(__('Plus %s more items', 'ddl-layouts'), $limit);
                    }
                    ?>

                </td>
            </tr>
            </tbody>
        </table>
        <div class="presets-list fields-group">
            <div class="row-fluid">
                <div class="span-preset12 views-cell-preview views-cell-preview-more">
                    <?php echo $cell_message ?>
                </div>
            </div>
        </div>
    <?php
    else:
        $view_count = $count_view_output;
        ?>
        <?php _e('View name', 'ddl-layouts'); ?>: <?php echo $post_title; ?><br>
		<?php _e('Layout Style', 'ddl-layouts'); ?>: <?php echo isset($layout_style[$meta['style']])?$layout_style[$meta['style']]:'Undefined'; ?><br>
		<?php if ( $meta['style'] == 'bootstrap-grid' ) : ?>
        <?php _e('Columns', 'ddl-layouts'); ?>: <?php echo $meta['bootstrap_grid_cols']; ?><br>
    <?php endif; ?>
        <?php if ( $meta['style'] == 'table' ): ?>
        <?php _e('Columns', 'ddl-layouts'); ?> <?php echo $meta['table_cols']; ?><br>
    <?php endif; ?>
        <?php _e('Items to display', 'ddl-layouts'); ?>: <?php echo $view_count; ?><br>
		<?php
    endif;
}

function ddl_view_content_grid_get_title( $view_post ){
    $cell_content = '';
    if ( isset($view_post->post_title) ){
        $cell_content = $view_post->post_title;
    }
    if ( isset($view_post->name) ){
        $cell_content = $view_post->name;
    }
    if ( isset($view_post->user_login) ){
        $cell_content = $view_post->user_login;
    }
    return $cell_content;
}