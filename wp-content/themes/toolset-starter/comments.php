<?php
/**
 * The template for displaying Comments.
 *
 */

	if ( post_password_required() ) {
		return;
	}
?>


		<?php if ( comments_open() ) : ?>
			<?php if ( have_comments() ) : ?>
		<section id="comments" class="clear clearfix section-notitle">
				<h3 id="comments-title">
				<?php
					$comment_count_actual = get_comments_number();
					if ( $comment_count_actual === 1 ) {
						printf( __( 'One thought on', THEMETD ) . ' &ldquo;%1$s&rdquo;', '<span>' . get_the_title() . '</span>' );
                    }
					elseif ( $comment_count_actual > 1 ) {
						printf( '%1$s ' . __( 'thoughts on', THEMETD ) . ' &ldquo;%2$s&rdquo;', number_format_i18n( get_comments_number() ), '<span>' . get_the_title() . '</span>' );
					}
				?>
				</h3>

				<ol class="commentlist list-unstyled">
					<?php
					wp_list_comments(array(
						'walker' => new Wpbootstrap_Comments(),
					));
					?>
				</ol>

				<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // are there comments to navigate through ?>
				<ul class="pagination">
					<li class="previous"><?php previous_comments_link( '&larr; ' . __('Older Comments', THEMETD) ); ?></li>
					<li class="next"><?php next_comments_link( __('Newer Comments', THEMETD) . ' &rarr;' ); ?></li>
				</ul>
				<?php endif; // check for comment navigation ?>
		</section>
			<?php endif; // have_comments() ?>

			<?php comment_form(); ?>


		<?php endif;?>
