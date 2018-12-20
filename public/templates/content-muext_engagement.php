<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

// importing function from Public_Facing
use function MU_Ext_Engagement\Public_Facing\muext_comma_separate_tax as muext_comma_separate_tax;

// Set up post meta.
$post_id  = get_the_ID();

//older data = string in '_muext_location_text'; newer data = array (repeater field) in '_muext_location_group'
$location = get_post_meta( $post_id, '_muext_location_group', true );
if( empty( $location ) ){ //see if it's stored in the old location group format
	$location = get_post_meta( $post_id, '_muext_location_text', true );
}
	$contact = get_post_meta( $post_id, '_muext_contact_group', true );
if( empty( $contact ) ){ //see if it's stored in the old contact format
	$contact  = get_post_meta( $post_id, '_muext_contact_name', true );
}


$email    = get_post_meta( $post_id, '_muext_contact_email', true );
$phone    = get_post_meta( $post_id, '_muext_contact_phone', true );
$url      = get_post_meta( $post_id, '_muext_url', true );
$date     = get_post_meta( $post_id, '_muext_start_date', true );
$end_date = get_post_meta( $post_id, '_muext_end_date', true );
$website  = get_post_meta( $post_id, '_muext_url', true );

$affiliation_terms = wp_get_post_terms( $post_id, 'muext_program_affiliation' );

//get those affiliations into a comma-separated string
$this_aff_str = muext_comma_separate_tax( $affiliation_terms );


//outcomes, impact and audience section
$outcome  = apply_filters( 'the_content', get_post_meta( $post_id, '_muext_outcome_text', true ) );
$audience_terms = wp_get_post_terms( $post_id, 'muext_program_audience' );
$audience_str = muext_comma_separate_tax( $audience_terms );
$impact_terms = wp_get_post_terms( $post_id, 'muext_program_impact_area' );
$impact_str = muext_comma_separate_tax( $impact_terms );


// Convert dates that are stored in 2017-05-26 format to May 26, 2017 format for readability.
$human_date = ( $date ) ? \MU_Ext_Engagement\Public_Facing\convert_to_human_date( $date ) : '';
$human_end_date = ( $end_date ) ? \MU_Ext_Engagement\Public_Facing\convert_to_human_date( $end_date ) : '';

?>

<article id="post-<?php echo $post_id; ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
			if ( is_single() ) {
				the_title( '<h1 class="entry-title">', '</h1>' );
			} else {
				the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
			}
		?>
	</header><!-- .entry-header -->

	<?php if ( '' !== get_the_post_thumbnail() && ! is_single() ) : ?>
		<div class="post-thumbnail">
			<a href="<?php the_permalink(); ?>">
				<?php the_post_thumbnail(); ?>
			</a>
		</div><!-- .post-thumbnail -->
	<?php endif; ?>

	<div class="entry-content">
		<?php if ( $location || $human_date ) : ?>
			<div class="engagement-meta">

				<!--<div class="Grid Grid--full med-Grid--fit">-->
				<div>
					<?php if ( $location ) { ?>

						<div class="location Grid-cell">
							<div class="inset-contents-muext">

						<?php if ( is_array( $location ) ){

							foreach( $location as $one_place ){

									//var_dump( $one_place );
							?>
									<span class="fa fa-map-marker icon-left"></span>&nbsp;<span class=""><?php echo $one_place['_muext_location_text']; ?></span>
									<br />
							<?php }
						} else { ?>
									<span class="fa fa-map-marker icon-left"></span>&nbsp;<span class=""><?php echo $location; ?></span>
						<?php } ?>

							</div>
						</div>
					<?php } ?>

					<?php if ( $human_date ) : ?>
						<div class="date Grid-cell">
							<div class="inset-contents-muext">
								<span class="fa fa-calendar icon-left"></span>
								<span class="">
								<?php
									echo $human_date;
									if ( $human_end_date ) {
										echo '&ndash;' . $human_end_date;
									}
								?>
								</span>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="description">
			<h4>About</h4>
			<?php
				/* translators: %s: Name of current post */
				the_content( sprintf(
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'muext-engagement' ),
					get_the_title()
				) );

			?>
		</div>


		<?php if ( is_single() ) { ?>
			<div class="engagement-meta">
		<?php } else { ?>
			<div class="engagement-meta end-of-single">
		<?php } ?>
				<?php if ( $contact ) {

					if( is_array( $contact ) ){ ?>

						<div class="inset-contents-muext">
							<h4>Contacts</h4>
							<?php
							foreach( $contact as $one_contact ){ ?>

									<?php echo $one_contact["_muext_contact_name"]; ?>
										<?php if ( $one_contact["_muext_contact_email"] ) : ?>
											&emsp;<a href="mailto:<?php echo $one_contact["_muext_contact_email"]; ?>"><span class="fa fa-envelope"></span></a>
										<?php endif; ?>
										<?php if ( $one_contact["_muext_contact_phone"] ) : ?>
											&emsp;<a href="tel:<?php echo $phone; ?>"><?php echo $one_contact["_muext_contact_phone"]; ?></a>
										<?php endif; ?>
										<br />
							<?php } ?>
						</div>
					<?php
					} else { ?>
						<div class="inset-contents-muext">
							<h4>Contact</h4>
							<?php echo $contact; ?>
								<?php if ( $email ) : ?>
									&emsp;<a href="mailto:<?php echo $email; ?>"><span class="fa fa-envelope"></span></a>
								<?php endif; ?>
								<?php if ( $phone ) : ?>
									&emsp;<a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
								<?php endif; ?>
						</div>
					<?php } ?>

				<?php } //endif ?>

				<?php if ( $website ) { ?>
					<div class="inset-contents-muext">
						<h4>Website</h4>
						<?php echo '<a href="' . $website . '" title="' . get_the_title() . '">' . $website . '</a>'; ?>
					</div>
				<?php } ?>

				<?php if ( is_single() && $this_aff_str ) { ?>
					<div class="inset-contents-muext">
						<h4>Affiliation</h4>
						<?php echo $this_aff_str; ?>
					</div>
				<?php } ?>

			</div>

		<?php if ( is_single() ) : ?>
			<div class="engagement-meta end-of-single">
				<?php if( $outcome || $audience_str || $impact_str ){ ?>

					<?php if( $outcome ){ ?>
						<div class="inset-contents-muext">
							<h4>Outcomes</h4>
							<p><?php echo $outcome; ?> </p>
						</div>
					<?php } ?>

					<?php if( $audience_str ){ ?>
						<div class="inset-contents-muext">
							<h4>Audience</h4>
							<?php echo $audience_str; ?>
						</div>
					<?php } ?>

					<?php if( $impact_str ){ ?>
						<div class="inset-contents-muext">
							<h4>Impact</h4>
							<?php echo $impact_str; ?>
						</div>
					<?php } ?>

				<?php } ?>

			</div>
		<?php endif; ?>



		<?php
			wp_link_pages( array(
				'before'      => '<div class="page-links">' . __( 'Pages:', 'muext-engagement' ),
				'after'       => '</div>',
				'link_before' => '<span class="page-number">',
				'link_after'  => '</span>',
			) );
		?>

	</div><!-- .entry-content -->

	<?php if ( is_single() ) { ?>
		<footer class="entry-footer">
		<?php
			// Get Categories for posts.
			$categories_tax = get_taxonomy( 'muext_program_category' );
			$cat_list_label = $categories_tax->labels->name . ': ';
			$categories_list = get_the_term_list( $post_id, 'muext_program_category', $cat_list_label, ', ' );

			// Get Tags.
			$tags_tax = get_taxonomy( 'muext_program_tag' );
			$tag_list_label = $tags_tax->labels->name . ': ';
			$tags_list = get_the_term_list( $post_id, 'muext_program_tag', $tag_list_label, ', ' );

			// We don't want to output .entry-footer if it will be empty, so make sure its not.
			if ( ( $categories_list || $tags_list ) || get_edit_post_link() ) {

					if ( $categories_list || $tags_list ) {
						echo '<span class="cat-tags-links">';

							// Make sure there's more than one category before displaying.
							if ( $categories_list ) {
								echo '<span class="cat-links">' . twentyseventeen_get_svg( array( 'icon' => 'folder-open' ) ) . '<span class="screen-reader-text">' . $categories_tax->labels->name . '</span>' . $categories_list . '</span>';
							}

							if ( $tags_list ) {
								echo '<span class="tags-links">' . twentyseventeen_get_svg( array( 'icon' => 'hashtag' ) ) . '<span class="screen-reader-text">' . $tags_tax->labels->name . '</span>' . $tags_list . '</span>';
							}

						echo '</span>';
					}

					twentyseventeen_edit_link();
			}

		 ?>
		 </footer> <!-- .entry-footer -->
	<?php } ?>

</article><!-- #post-## -->
