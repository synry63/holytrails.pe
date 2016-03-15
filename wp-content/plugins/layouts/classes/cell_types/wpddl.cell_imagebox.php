<?php
/*
 * Image-box cell type.
 * Bootstrap thumbnail component that displays box with image, header and text. Suitable for callout boxes, key features, services showcase etc.
 *
 */

if ( ! function_exists('register_imagebox_cell_init') )
{
	function register_imagebox_cell_init() {
		if ( function_exists('register_dd_layout_cell_type') ) {
			register_dd_layout_cell_type ('imagebox-cell',
				array(
					'name'					   => __('Image box', 'ddl-layouts'),
					'cell-image-url'		   => DDL_ICONS_SVG_REL_PATH.'layouts-imagebox-cell.svg',
					'description'			   => __('Display an image with a header and text. Suitable for callout boxes, key features, services showcase etc. Use the Visual Editor cell if you need more flexibility.', 'ddl-layouts'),
					'category'				   => __('Text and media', 'ddl-layouts'),
					'button-text'			   => __('Assign imagebox cell', 'ddl-layouts'),
					'dialog-title-create'	   => __('Create a new imagebox cell', 'ddl-layouts'),
					'dialog-title-edit'		   => __('Edit imagebox cell', 'ddl-layouts'),
					'dialog-template-callback' => 'imagebox_cell_dialog_template_callback',
					'cell-content-callback'	   => 'imagebox_cell_content_callback',
					'cell-template-callback'   => 'imagebox_cell_template_callback',
                    'has_settings' => true,
					'preview-image-url'		   => DDL_ICONS_PNG_REL_PATH . 'image-box_expand-image.png',
					'translatable_fields'	   => array(
														'box_title' => array('title' => 'Image Box Title', 'type' => 'LINE'),
														'box_content' => array('title' => 'Image Box Content', 'type' => 'AREA')
													   ),
				)
			);
		}
	}
	add_action( 'init', 'register_imagebox_cell_init' );


	function imagebox_cell_dialog_template_callback() {
		ob_start();
		?>

		<ul class="ddl-form">
			<li class="js-ddl-media-field">
				<label for="<?php the_ddl_name_attr('box_image'); ?>"><?php _e('Image URL', 'ddl-layouts') ?>:</label>
				<input type="text" class="js-ddl-media-url" name="<?php the_ddl_name_attr('box_image'); ?>" />
				<div class="ddl-form-button-wrap">
					<button class="button js-ddl-add-media"
							data-uploader-title="<?php _e('Choose an image', 'ddl-layouts') ?>"
							data-uploader-button-text="<?php _e('Insert image URL', 'ddl-layouts') ?>">
							<?php _e('Choose an image', 'ddl-layouts') ?>
					</button>
                    
				</div>
			</li>
            <li class="ddl-form-button-wrap">
				<label for="<?php the_ddl_name_attr('disable_bootstrap_thumbnail'); ?>"></label>
				<?php _e('Disable Bootstrap "thumbnail"', 'ddl-layouts') ?> <input type="checkbox" name="<?php the_ddl_name_attr('disable_bootstrap_thumbnail'); ?>"> 
			</li>  
            <li>
				<label for="<?php the_ddl_name_attr('box_title'); ?>"><?php _e('Caption title', 'ddl-layouts') ?>:</label>
				<input type="text" name="<?php the_ddl_name_attr('box_title'); ?>">
			</li>			
			<li>
				<label for="<?php the_ddl_name_attr('box_content'); ?>"><?php _e('Caption description', 'ddl-layouts') ?>:</label>
				<textarea name="<?php the_ddl_name_attr('box_content'); ?>" rows="4"></textarea>
			</li>
                      
		</ul>
		<?php
		return ob_get_clean();
	}


	// Callback function for displaying the cell in the editor.
	function imagebox_cell_template_callback() {
		ob_start();
		?>
			<div class="cell-content">

				<p class="cell-name"><?php _e('Image box', 'ddl-layouts'); ?> - {{ name }}</p>
				<div class="cell-preview">
	                <div class="ddl-image-box-preview">
						<img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/image-box.svg'; ?>" height="130px">
					</div>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}


	// Callback function for display the cell in the front end.
	function imagebox_cell_content_callback() {
		ob_start();
		$title = get_ddl_field('box_title');
		$content = get_ddl_field('box_content');
		?>

		<?php if ( !get_ddl_field('disable_bootstrap_thumbnail') ){?>
        <div class="thumbnail">
        <?php }?>
			<img src="<?php the_ddl_field('box_image'); ?>">
			<?php if ($title || $content): ?>
				<div class="caption text-center">
					<?php if ($title): ?>
						<h3>
							<?php echo $title; ?>
						</h3>
					<?php endif; ?>
					<?php if ($content): ?>
						<p>
							<?php echo $content ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php if ( !get_ddl_field('disable_bootstrap_thumbnail') ){?>
        </div>
        <?php }?>
		<?php
		return ob_get_clean();
	}
}