<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<a class="screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'lunara' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="container">
			<div class="site-branding">
				<?php
				if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
					the_custom_logo();
				} else {
					?>
					<div class="site-name-wrap">
						<?php if ( is_front_page() && is_home() ) : ?>
							<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
						<?php else : ?>
							<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
						<?php endif; ?>

						<?php
						$description = get_bloginfo( 'description', 'display' );
						if ( $description || is_customize_preview() ) : ?>
							<p class="site-description"><?php echo esc_html( $description ); ?></p>
						<?php endif; ?>
					</div>
					<?php
				}
				?>
			</div><!-- .site-branding -->

			<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'lunara' ); ?>">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle navigation menu', 'lunara' ); ?>">
					<span aria-hidden="true">&#9776;</span>
				</button>

				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => 'lunara_default_nav_menu',
				) );
				?>
			</nav><!-- #site-navigation -->
		</div><!-- .container -->
	</header><!-- #masthead -->
