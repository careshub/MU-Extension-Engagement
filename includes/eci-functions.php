<?php
/**
 * Utility functions for the plugin.
 *
 * @package   Engagement_Council
 * @author    CARES
 * @license   GPL-2.0+
 * @link      http://www.communitycommons.org
 * @copyright 2017 CARES, University of Missouri
 */

$base_path = plugin_dir_path( __FILE__ );
$base_uri = trailingslashit( get_stylesheet_directory_uri() );

 function get_ecpp(){
 	 return array (
			'County'					=>	array(
				'id' => 'ecpp_county'
			),
			'School District'			=> array(
				'id' => 'ecpp_school'
			),
			'Senate District'			=> array(
				'id' => 'ecpp_senate'
			),
			'House District'			=> array(
				'id' => 'ecpp_house'
			),
			'Congressional District'	=> array(
				'id' => 'ecpp_congressional'
			)
		);
 }

 function get_eci_theme(){
 	 return array(
	 		'Agriculture and Environment',
			'Arts and Culture',
			'Business and Community',
			'Health and Safety',
			'Science and Technology',
			'Youth and Family'
		);
 }

 /**
 * Grab latest post title by an author!
 *
 * @param array $data Options for the function.
 * @return string|null Post title for the latest,  * or null if none.
 */
 function get_eci( $data ) {

	  //$posts = get_posts( array(
	 //	"geoid" => $data["geoid"]
	  //) );
	$posts = get_posts();
	if ( empty( $posts ) ) {
		return null;
	}else{
		$eci = array();
		foreach($posts as $item){
			array_push($eci, array(
				"title" => $item->post_title,
				"thumbnail" => $item->thumbnail,
				"theme" => $item->theme,
				"type" => $item->type,
				"affiliation" => $item->affiliation
			));
		}
		return $eci;
	}
 }

 function get_eci_geoid (){
 	$posts = get_posts();
	  if ( empty( $posts ) ) {
		return null;
	  }else{
		$eci = array();
		foreach($posts as $item){
			array_push($eci, array(
				"title" => $item->post_title,
				"thumbnail" => $item->thumbnail,
				"theme" => $item->theme,
				"type" => $item->type,
				"affiliation" => $item->affiliation
			));
		}
		return $eci;
	  }
 }