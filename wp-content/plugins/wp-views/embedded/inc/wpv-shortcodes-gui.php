<?php

/**
* wpv-shortcodes-gui.php
*
* All callback actions to display popups to set options for our Views shortcodes go here
*
* @package Views
* @since unknown
*/

/**
* ----------------------------------------------------------------------
## Parametric search ##
* ----------------------------------------------------------------------
*/

/**
* wpv_ajax_wpv_view_form_popup
*
* Popup for inserting a View form, loaded from a ColorBox AJAX call
*
* @param $_GET['_wpnonce']
* @param $_GET['view_id']
*
* @since 1.4
* @uses ColorBox
*/


function wpv_ajax_wpv_view_form_popup() {
    
	global $wpdb, $WP_Views;
    
	if ( wp_verify_nonce( $_GET['_wpnonce'], 'wpv_editor_callback' ) ) {

        $view_id = intval( $_GET['view_id'] );
		$view_title = sanitize_text_field( $_GET['view_title'] );
		$orig_id = intval( $_GET['orig_id'] );
		$has_submit = false;
		$view_settings = $WP_Views->get_view_settings( $view_id );
		if ( isset( $view_settings['filter_meta_html'] ) ) {
			$filter_meta_html = $view_settings['filter_meta_html'] ;
		} else {
			$filter_meta_html = '';
		}
		if ( strpos( $filter_meta_html, '[wpv-filter-submit' ) !== false ) {
			$has_submit = true;
		}
        ?>
		<div class="wpv-dialog">
			<p class="toolset-alert toolset-alert-info"><i class="icon-filter toolset-rounded-icon"></i><?php _e( 'This View contains a parametric search', 'wpv-views' ); ?></p>
			<input type="hidden" id="js-wpv-view-form-gui-dialog-view-title" value="<?php echo esc_attr( $view_title ); ?>" />
			<div class="js-wpv-insert-view-form-display-container">
				<p><strong><?php _e( 'What do you want to include here?', 'wpv-views' ); ?></strong></p>
				<ul>
					<li>
						<input id="wpv-filter-form-display-both" value="both" type="radio" name="wpv-insert-view-form-display" class="js-wpv-insert-view-form-display" checked="checked" />
						<label for="wpv-filter-form-display-both"><?php _e('Both the search box and results', 'wpv-views'); ?></label>
						<span class="wpv-helper-text"><?php _e( 'This will display the full View.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-filter-form-display-form" value="form" type="radio" name="wpv-insert-view-form-display" class="js-wpv-insert-view-form-display" />
						<label for="wpv-filter-form-display-form"><?php _e('Only the search box', 'wpv-views'); ?></label>
						<span class="wpv-helper-text"><?php _e( 'This will display just the form, you can select where to display the results in the next step.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-filter-form-display-results" value="results" type="radio" name="wpv-insert-view-form-display" class="js-wpv-insert-view-form-display" />
						<label for="wpv-filter-form-display-results"><?php _e('Only the search results', 'wpv-views'); ?></label>
						<span class="wpv-helper-text"><?php _e( 'This will display just the results, you need to add the form elsewhere targeting this page.', 'wpv-views' ); ?></span>
					</li>
				</ul>
			</div>
			<div class="js-wpv-insert-view-form-target-container" style="display:none">
				<p><strong><?php _e( 'Where do you want to display the results of this search?', 'wpv-views' ); ?></strong></p>
				<?php if ( ! $has_submit ) { ?>
				<span class="toolset-alert toolset-error">
					<?php _e( 'The form in this View does not have a submit button, so you can only display the results on this same page.', 'wpv-views' ); ?>
				</span>
				<?php } ?>
				<ul>
					<li>
						<input id="wpv-filter-form-target-self" value="self" type="radio" name="wpv-insert-view-form-target" class="js-wpv-insert-view-form-target" checked="checked" />
						<label for="wpv-filter-form-target-self"><?php _e('In other place on this same page', 'wpv-views'); ?></label>
					</li>
					<li>
						<input id="wpv-filter-form-target-other" <?php disabled( $has_submit, false ); ?> value="other" type="radio" name="wpv-insert-view-form-target" class="js-wpv-insert-view-form-target" />
						<label for="wpv-filter-form-target-other" <?php if ( ! $has_submit ) { ?>style="color:#999"<?php } ?>><?php _e('On another page', 'wpv-views'); ?></label>
					</li>
				</ul>
				<div class="js-wpv-insert-view-form-target-set-container" style="display:none;margin-left:20px;">
					<p><?php _e( 'You can display the results on an existing page or create a new one:', 'wpv-views' ); ?></p>
					<ul>
						<li>
							<input id="wpv-insert-view-form-target-set-existing" value="existing" type="radio" name="wpv-insert-view-form-target-set" class="js-wpv-insert-view-form-target-set" checked="checked" />
							<label for="wpv-insert-view-form-target-set-existing"><?php _e( 'Use an existing page', 'wpv-views' ); ?></label>
							<div class="js-wpv-insert-view-form-target-set-existing-extra" style="margin:5px 0 0 20px;">
								<input class="js-wpv-insert-view-form-target-set-existing-title" type="text" name="wpv-insert-view-form-target-set-existing-title" placeholder="<?php echo esc_attr( __( 'Type the title of the page', 'wpv-views' ) ); ?>" value="" />
								<input class="js-wpv-insert-view-form-target-set-existing-id" type="hidden" name="wpv-insert-view-form-target-set-existing-id" value="" />
								<div class="js-wpv-insert-view-form-target-set-actions" style="display:none;background:#ddd;margin-top: 5px;padding: 5px 10px 10px;">
									<?php _e( 'Be sure to complete the setup:', 'wpv-views' ); ?><br />
									<a href="#" target="_blank" class="button-primary js-wpv-insert-view-form-target-set-existing-link" data-origid="<?php echo $orig_id; ?>" data-viewid="<?php echo $view_id; ?>" data-editurl="<?php echo admin_url( 'post.php' ); ?>?post="><?php _e( 'Add the search results to this page', 'wpv-views' ); ?></a>
									<a href="#" class="button-secondary js-wpv-insert-view-form-target-set-discard"><?php _e( 'Not now', 'wpv-views' ); ?></a>
								</div>
							</div>
						</li>
						<li>
							<input id="wpv-insert-view-form-target-set-create" value="create" type="radio" name="wpv-insert-view-form-target-set" class="js-wpv-insert-view-form-target-set" />
							<label for="wpv-insert-view-form-target-set-create"><?php _e( 'Use one new page', 'wpv-views' ); ?></label>
							<div class="js-wpv-insert-view-form-target-set-create-extra" style="display:none;margin:5px 0 0 20px;">
								<input class="js-wpv-insert-view-form-target-set-create-title" type="text" name="wpv-insert-view-form-target-set-extra-title" placeholder="<?php echo esc_attr( __( 'Type a title of the new page', 'wpv-views' ) ); ?>" value="" />
								<button class="button-secondary js-wpv-insert-view-form-target-set-create-action" disabled="disabled" data-viewid="<?php echo $view_id; ?>" data-nonce="<?php echo wp_create_nonce('wpv_create_form_target_page_nonce'); ?>"><?php _e( 'Create page', 'wpv-views' ); ?></button>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>
        <?php
	}   
	die();
}


/**
* wpv_shortcode_gui_dialog_render_attribute
*
* Render the options for each shortcode attribute in the dialog
*
* @param $id (string) Format {shortcode}-{attribute_key}(?-value)
* @param $data (array) Data for the current attribute
* @param $classes (array) Classnames to be applied to the current attribute form elements
* @param $post_type (array) Post type object that the current post belongs to, empty array of none, to render the post selector if needed
*
* @return Print the shortcode attribute options
*
* @since 1.9
*/

function wpv_shortcode_gui_dialog_render_attribute( $id, $data = array(), $classes = array(), $post_type = array() ) {
    if ( 
		isset( $data['classes'] ) 
		&& is_array( $data['classes'] ) 
	) {
        $classes = array_merge( $classes, $data['classes'] );
    }
	$attr_value = isset( $data['default'] ) ? $data['default'] : '';
	$attr_value = isset( $data['default_force'] ) ? $data['default_force'] : $attr_value;
    $content = '';
    /**
     * produce code
     */
    switch( $data['type'] ) {
    case 'number':
    case 'text':
    case 'url':
        $classes[] = 'large-text';
		if ( isset( $data['placeholder'] ) ) {
			$classes[] = 'js-wpv-shortcode-gui-attribute-has-placeholder';
		}
        $content .= sprintf(
            '<input id="%s" type="text" data-type="%s" placeholder="%s" data-placeholder="%s" class="%s" value="%s"%s />',
            esc_attr( $id ),
            esc_attr( $data['type'] ),
            isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
			isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $attr_value ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        break;
    case 'suggest':
        $classes[] = 'large-text';
        $classes[] = 'js-wpv-shortcode-gui-suggest';
		if ( isset( $data['placeholder'] ) ) {
			$classes[] = 'js-wpv-shortcode-gui-attribute-has-placeholder';
		}
        $content .= sprintf(
            '<input id="%s" type="text" data-type="%s" data-action="%s" placeholder="%s" data-placeholder="%s" class="%s" value="%s"%s />',
            esc_attr( $id ),
            esc_attr( $data['type'] ),
            isset( $data['action'] ) ? esc_attr( $data['action'] ) : '',
            isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
			isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $attr_value ),
            isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        break;
    case 'fixed':
        $classes[] = 'large-text';
        $classes[] = 'js-wpv-shortcode-gui-fixed';
        $content .= sprintf(
            '<input id="%s" type="text" data-type="%s" class="%s" value="%s" disabled="disabled"%s />',
            esc_attr( $id ),
            esc_attr( $data['type'] ),
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $attr_value ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        break;
    case 'radio':
        $content .= sprintf(
            '<ul id="%s">', 
            esc_attr( $id )
        );
        foreach ( $data['options'] as $option_value => $option_label ) {
            if ( 'custom-combo' == $option_value ) {
				$classes[] = 'js-wpv-shortcode-gui-attribute-custom-combo-pointer';
                $content .= sprintf(
                    '<li class="custom-combo js-wpv-shortcode-gui-attribute-custom-combo"><label><input type="%s" name="%s" value="%s" class="%s"%s />%s</label>',
                    esc_attr( $data['type'] ),
                    esc_attr( $id ),
                    esc_attr( $option_value ),
                    esc_attr( implode( ' ', $classes ) ),
                    $option_value == $attr_value ? ' checked="checked"' : '',
                    esc_html( $option_label['label'] )
                );
                $option_label['classes'] = array(
					'custom-combo-target',
					'js-wpv-shortcode-gui-attribute-custom-combo-target',
					'js-shortcode-gui-field'
				);
				if ( isset( $option_label['required'] ) ) {
					$option_label['classes'][] = 'js-wpv-shortcode-gui-required';
				}
                $option_label['hide'] = true;
                $content .= wpv_shortcode_gui_dialog_render_attribute( $id.'-value', $option_label );
                $content .= '</li>';
            } else {
                $content .= sprintf(
                    '<li><label><input type="%s" name="%s" value="%s" class="%s"%s />%s</label></li>',
                    esc_attr( $data['type'] ),
                    esc_attr( $id ),
                    esc_attr( $option_value ),
                    esc_attr( implode( ' ', $classes ) ),
                    $option_value == $attr_value ? ' checked="checked"' : '',
                    esc_html( $option_label )
                );
            }
        }
        $content .= '</ul>';
        break;
    case 'select':
        $content .= sprintf(
            '<select id="%s" class="%s"%s>',
            esc_attr( $id ),
            esc_attr( implode( ' ', $classes ) ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        foreach ( $data['options'] as $option_value => $option_label ) {
            $content .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $option_value ),
                $option_value == $attr_value ? ' selected="selected"' : '',
                esc_html( $option_label )
            );
        }
        $content .= '</select>';
        break;
    case 'post':
		$content .= sprintf(
            '<ul id="%s">', 
            esc_attr( $id )
        );
		
        $content .= '<li class="wpv-shortcode-gui-post-selector-option">';
		$content .= '<label for="wpv-shortcode-gui-post-selector-post-id-current">';
        $content .= '<input type="radio" class="js-wpv-shortcode-gui-post-selector" id="wpv-shortcode-gui-post-selector-post-id-current" name="post_id" value="current" checked="checked" />';
        $content .=  __( 'The current post being displayed either directly or in a View loop', 'wpv-views' );
        $content .= '</label>';
        $content .= '</li>';

		/**
		* Hierarchical
		*/
        if ( 
			! empty( $post_type ) 
			&& isset( $post_type->hierarchical ) 
			&& $post_type->hierarchical
		) {
            $content .= '<li class="wpv-shortcode-gui-post-selector-option">';
			$content .= '<label for="wpv-shortcode-gui-post-selector-post-id-parent">';
            $content .= '<input type="radio" class="js-wpv-shortcode-gui-post-selector" id="wpv-shortcode-gui-post-selector-post-id-parent" name="post_id" value="parent" />';
            $content .= __( 'The parent of the current post in the same post type, set by WordPress hierarchical relationship', 'wpv-views' );
            $content .= '</label>';
            $content .= '</li>';
        }

        /**
		* Types relationships
		*/
        
		if (
			! empty( $post_type )
            && isset( $post_type->slug )
		) {
			$custom_post_types_relations = get_option( 'wpcf-custom-types', array() );
			$current_post_type_parents = array();
			// Fix legacy problem, when child CPT has no parents itself, but parent CPT has children
			foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
				if ( 
					isset( $cptr_data['post_relationship']['has'] ) 
					&& in_array( $post_type->slug, array_keys( $cptr_data['post_relationship']['has'] ) )
				) {
					$current_post_type_parents[] = $cptr_key;
				}
			}
			if ( isset( $custom_post_types_relations[$post_type->slug] ) ) {
				$current_post_type_data = $custom_post_types_relations[$post_type->slug];
				if (
					isset( $current_post_type_data['post_relationship'] )
					&& ! empty( $current_post_type_data['post_relationship'] )
					&& isset( $current_post_type_data['post_relationship']['belongs'] )
				) {
					foreach ( array_keys( $current_post_type_data['post_relationship']['belongs'] ) as $cpt_in_relation) {
						$current_post_type_parents[] = $cpt_in_relation;
					}
				}
			}
			if ( ! empty( $current_post_type_parents) ) {
				$content .= '<li class="wpv-shortcode-gui-post-selector-option wpv-shortcode-gui-post-selector-has-related js-wpv-shortcode-gui-post-selector-has-related">';
				$content .= '<label for="wpv-shortcode-gui-post-selector-post-id-related">';
				$content .= '<input type="radio" class="js-wpv-shortcode-gui-post-selector" id="wpv-shortcode-gui-post-selector-post-id-related" name="post_id" value="related" />';
				$content .= __( 'The parent of the current post in another post type, set by Types relationship', 'wpv-views' );
				$content .= '</label>';
				$content .= '<div class="wpv-shortcode-gui-post-selector-is-related js-wpv-shortcode-gui-post-selector-is-related" style="display:none">';
				$first = true;
				foreach ( $current_post_type_parents as $slug  ) {
					$content .= sprintf( '<label for="post-id-%s">', $slug );
					$content .= sprintf(
						'<input type="radio" name="related_post" id="wpv-shortcode-gui-post-selector-post-id-%s" value="%s" %s />',
						$slug,
						$slug,
						$first ? 'checked="checked"' : ''
					);
					$content .= $custom_post_types_relations[$slug]['labels']['singular_name'];
					$content .= '</label>';
					$first = false;
				}
				$content .= '</div>';
				$content .= '</li>';
			}
		}
		
		/**
		* Specific post selection
		*/

        $content .= '<li class="wpv-shortcode-gui-post-selector-option wpv-shortcode-gui-post-selector-has-related js-wpv-shortcode-gui-post-selector-has-related">';
		$content .= '<label for="wpv-shortcode-gui-post-selector-post-id">';
        $content .= '<input type="radio" class="js-wpv-shortcode-gui-post-selector" id="wpv-shortcode-gui-post-selector-post-id" name="post_id" value="post_id" />';
        $content .= __( 'A specific post', 'wpv-views' );
        $content .= '</label>';
        $content .= '<div class="wpv-shortcode-gui-post-selector-is-related js-wpv-shortcode-gui-post-selector-is-related" style="display:none">';
        $content .= '<label for="wpv-shortcode-gui-post-selector-post-id-post_id">';
        //$content .= __( 'Post selection', 'wpv-views' );
        $content .= '<input type="text" id="wpv-shortcode-gui-post-selector-post-id-post_id" class="js-wpv-shortcode-gui-attribute-has-placeholder" name="specific_post_id" placeholder="' . esc_attr( __( 'Enter a post ID, eg 15', 'wpv-views' ) ) . '" data-placeholder="' . esc_attr( __( 'Enter a post ID, eg 15', 'wpv-views' ) ) . '" />';
        $content .= '</label>';
        $content .= '</div>';
        $content .= '</li>';

		$content .= '</ul>';
        $content .= '<p class="description">';
        $content .= sprintf(
            __( 'Learn about displaying content from parent and other posts in the %sdocumentation page%s.', 'wpv-views' ),
            '<a href="http://wp-types.com/documentation/user-guides/displaying-fields-of-parent-pages/" target="_blank">',
            '</a>'
        );
        $content .= '</p>';
		
        break;
    default:
        $content .= $data['type'];
        break;
    }
    return $content;
}

add_action('wp_ajax_wpv_shortcode_gui_dialog_create', 'wp_ajax_wpv_shortcode_gui_dialog_create');

/**
* wp_ajax_wpv_shortcode_gui_dialog_create
*
* Render dialog for shortcodes attributes
*
* @since 1.9.0
*/
function wp_ajax_wpv_shortcode_gui_dialog_create() {
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpv_editor_callback' ) ) {
        die();
    }
    if (
		! isset( $_GET['shortcode'] ) 
		|| empty( $_GET['shortcode'] ) 
	) {
        die();
    }
    /**
	* White list of shortcodes.
	*
	* Filter allow to add shortcode definition to white list of allowed 
	* shortcodes.
	*
	* @since 1.9.0
	*
	* @param array $views_shortcodes {
	*     Complex array with shortcode definition.
	*
	*     @type string $name Name of shortcode.
	*     @type string $label Label displayed as a title of modal popup.
	*     @type array $attributes {
	*          Allowed attributes of shortode.
	*
	*          @type string $label Label of field.
	*          @type string $type Type of field
	*          @type array $options {
	*              Optional param with radio or select options. Array keys is 
	*              a field name.
	*
	*              @@type string Label of value.
	*
	*          }
	*     }
	* }
	*/
	
	$shortcode = $_GET['shortcode'];
	$views_shortcodes_gui_data = apply_filters( 'wpv_filter_wpv_shortcodes_gui_data', array() );
	
    if ( ! isset( $views_shortcodes_gui_data[$shortcode] ) ) {
        die();
    }
    
	$shortcode_data = $views_shortcodes_gui_data[$shortcode];
	if ( 
		isset( $shortcode_data['callback'] )
		&& is_callable( $shortcode_data['callback'] )
	) {
		$options = call_user_func( $shortcode_data['callback'] );
	} else {
		die();
	}

    /**
	* Post selection tab
	*/
    if ( 
		isset( $options['post-selection'] ) 
		&& $options['post-selection'] 
	) {
        if ( ! isset($options['attributes'] ) ) {
            $options['attributes'] = array();
        }
        $options['attributes']['post-selection'] = array(
            'label' => __('Post selection', 'wpv-views'),
            'header' => __('Display data for:', 'wpv-views'),
            'fields' => array(
                'id' => array(
                    'type' => 'post'
                ),
            ),
        );
    }

    /**
	* If post_id was passed, get the current post type object
	*/
    $post_id = 0;
    if ( isset( $_GET['post_id'] ) ) {
        $post_id = intval( $_GET['post_id'] );
    }
    $post_type = array();
    if ( $post_id ) {
        $post_type = get_post_type_object( get_post_type( $post_id ) );
    }

    printf(
        '<div class="wpv-dialog js-insert-%s-dialog">',
        esc_attr( $shortcode )
    );
    echo '<input type="hidden" value="' . esc_attr( $shortcode ) . '" class="wpv-shortcode-gui-shortcode-name js-wpv-shortcode-gui-shortcode-name" />';
    echo '<div id="js-wpv-shortcode-gui-dialog-tabs" class="wpv-shortcode-gui-tabs js-wpv-shortcode-gui-tabs">';
    $tabs = '';
    $content = '';
    foreach( $options['attributes'] as $group_id => $group_data ) {
        $tabs .= sprintf(
            '<li><a href="#%s-%s">%s</a></li>',
            esc_attr( $shortcode ),
            esc_attr( $group_id ),
            esc_html( $group_data['label'] )
        );
        $content .= sprintf(
			'<div id="%s-%s">', 
			esc_attr( $shortcode ), 
			esc_attr( $group_id )
		);
        if ( isset( $group_data['header'] ) ) {
            $content .= sprintf(
				'<h2>%s</h2>', 
				esc_html( $group_data['header'] ) 
			);
        }
        /**
         * add fields
         */
        foreach ( $group_data['fields'] as $key => $data ) {
            if ( ! isset( $data['type'] ) ) {
                continue;
            }
            $id = sprintf(
				'%s-%s', 
				$shortcode, 
				$key
			);
            $content .= sprintf(
                '<div class="wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper-for-%s" data-type="%s" data-attribute="%s" data-default="%s">',
                esc_attr( $key ),
				esc_attr( $data['type'] ),
                esc_attr( $key ),
                isset( $data['default'] ) ? esc_attr( $data['default'] ) : ''
            );
			$attr_value = isset( $data['default'] ) ? $data['default'] : '';
			$attr_value = isset( $data['default_force'] ) ? $data['default_force'] : $attr_value;
			
            $classes = array('js-shortcode-gui-field');
			$required = '';
			if ( 
				isset( $data['required'] ) 
				&& $data['required'] 
			) {
				$classes[] = 'js-wpv-shortcode-gui-required';
				$required = ' <span>- ' . esc_html( __( 'required', 'wpv-views' ) ) . '</span>';
			}
			if ( isset( $data['label'] ) ) {
                $content .= sprintf(
					'<h3>%s%s</h3>', 
					esc_html( $data['label'] ),
					$required
				);
            }
            /**
             * require
             */
            if ( isset($data['required']) && $data['required']) {
                $classes[] = 'js-required';
            }
            /**
             * Filter of options
             *
             * This filter allow to manipulate of radio/select field options.
             * Filter is 'wpv_filter_wpv_shortcodes_gui_api_{shortode}_options'
             *
             * @param array $options for description see param $options in 
             * wpv_filter_wpv_shortcodes_gui_api filter.
             *
             * @param string $type field type
             *
             */
            if ( isset( $data['options'] ) ) {
                $data['options'] = apply_filters( 'wpv_filter_wpv_shortcodes_gui_api_' . $id . '_options', $data['options'], $data['type'] );
            }

            $content .= wpv_shortcode_gui_dialog_render_attribute( $id, $data, $classes, $post_type );

			$desc_and_doc = array();
			if ( isset( $data['description'] ) ) {
				$desc_and_doc[] = esc_html( $data['description'] );
			}
			if ( isset( $data['documentation'] ) ) {
				$desc_and_doc[] = sprintf(
					__( 'Specific documentation: %s', 'wpv-views' ),
					$data['documentation']
				);
			}
			if ( ! empty( $desc_and_doc ) ) {
				$content .= '<p class="description">' . implode( '<br />', $desc_and_doc ) . '</p>';
			}
			$content .= '</div>';
		}
		if ( isset( $group_data['content'] ) ) {
			if ( isset( $group_data['content']['hidden'] ) ) {
				$content .= '<span class="wpv-shortcode-gui-content-wrapper js-wpv-shortcode-gui-content-wrapper" style="display:none">';
				$content .= sprintf(
					'<input id="shortcode-gui-content-%s" type="text" class="large-text js-wpv-shortcode-gui-content" />',
					esc_attr( $shortcode )
				);
				$content .= '</span>';
			} else {
				$content .= '<div class="wpv-shortcode-gui-content-wrapper js-wpv-shortcode-gui-content-wrapper">';
				$content .= sprintf(
					'<h3>%s</h3>', 
					esc_html( $group_data['content']['label'] )
				);
				$content .= sprintf(
					'<input id="shortcode-gui-content-%s" type="text" class="large-text js-wpv-shortcode-gui-content" />',
					esc_attr( $shortcode )
				);
				$desc_and_doc = array();
				if ( isset( $group_data['content']['description'] ) ) {
					$desc_and_doc[] = $group_data['content']['description'];
				}
				if ( isset( $group_data['content']['documentation'] ) ) {
					$desc_and_doc[] = sprintf(
						__( 'Specific documentation: %s', 'wpv-views' ),
						$group_data['content']['documentation']
					);
				}
				if ( ! empty( $desc_and_doc ) ) {
					$content .= '<p class="description">' . implode( '<br />', $desc_and_doc ) . '</p>';
				}
				$content .= '</div>';
			}
		}
        $content .= '</div>';
    }
    printf(
		'<ul>%s</ul>', 
		$tabs
	);
    echo $content;
	echo '</div>';
	echo '<div class="wpv-filter-toolset-messages js-wpv-filter-toolset-messages"></div>';
	echo '</div>';
    die();
}

/**
 * Add user shortode definition.
 *
 * Add user shortcode definition to white list of shortcodes.
 *
 * @since 1.9.0
 *
 * @param array $views_shortcodes see at wpv_filter_wpv_shortcodes_gui_api 
 * definition to explain.
 *
 */

// The wpv-user does not take optional attributes, just the mandatory and already added one, so we skip this
/*
function wpv_register_user($views_shortcodes)
{
    $views_shortcodes['wpv-user'] = array(
        'name' => __( 'Show user data', 'wpv-views' ),
        'label' => __( 'User data', 'wpv-views' ),
        'attributes' => array(
            'display-options' => array(
                'label' => __('Display options', 'wpv-views'),
                'header' => __('Display options for this field:', 'wpv-views'),
                'fields' => array(
                    'output' => array(
                        'label' => __( 'Output', 'wpv-views'),
                        'type' => 'radio',
                        'options' => array(
                            'display_name' => __('display name', 'wpv-views'),
                            'ID' => __('ID', 'wpv-views'),
                            'nickname' => __('nickname', 'wpv-views'),
                            'spam' => __('spam', 'wpv-views'),
                            'user_email' => __('email', 'wpv-views'),
                            'user_firstname' => __('firstname', 'wpv-views'),
                            'user_lastname' => __('lastname', 'wpv-views'),
                            'user_login' => __('login', 'wpv-views'),
                            'user_registered' => __('registered', 'wpv-views'),
                            'user_status' => __('status', 'wpv-views'),
                            'user_url' => __('URL', 'wpv-views'),
                        ),
                        'default' => 'display_name',
                    ),
                ),
            ),
        ),
    );
    return $views_shortcodes;
}
*/

// -------------------------------
// Suggest callbacks
// -------------------------------

add_action('wp_ajax_wpv_suggest_wpv_post_body_view_template', 'wpv_suggest_wpv_post_body_view_template');
add_action('wp_ajax_nopriv_wpv_suggest_wpv_post_body_view_template', 'wpv_suggest_wpv_post_body_view_template');

function wpv_suggest_wpv_post_body_view_template() {
	global $wpdb, $sitepress;
	$values_to_prepare = array();
	$wpml_join = $wpml_where = "";
	if (
		isset( $sitepress ) 
		&& function_exists( 'icl_object_id' )
	) {
		$content_templates_translatable = $sitepress->is_translated_post_type( 'view-template' );
		if ( $content_templates_translatable ) {
			$wpml_current_language = $sitepress->get_current_language();
			$wpml_join = " JOIN {$wpdb->prefix}icl_translations t ";
			$wpml_where = " AND p.ID = t.element_id AND t.language_code = %s ";
			$values_to_prepare[] = $wpml_current_language;
		}
	}
	
	$exclude_loop_templates = '';
	$exclude_loop_templates_ids = $wpdb->get_col( 
		"SELECT meta_value FROM {$wpdb->postmeta} 
		WHERE meta_key='_view_loop_template'" 
	);
	// Be sure not to include the current CT when editing one
	if ( isset( $_REQUEST['wpv_suggest_wpv_post_body_view_template_exclude'] ) ) {
		$exclude_loop_templates_ids[] = $_REQUEST['wpv_suggest_wpv_post_body_view_template_exclude'];
	}
	if ( count( $exclude_loop_templates_ids ) > 0 ) {
		$exclude_loop_templates_ids_sanitized = array_map( 'esc_attr', $exclude_loop_templates_ids );
		$exclude_loop_templates_ids_sanitized = array_map( 'trim', $exclude_loop_templates_ids_sanitized );
		// is_numeric + intval does sanitization
		$exclude_loop_templates_ids_sanitized = array_filter( $exclude_loop_templates_ids_sanitized, 'is_numeric' );
		$exclude_loop_templates_ids_sanitized = array_map( 'intval', $exclude_loop_templates_ids_sanitized );
		if ( count( $exclude_loop_templates_ids_sanitized ) > 0 ) {
			$exclude_loop_templates = " AND p.ID NOT IN ('" . implode( "','" , $exclude_loop_templates_ids_sanitized ) . "') ";
		}
	}
	$values_to_prepare[] = 'view-template';
	$values_to_prepare[] = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$view_tempates_available = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_name, p.post_title 
			FROM {$wpdb->posts} p {$wpml_join} 
			WHERE p.post_status = 'publish' 
			{$wpml_where} 
			AND p.post_type = %s 
			AND p.post_title LIKE %s
			{$exclude_loop_templates}
			ORDER BY p.post_title 
			LIMIT 5",
			$values_to_prepare
		)
	);
	foreach ( $view_tempates_available as $row ) {
		echo $row->post_title . "\n";
	}
	die();
}


add_action('wp_ajax_wpv_suggest_wpv_post_field_name', 'wpv_suggest_wpv_post_field_name');
add_action('wp_ajax_nopriv_wpv_suggest_wpv_post_field_name', 'wpv_suggest_wpv_post_field_name');

function wpv_suggest_wpv_post_field_name() {
	global $wpdb;
	$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$cf_keys = $wpdb->get_col( 
		$wpdb->prepare(
			"SELECT DISTINCT meta_key
			FROM {$wpdb->postmeta}
			WHERE meta_key LIKE %s
			ORDER BY meta_key
			LIMIT 5",
			$meta_key_q 
		) 
	);
	foreach ( $cf_keys as $key ) {
		echo $key . "\n";
	}
	die();
}

/**
* wpv_suggest_form_targets
*
* Suggest for WPML string shortcode context, from a suggest callback
*
* @since 1.4
*/

add_action('wp_ajax_wpv_suggest_form_targets', 'wpv_suggest_form_targets');
add_action('wp_ajax_nopriv_wpv_suggest_form_targets', 'wpv_suggest_form_targets');

function wpv_suggest_form_targets() {
	global $wpdb, $sitepress;
	$trans_join = '';
	$trans_where = '';
	$values_to_prepare = array();
	$title_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$values_to_prepare[] = $title_q;
	$exclude_post_type_slugs_where = '';
	$excluded_post_type_slugs = array();
	$excluded_post_type_slugs = apply_filters( 'wpv_admin_exclude_post_type_slugs', $excluded_post_type_slugs );
	if ( count( $excluded_post_type_slugs ) > 0 ) {
		$excluded_post_type_slugs_count = count( $excluded_post_type_slugs );
		$excluded_post_type_slugs_placeholders = array_fill( 0, $excluded_post_type_slugs_count, '%s' );
		$excluded_post_type_slugs_flat = implode( ",", $excluded_post_type_slugs_placeholders );
		foreach ( $excluded_post_type_slugs as $excluded_post_type_slugs_item ) {
			$values_to_prepare[] = $excluded_post_type_slugs_item;
		}
		$exclude_post_type_slugs_where = "AND post_type NOT IN ({$excluded_post_type_slugs_flat})";
	}
	if ( isset( $sitepress ) && function_exists( 'icl_object_id' ) ) {
		$current_lang_code = $sitepress->get_current_language();
		$trans_join = " JOIN {$wpdb->prefix}icl_translations t ";
		$trans_where = " AND ID = t.element_id AND t.language_code = %s ";
		$values_to_prepare[] = $current_lang_code;
	}
	$results = $wpdb->get_results( 
		$wpdb->prepare( "
            SELECT ID, post_title
            FROM {$wpdb->posts} {$trans_join}
            WHERE post_title LIKE '%s'
			{$exclude_post_type_slugs_where}
			AND post_status='publish' 
			{$trans_where}
            ORDER BY post_title ASC
			LIMIT 5",
			$values_to_prepare 
		) 
	);
	foreach ($results as $row) {
		echo $row->post_title . " [#" . $row->ID . "]\n";
	}
	die();
}

add_action( 'wp_ajax_wpv_create_form_target_page', 'wpv_create_form_target_page' );

function wpv_create_form_target_page() {
	if ( 
		current_user_can( 'publish_pages' )
		&& wp_verify_nonce( $_GET['_wpnonce'], 'wpv_create_form_target_page_nonce' ) 
	) {
		$target_page = array(
		  'post_title' => wp_strip_all_tags( $_GET['post_title'] ),
		  'post_status' => 'publish',
		  'post_type' => 'page'
		);
		$target_page_id = wp_insert_post( $target_page );
		$target_page_title = get_the_title( $target_page_id );
		$response = array(
			'result' => 'success',
			'page_title' => $target_page_title,
			'page_id' => $target_page_id
		);
		echo json_encode( $response );
	} else {
		$response = array(
			'result' => 'error',
			'error' => __( 'Security error', 'wpv-views' )
		);
		echo json_encode( $response );
	}
	die();
}
