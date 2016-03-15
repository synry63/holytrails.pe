<?php
function toolset_assigned_message( $kind, $slug = null ) {
	global $post;
	switch ( $kind ) {
		case "content-template":
			if ( function_exists( 'is_wpv_content_template_assigned' ) ) :
				if ( ! is_wpv_content_template_assigned() && ! ( preg_match( '/\[wpv\-(view|post\-body)/', get_the_content() ) ) && current_user_can( 'manage_options' ) ) : ?>
					<div class="panel panel-default not-assigned collapse in">
						<div class="panel-heading">
							<?php _e( 'There is no Content Template assigned', THEMETD ); ?>
							<a href="#" data-toggle="collapse" data-target=".not-assigned" class="alignright small">
								<i class="fa fa-close"></i> <?php _e( "Dismiss", THEMETD ); ?>
							</a>
						</div>
						<div class="panel-body">
							<div class="not-assigned-body">
								<h4><?php _e( "Do you want this page to look different?", THEMETD ); ?> </h4>
								<a class="btn btn-lg btn-primary"
								   href="<?php echo admin_url( 'admin.php?page=view-templates' ); ?>"
								   title="<?php _e( "Content Templates", THEMETD ); ?>">
									<?php _e( "Assign a Content Template", THEMETD ); ?>
								</a>
							</div>
							<div class="not-assigned-helper">

								<a href="http://wp-types.com/documentation/user-guides/view-templates/" target="_blank"
								   title="<?php _e( "Designing WordPress Content with Content Templates", THEMETD ); ?>">
									<?php _e( 'Learn about Content Templates', THEMETD ); ?>
								</a>

							</div>
						</div>
						<div class="panel-footer panel-footer-sm text-center">
							<?php _e( "You can see this message because you are logged in as a user who can assign Content Templates. <br>Your visitors won't see this message.", THEMETD ); ?>
						</div>
					</div>
				<?php
				endif;
			endif;
			break;

		case "views-archive" :
			if ( function_exists( 'is_wpv_wp_archive_assigned' ) ) :
				if ( ! is_wpv_wp_archive_assigned() && current_user_can( 'manage_options' ) ) : ?>
					<div class="panel panel-default not-assigned collapse in">
						<div class="panel-heading">
							<?php _e( 'There is no WordPress Archive Template assigned', THEMETD ); ?>
							<a href="#" data-toggle="collapse" data-target=".not-assigned" class="alignright small">
								<i class="fa fa-close"></i> <?php _e( "Dismiss", THEMETD ); ?>
							</a>
						</div>
						<div class="panel-body">
							<div class="not-assigned-body">
								<h4><?php _e( "Do you want this page to look different?", THEMETD ); ?> </h4>
								<a class="btn btn-lg btn-primary"
								   href="<?php echo admin_url( 'admin.php?page=view-archives' ); ?>"
								   title="<?php _e( "WordPress Archive", THEMETD ); ?>">
									<?php _e( "Assign a WordPress Archive Template", THEMETD ); ?>
								</a>
							</div>
							<div class="not-assigned-helper">
								<a href="http://wp-types.com/documentation/user-guides/normal-vs-archive-views/"
								   target="_blank"
								   title="<?php _e( "Customize Archive Page with WordPress Archive Template", THEMETD ); ?>">
									<?php _e( 'Learn about WordPress Archive Templates', THEMETD ); ?>
								</a>
							</div>
						</div>
						<div class="panel-footer panel-footer-sm text-center">
							<?php _e( "You can see this message because you are logged in as a user who can assign WordPress Archive Templates. <br>Your visitors won't see this message.", THEMETD ); ?>
						</div>
					</div>
				<?php endif; //if user can
			endif;
			break;


		case ( preg_match( '/layout-*/', $kind ) ? true : false ) :
			if ( function_exists( 'user_can_assign_layouts' ) ) :
				if ( ! is_ddlayout_assigned() && user_can_assign_layouts() && ! ddl_layout_slug_exists( $slug ) ) :

					switch ( $kind ) {
						case "layout-page" :
							$header       = __( 'Page: There is no Layout assigned', THEMETD );
							$learn_link   = "http://wp-types.com/documentation/user-guides/designing-pages-archive-templates-using-views-plugin#layouts-as-templates-for-content";
							$learn_anchor = __( "Using Layouts as Templates for Contents", THEMETD );
							break;
						case "layout-post" :
							$header       = __( 'Single Post: There is no Layout assigned', THEMETD );
							$learn_link   = "http://wp-types.com/documentation/user-guides/designing-pages-archive-templates-using-views-plugin#layouts-as-templates-for-content";
							$learn_anchor = __( "Using Layouts as Templates for Contents", THEMETD );
							break;
						case "layout-archive" :
							$header       = __( 'Archive Page: There is no Layout assigned', THEMETD );
							$learn_link   = "http://wp-types.com/documentation/user-guides/designing-pages-archive-templates-using-views-plugin#layouts-for-archives";
							$learn_anchor = __( "Using Layouts as Templates for Archives", THEMETD );
							break;
						case "layout-404" :
							$header       = __( 'Error 404 Page: There is no Layout assigned', THEMETD );
							$learn_link   = "http://wp-types.com/documentation/user-guides/designing-custom-404-error-pages-with-layouts/";
							$learn_anchor = __( "Designing the 404 Page with Layouts", THEMETD );
							break;
					}

					?>
					<div class="panel panel-default not-assigned ">
						<div class="panel-heading">
							<?php echo $header; ?>

						</div>
						<div class="panel-body">
							<div class="not-assigned-body">

								<h4><?php _e( "Do you want this page to look different?", THEMETD ); ?> </h4>
								<a class="btn btn-lg btn-primary"
								   href="<?php echo admin_url( 'admin.php?page=dd_layouts' ); ?>"
								   title="<?php _e( "Layouts", THEMETD ); ?>"><?php _e( "Assign a Layout", THEMETD ); ?>
								</a>
							</div>
							<div class="not-assigned-helper">

								<p><?php _e( "Find out more:", THEMETD ); ?></p>
								<ul>
									<li>
										<a href="<?php echo $learn_link;?>"
										   target="_blank"
										   title="<?php echo $learn_anchor ?>">
											<?php echo $learn_anchor ?>
										</a>
									</li>
									<li>
										<a href="http://wp-types.com/documentation/user-guides/develop-layouts-based-themes/#how-layout-plugins-works"
										   target="_blank"
										   title="<?php _e( "Learn how the Layouts plugin works", THEMETD ); ?>">
											<?php _e( 'Learn how the Layouts plugin works', THEMETD ) ?>
										</a>
									</li>
									<li>
										<a href="http://discover-wp.com/site-types/toolset-classifieds-layouts/"
										   target="_blank"
										   title="<?php _e( "Try our reference site built with this theme", THEMETD ); ?>">
											<?php _e( 'Try our reference site built with this theme', THEMETD ) ?>
										</a>
									</li>
								</ul>
							</div>
						</div>
						<div class="panel-footer panel-footer-sm text-center">
							<?php _e( "You can see this message because you are logged in as a user who can assign Layouts. <br>Your visitors won't see this message.", THEMETD ); ?>
						</div>
					</div>
				<?php
				endif; //if user can
			endif; //function exists
			break;

	}


}

