<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'ramsetu' ); ?></a>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			$logo_id = get_theme_mod( 'custom_logo' );
			if ( $logo_id ) {
				echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'brand__logo', 'alt' => '' ) );
			} else {
				printf( '<img class="brand__logo" src="%s" width="56" height="56" alt="">', esc_url( get_template_directory_uri() . '/assets/img/logo.png' ) );
			}
			?>
			<span class="brand__text"><span class="wordmark"><span class="wordmark__ram">Ram</span><span class="wordmark__setu">Setu</span></span><span class="brand__sub">Financial Service Private Limited</span></span>
		</a>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<span class="nav-toggle__open"><?php echo rs_icon( 'menu' ); // phpcs:ignore ?></span>
			<span class="nav-toggle__close"><?php echo rs_icon( 'close' ); // phpcs:ignore ?></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'ramsetu' ); ?></span>
		</button>

		<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'Main', 'ramsetu' ); ?>" data-nav>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'menu',
				'depth'          => 3,
				'fallback_cb'    => function () {
					echo '<ul class="menu">';
					foreach ( array( 'ramaffordable' => 'Our Products', 'about' => 'About Us', 'partner' => 'Become a Partner', 'branches' => 'Branch Locator', 'contact' => 'Contact Us' ) as $t => $l ) {
						printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( rs_page_url( $t ) ), esc_html( $l ) );
					}
					echo '</ul>';
				},
			) );
			?>
			<a class="site-nav__login" href="<?php echo esc_url( rs_portal_link() ); ?>"><?php echo rs_icon( 'users' ); // phpcs:ignore ?><span><?php esc_html_e( 'Partner Login', 'ramsetu' ); ?></span></a>
			<a class="btn btn--primary site-nav__apply" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?></a>
		</nav>
	</div>
</header>

<main id="main" class="site-main" tabindex="-1">
