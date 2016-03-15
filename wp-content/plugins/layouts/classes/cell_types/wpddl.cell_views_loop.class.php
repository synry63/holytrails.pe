<?php
class WPDD_layout_loop_views_cell extends WPDD_layout_cell {

	function __construct($name, $width, $css_class_name = '', $content = null, $css_id, $tag) {
		parent::__construct($name, $width, $css_class_name, 'post-loop-views-cell', $content, $css_id, $tag);
		$this->set_cell_type('post-loop-views-cell');
	}

	function frontend_render_cell_content($target) {
		
		global $ddl_fields_api;
		$ddl_fields_api->set_current_cell_content($this->get_content());
		
        if( function_exists('render_view') )
        {
            global $WPV_view_archive_loop, $wp_query;
			$WPV_view_archive_loop->query = clone $wp_query;
			$WPV_view_archive_loop->in_the_loop = true;
			$target->cell_content_callback( render_view( array( 'id' => get_ddl_field('ddl_layout_view_id') ) ), $this );
			$WPV_view_archive_loop->in_the_loop = false;
        }
        else
        {
            $target->cell_content_callback( WPDDL_Messages::views_missing_message(), $this );
        }

	}
}

class WPDD_layout_loop_views_cell_factory extends WPDD_layout_cell_factory{

	public function build($name, $width, $css_class_name = '', $content = null, $css_id, $tag) {
		return new WPDD_layout_loop_views_cell($name, $width, $css_class_name, $content, $css_id, $tag);
	}

	public function get_cell_info($template) {
		//$template['icon-url'] = WPDDL_RES_RELPATH .'/images/views-icon-color_16X16.png';
		//	$template['preview-image-url'] = WPDDL_RES_RELPATH . '/images/child-layout.png';
        $template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'views-post-loop.svg';
		$template['name'] = __('WordPress Archive (post archives, blog, search, ...)', 'ddl-layouts');
		$template['description'] = __('Display the WordPress ‘loop’ with your styling. You need to include this cell in layouts used for the blog, archives, search and other pages that display WordPress content lists.', 'ddl-layouts');
		$template['button-text'] = __('Assign WordPress Archive cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create a WordPress Archive cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit WordPress Archive cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['allow-multiple'] = false;
		$template['cell-class'] = 'post-loop-views-cell';
		$template['category'] = __('Content display', 'ddl-layouts');
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'views-post-loop_expand-image.png';
        $template['has_settings'] = false;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start(); ?>
		<div class="cell-content">
			<p class="cell-name">{{ name }}</p>
			<div class="cell-preview">
				<#
					if (content) {
						var preview = DDLayout.views_preview.get_preview( name,
											content,
											'<?php _e('Updating', 'ddl-layouts'); ?>...',
											'<?php _e('Loading', 'ddl-layouts'); ?>...',
											'<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/views-post-loop-preview.svg'; ?>'
											);
						print( preview );
					}
				#>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}


	private function _dialog_template() {
		global $WPV_templates, $WP_Views;

		$output = views_content_grid_dialog_template_callback();
		
		// Fix the help link for Views Post Loop cell
		$output = str_replace(WPDLL_VIEWS_CONTENT_GRID_CELL, WPDLL_VIEWS_LOOP_CELL, $output);
		
		$output = str_replace(__('Learn about the Views Content Grid cell', 'ddl-layouts'),
							  __('Learn about the Views Post Loop cell', 'ddl-layouts'),
							  $output);

		return $output;
	}

	public function enqueue_editor_scripts() {
	}
}

