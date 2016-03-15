<?php
/*
Template name: Reference Single
*/
if ( defined( 'WPDDL_VERSION' )) :
    get_header( 'layouts', 'page-default');
		toolset_assigned_message('layout-post', 'page-default');
        the_ddlayout( 'page-default' ); // Loads 'page-default' layout by default
    get_footer( 'layouts' );
else:
    get_header();
	if ( have_posts()) : while ( have_posts() ) : the_post();
		toolset_assigned_message('content-template');
	    the_content();
        comments_template();
    endwhile; endif; // WP Loop
    get_footer();
endif; // IF Layouts are enabled
?>
