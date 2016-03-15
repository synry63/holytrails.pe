<?php
/*
 * YouTube video cell type.
 * Displays YouTube video
 *
 */

if( !function_exists('register_video_cell_init') )
{
	function register_video_cell_init() {
		if ( function_exists('register_dd_layout_cell_type') ) {
			register_dd_layout_cell_type ( 'video-cell',
				array (
					'name'						=> __('YouTube video', 'ddl-layouts'),
					'description'				=> __('Display a video from YouTube.', 'ddl-layouts'),
					'category'					=> __('Text and media', 'ddl-layouts'),
					'cell-image-url'			=> DDL_ICONS_SVG_REL_PATH.'layouts-video-cell.svg',
					'button-text'				=> __('Assign YouTube video cell', 'ddl-layouts'),
					'dialog-title-create'		=> __('Create a new YouTube video cell', 'ddl-layouts'),
					'dialog-title-edit'			=> __('Edit YouTube video cell', 'ddl-layouts'),
					'dialog-template-callback'	=> 'video_cell_dialog_template_callback',
					'cell-content-callback'		=> 'video_cell_content_callback',
					'cell-template-callback'	=> 'video_cell_template_callback',
                    'has_settings' => true,
					'cell-class'				=> '',
					'preview-image-url'			=>  DDL_ICONS_PNG_REL_PATH  . 'youtube_expand-image.png',
                    'register-scripts'		   => array(
                        array( 'ddl-video-cell-script', WPDDL_GUI_RELPATH . 'editor/js/ddl-video-cell-script.js', array( 'jquery' ), WPDDL_VERSION, true ),
                    )
				)
			);
		}
	}
	add_action( 'init', 'register_video_cell_init' );


	function video_cell_dialog_template_callback() {
		ob_start();
		?>

		<div class="ddl-form">
			<p>
				<label for="<?php the_ddl_name_attr('video_url'); ?>"><?php _e( 'Video URL', 'ddl-layouts' ) ?>:</label>
				<input type="text" name="<?php the_ddl_name_attr('video_url'); ?>">
				<span class="desc"><?php _e( 'eg. https://www.youtube.com/watch?v=vHkRZ-70SRs', 'ddl-layouts' ) ?></span>
                <div class="js-video-message" id="js-video-message"></div>
			</p>
			<p>
				<label for="<?php the_ddl_name_attr('video_height'); ?>"><?php _e( 'Player height', 'ddl-layouts' ) ?>:</label>
				<input type="number" name="<?php the_ddl_name_attr('video_height'); ?>" value="300">
				<span class="desc"><?php _e( 'Fixed height of the video player. Width adjusts to cell\'s size', 'ddl-layouts' ) ?></span>
			</p>
		</div>

		<?php
		return ob_get_clean();
	}


	// Callback function for displaying the cell in the editor.
	function video_cell_template_callback() {
		ob_start();
		?>
			<div class="cell-content">

				<p class="cell-name"><?php _e('Youtube', 'ddl-layouts'); ?> - {{ name }}</p>
				<div class="cell-preview">
	                <div class="ddl-video-preview">
						<img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/youtube-video.svg'; ?>" height="130px">
					</div>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}


	// Callback function for display the cell in the front end.
	function video_cell_content_callback() {

		$video_url = get_ddl_field('video_url');
		$filter    = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';

		preg_match( $filter, $video_url, $matches );

        if( isset($matches[1]) )
        {
            $video_id  = $matches[1];
        }
		else
        {
            $video_id = null;
        }

		ob_start();
		?>

		<style>
			.video-container iframe {
				width: 100%;
			}
		</style>

            <?php if( $video_id === null ):?>

            <div class="video-container">
                <?php
                echo WPDDL_Messages::display_message(WPDDL_Messages::$message_warning, sprintf(__('The URL %s is not a valid YouTube URL.', 'ddl-layouts'), $video_url ) );
                ;?>
            </div>

            <?php else: ?>

			<div class="video-container">
				<iframe height="<?php the_ddl_field('video_height') ?>" src="//www.youtube.com/embed/<?php echo $video_id ?>" frameborder="0" ></iframe>
			</div>

            <?php endif; ?>

		<?php
		return ob_get_clean();
	}

}