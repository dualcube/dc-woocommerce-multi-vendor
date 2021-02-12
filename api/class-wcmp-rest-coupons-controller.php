<?php
/**
 * REST API Coupon controller
 *
 * Handles requests to the /coupons endpoint of WooCommerce.
 *
 * @package WCMp/API
 * @since   3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * REST API Coupons controller
 *
 * List of Coupons via get request.
 * New params
 * vendor integer vendor id whose coupons will be retrived.
 * include_vendor array array of vendor id want to include
 * exclude_vendor array array of vendor id want to exclude
 */

add_filter('woocommerce_rest_shop_coupon_object_query', 'enable_vendor_on_list_shop_coupon_query', 10, 2);

function enable_vendor_on_list_shop_coupon_query($args, $request) {
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
 * vendor pass vendor id to assign the vendor with the coupon.
 */

add_action('woocommerce_rest_insert_shop_coupon_object', 'assign_shop_coupon_to_vendor', 10, 3);

function assign_shop_coupon_to_vendor($object, $request, $new_shop_coupon) {
	
	if(isset($request['vendor']) && $request['vendor'] != '') {
		$vendor = get_wcmp_vendor($request['vendor']);
		
		if(isset($vendor->user_data->roles) && in_array('dc_vendor', $vendor->user_data->roles)) {
			$update_post_author = array(
				'ID' => $object->get_id(),
				'post_author' => absint($request['vendor']),
			);
			
			wp_update_post( $update_post_author );
		} else {
			return new WP_Error(
				"woocommerce_rest_shop_coupon_invalid_vendor_id", __( 'Invalid Vendor ID.', 'dc-woocommerce-multi-vendor' ), array(
					'status' => 404,
				)
			);
		}
	}
}

// Adding vendor parameter in return JSON.
add_filter('woocommerce_rest_prepare_shop_coupon_object', 'return_vendor_info_on_list_shop_coupon_query', 10, 3);

function return_vendor_info_on_list_shop_coupon_query($response, $object, $request) {
	$vendor_id = get_post_field('post_author', $object->get_id());
	
	$vendor = get_wcmp_vendor($vendor_id);
	if(isset($vendor->user_data->roles) && in_array('dc_vendor', $vendor->user_data->roles)) {
		$data = $response->get_data();
		$data['vendor'] = $vendor_id;
		$response->set_data($data);
	}
	return $response;
}
