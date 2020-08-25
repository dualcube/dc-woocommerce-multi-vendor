<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/emails/plain/vendor-review.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version   0.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$text_align = is_rtl() ? 'right' : 'left';
$customer_name  = isset( $customer_name ) ? $customer_name : '';
$review = isset( $review ) ? $review : '';
$rating = isset( $rating ) ? absint($rating) : '';

do_action( 'woocommerce_email_header', $email_heading ); ?>

<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
        <h2><?php _e( 'Review details', 'dc-woocommerce-multi-vendor' ); ?></h2>
        <ul>
            <li><strong><?php _e( 'Customer Name', 'dc-woocommerce-multi-vendor' ); ?>:</strong> <span class="text"><?php echo $customer_name; ?></span></li>
            <?php if(!empty($rating)){ ?>
	        <li>
	            <strong><?php _e( 'Rating', 'dc-woocommerce-multi-vendor' ); ?>:</strong> 
	            <strong><?php echo $rating; ?></strong> 
	            <?php _e( 'out of 5', 'dc-woocommerce-multi-vendor' ); ?>
	        </li>
            <?php } ?>
            <li><strong><?php _e( 'Comment', 'dc-woocommerce-multi-vendor' ); ?>:</strong> <span class="text"><?php echo $review; ?></span></li>
        </ul>
</div>
<?php do_action( 'wcmp_email_footer' ); ?>
