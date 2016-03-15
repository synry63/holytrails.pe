<?php

WPV_Frontend_Render_Filters::on_load();

class WPV_Frontend_Render_Filters {
	
	static function on_load() {
		add_filter( 'the_content', array( 'WPV_Frontend_Render_Filters', 'the_content' ), 5 );
		add_filter( 'wpv-pre-do-shortcode', array( 'WPV_Frontend_Render_Filters', 'wpv_pre_do_shortcode' ), 5 );
	}
	
	static function the_content( $content ) {
		
		$content = wpv_preprocess_foreach_shortcodes_for_4_2_3( $content );
		
		$content = wpv_resolve_internal_shortcodes( $content );
		
		$content = wpv_resolve_wpv_if_shortcodes( $content );
		
		$content = wpv_preprocess_shortcodes_in_html_elements( $content );
		
		return $content;
	}
	
	static function wpv_pre_do_shortcode( $content ) {
		
		$content = wpv_preprocess_foreach_shortcodes_for_4_2_3( $content );
		
		$content = wpv_parse_content_shortcodes( $content );
		
		$content = wpv_parse_wpv_if_shortcodes( $content );
		
		$content = wpv_preprocess_shortcodes_in_html_elements( $content );
		
		return $content;
	}
	
}
