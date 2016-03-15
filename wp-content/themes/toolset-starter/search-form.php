<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ) ?>">
	<label>
		<span class="screen-reader-text"><?php _e( 'Search for:', 'label' ) ?></span>
		<input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Search &hellip;', 'placeholder' ) ?>"
		       value="<?php echo get_search_query() ?>" name="s" title="<?php esc_attr_e( 'Search for:', 'label' ) ?>"/>
	</label>

	<div>
		<input type="submit" class="search-submit" value="<?php  esc_attr_e( 'Search', 'submit button' ) ?>"/>
	</div>
</form>