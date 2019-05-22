<?php 

/**
 * Template Name: ECI Public-facing home page
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Engagement_Council
 * @since 1.0
 * @version 1.0
 */
		
	get_header('eci'); ?>

<div class="eci-wrap contentclass" role="document">

	<?php
		//\Engagement_Council\ECI_Main\get_template_part( 'home', 'engagement_council' );
		get_template_part( 'home', 'engagement_council' );
		//\Engagement_Council\ECI_Main\get_template_part( 'home', 'filters-left' );
	
	?>
					
	
	
</div> <!-- /.eci-wrap -->
		
<?php get_footer('eci');