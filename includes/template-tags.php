<?php
/**
 * Template functions for the plugin.
 *
 * @package   MUExt_Engagement
 * @author    dcavins
 * @license   GPL-2.0+
 * @link      http://www.communitycommons.org
 * @copyright 2017 CARES, University of Missouri
 */

/**
 * Render the filter box for the engagements archive.
 *
 * @since 1.0.0
 *
 * @return string
 */
function muext_render_filters_box() {
	// Get our post type's details.
	$eng_obj = get_post_type_object( 'muext_engagement' );
	// Find out which taxonomies are related.
	$all_taxonomies = get_object_taxonomies( 'muext_engagement' );

	//uninclude program tags
	$taxonomies = array_diff($all_taxonomies, array('muext_program_tag'));

	// Are there any search terms?
	$search_terms = ( ! empty( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
	$toggle_class = muext_archive_is_filtered_view() ? 'toggle-open' : 'toggle-closed';
	?>
	<div id="engagements-filters-container" class="muext-filters toggle-container <?php echo $toggle_class; ?>">
		<span class="arrow"></span><a href="#engagements-filters" id="engagements-filter-toggle" class="toggle-trigger">Filter <?php echo $eng_obj->labels->name; ?></a>
		<form id="engagements-filters" action="" method="GET" class="toggle-content engagements-filters clear">
			<!--
			<input id="search-form-engagements" class="search-field" placeholder="<?php echo $eng_obj->labels->search_items; ?>&hellip;" value="<?php echo $search_terms ?>" name="s" type="search">
			-->
			<div class="Grid Grid--full">
				<?php
				// Provide filters for related taxonomies
				foreach ( $taxonomies as $taxonomy) {

					// Get the taxonomy's details
					$tax_object = get_taxonomy( $taxonomy );
					$friendly_param = muext_get_friendly_filter_param_name( $taxonomy );
					?>
					<div class="Grid-cell Grid--full">

						<div class="inset-contents-muext">
							<fieldset class="taxonomy-terms toggle-container <?php echo $toggle_class; ?>">
								<!--<legend><?php echo $tax_object->labels->name; ?></legend>-->
								<!--<legend>-->
									<span class="arrow"></span>
									<a href="#filter-<?php echo $tax_object->name; ?>" id="filter-<?php echo $tax_object->name; ?>-toggle" class="toggle-trigger"><?php echo $tax_object->labels->name; ?>
									</a>
								<!--</legend>-->
								<?php
								// Get all the terms in the taxonomy, to build the checklist.
								$terms = get_terms( array(
									'taxonomy' => $taxonomy,
									'hide_empty' => false,
								) );
								// Get the active filter terms, if available.
								$selected_terms = muext_get_archive_filter_params( $taxonomy );
								?>

								<div id="filter-<?php echo $tax_object->name; ?>" class="toggle-content filter-<?php echo $tax_object->name; ?> clear">

									<?php
									foreach ( $terms as $term ) : ?>
										<label><input type="checkbox" value="<?php echo $term->slug; ?>" name="<?php echo $friendly_param; ?>[]" <?php
										if ( in_array( $term->slug, $selected_terms ) ) {
											echo 'checked="checked"';
										}
										?>> <?php echo $term->name; ?></label>
									<?php endforeach; ?>

								</div>

							</fieldset>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<input type="hidden" name="engagement_filter_active" value=1>
			<input type="submit" value="Filter">
		</form>
	</div>
	<?php
}
//add as action so we can use in our theme
add_action( 'call_muext_render_filters_box', 'muext_render_filters_box' );

function muext_import_shortcode(){
	ob_start();
	echo '<pre>';
	$taxonomy = 'muext_program_affiliation';
	// Top-level terms.
	// $terms = array(
	// 	'Athletics' => 'ATHL',
	// 	'College of Agriculture, Food and Natural Resources' => 'CAFNR',
	// 	'College of Arts and Science' => 'AS',
	// 	'College of Education' => 'EDUC',
	// 	'College of Engineering' => 'ENGR',
	// 	'College of Human Environmental Sciences' => 'HES',
	// 	'College of Veterinary Medicine' => 'VETM',
	// 	'Graduate School' => 'GRAD',
	// 	'Marketing & Communications' => 'COMM',
	// 	'Office of the Chancellor' => 'CHANC',
	// 	'Office of the Provost' => 'PROVOST',
	// 	'School of Health Professions' => 'HP',
	// 	'School of Journalism' => 'JOURN',
	// 	'School of Law' => 'LAW',
	// 	'School of Medicine' => 'MED',
	// 	'School of Public Affairs' => 'PUBAF',
	// 	'Sinclair School of Nursing' => 'NURS',
	// 	'Student Affairs' => 'STUDENT',
	// 	'Trulaske Collee of Business' => 'BUS',
	// 	'University Advancement' => 'ADVANCE',
	// 	'University Operations' => 'OPER'
	// );

	// foreach ( $terms as $name => $slug ) {
	// 	$term_id = wp_insert_term( $name, $taxonomy, $args = array( 'slug' => $slug ) );
	// 	echo "{$term_id} {$name}: {$slug}" . PHP_EOL;
	// }

	// Child terms

	// $terms = array(
	// 	"cafnr" => array( "Agribusiness Management", "Agricultural Economics", "Agricultural Education", "Agricultural Systems Management", "Agriculture", "Animal Sciences", "Baskett Research Center", "Biochemistry", "Bradford Research Center", "Captive Wild Animal Management", "Division of Applied Social Sciences", "Entrepreneurship", "Environmental Sciences", "Fisher Delta Research Center", "Food Science & Nutrition", "Food Systems and Bioengineering", "Forage Systems Research Center", "Foremost Dairy Research Center", "Forestry", "Graves-Chapple Research Center", "Greenley Research Center", "Horticulture and Agroforestry Research Center", "Hospitality Management", "Hundley-Whaley Research Center", "International Agriculture, Food & Natural Resources", "Jefferson Farm and Garden", "Natural Resource Science and Management", "Parks, Recreation & Sport", "Plant Sciences", "Rural Sociology", "School of Natural Resources", "Science & Agricultural Journalism", "Soil, Environmental and Atmospheric Sciences", "South Farm Research Center", "Southwest Research Center", "Sustainable Agriculture", "Thompson Research Center", "Viticulture & Enology", "Wurdack Research Center" ),
	// 	"as" => array( "Aerospace Studies", "American Archaeology", "Anthropology", "Art History and Archaeology", "Biological Sciences", "Black Studies", "Canadian Studies", "Chemistry", "Chinese Studies", "Classical Studies", "Communication", "Digital Storytelling", "East Asian Studies", "Economics", "English", "Geography", "Geological Sciences", "German and Russian Studies", "History", "Mathematics", "Military Science and Leadership", "Music", "Philosophy", "Physics and Astronomy", "Political Science", "Psychological Sciences", "Religious Studies", "Romance Languages", "Sociology", "Statistics", "Theatre", "Women\'s and Gender Studies" ),
	// 	"educ" => array( "Adventure Club", "Assessment Resource Center (ARC)", "Educational Leadership & Policy Analysis", "Educational, School & Counseling Psychology", "Hook Center for Educational Leadership and District Renewal", "Information Science & Learning Technologies", "Learning, Teaching & Curriculum", "Missouri Prevention Center (MPC)", "Mizzou K-12", "ParentLink", "Positive Behavioral Interventions and Supports (PBIS)", "Special Education", "The ReSTEM Institute" ),
	// 	"engr" => array( "Bioengineering", "Chemical Engineering", "Civil & Environmental Engineering", "Computer Science", "Electrical & Computer Engineering", "Information Technology (IT)", "International Coordinated Degree Programs", "Mechanical & Aerospace Engineering", "MU Informatics Institute", "Naval Sciences", "Nuclear Engineering Program" ),
	// 	"hes" => array( "Architectural Studies", "Center for Children and Families Across Cultures", "Center for Family Policy and Research", "Center for Relationship and Family Resilience", "Human Development & Family Science", "Institute for Professional Development", "MU Child Development Lab", "Nutrition & Exercise Physiology", "Personal Financial Planning", "School of Social Work", "Textile & Apparel Management" ),
	// 	"vetm" => array( "Veterinary Health Center", "Veterinary Medical Diagnostic Laboratory (VMDL)" ),
	// 	"hp" => array( "Clinical and Diagnostic Sciences", "Communication Science and Disorders", "Health Psychology", "Health Sciences", "Occupational Therapy", "Physical Therapy " ),
	// 	"journ" => array( "Reynolds Journalism Institute" ),
	// 	"law" => array( "Center for Intellectual Property & Entrepreneurship", "Center for the Study of Dispute Resolution" ),
	// 	"med" => array( "Anesthesiology and Perioperative Medicine", "Biochemistry", "Center for Health Care Quality", "Center for Health Ethics", "Center for Health Policy", "Center for Micro/Nano Systems and Nanotechnology", "Center for Patient-Centered Outcomes Research", "Center for Precision Medicine", "Center for Translational Neuroscience", "Child Health", "Christopher S. Bond Life Sciences Center", "Clinical Research Center", "Dalton Cardiovascular Research Center", "Dermatology", "Electron Microscopy Core Facility", "Ellis Fischel Cancer Center", "Emergency Medicine", "Family and Community Medicine", "Health and Behavioral Risk Research Center", "Health Management and Informatics", "Interdisciplinary Center on Aging", "International Institute of Nano and Molecular Medicine", "Mason Eye Institute", "Medical Pharmacology & Physiology", "Medicine", "Missouri Cancer Registry", "Molecular Microbiology and Immunology", "MU Coulter Translational Partnership Program", "MU Institute for Clinical and Translational Science", "MU Research Reactor", "National Center for Gender Physiology", "Neurology", "Nuclear Science and Engineering Institute", "Nutrition & Exercise Physiology", "Obstetrics, Gynecology and Women\'s Health", "OneHealth BioRepository", "Ophthalmology", "Orthopaedic Surgery", "Otolaryngology", "Pathology and Anatomical Sciences", "Physical Medicine and Rehabilitation", "Proteomics Center", "Psychiatry", "Radiology", "Radiopharmaceutical Sciences Research Institute", "Structural Biology Core Facility", "Surgery", "Thompson Center for Autism & Neurodevelopmental Disorders", "Thompson Laboratory for Regenerative Orthopaedics" ),
	// 	"pubaf" => array( "Institute of Public Policy" ),
	// 	"bus" => array( "Accountancy", "Center for Sales and Customer Development", "Entrepreneurship Bootcamp for Veterans with Disabilities", "Finance", "Financial Research Institute", "International Trade Center", "Jeffrey E. Smith Institute of Real Estate", "Management", "Marketing" )
	// );
	// foreach ( $terms as $parent => $children ) {
	// 	$parent_term = get_term_by( 'slug', $parent, $taxonomy );

	// 	foreach ( $children as $child_name ) {
	// 		$new_term_id = wp_insert_term( $child_name, $taxonomy, array( 'parent' => $parent_term->term_id ) );
	// 		if ( is_wp_error( $new_term_id ) ) {
	// 			echo "{$child_name} errored";
	// 			var_dump($new_term_id);
	// 		} else {
	// 			echo $parent_term->term_id . " : {$child_name} : {$new_term_id}" . PHP_EOL;
	// 		}
	// 	}
	// }

	echo '</pre>';

	return ob_get_clean();
}
add_shortcode( 'muext_importer', 'muext_import_shortcode' );
