<?php if (isset($_GET['s'])) {
	$placeholder = $_GET['s'];
} ?>

<form method="get" id="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="s" class="assistive-text"><?php _e( 'Search', 'twentyeleven' ); ?></label>
	<input type="text" class="field" name="s" id="s" placeholder="<?php echo esc_attr_e( 'Search', 'twentyeleven' ); ?>" value="<?php echo $placeholder;?>" />
	<input type="submit" class="submit" name="submit" id="searchsubmit" value="<?php esc_attr_e( 'Search', 'twentyeleven' ); ?>" />
</form>