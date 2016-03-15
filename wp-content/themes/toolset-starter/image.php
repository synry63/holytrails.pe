<?php
/*
Template name: Reference
*/
if ( defined( 'WPDDL_VERSION' )) :
    get_header( 'layouts', 'page-default');
		toolset_assigned_message('layout-post', 'page-default');
        the_ddlayout( 'page-default' ); // Loads 'page-default' layout by default
    get_footer( 'layouts' );
else:
    get_header();
	if ( have_posts()) : while ( have_posts() ) : the_post();
		$att_image = wp_get_attachment_image_src( $post->id, "large");
		?>
		<h1 class="post-title"><?php the_title();?></h1>
		<nav class="pagination">
			<?php previous_image_link(false, '<span class="wpv-filter-previous-link">&laquo; '. __( 'Previous Image', THEMETD) .'</span>'); ?>
			<?php next_image_link( false, '<span class="wpv-filter-next-link"> '. __( 'Next Image', THEMETD).' &raquo;</span>' ); ?>
		</nav>

		<p class="attachment text-center">
			<a href="<?php echo wp_get_attachment_url($post->id); ?>" title="<?php the_title_attribute(); ?>">
				<img src="<?php echo $att_image[0];?>" width="<?php echo $att_image[1];?>" height="<?php echo $att_image[2];?>"  class="attachment-medium" alt="<?php $post->post_excerpt; ?>" />
			</a>
	    </p>
		<div class="post-content text-center">
			<?php the_content(); ?>
		</div>
    <?php endwhile; endif; // WP Loop
    get_footer();
endif; // IF Layouts are enabled
?>
