<?php
/**
 * The template for displaying vendor dashboard
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/non-vendor/rejected-vendor-dashboard.php
 *
 * @author 	WC Marketplace
 * @package 	WCMp/Templates
 * @version     3.1.0
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
echo '<div class="col-md-12 text-center"><div class="panel wcmp-rejected-vendor-notice">' . apply_filters( 'wcmp_rejected_vendor_dashboard_message', __('We have reviewed your application. Unfortunately, you are not the right fit with us at this time.', 'dc-woocommerce-multi-vendor') ) . '</div></div>';
$wcmp_vendor_rejection_notes = unserialize( get_user_meta( get_current_user_id(), 'wcmp_vendor_rejection_notes', true ) );

if(is_array($wcmp_vendor_rejection_notes) && count($wcmp_vendor_rejection_notes) > 0) {
	echo '<div class="col-md-12"><div class="panel panel-default pannel-outer-heading"><div class="panel-heading d-flex"><h3>' . __('Notes from our reviewer', 'dc-woocommerce-multi-vendor') . '</h3></div>';
	echo '<div class="panel-body panel-content-padding"><div class="note-clm-wrap">';
	foreach($wcmp_vendor_rejection_notes as $time => $notes) {
		echo '<div class="note-clm"><p class="note-description">' . $notes['note'] . '</p><p class="note_time note-meta">On ' . date( "Y-m-d", $time ) . '</p></div>';
	}
	echo '</div></div></div></div>';
}

echo '<div class="wcmp-action-container"><a class="btn btn-default" href="' . esc_url(wcmp_get_vendor_dashboard_endpoint_url('rejected-vendor-reapply')) . '">' . __('Resubmit Application', 'dc-woocommerce-multi-vendor') . '</a></div>';



		
	
	
	
	
