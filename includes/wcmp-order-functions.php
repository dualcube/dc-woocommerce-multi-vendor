<?php

/**
 * WCMp Order Functions
 *
 * Functions for order specific things.
 *
 * @package WCMp/Functions
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get all orders.
 *
 * @since 3.4.0
 * @param $args query_args
 * @param $return_type return types
 * @return array
 */
function wcmp_get_orders($args = array(), $return_type = 'ids') {
    
    $default = array(
	'posts_per_page'   => -1,
	'orderby'          => 'date',
	'order'            => 'DESC',
	'post_type'        => 'shop_order',
	'post_status'      => 'any',
	'fields'           => 'ids',
    );
    $args = wp_parse_args($args, $default);
    $query = new WP_Query( apply_filters( 'wcmp_get_orders_query_args', $args ) );
    if(strtolower($return_type) == 'object'){
        $orders = array();
        foreach ($query->get_posts() as $post_id) {
            $orders[$post_id] = wc_get_order($post_id);
        }
        return $orders;
    }
    return $query->get_posts();
}

/**
 * Get Vendor order object.
 *
 * @since 3.4.0
 * @return object/false Vendor order object
 */
function wcmp_get_order($id){
    global $WCMp;
    if($id){
        if(!class_exists('WCMp_Vendor_Order')){
            // Init WCMp Vendor Order class
            $WCMp->load_class('vendor-order');
        }
        $vendor_order = new WCMp_Vendor_Order($id);
        if(!$vendor_order->vendor_id) return false;
        return $vendor_order;
    }else{
        return false;
    }
}

/**
 * Checking order is vendor order or not.
 *
 * @since 3.4.0
 * @param $order integer/object
 * @param $current_vendor boolean. Default false
 * @return boolean
 */
function is_wcmp_vendor_order( $order, $current_vendor = false ) {
    $order_id = 0;
    if( is_object( $order ) ){
        $order_id = $order->get_id();
    }else{
        $order_id = absint( $order );
    }
    $vendor_order = wcmp_get_order( $order_id );
    if( $current_vendor ){
        return ( $vendor_order && $vendor_order->vendor_id === get_current_user_id() ) ? true : false;
    }
    return ( $vendor_order ) ? true : false;
}

/**
 * Get total refunded commission amount associated with refund.
 *
 * @since 3.4.0
 * @return boolean
 */
function get_refund_commission_amount($refund_id, $context = 'view') {
    if( $refund_id ){
        $order_id = wp_get_post_parent_id( $refund_id );
        $commission_id = get_post_meta( $order_id, '_commission_id', true );
        $commission_refunded_data = get_post_meta( $commission_id, '_commission_refunded_data', true );
        if( isset($commission_refunded_data[$refund_id][$commission_id]) ){
            $refund_commission_data = $commission_refunded_data[$refund_id][$commission_id];
            return array_sum($refund_commission_data);
            //return $context == 'view' ? wc_price($refund_commission, array('currency' => $order->get_currency())) : $refund_commission;
        }
    }
    return false;
}

/**
 * Get total refunded item commission amount associated with refund.
 *
 * @since 3.4.0
 * @return boolean
 */
function wcmp_get_total_refunded_for_item( $item_id, $order_id ) {
    if( $item_id && $order_id ) {
        $commission_id = get_post_meta( $order_id, '_commission_id', true );
        $commission_refunded_items_data = get_post_meta( $commission_id, '_commission_refunded_items_data', true );
        $refunds = wc_get_orders(
            array(
                'type'   => 'shop_order_refund',
                'parent' => $order_id,
                'limit'  => -1,
            )
        );
        $item_total = 0;
        if($refunds){
            foreach ( $refunds as $refund ) {
                foreach ( $refund->get_items( 'line_item' ) as $refunded_item ) {
                    if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $item_id ) {
                        if( isset($commission_refunded_items_data[$refund->get_id()][$item_id]) )
                            $item_total += $commission_refunded_items_data[$refund->get_id()][$item_id];
                    }
                }
            }
        }
        return $item_total;
    }
    return false;
}

/**
 * Get WCMp suborders if available.
 *
 * @param int $order_id.
 * @param array $args.
 * @param boolean $object.
 * @return object suborders.
 */
function get_wcmp_suborders( $order_id, $args = array(), $object = true ) {
    $default = array(
        'post_parent' => $order_id,
        'post_type' => 'shop_order',
        'numberposts' => -1,
        'post_status' => 'any'
    );
    $args = ( $args ) ? wp_parse_args( $args, $default ) : $default;
    $orders = array();
    $posts = get_posts( $args );
    foreach ( $posts as $post ) {
        $orders[] = ( $object ) ? wc_get_order( $post->ID ) : $post->ID;
    }
    return $orders;
}

/**
 * Get WCMp commisssion order
 *
 * @param int $commission_id.
 * @return object WCMp vendor order class object.
 */
function get_wcmp_order_by_commission( $commission_id ) {
    $order_id = wcmp_get_commission_order_id( $commission_id );
    if( $order_id ){
        $vendor_order = wcmp_get_order( $order_id );
        return $vendor_order;
    }
    return false;
}

/**
 * Get Parent shipping item id
 *
 * @param int $commission_id.
 * @return object WCMp vendor order class object.
 */
function get_vendor_parent_shipping_item_id( $order_id, $vendor_id ) {
    if( $order_id ){
        $order = wc_get_order( $order_id );
        $line_items_shipping = $order->get_items( 'shipping' );
        foreach ( $line_items_shipping as $item_id => $item ){
            $shipping_vendor_id = $item->get_meta('vendor_id', true);
            if( $shipping_vendor_id == $vendor_id ) return $item_id;
        }
    }
    return false;
}

/**
 * Get commission order ID
 *
 * @param int $commission_id.
 * @return order ID
 */
function wcmp_get_commission_order_id( $commission_id ) {
    $order_id = get_post_meta( $commission_id, '_commission_order_id', true );
    return ( $order_id ) ? $order_id : false;
}

/**
 * Get order commission ID
 *
 * @param int $order_id.
 * @return commission ID
 */
function wcmp_get_order_commission_id( $order_id ) {
    $commission_id = get_post_meta( $order_id, '_commission_id', true );
    return ( $commission_id ) ? $commission_id : false;
}