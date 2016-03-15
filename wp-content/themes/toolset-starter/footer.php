		</section>
		<footer class="main-footer">
			<?php if( is_active_sidebar( 'sidebar-footer' ) ): ?>
				<div class="container container-sidebar-footer">
					<div class="row sidebar-footer">
						<?php dynamic_sidebar('sidebar-footer'); ?>
					</div>
				</div>
			<?php endif; ?>
			<?php
				do_action( 'wpbootstrap_before_footer' );
				do_action( 'wpbootstrap_before_wp_footer' );
				wp_footer();
				do_action( 'wpbootstrap_after_footer' );
				do_action( 'wpbootstrap_after_wp_footer' );
			?>
		</footer>
	</div><!-- .wrapper -->
</body>
</html>