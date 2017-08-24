<?php
/**
 * The template for displaying the home page
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header( 'muext' ); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php
				while ( have_posts() ) : the_post();
				the_content(  );
				endwhile; // End of the loop.
				
				//bring in the latest post only
				$args = array(
					'post_type' 		=> 'muext_engagement',//(int) - use category id.
					'posts_per_page'	=> 1,
					'order'   			=> 'DESC'
				);    
				$latest_post = new WP_Query( $args );

				if ( $latest_post->have_posts() ) :
				
					//display the latest post
					while ( $latest_post->have_posts() ) : $latest_post->the_post();
				
						\MU_Ext_Engagement\Public_Facing\get_template_part( 'content', get_post_type() );
					endwhile;

				endif;
				
			?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
