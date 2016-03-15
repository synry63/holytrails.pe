<?php
if ( defined( 'WPDDL_VERSION' )) :
    get_header( 'layouts' );
		toolset_assigned_message('layout-404', 'error-404');
		the_ddlayout( 'error-404' ); // Loads 'error-404' layout by default
    get_footer( 'layouts' );
else:
    get_header( ); ?>
    <div class="text-center">
        <h1 class="page-title"><?php _e('Error 404',THEMETD);?></h1>
        <h2 style="margin: 0 0 50px 0"><?php _e('Unfortunately the page you were looking for does not exist',THEMETD);?></h2>
        <p><?php _e("Don't worry! You can always",THEMETD);?>: <a href="<?php echo esc_url( home_url() );?>" title="<?php _e('Homepage',THEMETD);?>"><?php _e('go to homepage',THEMETD);?></a></p>
    </div>
    <?php
    get_footer(  );
endif; // IF Layouts are enabled
?>
