<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package bmcr
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-6977106-2"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());
	
	  gtag('config', 'UA-6977106-2');
	</script>
	
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'bmcr' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="login-wrapper container-fluid">
			<div class="row">
				<div class="col-sm-10 offset-sm-1">
					<p><a href="<?php echo wp_login_url(); ?>" title="Login">Login</a></p>
				</div>
			</div>
		</div>
		<nav class="navbar navbar-expand-lg container-fluid">
			<div class="row">
				<div class="col-sm-10 offset-sm-1 brand-wrapper">
					<a class="navbar-brand navbar-brand-lg" href="<?php echo esc_url( home_url( '/' ) ); ?>"><h1 class="site-title"><?php the_field('site_full_name', 'option'); ?></h1></a>
					<a class="navbar-brand navbar-brand-sm" href="<?php echo esc_url( home_url( '/' ) ); ?>"><h1 class="site-title"><?php the_field('site_acronym', 'option'); ?></h1><small><?php the_field('site_full_name', 'option'); ?></small></a>

					<button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
						<span class="navbar-toggler-icon"></span>
						<span class="navbar-toggler-icon"></span>
					</button>
					<form class="form-inline my-2 my-lg-0" method="get" action="<?php echo home_url( '/' ); ?>">
						<input class="form-control mr-sm-2 header-search" type="search" placeholder="Search" aria-label="Search" name="s">
    				</form>
				</div><!--/.col-sm-10 -->
			</div><!--/.row -->
			<div class="row mobile-nav-menu">
				<div class="col-xs-12 col-sm-10 offset-sm-1 nav-wrapper">
					<?php wp_nav_menu( array(
						'theme_location'  => 'menu-1',
						'depth'	          => 2, // 1 = no dropdowns, 2 = with dropdowns.
						'container'       => 'div',
						'container_class' => 'collapse navbar-collapse',
						'container_id'    => 'navbarSupportedContent',
						'menu_class'      => 'navbar-nav mr-auto',
						'fallback_cb'     => 'WP_Bootstrap_Navwalker::fallback',
						'walker'          => new WP_Bootstrap_Navwalker(),
					) ); ?>
					
					<?php wp_nav_menu( array(
						'theme_location'  => 'menu-2',
						'depth'	          => 2, // 1 = no dropdowns, 2 = with dropdowns.
						'container'       => 'div',
						'container_class' => '',
						'container_id'    => '',
						'menu_class'      => 'navbar-nav mr-auto nav-right',
						'fallback_cb'     => 'WP_Bootstrap_Navwalker::fallback',
						'walker'          => new WP_Bootstrap_Navwalker(),
					) ); ?>
  				</div><!-- /.col-sm-10 -->
  			</div><!-- /.row -->
		</nav>
	</header><!-- #masthead -->

	<div id="content" class="site-content">
