<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @package Twenty Seventeen Child Theme
 * @since 1.0
 * @version 1.0
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'twentyseventeen' ); ?></a>



<header id="engagement-council-header" class="banner headerclass eci-header" itemscope itemtype="http://schema.org/WPHeader">


	<div id="topbar" class="topclass">

		<?php if( current_user_can('edit_posts' ) && has_nav_menu( 'top-logged-in' ) ) { ?>

			<div class="navigation-top">
				<div class="">
					<?php get_template_part( 'template-parts/navigation/navigation', 'top-logged-in' ); ?>
				</div><!-- .wrap -->
			</div><!-- .navigation-top -->

		<?php
		} else if ( has_nav_menu( 'top' ) ) { ?>
			<div class="navigation-top">
				<div class="">
					<?php get_template_part( 'template-parts/navigation/navigation', 'top' ); ?>
				</div><!-- .wrap -->
			</div><!-- .navigation-top -->
		<?php } ?>

	</div>


	<link href="https://fonts.googleapis.com/css?family=Lato|Libre+Franklin|Merriweather+Sans|Merriweather" rel="stylesheet">

	<?php
		$logocclass = 'col-xs-12';
		$menulclass = 'hidden';
		?>
	<div class="fullpage-container engagement-header hidden-xs">
		<div class="row">
			<div class="<?php echo esc_attr($logocclass); ?> clearfix kad-header-left">
				<div id="logo" class="logocase col-xs-12">
					<div class="menu-logo">
						<div class="hamburger float-left">
							<i class="fa fa-bars fa-3x"></i>
						</div>
						<a class="brand logofont " href="<?php echo home_url(); ?>/">
						<?php
							echo "<img class='' src=" . ECPP_MU_LOGO_HEADER . " title='mu stacked logo' >";
						?>
						</a>
					</div>
					<div class="title-tagline">
						<h1>University FOR Missouri</h1>
						<h4>How is the University of Missouri Engaging with My Community?</h4>
					</div>
				</div> <!-- Close #logo -->
		   </div><!-- close logo span -->
		</div> <!-- Close Row -->
	</div> <!-- Close Container -->

	<!-- small device header view -->
	<div class="fullpage-container engagement-header-xs hidden-md hidden-lg hidden-sm">
		<div class="row">
			<div class="<?php echo esc_attr($logocclass); ?> ">
				<div id="logo" class="logocase col-xs-12">
					<div class="menu-logo">
						<div class="hamburger float-left">
							<i class="fa fa-bars fa-3x"></i>
						</div>
						<a class="brand logofont " href="<?php echo home_url(); ?>/">
						<?php
							echo "<img class='' src=" . ECPP_MU_LOGO_HEADER . " title='mu stacked logo' >";
						?>
						</a>
					</div>
					<div class="title-tagline">
						<h1>University FOR Missouri</h1>
						<h4>How Is the University of Missouri Engaging with My Community?</h4>
					</div>
				</div> <!-- Close #logo -->
		   </div><!-- close logo span -->
		</div> <!-- Close Row -->
	</div> <!-- Close Container -->

</header>
