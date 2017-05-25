<?php
/**
 * The template for displaying archive pages
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<div class="wrap">

	<?php if ( have_posts() ) : ?>
		<header class="page-header">
			<?php
				// the_archive_title( '<h1 class="page-title">', '</h1>' );
				// the_archive_description( '<div class="taxonomy-description">', '</div>' );
				if ( \MU_Ext_Engagement\Public_Facing\is_engagement_tax_archive() ) {
					$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
					$queried_tax = get_taxonomy( $term->taxonomy );
					?>
					<h1 class="page-title"><a href="<?php echo get_post_type_archive_link( 'muext_engagement' ); ?>">Engagements</a></h1>
					<h2 class="page-title"><?php echo $queried_tax->labels->name; ?>: <?php echo $term->name; ?></h2>
					<?php
				} else {
					?>
					<h1 class="page-title">Engagements</h1>
					<?php
				}
			?>
		</header><!-- .page-header -->
	<?php endif; ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		if ( have_posts() ) : ?>
			<?php
			/* Start the Loop */
			while ( have_posts() ) : the_post();

				\MU_Ext_Engagement\Public_Facing\get_template_part( 'content', get_post_type() );

			endwhile;

			the_posts_pagination( array(
				'prev_text' => twentyseventeen_get_svg( array( 'icon' => 'arrow-left' ) ) . '<span class="screen-reader-text">' . __( 'Previous page', 'muext-engagement' ) . '</span>',
				'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'muext-engagement' ) . '</span>' . twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'muext-engagement' ) . ' </span>',
			) );

		else :

			get_template_part( 'content', 'none' );

		endif; ?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
