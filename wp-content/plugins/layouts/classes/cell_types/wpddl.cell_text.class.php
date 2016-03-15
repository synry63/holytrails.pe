<?php

class WPDD_layout_cell_text extends WPDD_layout_cell {

	function __construct($name, $width, $css_class_name = '', $content = null, $css_id, $tag, $unique_id) {
		parent::__construct($name, $width, $css_class_name, 'cell-text-template', $content, $css_id, $tag, $unique_id);

		$this->set_cell_type('cell-text');
	}

	function frontend_render_cell_content($target) {
		$content = $this->get('content');
		
		$content = apply_filters('wpml_translate_string', $content, $this->get_unique_id() . '_content', $target->get_context());
		
		if ($this->get('responsive_images')) {
			// stript hieght="xx" and width="xx" from images.
			$regex = '/<img[^>]*?(width="[^"]*")/siU';
			if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $val) {
					$found = str_replace($val[1], '', $val[0]);
					$content = str_replace($val[0], $found, $content);
				}
			}
			$regex = '/<img[^>]*?(height="[^"]*")/siU';
			if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $val) {
					$found = str_replace($val[1], '', $val[0]);
					$content = str_replace($val[0], $found, $content);
				}
			}

			// Process the caption shortcode
			$regex = '/\[caption.*?\[\/caption\]/siU';
			if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $val) {
					$shortcode = $val[0];
					$result = do_shortcode($shortcode);

					// set the generated div to 100% width
					$regex = '/<div[^>]*?width:([^"^;]*?)/siU';
					if(preg_match_all($regex, $result, $new_matches, PREG_SET_ORDER)) {
						foreach ($new_matches as $val) {
							$found = str_replace($val[1], '100%', $val[0]);
							$result = str_replace($val[0], $found, $result);
						}
					}
					$content = str_replace($shortcode, $result, $content);
				}

			}

			$content = $target->make_images_responsive($content);

		}
		
		if ($this->get('disable_auto_p') && has_filter('the_content', 'wpautop')) {
			remove_filter('the_content', 'wpautop');
			$content = apply_filters( 'the_content', $content );
			add_filter('the_content', 'wpautop');
		} else {
			$content = apply_filters( 'the_content', $content );
		}

		$content = do_shortcode( $content );
		$target->cell_content_callback($content, $this);
	}
	
	function register_strings_for_translation ($context) {
		$unique_id = $this->get_unique_id();
		
		if ($unique_id) {
			$content = $this->get('content');

            if( empty($content) ) return;

			do_action('wpml_register_string',
						  $content,
						  $unique_id . '_content',
						  $context,
						  $this->get_name() . ' - ' . 'Content',
						  'VISUAL');
		}
	}


}

class WPDD_layout_cell_text_factory extends WPDD_layout_cell_factory{
	
	public function __construct() {
		add_filter('gform_display_add_form_button', array($this, 'add_gf_support'));
	}
	
	public function add_gf_support($value) {
		if (isset($_GET['page']) && $_GET['page'] == 'dd_layouts_edit') {
			$value = true;
		}
		return $value;
	}

	public function build($name, $width, $css_class_name = '', $content = null, $css_id, $tag, $unique_id) {
		return new WPDD_layout_cell_text($name, $width, $css_class_name, $content, $css_id, $tag, $unique_id);
	}

	public function get_cell_info($template) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'rich-content.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'visual-editor_expand-image.png';
		$template['name'] = __('Visual editor (text, images, HTML)', 'ddl-layouts');
		$template['description'] = __('Display static text, images and any other media that you can include using the WordPress visual editor.
', 'ddl-layouts');
		$template['button-text'] = __('Assign Visual editor (text, images, HTML) Cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create a Visual editor (text, images, HTML) Cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Visual editor (text, images, HTML) Cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
        $template['has_settings'] = true;
        $template['category'] = __('Text and media', 'ddl-layouts');
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
			<div class="cell-content clearfix">

				<p class="cell-name">{{ name }} &ndash; <?php _e('Visual editor Cell', 'ddl-layouts'); ?></p>

				<# if( content.content ){ #>
					<div class="cell-preview">
						<#
						var preview = content.content;
						if (typeof content.disable_auto_p != 'undefined' && !content.disable_auto_p) {
							// display the content with auto paragraphs
							preview = window.switchEditors.wpautop(preview);
						}
                            preview = DDL_Helper.sanitizeHelper.strip_srcset_attr(preview);
						preview = DDL_Helper.sanitizeHelper.stringToDom( preview );
						print( DDL_Helper.sanitizeHelper.transform_caption_shortcode(preview.innerHTML) );
						#>
					</div>
				<# } #>
			</div>
		<?php
		return ob_get_clean();
	}

	public function enqueue_editor_scripts() {
		wp_enqueue_script('page');
		wp_enqueue_script('editor');
		add_thickbox();
		wp_enqueue_script('media-upload');
		wp_enqueue_script('word-count');
        if( WPDD_Layouts::views_available() ){
            $deps = array('jquery', 'views-shortcodes-gui-script');
        } else {
            $deps = array('jquery');
        }
		wp_register_script('text_cell_js', WPDDL_RELPATH . '/inc/gui/editor/js/text-cell.js', $deps, WPDDL_VERSION, true);
		wp_enqueue_script('text_cell_js');
    }
	
	private function _dialog_template() {
		add_filter('user_can_richedit', array(__CLASS__, '__true'), 100);
		ob_start();

		?>
			<div class="ddl-form">

				<div class="ddl-form-item">
					<fieldset>
						<p class="fields-group">
							<label class="checkbox" for="<?php the_ddl_name_attr('responsive_images'); ?>">
								<input type="checkbox" name="<?php the_ddl_name_attr('responsive_images'); ?>" id="<?php the_ddl_name_attr('responsive_images'); ?>">
								<?php _e('Display images with responsive size', 'ddl-layouts'); ?>
							</label>
						</p>
					</fieldset>
				</div>

				<div class="ddl-form-item">
					<fieldset>
						<p class="fields-group">
							<label class="checkbox" for="<?php the_ddl_name_attr('disable_auto_p'); ?>">
								<input type="checkbox" name="<?php the_ddl_name_attr('disable_auto_p'); ?>" id="<?php the_ddl_name_attr('disable_auto_p'); ?>">
								<?php _e('Disable automatic paragraphs', 'ddl-layouts'); ?>
							</label>
						</p>
						
					</fieldset>
				</div>
				
				<div class="js-visual-editor-views-shortcode-notification"
					 data-view="<?php esc_attr_e("It looks like you are trying to display a View. For your convenience, Layouts now comes with a View cell, which will let you achieve this much easier. We suggest that you try the new 'Views Content Grid' cell. You will be able to insert an existing View or create a new View.", 'ddl-layouts');?>"
					 data-content-template="<?php esc_attr_e("It looks like you are trying to display fields. For your convenience, Layouts now comes with a Content Template cell, which will let you achieve this much easier.", 'ddl-layouts');?>" 
					 data-cred="<?php esc_attr_e("It looks like you are trying to display a Form. For your convenience, Layouts now comes with a CRED form cell, which will let you achieve this much easier. We suggest that you try the new 'CRED Form' cell. You will be able to insert an existing Form or create a new Form.", 'ddl-layouts');?>">
				</div>
			</div>

            <div id="visual-editor-editor-container" class="js-visual-editor-editor-container ddl-form">
                <div id="js-visual-editor-tinymce">
                    <?php
                    $this->do_tinymce();
                    ?>
                </div>
                <div id="js-visual-editor-codemirror">
                    <?php
                    $this->do_codemirror();
                    ?>
                </div>
                <div id="visual-editor-editor-switch-message"></div>
                <p id="visual-editor-editor-switch-container">
                    <a class="js-visual-editor-toggle" data-editor="tinymce"><?php esc_attr_e( 'Switch to the WordPress Visual Editor', 'ddl-layouts' ); ?></a>
                    <a class="js-visual-editor-toggle" data-editor="codemirror"><?php esc_attr_e( 'Switch to HTML editing', 'ddl-layouts' ); ?></a>
                    <input type="hidden" id="preferred_editor" value="<?php $ddl_preferred_editor = get_user_option( 'ddl_preferred_editor', get_current_user_id() ); echo esc_attr( $ddl_preferred_editor !== false ? $ddl_preferred_editor : 'tinymce' ); ?>">
                </p>
            </div>

			<div class="ddl-form">
				<div class="ddl-form-item">
					<br />
					<?php ddl_add_help_link_to_dialog(WPDLL_RICH_CONTENT_CELL,
													  __('Learn about the Visual editor cell', 'ddl-layouts'));
					?>
				</div>
			</div>
		<?php
		
		return ob_get_clean();
	}
    
    function configure_tinymce_editor( $in ) {

        $in['add_unload_trigger'] = false;
        $in['entities'] = '34,quot,39,apos'; // Unaffected; Special cases
        $in['entity_encoding'] = 'raw';
        $in['forced_root_block'] = 'p';
        $in['mode'] = 'exact';
        $in['protect'] = '[ /\r\n/g ]'; // Avoid joining lines when switching to Visual mode
        $in['remove_linebreaks'] = false;
        $in['remove_trailing_brs'] = false;
        $in['resize'] = false;
        $in['wpautop'] = true; /* Also as wp_editor param */
        
        return $in;
    }
    
    function do_tinymce() {
        
        $options = array(
            // See text-cell.js for editor height too
            'editor_height' => 300,
            'dfw' => false,
            'drag_drop_upload' => true,
            'tabfocus_elements' => 'insert-media-button,save-post',
            'textarea_name' => $this->element_name( 'content' ),
            'wpautop' => true, /* Also as TinyMCE setting */
        );
        add_filter( 'tiny_mce_before_init', array( $this, 'configure_tinymce_editor' ), 999 );

        wp_editor( '', 'celltexteditor', $options );
        remove_filter( 'user_can_richedit', array( __CLASS__, '__true' ), 100 );
        
    }

    function do_codemirror() {
        ?>
        <div class="code-editor-toolbar js-code-editor-toolbar">
           <ul class="js-wpv-v-icon">
                <li>
                    <button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="" data-content="visual-editor-html-editor">
                        <i class="icon-picture"></i>
                        <span class="button-label"><?php _e('Media','ddl-layouts'); ?></span>
                    </button>
                </li>
                <?php
                do_action( 'wpv_views_fields_button', 'visual-editor-html-editor' );
             //   do_action( 'wpv_cred_forms_button', 'visual-editor-html-editor' );
                ?>
           </ul>
        </div>
        <textarea name="name" rows="10" class="js-visual-editor-html-editor-textarea" data-id="" id="visual-editor-html-editor"></textarea>
        <?php
    }
    
    // auxiliary functions
	public static function __true()
	{
		return true;
	}

	public static function __false()
	{
		return false;
	}


}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_text_factory');
function dd_layouts_register_cell_text_factory($factories) {
	$factories['cell-text'] = new WPDD_layout_cell_text_factory;
	return $factories;
}
