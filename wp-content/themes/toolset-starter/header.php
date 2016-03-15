<!DOCTYPE html>
<!--[if lt IE 7 ]>
<html <?php language_attributes(); ?> class="no-js ie ie6 ie-lte7 ie-lte8 ie-lte9"><![endif]-->
<!--[if IE 7 ]>
<html <?php language_attributes(); ?> class="no-js ie ie7 ie-lte7 ie-lte8 ie-lte9"><![endif]-->
<!--[if IE 8 ]>
<html <?php language_attributes(); ?> class="no-js ie ie8 ie-lte8 ie-lte9"><![endif]-->
<!--[if IE 9 ]>
<html <?php language_attributes(); ?> class="no-js ie ie9 ie-lte9"><![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
<html <?php language_attributes(); ?> class="no-js"><!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<link rel="profile" href="http://gmpg.org/xfn/11"/>
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>"/>
	<?php
	do_action( 'wpbootstrap_before_wp_head' );
	wp_head();
	do_action( 'wpbootstrap_after_wp_head' );
	?>
	<!--[if lt IE 9]>
	<?php // Loads HTML5 JavaScript file to add support for HTML5 elements in older IEs: http://code.google.com/p/html5shiv/ ?>
	<script src="<?php echo get_template_directory_uri() ?>/js/html5shiv.js" type="text/javascript"></script>
	<?php // Loads selectivizr script to add support for some CSS3 selectors in older IEs. More info: http://selectivizr.com/ ?>
	<script src="<?php echo get_template_directory_uri() ?>/js/selectivizr.min.js" type="text/javascript"></script>
	<?php // Loads respons.js script to add baisc support for @media-queries for older IEs. More info: https://github.com/scottjehl/Respond ?>
	<script src="<?php echo get_template_directory_uri() ?>/js/respond.min.js" type="text/javascript"></script>
	<![endif]-->
</head>

<body <?php body_class(); ?>>
	<div class="wrapper">
		<?php if( is_active_sidebar( 'sidebar-header' ) ): ?>
		<div class="container container-sidebar-header">
			<div class="header-top sidebar-header row">
				<?php dynamic_sidebar('sidebar-header'); ?>
			</div>
		</div>
		<?php endif;?>
		<?php if( has_header_image() ): ?>
		<div class="container-fluid header-background-image">
			<div class="row header-background-image">
				<img src="<?php header_image(); ?>" height="<?php echo get_custom_header()->height; ?>" width="<?php echo get_custom_header()->width; ?>" alt="" class="img-responsive"/>
			</div>
		</div>
		<?php endif; ?>
		<header class="container container-header">
			<div class="row js-header-height header-nav">
					<div class="col-sm-3 logo col-xs-6">
						<?php if(get_theme_mod( 'logo', get_template_directory_uri() . '/images/toolset-logo-white.png') != '') :?>
						<a href="<?php echo esc_url( home_url() );?>">
							<img src="<?php echo get_theme_mod( 'logo', get_template_directory_uri() . '/images/toolset-logo-white.png');?>" alt="">
						</a>
						<?php endif;?>
					</div>
					<div class="col-sm-9 static col-xs-5">
						<nav class="nav-wrap navbar navbar-default" role="navigation">
							<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
								<span class="sr-only"><?php _e('Toggle navigation', THEMETD); ?></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
							</button>
							<div class="collapse navbar-collapse" id="nav-main">
								<?php
								wp_nav_menu(array(
									'theme_location' => 'header-menu',
									'depth'          => 5,
									'menu_class'     => 'nav navbar-nav',
									'fallback_cb'    => 'wpbootstrap_menu_fallback',
									'walker'         => new Wpbootstrap_Nav_Walker(),
								));
								?><!-- #nav-main -->
							</div><!-- .navbar-collapse -->
						</nav><!-- .navbar -->
					</div>
			</div>
		</header>
		<section class="container container-main" role="main">

