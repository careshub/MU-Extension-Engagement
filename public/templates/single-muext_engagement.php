<?php
/**
 * The template for displaying all single posts
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
				/* Start the Loop */
				while ( have_posts() ) : the_post();

					\MU_Ext_Engagement\Public_Facing\get_template_part( 'content', get_post_type() );

					// If comments are open or we have at least one comment, load up the comment template.
					if ( comments_open() || get_comments_number() ) :
						comments_template();
					endif;
					
					$coauthor_id = get_post_meta( $post->ID, '_muext_coauthor', true );
					
					echo "<div class='hidden' id='coauthor_id'>" . $coauthor_id . "</div>";
					echo "<div class='hidden' id='currentuser_id'>" . get_current_user_id() . "</div>";
					
					//make cmb form editable on post if current user is author OR coauthor
					if (is_user_logged_in() && ( get_current_user_id() == $post->post_author || get_current_user_id() == $coauthor_id ) ) {
					?>	
					
						<div id="edit-cmb2">
							<div id="edit-cmb2-button">
								<button id="edit-button" class="button">Edit this Engagement</button>
							</div>
						
							<div id="edit-engagement-section" class="hidden">
							<?php
							
								//get the shortcode
								$shortcode_str = '[cmb-frontend post_id=' . $post->ID . ']';
								echo do_shortcode( $shortcode_str );
							?>
							</div>
							
						</div>
					
					<?php
					}

					the_post_navigation( array(
						'prev_text' => '<span class="screen-reader-text">' . __( 'Previous Post', 'muext-engagement' ) . '</span><span aria-hidden="true" class="nav-subtitle">' . __( 'Previous', 'muext-engagement' ) . '</span> <span class="nav-title"><span class="nav-title-icon-wrapper">' . twentyseventeen_get_svg( array( 'icon' => 'arrow-left' ) ) . '</span>%title</span>',
						'next_text' => '<span class="screen-reader-text">' . __( 'Next Post', 'muext-engagement' ) . '</span><span aria-hidden="true" class="nav-subtitle">' . __( 'Next', 'muext-engagement' ) . '</span> <span class="nav-title">%title<span class="nav-title-icon-wrapper">' . twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ) . '</span></span>',
					) );

				endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
