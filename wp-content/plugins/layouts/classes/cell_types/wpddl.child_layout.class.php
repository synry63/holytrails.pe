<?php

class WPDD_layout_cell_child_layout extends WPDD_layout_cell {

	function __construct($name, $width, $css_class_name = '', $content = null, $css_id, $tag) {
		parent::__construct($name, $width, $css_class_name, 'child-layout', $content, $css_id, $tag);

		$this->set_cell_type('child-layout');
	}

	function frontend_render_cell_content($target) {

		global $wpddlayout;
		
		if ($target->has_child_renderer()) {
			$target->cell_content_callback($target->render_child(), $this);
		} else {
			$layout_id = $wpddlayout->get_rendered_layout_id();
			$layout = $wpddlayout->get_layout_from_id($layout_id);
			$children = $layout->get_children();
			
			ob_start();
			
			?>
				<div class="toolset-alert toolset-alert-error">
					<p><strong><?php _e('A child layout should display here', 'ddl-layouts'); ?></strong></p>
					<?php if (count($children)): ?>
						<p><?php echo sprintf(__('Instead of using the parent layout (%s), you should assign one of these child layouts to content:', 'ddl-layouts'), $layout->get_name()); ?></p>
						<ul>
						<?php
							foreach ($children as $child_id) {
								$child_layout = $wpddlayout->get_layout_from_id($child_id);
                                if( null === $child_layout ){
                                    continue;
                                } else { ?>
                                    <li><?php echo $child_layout->get_name(); ?></li>
                              <?php  }

							}
						?>
						</ul>
						<p><?php _e('Or, you can create new child layouts for other pages.', 'ddl-layouts'); ?></p>
					<?php else: ?>
						<p><?php echo sprintf(__('You should create child layouts that fit into this space and then assign them to content. The parent layout (%s) should not be assigned to content, but only its children layouts.', 'ddl-layouts'), $layout->get_name()); ?></p> 
						<p><?php _e('If you did not intend to create multiple child layouts, you can simply delete the Child Layout cell. A Grid cell can be used to split a cell into several rows and columns.', 'ddl-layouts'); ?></p>
					<?php endif; ?>

					<p><?php ddl_add_help_link_to_dialog(WPDLL_CHILD_LAYOUT_CELL, __('Learn about designing hierarchical layouts using parents and children layouts.', 'ddl-layouts')); ?></p>
				</div>
			<?php
				
			$target->cell_content_callback(ob_get_clean(), $this);
		}
	}

	function get_width_of_child_layout_cell() {
		return $this->get_width();
	}

}

class WPDD_layout_cell_child_layout_factory extends WPDD_layout_cell_factory{


	public function build($name, $width, $css_class_name = '', $content = null, $css_id, $tag) {
		return new WPDD_layout_cell_child_layout($name, $width, $css_class_name, $content, $css_id, $tag);
	}

	public function get_cell_info($template) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'child-layout-cell.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'child-layout_expand-image2.png';
		$template['name'] = __('Child layout (Hierarchical layouts tree)', 'ddl-layouts');
		$template['description'] = __('Insert a placeholder for a Child Layout. Use this cell to design hierarchical layouts, where different child layouts inherit page-elements from a parent layout.', 'ddl-layouts');
		$template['button-text'] = __('Assign Child layout', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create a Child layout Cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Child layout Cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['allow-multiple'] = false;
		$template['category'] = __('Layout structure', 'ddl-layouts');
        $template['has_settings'] = false;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
			<div class="cell-content">
				<p class="cell-name">{{{ name }}}</p>
				<div class="cell-preview">
	                <div class="ddl-child-layout-preview">
						<img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/child-layout-preview.svg'; ?>" height="130px">
					</div>
				</div>
			</div>
			</div>
		<?php
		return ob_get_clean();
	}


	private function _dialog_template() {
		global $wpdb, $wpddlayout;
		ob_start();
		?>

				<?php if (isset($_GET['layout_id'])) {
					$layout = $wpddlayout->get_layout_settings($_GET['layout_id'], true);

					if ($layout && isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {

						?>
						<ul class="tree js-child-layout-list">
							 <li class="js-tree-category js-tree-category-title">
								<h3 class="tree-category-title">
									<?php _e( 'Select Child layout for editing', 'ddl-layouts' ); ?>
								</h3>

								<ul class="js-tree-category-items">

									<?php

										$layout_slug = $layout->slug;

										$post_ids =  $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE));
										foreach ($post_ids as $post_id) {
											$layout = $wpddlayout->get_layout_settings($post_id, true);
											if ( is_object($layout) && property_exists($layout, 'parent') && $layout->parent == $layout_slug ) {
												?>

												<li class="js-tree-category-item">
													<p class="item-name-wrap js-item-name-wrap">
														<a href="#" class="js-switch-to-layout" data-layout-id="<?php echo $post_id; ?>">
															<span class="js-item-name js-child-layout-item"><?php echo $layout->name; ?></span>
														</a>
													</p>
												</li> <!-- .js-tree-category-item -->

												<?php
											}

										}
									?>

									<?php // ( while ( has_child_ddl_layouts() ) : the_child_layout(); ) ?>
								</ul> <!-- . js-tree-category-items -->
							</li>
						</ul> <!-- .js-tree-category-items -->
						<?php
						}
					}?>

                    <?php if( $wpddlayout->is_embedded() === false ) : ?>
					<input type="button" class="button js-create-new-child-layout" data-url="<?php echo admin_url().'admin.php?page=dd_layouts&new_layout=true'; ?>" value="<?php _e('Create a new child layout', 'ddl-layouts'); ?>">
					<?php endif; ?>

					<?php ddl_add_help_link_to_dialog(WPDLL_CHILD_LAYOUT_CELL,
												  __('Learn about the Child layout cell', 'ddl-layouts'));
					?>
					

		<?php
		return ob_get_clean();
	}

	public function enqueue_editor_scripts() {
		wp_register_script( 'wp-child-layout-editor', ( WPDDL_GUI_RELPATH . "editor/js/child-cell.js" ), array('jquery'), null, true );
		wp_enqueue_script( 'wp-child-layout-editor' );
	}


}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_child_layout_factory');
function dd_layouts_register_cell_child_layout_factory($factories) {
	$factories['child-layout'] = new WPDD_layout_cell_child_layout_factory;
	return $factories;
}

