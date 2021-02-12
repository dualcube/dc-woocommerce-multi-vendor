<?php
/**
 * REST API Vendor Reviews Controller
 *
 * Handles requests to /vendors/<vendor_id>/reviews.
 *
 * @author   WC Marketplace
 * @category API
 * @package  WCMp/API
 * @since    3.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Vendor Reviews Controller Class.
 *
 * @package WCMp/API
 * @extends WC_REST_Controller
 */
class WCMp_REST_API_Vendor_Reviews_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wcmp/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'vendors/(?P<vendor_id>[\d]+)/reviews';

	/**
	 * Register the routes for vendor reviews.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'args' => array(
				'vendor_id' => array(
					'description' => __( 'Unique identifier for the vendor.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'integer',
				),
				'id' => array(
					'description' => __( 'Unique vendor review id.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'review' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Review content.', 'dc-woocommerce-multi-vendor' ),
					),
					'email' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Email of the reviewer.', 'dc-woocommerce-multi-vendor' ),
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args' => array(
				'vendor_id' => array(
					'description' => __( 'Unique identifier for the vendor.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'integer',
				),
				'id' => array(
					'description' => __( 'Unique vendor review.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'type'        => 'boolean',
						'description' => __( 'Whether to bypass trash and force deletion.', 'dc-woocommerce-multi-vendor' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to read webhook deliveries.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'dc-woocommerce-multi-vendor' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read a vendor review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'dc-woocommerce-multi-vendor' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to create a new vendor review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'dc-woocommerce-multi-vendor' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to update a vendor review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'dc-woocommerce-multi-vendor' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to delete a vendor review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_cannot_edit', __( 'Sorry, you cannot delete this resource.', 'dc-woocommerce-multi-vendor' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Get all reviews from a vendor.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 * Resoponse :
	 	"id"
    	"rating_count"
    	"avg_rating"
    	"reviews_list"
            
	 *  Method : post
	 *  url :
	 	[site_url]/wp-json/wcmp/v1/vendors/[vendor_id]/reviews
	 */
	public function get_items( $request ) {

		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;

		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_vendor_invalid_id', __( 'Invalid Vendor ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}

		$vendor_term_id = get_user_meta( $request['vendor_id'], '_vendor_term_id', true );
    	$vendor_review_info = wcmp_get_vendor_review_info($vendor_term_id);
    	$avg_rating = number_format(floatval($vendor_review_info['avg_rating']), 1);
    	$rating_count = $vendor_review_info['total_rating'];

		// find vendor review
	    $args_default = array(
	      'status' => 'approve',
	      'type' => 'wcmp_vendor_rating',
	      'meta_key' => 'vendor_rating_id',
	      'meta_value' => $vendor_id,
	      'meta_query' => array(
	        'relation' => 'AND',
	        array(
	          'key' => 'vendor_rating_id',
	          'value' => $vendor_id
	          ),
	        array(
	          'key' => 'vendor_rating',
	          'value' => '',
	          'compare' => '!='
	          )
	        )
	      );

	    $args = apply_filters('wcmp_vendor_review_rating_args_to_fetch', $args_default);
	    $reviews = get_comments($args);

	    $data['id'] = $vendor_id;
	   	$data['rating_count'] = $rating_count;
	    $data['avg_rating'] = $avg_rating;

		foreach ( $reviews as $review_data ) {
			$review = $this->prepare_item_for_response( $review_data, $request );
			$review = $this->prepare_response_for_collection( $review );
			$data['reviews_list'][] = $review;
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Get a single vendor review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 * Response: Get details of <id> review 
		"id"
		"review_content"
		"review_rating"
		"reviewer_id"
		"reviewer_name"
		"reviewer_email"
		"reviewer_verified"
		"date_created"
		...
		
	 *  Method : post
	 *  url :
	 	[site_url]/wp-json/wcmp/v1/vendors/[vendor_id]/reviews/[review_id]
	 */
	public function get_item( $request ) {
		$id         = isset($request['id']) ? (int) $request['id'] : 0;
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		//return if not a vendor
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_vendor_invalid_id', __( 'Invalid Vendor ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}
		$review = get_comment( $id );
		$comment_vendor_id = get_comment_meta($id, 'vendor_rating_id', true);

		if ( empty( $id ) || empty( $review ) || intval( $comment_vendor_id ) !== $vendor_id ) {
			return new WP_Error( 'wcmp_rest_invalid_id', __( 'Invalid resource ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}

		$delivery = $this->prepare_item_for_response( $review, $request );
		$response = rest_ensure_response( $delivery );

		return $response;
	}


	/**
	 * Create a vendor review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 *  Method : post
	 *	Request Example :
	 	{
			"name": abc
			"email": abc@gmail.com
			"user_url": www.abc.com
			"review": "Nice album!",
			"user_id": "32",
			"rating": "2"
		}
	 *  url :
	 	[site_url]/wp-json/wcmp/v1/vendors/[vendor_id]/reviews
	 */
	public function create_item( $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_vendor_invalid_id', __( 'Invalid Vendor ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}

		$userdata = false;
		if ( !empty( $request['email'] ) ) {
			$userdata = get_user_by('email', $request['email']);
		} elseif ( !empty( $request['user_id'] ) ) {
			$userdata = get_user_by("ID", $request['user_id']);
		} else {
			return new WP_Error( 'wcmp_rest_empty_user', __( 'Email or user Id must required.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}
		if($userdata === false) {
			return new WP_Error( 'wcmp_rest_user_not_exists', __( 'User email / UserId does not exists.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		} elseif (!empty( $request['user_id'] ) && $request['user_id'] != $userdata->ID) {
			return new WP_Error( 'wcmp_rest_validate_user_with_email', __( 'User Id not match with email.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}

		$prepared_review = $this->prepare_item_for_database( $request );

		/**
		 * Filter a vendor review (comment) before it is inserted via the REST API.
		 *
		 * Allows modification of the comment right before it is inserted via `wp_insert_comment`.
		 *
		 * @param array           $prepared_review The prepared comment data for `wp_insert_comment`.
		 * @param WP_REST_Request $request          Request used to insert the comment.
		 */
		$prepared_review = apply_filters( 'rest_pre_insert_vendor_review', $prepared_review, $request );

		$vendor_review_id = wp_insert_comment( $prepared_review );
		if ( ! $vendor_review_id ) {
			return new WP_Error( 'rest_vendor_review_failed_create', __( 'Creating vendor review failed.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 500 ) );
		}

		update_comment_meta( $vendor_review_id, 'vendor_rating', ( ! empty( $request['vendor_rating'] ) ? $request['vendor_rating'] : '0' ) );

		if ($request['rating'] && !empty($request['rating'])) {
			update_comment_meta($vendor_review_id, 'vendor_rating', $request['rating']);
		}
		update_comment_meta($vendor_review_id, 'vendor_rating_id', $vendor_id);


		$vendor_review = get_comment( $vendor_review_id );
		$this->update_additional_fields_for_object( $vendor_review, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Comment      $vendor_review Inserted object.
		 * @param WP_REST_Request $request        Request object.
		 * @param boolean         $creating       True when creating item, false when updating.
		 */
		do_action( "wcmp_rest_insert_vendor_review", $vendor_review, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $vendor_review, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$base = str_replace( '(?P<vendor_id>[\d]+)', $vendor_id, $this->rest_base );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $vendor_review_id ) ) );

		return $response;
	}

	/**
	 * Update a single vendor review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 * user_url,name,user_id,email will not be updated
	 * Method : post
	 * Request post Example :
	 	{
			"review": "Nice album!",
			"rating": "2"
		}
	 *  url :
	 	[site_url]/wp-json/wcmp/v1/vendors/[vendor_id]/reviews/[review_id]
	 */
	public function update_item( $request ) {
		$vendor_review_id = isset($request['id']) ? (int) $request['id'] : 0;
		$vendor_id        = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;

		if ( !is_user_wcmp_vendor( $vendor_id ) ) {
			return new WP_Error( 'wcmp_rest_vendor_invalid_id', __( 'Invalid vendor ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}
		
		if ( !empty( $request['user_url'] ) || !empty( $request['name'] ) || !empty( $request['user_id'] ) || !empty( $request['email'] ) ) {
			return new WP_Error( 'wcmp_rest_vendor_review_update', __( 'You can not change url,name,user id and email ', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}


		$review = get_comment( $vendor_review_id );
		$comment_vendor_id = get_comment_meta($vendor_review_id, 'vendor_rating_id', true);

		if ( empty( $vendor_review_id ) || empty( $review ) || intval( $comment_vendor_id ) !== $vendor_id ) {
			return new WP_Error( 'wcmp_rest_vendor_review_invalid_id', __( 'Invalid resource ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}

		$prepared_review = $this->prepare_item_for_database( $request );

		$updated = wp_update_comment( $prepared_review );
		if ( 0 === $updated ) {
			return new WP_Error( 'rest_vendor_review_failed_edit', __( 'Updating vendor review failed.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 500 ) );
		}

		if ($request['rating'] && !empty($request['rating'])) {
			update_comment_meta($vendor_review_id, 'vendor_rating', $request['rating']);
		}
		update_comment_meta($vendor_review_id, 'vendor_rating_id', $vendor_id);

		$vendor_review = get_comment( $vendor_review_id );
		$this->update_additional_fields_for_object( $vendor_review, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Comment         $comment      Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "wcmp_rest_insert_vendor_review", $vendor_review, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $vendor_review, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Delete a vendor review.
	 *
	 * @param WP_REST_Request $request Full details about the request
	 *
	 * @return bool|WP_Error|WP_REST_Response
	 * Method : delete
	 */
	public function delete_item( $request ) {
		$vendor_review_id = absint( is_array( $request['id'] ) ? $request['id']['id'] : $request['id'] );
		$force             = isset( $request['force'] ) ? (bool) $request['force']     : false;

		$vendor_review = get_comment( $vendor_review_id );
		if ( empty( $vendor_review_id ) || empty( $vendor_review->comment_ID ) || empty( $vendor_review->comment_post_ID ) ) {
			return new WP_Error( 'wcmp_rest_vendor_review_invalid_id', __( 'Invalid vendor review ID.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 404 ) );
		}

		/**
		 * Filter whether a vendor review is trashable.
		 *
		 * Return false to disable trash support for the vendor review.
		 *
		 * @param boolean $supports_trash        Whether the object supports trashing.
		 * @param WCMp vendors $vendor_review        The object being considered for trashing support.
		 */
		$supports_trash = apply_filters( 'rest_vendor_review_trashable', ( EMPTY_TRASH_DAYS > 0 ), $vendor_review );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $vendor_review, $request );

		if ( $force ) {
			$result = wp_delete_comment( $vendor_review_id, true );
		} else {
			if ( ! $supports_trash ) {
				return new WP_Error( 'rest_trash_not_supported', __( 'The vendor review does not support trashing.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 501 ) );
			}

			if ( 'trash' === $vendor_review->comment_approved ) {
				return new WP_Error( 'rest_already_trashed', __( 'The comment has already been trashed.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 410 ) );
			}

			$result = wp_trash_comment( $vendor_review->comment_ID );
		}

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The vendor review cannot be deleted.', 'dc-woocommerce-multi-vendor' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a vendor review is deleted via the REST API.
		 *
		 * @param object           $vendor_review  The deleted item.
		 * @param WP_REST_Response $response        The response data.
		 * @param WP_REST_Request  $request         The request sent to the API.
		 */
		do_action( 'rest_delete_vendor_review', $vendor_review, $response, $request );

		return $response;
	}

	/**
	 * Prepare a single vendor review output for response.
	 *
	 * @param WP_Comment $review vendor review object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $review, $request ) {

		$data = array(
			'id'           		=> (int) $review->comment_ID,
			'review_content'    => $review->comment_content,
			'review_rating'     => (int) get_comment_meta( $review->comment_ID, 'vendor_rating', true ),
			'reviewer_id'       => $review->user_id,
			'reviewer_name'     => $review->comment_author,
			'reviewer_email'    => $review->comment_author_email,
			'reviewer_verified' => wc_review_is_from_verified_owner( $review->comment_ID ),
			'date_created' 		=> wc_rest_prepare_date_response( $review->comment_date_gmt ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $review, $request ) );

		/**
		 * Filter vendor reviews object returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Comment       $review   vendor review object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'wcmp_rest_prepare_vendor_review', $response, $review, $request );
	}

	/**
	 * Prepare a single vendor review to be inserted into the database.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return array|WP_Error  $prepared_review
	 */
	protected function prepare_item_for_database( $request ) {
		$comment_approve_by_settings = get_option('comment_moderation') ? 0 : 1;
		$prepared_review = array( 'comment_approved' => $comment_approve_by_settings, 'comment_type' => 'wcmp_vendor_rating' );

		if ( isset( $request['id'] ) ) {
			$prepared_review['comment_ID'] = (int) $request['id'];
		}

		if ( isset( $request['vendor_id'] ) ) {
			$prepared_review['comment_post_ID'] = (int) wcmp_vendor_dashboard_page_id();
		}

		if ( isset( $request['review'] ) ) {
			$prepared_review['comment_content'] = $request['review'];
		}

		if ( isset( $request['user_url'] ) ) {
			$prepared_review['comment_author_url'] = $request['user_url'];
		}

		if ( isset( $request['name'] ) ) {
			$prepared_review['comment_author'] = $request['name'];
		}

		if ( isset( $request['user_id'] ) ) {
			$prepared_review['user_id'] = $request['user_id'];
		}

		if ( isset( $request['email'] ) ) {
			$prepared_review['comment_author_email'] = $request['email'];
		}

		$prepared_review['comment_date'] = current_time('mysql');


		return apply_filters( 'rest_preprocess_vendor_review', $prepared_review, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Comment $review vendor review object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given vendor review.
	 */
	protected function prepare_links( $review, $request ) {
		$vendor_id = isset($request['vendor_id']) ? (int) $request['vendor_id'] : 0;
		$base       = str_replace( '(?P<vendor_id>[\d]+)', $vendor_id, $this->rest_base );
		$links      = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $review->comment_ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up' => array(
				'href' => rest_url( sprintf( '/%s/vendors/%d', $this->namespace, $vendor_id ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Vendor Review's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'vendor_review',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'review_content' => array(
					'description' => __( 'The content of the review.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'review_rating' => array(
					'description' => __( 'Review rating (0 to 5).', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'reviewer_id' => array(
					'description' => __( "The date the review was created, in the site's timezone.", 'dc-woocommerce-multi-vendor' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'reviewer_name' => array(
					'description' => __( 'Reviewer name.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'reviewer_email' => array(
					'description' => __( 'Reviewer email.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'reviewer_verified' => array(
					'description' => __( 'Shows if the reviewer bought from the vendor or not.', 'dc-woocommerce-multi-vendor' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the review was created, in the site's timezone.", 'dc-woocommerce-multi-vendor' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}
