<?php
/**
 * REST API: WP_REST_Posts_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Core class to access posts via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_Engagements_Controller extends WP_REST_Posts_Controller {

	/**
	 * We're overwriting the standard Posts controller because we need to do 
	 * non-standard ordering that will require multiple queries.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		// If we don't need to use our special logic, just use the parent method.
		if ( empty( $request['filter']['muext_geoid'] ) ) {
			return parent::get_items( $request );
		}

		// Ensure a search string is set in case the orderby is set to 'relevance'.
		if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {
			return new WP_Error( 'rest_no_search_term_defined', __( 'You need to define a search term to order by relevance.' ), array( 'status' => 400 ) );
		}

		// Ensure an include parameter is set in case the orderby is set to 'include'.
		if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {
			return new WP_Error( 'rest_orderby_include_missing_include', __( 'You need to define an include parameter to order by include.' ), array( 'status' => 400 ) );
		}

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args = array();

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'author'         => 'author__in',
			'author_exclude' => 'author__not_in',
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'menu_order'     => 'menu_order',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'parent'         => 'post_parent__in',
			'parent_exclude' => 'post_parent__not_in',
			'search'         => 's',
			'slug'           => 'post_name__in',
			'status'         => 'post_status',
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Check for & assign any parameters which require special handling or setting.
		$args['date_query'] = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['before'], $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['after'], $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

		if ( isset( $registered['sticky'], $request['sticky'] ) ) {
			$sticky_posts = get_option( 'sticky_posts', array() );
			if ( ! is_array( $sticky_posts ) ) {
				$sticky_posts = array();
			}
			if ( $request['sticky'] ) {
				/*
				 * As post__in will be used to only get sticky posts,
				 * we have to support the case where post__in was already
				 * specified.
				 */
				$args['post__in'] = $args['post__in'] ? array_intersect( $sticky_posts, $args['post__in'] ) : $sticky_posts;

				/*
				 * If we intersected, but there are no post ids in common,
				 * WP_Query won't return "no posts" for post__in = array()
				 * so we have to fake it a bit.
				 */
				if ( ! $args['post__in'] ) {
					$args['post__in'] = array( 0 );
				}
			} elseif ( $sticky_posts ) {
				/*
				 * As post___not_in will be used to only get posts that
				 * are not sticky, we have to support the case where post__not_in
				 * was already specified.
				 */
				$args['post__not_in'] = array_merge( $args['post__not_in'], $sticky_posts );
			}
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filters the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post collection request.
		 *
		 * @since 4.7.0
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_query/
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( "rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
			$tax_exclude = $base . '_exclude';

			if ( ! empty( $request[ $base ] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $base ],
					'include_children' => false,
				);
			}

			if ( ! empty( $request[ $tax_exclude ] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $tax_exclude ],
					'include_children' => false,
					'operator'         => 'NOT IN',
				);
			}
		}

		/* Approach: fetch the IDs of posts in _this_ geoid.
		 * then, find the IDs of posts in _related_ geoids.
		 * Then, apply pagination to the array, and fetch the requested posts.
		 */  

		// First we find the engagements that apply to the selected geoid.
		// How many posts were requested? (-1 could mean all)
		$num_results = $query_args['posts_per_page'] > 0 ? $query_args['posts_per_page'] : 100;
		$page        = (int) $query_args['paged'];
		// Blow up the per_page and paged arguments, because we'll apply pagination later.
		$query_args['posts_per_page'] = -1;
		unset( $query_args['paged'] );
		// For efficiency, we only want the post IDs. We'll fetch the details later.
		$query_args['fields'] = 'ids';

		// Fetch an array of IDs that match the query.
		$geoid_query   = new WP_Query();
		$geoid_results = $geoid_query->query( $query_args );

		// Next, find engagements in "related" geoids.
		// Make sure to exclude posts found in the specific query.
		if ( ! empty( $geoid_results ) ) {
			$query_args['post__not_in'] = array_merge( $query_args['post__not_in'], $geoid_results );
		}
		// Expand to find contained, containing and overlapping geoids.
		if ( is_array( $args['muext_geoid'] ) ) {
			$args['muext_geoid'] = implode( ',', $args['muext_geoid'] );
		}

		$api_url = add_query_arg( array(
			'geoid' => trim( $args['muext_geoid'] ),
		), 'https://services.engagementnetwork.org/api-extension/v1/eci-geoid-list' );
		$response = wp_remote_get( $api_url );

		$related_geoid_result = array();
		// Only continue if we've received a successful response.
		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$query_args['muext_geoid'] = json_decode( wp_remote_retrieve_body( $response ) );
			$related_geoid_query   = new WP_Query();
			$related_geoid_results = $related_geoid_query->query( $query_args );
		}

		$found_posts = array_merge( $geoid_results, $related_geoid_results );
		$total_posts = count( $found_posts ); 

		// Now, we apply pagination to our array of found post IDs.
		$paged_posts = array_slice( $found_posts, $num_results * ( $page - 1 ), $num_results );

		// Fetch the details of the requested posts.
		$posts = array();
		if ( ! empty( $paged_posts ) ) {
			// We'll fetch them by the post ID.
			$contents_query_args = array(
				'post__in'       => $paged_posts,
				'orderby'        => 'post__in', // Keep them in the order set in the query
				'post_type'      => 'muext_engagement',
				'posts_per_page' => -1
			);

			$contents_query   = new WP_Query();
			$contents_results = $contents_query->query( $contents_query_args );
			
			// Allow access to all password protected posts if the context is edit.
			if ( 'edit' === $request['context'] ) {
				add_filter( 'post_password_required', '__return_false' );
			}

			foreach ( $contents_results as $post ) {
				if ( ! $this->check_read_permission( $post ) ) {
					continue;
				}

				$data    = $this->prepare_item_for_response( $post, $request );
				$posts[] = $this->prepare_response_for_collection( $data );
			}

			// Reset filter.
			if ( 'edit' === $request['context'] ) {
				remove_filter( 'post_password_required', '__return_false' );
			}
		}

		$max_pages = ceil( $total_posts / $num_results );

		if ( $page > $max_pages && $total_posts > 0 ) {
			return new WP_Error( 'rest_post_invalid_page_number', __( 'The page number requested is larger than the number of pages available.' ), array( 'status' => 400 ) );
		}

		$response  = rest_ensure_response( $posts );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$base = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}
}
