<?php
/**********************************************************************
 *           Theme Customization
 ********************************************************************/


/****************************************
 *  Customization for Page with just Views
 *****************************************/
if ( ! defined( 'WPDDL_VERSION' ) ) {
	function ref_nolayouts_header() {
		add_theme_support( 'custom-header', array(
			'default-image' => get_template_directory_uri() . '/images/header.jpg',
			'width'         => 1920,
			'height'        => 335,
			'uploads'       => true,
		) );

	}

	add_action( 'after_setup_theme', 'ref_nolayouts_header' );

	function ref_nolayouts_customize_register( $wp_customize ) {

		$wp_customize->remove_control( 'display_header_text' );
		$wp_customize->remove_control( 'header_textcolor' );

		$wp_customize->add_section( 'ref_logo', array(
			'title'    => __( 'Logo', THEMETD ),
			'priority' => 10,
		) );

		$wp_customize->add_setting( 'logo', array(
			'default'           => get_template_directory_uri() . '/images/toolset-logo-white.png',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'logo', array(
			'label'   => __( 'Upload a logo', THEMETD ),
			'section' => 'ref_logo',
		) ) );
	}

	add_action( 'customize_register', 'ref_nolayouts_customize_register' );
}

function ref_advanced_customize_options( $wp_customize ) {
	$wp_customize->add_section( 'ref_advanced_settings', array(
		'title'       => __( 'Advanced Settings', THEMETD ),
		'priority'    => 1020,
		'description' => __( 'Change these settings only if you are sure you know what you are doing<br><br>', THEMETD )
	) );

	$wp_customize->add_setting( 'ref_wc_styles', array(
		'default'   => 1,
		'transport' => 'postMessage'
	) );
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'ref_wc_styles',
			array(
				'label'   => __( 'Load theme CSS for WooCommerce', THEMETD ),
				'section' => 'ref_advanced_settings',
				'type'    => 'checkbox'
			)
		)
	);

	$wp_customize->add_setting( 'ref_theme_styles', array(
		'default'   => 1,
		'transport' => 'postMessage'
	) );
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'ref_theme_styles',
			array(
				'label'   => __( 'Load theme CSS', THEMETD ),
				'section' => 'ref_advanced_settings',
				'type'    => 'checkbox'
			)
		)
	);

	$wp_customize->add_setting( 'ref_color_styles', array(
		'default'   => 1,
		'transport' => 'postMessage'
	) );
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'ref_color_styles',
			array(
				'label'   => __( 'Enable Primary Color setting', THEMETD ),
				'section' => 'ref_advanced_settings',
				'type'    => 'checkbox'
			)
		)
	);


}

add_action( 'customize_register', 'ref_advanced_customize_options' );

/****************************************
 *  Customization for Page with Layouts
 *****************************************/


function ref_customize_register( $wp_customize ) {

	$wp_customize->add_setting( 'primary_color', array(
		'default'           => '#3CBEFE',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );

	if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primary_color', array(
			'label'       => __( 'Primary Color', THEMETD ),
			'description' => '<span id="primary_color_enabled">' . __( 'Applied to the menu, links, etc.', THEMETD ) . '</span><span id="primary_color_disabled">' . __( 'You turned off the Primary Color setting in Advanced Settings.<br><br> To change the Primary Color turn the setting on.', THEMETD ) . '</span>',
			'section'     => 'colors',
		) ) );
	} else {
		$wp_customize->add_control( new WP_Customize_Dummy_Control( $wp_customize, 'primary_color', array(
			'label'       => __( 'Primary Color', THEMETD ),
			'description' => sprintf( __( 'Your PHP version (%s) does not support this feature. Please upgrade to at least PHP 5.3 version to customise your colors.', THEMETD ), PHP_VERSION ),
			'section'     => 'colors',
		) ) );
	}

}

add_action( 'customize_register', 'ref_customize_register', 1 );

function ref_dynamic_css() {
	require get_template_directory() . "/css/theme-customizer-css.php";
	exit;
}

function ref_enqueue_customizer_css() {
	wp_register_style( 'ref_customizer', admin_url( 'admin-ajax.php' ) . '?action=ref_dynamic_css', array(), null );
	wp_enqueue_style( 'ref_customizer' );
}

add_action( 'wp_enqueue_scripts', 'ref_enqueue_customizer_css', 100 );
add_action( 'wp_ajax_ref_dynamic_css', 'ref_dynamic_css' );
add_action( 'wp_ajax_nopriv_ref_dynamic_css', 'ref_dynamic_css' );


function ref_customizer_live_preview() {
	wp_enqueue_script( 'ref-themecustomizer', get_template_directory_uri() . '/js/theme-customizer.min.js', array(
		'jquery',
		'customize-preview'
	), '', true );
	wp_localize_script( 'ref-themecustomizer', 'toolset', array( 'ajaxurl' => admin_url(  'admin-ajax.php' ) ) );
}

add_action( 'customize_preview_init', 'ref_customizer_live_preview' );


/****************************************
 *  Additional controls
 *****************************************/
if ( class_exists( 'WP_Customize_Control' ) ) {

	class WP_Customize_Dummy_Control extends WP_Customize_Control {

		public function render_content() { ?>
			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif;
			if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo $this->description; ?></span>
			<?php endif; ?>
		<?php }
	}
}

/**
 * Custom CSS box
 */

add_action( 'customize_register', 'toolset_customizer_control_custom_css' );

if( ! function_exists( 'toolset_customizer_control_custom_css' ) ) {
	function toolset_customizer_control_custom_css( $wp_customize ) {

		// adding a new control type for our custom css contorl
		if( ! class_exists( 'Toolset_Customizer_Control_Custom_Css' ) && class_exists( 'WP_Customize_Control' ) ) {
			class Toolset_Customizer_Control_Custom_Css extends WP_Customize_Control {
				public $type = 'custom-css';

				public function render_content() {
					$textareaId = ( defined( 'WPV_VERSION' ) ) ? ' id="textarea-custom-css"' : ''; ?>
					<label class="toolset-customizer-control-custom-css">
						<textarea<?php echo $textareaId; ?> <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
					</label>
				<?php }
			}
		}

		// add section "Custom CSS"
		$wp_customize->add_section( 'section_custom_css', array(
			'title'     => __( 'Custom CSS', THEMETD ),
			'priority'  => 45
		) );

		// add setting
		$wp_customize->add_setting( 'setting_custom_css', array(
			'sanitize_callback' => 'toolset_sanitize_css',
			'transport' => 'postMessage'
		) );

		// add_control
		$wp_customize->add_control(
			new Toolset_Customizer_Control_Custom_Css(
				$wp_customize,
				'custom_css',
				array(
					'section'  => 'section_custom_css',
					'settings' => 'setting_custom_css',
				)
			)
		);
	}
}

// add tabs to code after save (empty space is deleted by default)
if( ! function_exists( 'toolset_indent_css' ) ) {
	function toolset_indent_css( $css ) {
		$output = '';
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $css ) as $line ) {
			$line = trim( $line );
			if( strpos( $line, '{' ) !== false || strpos( $line, '}' ) !== false ) {
				$output .= $line . "\n";
			} else if( preg_match( "/[A-Za-z]/", $line ) ) {
				$output .= '  ' . $line . "\n";
			} else {
				$output .= "\n";
			}
		}
		return $output;
	}
}

if( ! function_exists( 'indent_setting_custom_css' ) ) {
	function indent_setting_custom_css() {
		if( $custom_css = get_theme_mod( 'setting_custom_css' ) ) {
			$custom_css = toolset_indent_css( $custom_css );
			set_theme_mod( 'setting_custom_css', $custom_css );
		}
	}
}

add_action( 'customize_save_after', 'indent_setting_custom_css', 100 );

// function to sanitize css
function toolset_sanitize_css( $input ) {

	// use sanitize_text_field as long as HTMLPurifier/CSSTidy are not CSS3 ready
	$output = '';
	foreach( preg_split("/((\r?\n)|(\r\n?))/", $input ) as $line ) {
		$output .= sanitize_text_field( $line ) . "\n";
	}

	return $output;
}

// ajax sanitize css
if( ! function_exists( 'toolset_sanitize_css_ajax' ) ) {
	function toolset_sanitize_css_ajax() {
		if( isset( $_POST['customcss'] ) ) {
			$output = toolset_sanitize_css( $_POST['customcss'] );
			echo $output;
		}
		exit();
	}
}


add_action( 'wp_ajax_toolset_sanitize_css_ajax', 'toolset_sanitize_css_ajax' );

// load codemirror script and style files
function load_views_codemirror() {
	// enqueue codemirror script if VIEWS is active
	if ( defined( 'WPV_VERSION' ) ) {
		wp_register_script( 'theme_customizer-controls', get_template_directory_uri() . '/js/theme-customizer-controls.min.js', array( 'jquery', 'views-codemirror-css-script' ), null, true );
		wp_enqueue_script( 'theme_customizer-controls' );
		wp_localize_script( 'theme_customizer-controls', 'toolset', array(
			'ajaxurl' => admin_url(  'admin-ajax.php' ),
			'theme_css' => THEME_CSS,
			'theme_css_woo' => THEME_CSS_WOO,
			'theme_css_bootstrap' => THEME_CSS_BOOTSTRAP,
		) );

		wp_enqueue_script( 'views-codemirror-conf-script' );
		wp_enqueue_style( 'views-codemirror-css' );

		wp_enqueue_style( 'theme-customizer-admin', get_template_directory_uri() .'/css/theme-customizer-admin.css' );
	}
}


add_action( 'customize_controls_enqueue_scripts', 'load_views_codemirror', 1000 );