<?php
/**
 * A patch to preprocess shortcodes that are broken in WP 4.2.3
 */


// adding filter with priority before do_shortcode and other WP standard filters
//add_filter('the_content', 'wpv_preprocess_shortcodes_in_html_elements', 9);
//add_filter('wpv-pre-do-shortcode', 'wpv_preprocess_shortcodes_in_html_elements', 2);

function wpv_preprocess_shortcodes_in_html_elements($content) {
	global $WPV_settings;
	$inner_expressions = array();
	$inner_expressions[] = array(
								 'regex'       => "/\\[types.*?\\]\\[\\/types\\]/i",
								 'has_content' => false
								);
	$inner_expressions[] = array(
								 'regex'       => "/\\[types.*?\\](.*?)\\[\\/types\\]/i",
								 'has_content' => true
								);
	$inner_expressions[] = array(
								 'regex'       => "/\\[(wpv-post-|wpv-taxonomy-|types|wpv-current-user|wpv-user|wpv-attribute|wpv-bloginfo).*?\\]/i",
								 'has_content' => false
								);
	
	// support for custom inner shortcodes via settings page
	// since 1.4
	$custom_inner_shortcodes = array();
	if ( isset( $WPV_settings->wpv_custom_inner_shortcodes ) && is_array( $WPV_settings->wpv_custom_inner_shortcodes ) ) {
		$custom_inner_shortcodes = $WPV_settings->wpv_custom_inner_shortcodes;
	}
	// wpv_custom_inner_shortcodes filter
	// since 1.4
	// takes an array of shortcodes and returns an array of shortcodes
	$custom_inner_shortcodes = apply_filters( 'wpv_custom_inner_shortcodes', $custom_inner_shortcodes );
	// remove duplicates
	$custom_inner_shortcodes = array_unique( $custom_inner_shortcodes );
	// add the custom inner shortcodes, whether they are self-closing or not
	if ( sizeof( $custom_inner_shortcodes ) > 0 ) {
		foreach ( $custom_inner_shortcodes as $custom_inner_shortcode ) {
			$inner_expressions[] = array(
										 'regex'       => "/\\[" . $custom_inner_shortcode . ".*?\\](.*?)\\[\\/" . $custom_inner_shortcode . "\\]/is",
										 'has_content' => true
										);
		}
		$inner_expressions[] = array(
									 'regex' => "/\\[(" . implode( '|', $custom_inner_shortcodes ) . ").*?\\]/i",
									 'has_content' => false
									);
	}
			
			
	// Normalize entities in unfiltered HTML before adding placeholders.
	$trans = array( '&#91;' => '&#091;', '&#93;' => '&#093;' );
	$content = strtr( $content, $trans );
	$trans = array( '[' => '&#91;', ']' => '&#93;' );
	
	$comment_regex =
		  '!'           // Start of comment, after the <.
		. '(?:'         // Unroll the loop: Consume everything until --> is found.
		.     '-(?!->)' // Dash not followed by end of comment.
		.     '[^\-]*+' // Consume non-dashes.
		. ')*+'         // Loop possessively.
		. '(?:-->)?';   // End of comment. If not found, match all input.

	$regex =
		  '/('                   // Capture the entire match.
		.     '<'                // Find start of element.
		.     '(?(?=!--)'        // Is this a comment?
		.         $comment_regex // Find end of comment.
		.     '|'
		.         '[^>]*>?'      // Find end of element. If not found, match all input.
		.     ')'
		. ')/s';

	$textarr = preg_split( $regex, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

	foreach ( $textarr as &$element ) {
		if ( '<' !== $element[0] ) {
			continue;
		}

		$noopen = false === strpos( $element, '[' );
		$noclose = false === strpos( $element, ']' );
		if ( $noopen || $noclose ) {
			// This element does not contain shortcodes.
			if ( $noopen xor $noclose ) {
				// Need to encode stray [ or ] chars.
				$element = strtr( $element, $trans );
			}
			continue;
		}

		if ( '<!--' === substr( $element, 0, 4 ) ) {
			// Encode all [ and ] chars.
			$element = strtr( $element, $trans );
			continue;
		}
		
				
		foreach ($inner_expressions as $shortcode) {
			$counts = preg_match_all($shortcode[ 'regex' ], $element, $matches);
			
			if($counts > 0) {
				foreach($matches[0] as $index => &$match) {
					
					// We need to exclude wpv-post-body here otherwise
					// wpautop can be applied to it too soon.
					
					if ( strpos( $match, '[wpv-post-body' ) !== 0 ) {
						$string_to_replace = $match;
						
						// execute shortcode content and replace
						
						if ( $shortcode[ 'has_content' ] ) {
							$inner_content = $matches[1][ $index ];
							if ( $inner_content ) {
								$new_inner_content = wpv_preprocess_shortcodes_in_html_elements( $inner_content );
								$match = str_replace( $inner_content, $new_inner_content, $match );
							}
						}
						$filter_state = new WPV_WP_filter_state( 'the_content' );
						$replacement = do_shortcode( $match );
						$filter_state->restore();
						$resolved_match = $replacement;
						$element = str_replace($string_to_replace, $resolved_match, $element);
					}
				}
			}
		}
	
		// Now encode any remaining [ or ] chars.
		$element = strtr( $element, $trans );
	}
	
	$content = implode( '', $textarr );
	
	return $content;
}

// Parse wpv-for-each shortcodes properly

//add_filter('the_content', 'wpv_preprocess_foreach_shortcodes_for_4_2_3', 7);
//add_filter('wpv-pre-do-shortcode', 'wpv_preprocess_foreach_shortcodes_for_4_2_3', 1);

function wpv_preprocess_foreach_shortcodes_for_4_2_3($content) {
	global $shortcode_tags;
	// Back up current registered shortcodes and clear them all out
	$orig_shortcode_tags = $shortcode_tags;
	remove_all_shortcodes();			
	add_shortcode( 'wpv-for-each', 'wpv_for_each_shortcode' );
	$expression = "/\\[wpv-for-each.*?\\](.*?)\\[\\/wpv-for-each\\]/is";
	$counts = preg_match_all( $expression, $content, $matches );
	while ( $counts ) {
		foreach( $matches[0] as $index => $match ) {
			// encode the data to stop WP from trying to fix it.
			$match_encoded = str_replace( $matches[ 1 ][ $index ], 'wpv-b64-' . base64_encode( $matches[ 1 ][ $index ] ), $match );
			$shortcode = do_shortcode( $match_encoded );
			$content = str_replace( $match, $shortcode, $content );
		}
		$counts = preg_match_all( $expression, $content, $matches );
	}
	$shortcode_tags = $orig_shortcode_tags;		
	
	return $content;
}
/*
add_shortcode('temp-content', 'temp_content_shortcode');
function temp_content_shortcode( $atts, $value ) {
	return 'TEMP-CONTENT' . $value . 'TEMP-CONTENT';
}

add_shortcode('counter-increment', 'temp_counter_shortcode');
function temp_counter_shortcode( $atts, $value ) {
	return 'TEMP-temp_counter_shortcode' . $value . 'TEMP-temp_counter_shortcode';
}
*/