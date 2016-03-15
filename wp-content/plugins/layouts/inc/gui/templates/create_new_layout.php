<?php

function display_available_parents_in_select($available_parents, $parent_id, $selected, $depth = 0) {
	global $wpddlayout;

	if (isset($available_parents[$parent_id])) {
		foreach ($available_parents[$parent_id] as $child_id) {
			$layout = $wpddlayout->get_layout_from_id($child_id);
			if ($layout->get_post_id() == $selected) {
				$is_selected = 'selected';
			} else {
				$is_selected = '';
			}
			?>
				<option <?php echo $is_selected; ?> value="<?php echo $layout->get_post_id(); ?>" data-child-width="<?php echo $layout->get_width_of_child_layout_cell(); ?>" ><?php echo ($depth ? '&nbsp;' : '') . str_repeat('-', $depth) . ($depth ? '&nbsp;' : '') . $layout->get_name(); ?></option>
			<?php
			display_available_parents_in_select($available_parents, $child_id, $selected, $depth + 1);
		}
	}
}

function ddl_display_post_title_in_creation_box()
{
    global $post;

    if( null === $post || ( is_object($post) && $post->post_type === WPDDL_LAYOUTS_POST_TYPE ) ) {
         return __("Assign this layout to a specific page", 'ddl-layouts');
    }
    elseif( is_object($post) )
    {
       return sprintf( __("Assign this layout to %s", 'ddl-layouts'), $post->post_title );
    }

}

global $wpddl_features, $wpddlayout;

// get the presets.
$preset_layouts = array();

$preset_dir = WPDDL_RES_ABSPATH . '/preset-layouts/';
$dir = opendir( $preset_dir );
while( ( $currentFile = readdir($dir) ) !== false )
{
	if ( $currentFile == '.' || $currentFile == '..' || $currentFile[0] == '.' )
	{
		continue;
	}

	$currentFile = $preset_dir . $currentFile;
	$layout = $wpddlayout->load_layout($currentFile);
	$preset_layouts[$layout['name']] = array('file' => $currentFile,
											 'layout' => $layout);
}
closedir($dir);
asort($preset_layouts);

?>


<script type="application/javascript">
	var ddl_create_layout_error = '<?php echo esc_js( __('Failed to create the layout.', 'ddl-layouts') ); ?>';
</script>

<div class="ddl-dialogs-container hidden"> <!-- The create a new layout popup -->

	<div class="ddl-dialog create-layout-form-dialog js-create-layout-form-dialog">
		<?php wp_nonce_field('wp_nonce_create_layout', 'wp_nonce_create_layout'); ?>
		<input class="js-layout-new-redirect" name="layout_creation_redirect" type="hidden" value="<?php echo admin_url( 'admin.php?page=dd_layouts_edit&amp;layout_id='); ?>" />
		<div class="ddl-dialog-header">
			<h2><?php _e('Add new layout','ddl-layouts') ?></h2>
			<i class="icon-remove js-new-layout-dialog-close"></i>
		</div>
		<div class="ddl-dialog-content">

			<ul class="ddl-form">

				<li>

					<?php $name = 'dd-layout-type';
						  require WPDDL_GUI_ABSPATH . 'templates/layout-layout-type-selector.box.tpl.php';
					?>
					<?php if ($wpddl_features->is_feature('fixed-layout')): ?>
						<p class="toolset-alert toolset-alert-info js-diabled-fixed-rows-info">
							<?php _e('Only fluid layouts are allowed because the parent layout is fluid.', 'ddl-layouts'); ?>
						</p>
					<?php endif; ?>
				</li>

				<li class="js-preset-layouts-items">
					<label for="dd-layout-preset"><?php _e('Preset layouts','ddl-layouts'); ?></label>
					<?php // Previews for layout presets ?>
					<ul class="presets-list fields-group">
						<?php $count = 0; ?>
						<?php foreach ($preset_layouts as $name => $details) : ?>
							<?php
								$file = $details['file'];
								$decoder = new WPDD_json2layout(true);
								$layout = $decoder->json_decode($details['layout'], true);
								$renderer = new WPDD_layout_preset_render($layout);
							?>
							<li class="js-presets-list-item <?php if ( $count === 0 ) : ?>selected<?php endif; ?>" <?php if ( $count === 0 ) : ?>data-selected="true"<?php endif; ?> data-file="<?php echo $file; ?>" data-width="<?php echo $layout->get_width(); ?>">
								<?php if ($layout->get_name() == 'Empty'): $count++; ?>
									<div class="row-fluid  row-count-1">
										<div class="span-preset12 empty">
											<?php _e('Empty layout','ddl-layouts'); ?>
										</div>
									</div>
								<?php else: ?>
									<?php echo $renderer->render_to_html(); $count++; ?>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>

				</li>

				<li>
					<fieldset>
						<legend><?php _e('Layout assignment','ddl-layouts'); ?></legend>
						<div class="fields-group">

							<ul>
								<li>
									<div class="checkbox">
										<p class="no-top-margin four-bottom-margin"><label for"js-dont-assign-to"><input type="radio" id="js-dont-assign-to" class="js-dont-assign-post-type" checked name="ddl-new-layout-post-type-do-not-assign" value="0"> <?php _e("Don't assign this layout to content", 'ddl-layouts'); ?></label></p>
                                        <p class="no-top-margin four-bottom-margin"><label for"js-assign-to"><input type="radio" id="js-assign-to" class="js-dont-assign-post-type" name="ddl-new-layout-post-type-do-not-assign" value="1">  <?php _e("Use this layout as a template...", 'ddl-layouts'); ?></label></p>
                                        <div class="js-ddl-post-types-dropdown-list ddl-post-types-dropdown-list hidden">

                                            <?php do_action('print_post_types_checkboxes_in_dialog'); ?>
                                        </div>
                                        <p class="no-top-margin no-bottom-margin"><label for"js-assign-only-to"> <input type="radio" id="js-assign-only-to" class="js-dont-assign-post-type" name="ddl-new-layout-post-type-do-not-assign" value="2">  <?php echo ddl_display_post_title_in_creation_box(); ?></label></p>
                                        <?php do_action('ddl-create-layout-from-page-extra-fields'); ?>
                                    </div>
								</li>

								<li>
									<p class="hidden">
										<label class="js-ddl-for-post-types-open ddl-for-post-types-open" title="Click to toggle"><?php _e('Post types:','ddl-layouts'); ?> <i class="icon-caret-down"></i></label>
									</p>

                                    <div class="js-ddl-for-post-types-messages ddl-for-post-types-messages hidden">
                                        <?php printf(__('To assign this layout to a specific page, first create the layout and then assign it using the "Change how this layout is used" button in the layout editor.', 'ddl-layouts'), ''); ?>
                                    </div>


								</li>
							</ul>

						</div>
					</fieldset>
				</li>
				<?php
				$parents = WPDD_Layouts::get_available_parents();
				$hidden_parent_section = '';
				if ( count($parents) == 0 ){
					$hidden_parent_section = ' hidden';	
				}
				$default_parent = $wpddlayout->parents_options->get_options( WPDD_Layouts::PARENTS_OPTIONS );
				?>
				<li class="js-set-parent-layout-row<?php echo $hidden_parent_section?>">
					<fieldset>
						<legend><?php _e('Parent layout','ddl-layouts'); ?></legend>
						<div class="fields-group">
							<ul class="js-set-parent-layout-fieldset">
								<li>
									<label class="set-parent-layout-select">
										<select class="js-new-layout-parent js-layouts-list" name="new-layout-parent" class="select" data-show-group="js-set-parent-layout-row">
											<option value=""><?php _e("None", 'ddl-layouts'); ?></option>
											<?php
												for ( $i=0,$total_parents=count($parents); $i<$total_parents; $i++){
													$selected = '';
													if ( $parents[$i]->ID == $default_parent ){
														$selected = ' selected';	
													}
													echo '<option value="'.$parents[$i]->ID.'"'.$selected.'>'.$parents[$i]->post_title.'</option>';	
												}
											?>
										</select>										
									</label>
									<label class="checkbox set-parent-layout-checkbox">
										<?php
											$default_parent_label = __("Make this layout the default parent", 'ddl-layouts');
											$no_parent_label = __("Don't have any layout as the default parent", 'ddl-layouts')
										?>
										<input type="checkbox" class="js-make-this-default-parent" name="ddl-new-layout-make-this-default-parent" value="0"
											data-default-text="<?php echo esc_attr($default_parent_label); ?>"
											data-no-parent-text="<?php echo esc_attr($no_parent_label); ?>" />
										<span class="js-make-this-default-parent-label"><?php echo $default_parent_label; ?></span>
									</label>
								</li>
							</ul>
						</div>
					</fieldset>
				</li>				
				<li>
					<label for="layout_new_name"><?php _e('Name this layout','ddl-layouts'); ?></label>
					<input type="text" name="layout_new_name" id="layout_new_name" class="js-new-layout-title" placeholder="<?php echo htmlentities( __('Enter title here', 'ddl-layouts'), ENT_QUOTES ); ?>" data-highlight="<?php echo htmlentities( __('Now give this View a name', 'ddl-layouts'), ENT_QUOTES ); ?>" />
				</li>

			</ul>

			<div class="js-error-container js-ddl-message-container"></div>

		</div> <!-- .ddl-dialog-content -->

		<div class="ddl-dialog-footer">
			<?php wp_nonce_field('wp_nonce_create_layout', 'wp_nonce_create_layout'); ?>
			<button class="button js-new-layout-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
			<button class="button button-primary js-create-new-layout"><?php _e('Create layout','ddl-layouts') ?></button>
		</div>

	</div> <!-- .create-layout-form-dialog -->
</div>

<?php if ( isset( $_GET['new_layout'] ) && $_GET['new_layout'] == 'true'): ?>

	<script type="application/javascript">
		var ddl_layouts_create_new_layout_trigger = true;
	</script>

<?php endif; ?>

<script type="text/html" id="js-ddl-create-layout-for-post-types-selection">
    <div class="ddl-dialog-header">
        <h2><?php _e('New layout for:', 'ddl-layouts');?></h2>
        <i class="icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
		<p><input type="radio" name="create_layout_for_post_type" id="create_layout_for_post_type_one" value="one" checked /><label for="create_layout_for_post_type_one"><?php printf(__('Just for %s', 'ddl-layouts'), '{{{ post_title }}}'); ?></label></p>
        <p><input type="radio" name="create_layout_for_post_type" id="create_layout_for_post_type_all" value="all" /><label for="create_layout_for_post_type_all"><?php printf(__('Template for all %s', 'ddl-layouts'), '{{{ post_type_label }}}'); ?></label></p>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button button-primary js-ddl-continue-to-layout-creation ddl-continue-to-layout-creation">
				<?php _e('Continue', 'ddl-layouts'); ?></button>
        <button class="button js-edit-dialog-close close-change-use"><?php _e('Cancel', 'ddl-layouts'); ?></button>

    </div>

</script>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-ddl-create-layout-for-post-types-selection-wrap"></div>
</div>