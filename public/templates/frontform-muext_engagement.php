<?php
/**
 * The template for displaying fron end Add Engagement form
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php
			
			//start the form
			if current_user_can( 'edit_post' ){
				echo 'Yes, you can!';
				
				
			} else {
				echo 'You are not allowed to do this thing.'
				
			}
			
			
			
			
			?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
