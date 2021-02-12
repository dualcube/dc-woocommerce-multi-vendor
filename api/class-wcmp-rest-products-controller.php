<?php
/**
 * REST API Products controller
 *
 * Handles requests to the /products endpoint of WooCommerce.
 *
 * @package WCMp/API
 * @since   3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * REST API Products controller
 *
 * List of Products via get request.
 * New params
 * vendor integer vendor id whose products will be retrived.
 * include_vendor array array of vendor id want to include
 * exclude_vendor array array of vendor id want to exclude
 */

add_filter('woocommerce_rest_product_object_query', 'enable_vendor_on_list_product_query', 10, 2);

function enable_vendor_on_list_product_query($args, $request) {

	if (isset($request['vendor'])) {
		$args['author'] = $request['vendor'];
	}
	if (isset($request['include_vendor'])) {
		$args['author__in'] = $request['include_vendor'];
	}
	if (isset($request['exclude_vendor'])) {
		$args['author__not_in'] = $request['exclude_vendor'];
	}
	return $args;
}

/**
 * REST API assign vendor controller
 *
 * New params
 * vendor pass vendor id to assign the vendor with the product.
 */

add_action('woocommerce_rest_insert_product_object', 'assign_product_to_vendor', 10, 3);

function assign_product_to_vendor($object, $request, $new_product) {
	global $WCMp;
	
	if(isset($request['vendor']) && $request['vendor'] != '') {
		$vendor = get_wcmp_vendor($request['vendor']);
		
		if(isset($vendor->user_data->roles) && in_array('dc_vendor', $vendor->user_data->roles)) {
			$update_post_author = array(
				'ID' => $object->get_id(),
				'post_author' => $request['vendor'],
			);
			
			wp_update_post( $update_post_author );
			wp_set_object_terms($object->get_id(), absint($vendor->term_id), $WCMp->taxonomy->taxonomy_name);
		} else {
			return new WP_Error(
				"woocommerce_rest_product_invalid_vendor_id", __( 'Invalid Vendor ID.', 'dc-woocommerce-multi-vendor' ), array(
					'status' => 404,
				)
			);
		}
	}
}


// Adding vendor parameter in return JSON.
add_filter('woocommerce_rest_prepare_product_object', 'return_vendor_info_on_list_product_query', 10, 3);

function return_vendor_info_on_list_product_query($response, $object, $request) {
	$vendor_id = get_post_field('post_author', $object->get_id());
	
	$vendor = get_wcmp_vendor($vendor_id);
	if(isset($vendor->user_data->roles) && in_array('dc_vendor', $vendor->user_data->roles)) {
		$data = $response->get_data();
		$data['vendor'] = $vendor_id;
                $data['store_name'] = $vendor->page_title;
		$response->set_data( apply_filters( 'before_wcmp_rest_prepare_product_object_data', $data, $vendor, $object, $request ) );
	}
	return $response;
}
