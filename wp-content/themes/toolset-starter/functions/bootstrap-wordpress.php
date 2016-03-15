<?php
// Include custom nav walker
require_once('bs-nav-walker.php');
require_once('toolset-assigned-message.php');



// Support for Bootstrap Pager.
// More info: http://twitter.github.com/bootstrap/components.html#pagination
if ( ! function_exists('wpbootstrap_content_nav') ) {

	function wpbootstrap_content_nav() {
		global $wp_query;

		if ( $wp_query->max_num_pages > 1 ) : ?>

			<ul class="pagination" role="navigation">
				<li class="previous">
					<?php echo str_replace('<a href', '<a rel="prev" href', get_next_posts_link('&larr; ' . __('Olders posts', THEMETD))) ?>
				</li>
				<li class="next">
					<?php echo str_replace('<a href', '<a rel="next" href', get_previous_posts_link(__('Newer posts', THEMETD) . ' &rarr;')) ?>
				</li>
			</ul>

		<?php endif;
	}

}


// Adds 'table' class for <table> tags. Bootstrap needs an additional 'table' class to style tables.
// More info: http://twitter.github.com/bootstrap/base-css.htm
if ( ! function_exists('wpbootstrap_add_table_class') ) {

	function wpbootstrap_add_table_class( $content ) {
		$table_has_class = preg_match( '/<table class="/', $content );	// FIXME: regex to skip additional elements between table and class

		if ( $table_has_class ) {
			$content = str_replace( '<table class="', '<table class="table ', $content );

		} else {
			$content = str_replace( '<table', '<table class="table"', $content );
		}
		return $content;
	}

	add_filter( 'the_content', 'wpbootstrap_add_table_class' );
	add_filter( 'comment_text', 'wpbootstrap_add_table_class' );
}


// Pagination function.
// Thanks to: https://gist.github.com/3774261
if ( ! function_exists('wpbootstrap_link_pages') ) {

	function wpbootstrap_link_pages( $args = '') {

		$defaults = array(
			'before'			=> '<ul class="pagination">',
			'after'				=> '</ul>',
			'next_or_number'	=> 'number',
			'nextpagelink'		=> __('Next page', THEMETD),
			'previouspagelink'	=> __('Previous page', THEMETD),
			'pagelink'			=> '%',
			'echo'				=> 1
		);

		$r = wp_parse_args( $args, $defaults );
		$r = apply_filters( 'wp_link_pages_args', $r );
		extract( $r, EXTR_SKIP );

		global $page, $numpages, $multipage, $more, $pagenow;

		$output = '';
		if ( $multipage ) {
			if ( 'number' == $next_or_number ) {
				$output .= $before;
				for ( $i = 1; $i < ( $numpages + 1 ); $i = $i + 1 ) {
					$j = str_replace ('%', $i, $pagelink );
					$output .= ' ';
					if ( $i != $page || ( ( !$more ) && ( $page == 1 ) ) )
						$output .= '<li>' . _wp_link_page( $i );
					else
						$output .= '<li class="active"><a href="#">';

					$output .= $j;
					if ( $i != $page || ( ( !$more ) && ( $page == 1 ) ) )
						$output .= '</a>';
					else
						$output .= '</a></li>';
				}
				$output .= $after;
			} else {
				if ( $more ) {
					$output .= $before;
					$i = $page - 1;
					if ( $i && $more) {
						$output .= _wp_link_page( $i );
						$output .= $previouspagelink . '</a>';
					}
					$i = $page + 1;
					if ( $i <= $numpages && $more ) {
						$output .= _wp_link_page( $i );
						$output .= $nextpagelink . '</a>';
					}
					$output .= $after;
				}
			}
		}
		if ( $echo )
			echo $output;

		return $output;
	}
}



/** COMMENTS WALKER */
class Wpbootstrap_Comments extends Walker_Comment {

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;
		?>
		<ul class="children list-unstyled">
			<?php
		}

		function end_lvl( &$output, $depth = 0, $args = array() ) {
			$GLOBALS['comment_depth'] = $depth + 1;
			?>
		</ul>
		</div>
		<?php
	}

	/** START_EL */
	function start_el( &$output, $comment, $depth = 0, $args = array(), $id = 0 ) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$GLOBALS['comment'] = $comment;
		global $post
		?>

		<li <?php comment_class();?> id="comment-<?php comment_ID(); ?>">
			<span class="comment-avatar <?php echo ( $comment->user_id === $post->post_author ? 'thumbnail' : '' ); ?>">
				<?php
				if ( $comment->user_id === $post->post_author) {
					echo get_avatar( $comment, 54 );
				} else {
					echo get_avatar( $comment, 64 );
				}
				?>
			</span>
			<div class="comment-body">
				<h4 class="comment-author vcard">
					<?php
					printf('<cite>%1$s %2$s</cite>', get_comment_author_link(), ( $comment->user_id === $post->post_author ) ? '<span class="bypostauthor label label-primary"> ' . __('Post author', THEMETD) . '</span>' : ''
					);
					?>
				</h4>
				<?php
				printf('<a href="%1$s"><time class="comment-date" datetime="%2$s">%3$s</time></a>',
						esc_url(get_comment_link( $comment->comment_ID )),
						get_comment_time('c'), sprintf( '%1$s ' . __('at', THEMETD) . ' %2$s', get_comment_date(), get_comment_time() )
				);
				?>

				<?php if ( '0' == $comment->comment_approved ) : ?>
					<p class="alert alert-info comment-awaiting-moderation">
						<?php _e('Your comment is awaiting moderation.', THEMETD); ?>
					</p>
				<?php endif; ?>

				<div class="comment-content">
					<?php comment_text(); ?>
				</div>

				<div class="reply">
					<a class="btn btn-default btn-xs edit-link" href="<?php echo get_edit_comment_link(); ?>"><?php _e('Edit', THEMETD) ?></a>
					<?php
					comment_reply_link(array_merge( $args,
							array(
								'reply_text' => '<span class="btn btn-default btn-xs">' . __('Reply', THEMETD) . '</span>',
								'after'      => '',
								'depth'      => $depth,
								'max_depth'	 => $args['max_depth'],
					)));
					?>
				</div>

				<?php if ( empty( $args['has_children']) ) : ?>
			</div>
			<?php endif; ?>

			<?php
		}

		function end_el( &$output, $comment, $depth = 0, $args = array() ) {
			?>
		</li>
		<?php
	}

}

// Changes the default comment form textarea markup
if ( ! function_exists('wpbootstrap_comment_form') ) {

	function wpbootstrap_comment_form( $defaults ) {
		$req = get_option('require_name_email');

		$defaults['comment_field'] = ''
				. '<div class="comment-form-comment form-group">'
					. '<label for="comment">' . __('Comment', THEMETD) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label>'
					. '<textarea id="comment" class="form-control" name="comment" rows="8" aria-required="true"></textarea>'
				. '</div>';
		$defaults['comment_notes_after'] = ''
				. '<p class="form-allowed-tags help-block">'
				. sprintf(__('You may use these', THEMETD) . ' <abbr title="HyperText Markup Language">HTML</abbr> ' . __('tags and attributes:', THEMETD). '%s',
						'<pre>' . allowed_tags() . '</pre>')
				. '</p>';

		return $defaults;
	}

	add_filter( 'comment_form_defaults', 'wpbootstrap_comment_form' );
}

// Changes the default comment form fields markup
// Thanks to http://www.codecheese.com/2013/11/wordpress-comment-form-with-twitter-bootstrap-3-supports/
if ( ! function_exists('wpbootstrap_comment_form_fields') ) {

	function wpbootstrap_comment_form_fields( $defaults ) {

		$commenter = wp_get_current_commenter();
		$req       = get_option( 'require_name_email' );
		$aria_req  = ( $req ? " aria-required='true'" : '' );
		$html5     = current_theme_supports( 'html5', 'comment-form' ) ? 1 : 0;

		$defaults    =  array(
			'author' => '<div class="form-group comment-form-author">' . '<label for="author">' . __( 'Name', THEMETD ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
						'<input class="form-control" id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></div>',
			'email'  => '<div class="form-group comment-form-email"><label for="email">' . __( 'Email', THEMETD ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
						'<input class="form-control" id="email" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></div>',
			'url'    => '<div class="form-group comment-form-url"><label for="url">' . __( 'Website', THEMETD ) . '</label> ' .
						'<input class="form-control" id="url" name="url" ' . ( $html5 ? 'type="url"' : 'type="text"' ) . ' value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></div>',
		);

		return $defaults;
	}

	add_filter( 'comment_form_default_fields', 'wpbootstrap_comment_form_fields' );
}


// Changes the default password protection form markup
if ( ! function_exists( 'wpbootstrap_password_form' ) ) {

	function wpbootstrap_password_form() {

		global $post;
		$label = 'pwbox-' . ( empty( $post->ID ) ? rand() : $post->ID );
		$form = '<form class="protected-post-form form-inline" role="form" action="' . get_option( 'siteurl' ) . '/wp-login.php?action=postpass" method="post">'
				. '<div class="alert alert-info alert-dismissable">'
				. '<button type="button" class="close" data-dismiss="alert">&times;</button>'
				. '<strong>' . __( 'This post is password protected.', THEMETD ) . '</strong> ' . __( "To view it please enter your password below", THEMETD )
				. '</div>'
				. '<div class="form-group">'
				. '<label class="sr-only" for="' . $label . '">' . __( "Password: ", THEMETD ) . '</label>'
				. '<input type="password" class="form-control" placeholder="Password" name="post_password" id="' . $label . '" />'
				. '</div>'
				. '<button type="submit" class="btn btn-primary"/>' . __( 'Submit', THEMETD ) . '</button>'
				. '</form>';

		return $form;
	}

	add_filter( 'the_password_form', 'wpbootstrap_password_form' );
} 

// removes invalid rel="category tag" attribute from the links
if ( ! function_exists('wpbootstrap_remove_category_rel') ) {

	function wpbootstrap_remove_category_rel( $link ) {
		$link = str_replace( 'rel="category tag"', "", $link );
		return $link;
	}

	add_filter( 'the_category', 'wpbootstrap_remove_category_rel' );
}


// Declare Bootstrap version the theme is built with
if( function_exists('ddlayout_set_framework'))
{
	ddlayout_set_framework('bootstrap-3');
}
