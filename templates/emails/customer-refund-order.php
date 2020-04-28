<?php
/**
 * The template for displaying report abuse via customer.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/emails/customer-refund-order.php
 *
 * @author 	WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version   3.3.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $WCMp;

$order_data = wc_get_order( $order );

$order_uri = esc_url( $order_data->get_view_order_url() );


$user_data = get_userdata( $id );
$user_roles = $user_data->roles;

 $meta_field_data = get_post_meta( $order, '_customer_refund_order', true ) ? get_post_meta( $order, '_customer_refund_order', true ) : '';
 if( $meta_field_data == 'refund_accept' ){
 	$status_refund = __( 'Accepted', '' );
 }else{
 	$status_refund = __( 'Rejected', '' );
 }


 /***************************	Find reason   ***********************************/
 if( $refund_details ){
  $refund_reson_option = get_option( 'wcmp_RMA_refund_settings_name', true );
  $refund_message_by_admin = $refund_reson_option['refund_order_msg'];
  $massage_array = explode("||",$refund_message_by_admin);
  array_push( $massage_array , "others" );
  foreach ($massage_array as $key => $value) {
  	if( $key == $refund_details['refund_rason'] ) {
  		$mail_reason = $value;
  	}
  }
}


do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p style="text-align:<?php //echo $text_align; ?>;" ><?php  ?></p>
<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
        <h2><?php _e( 'Refund details', 'dc-woocommerce-multi-vendor' ); ?></h2>
        <?php if( is_user_wcmp_vendor( $id ) ){ ?>
        <ul>
            <li><strong><?php _e( 'Refund Order Main reason', 'dc-woocommerce-multi-vendor' ); ?>:</strong> <span class="text"><?php echo $mail_reason; if( $refund_details['others_reason'] ) echo ':'. $refund_details['others_reason'] . '' ; ?></span></li>

            <li><strong><?php _e( 'Refund Order Description', 'dc-woocommerce-multi-vendor' ); ?>:</strong> <span class="text"><?php echo $refund_details['description']; ?></span></li> 


            <li><strong><?php printf(__( 'Customer refund for order : <a href="%s" title="%s">#%s</a> ', 'dc-woocommerce-multi-vendor' ), esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders'), $order )), sanitize_title($order_data->get_status()), $order_data->get_order_number()  ); ?></span></li>
        </ul>



        <?php } elseif( in_array( 'administrator', $user_roles, true ) ) { ?>
       

        <ul>
            <li><strong><?php _e( 'Refund Order Main reason', 'dc-woocommerce-multi-vendor' ); ?>:</strong> <span class="text"><?php echo $mail_reason; ?></span></li>

            <li><strong><?php _e( 'Refund Order Description', 'dc-woocommerce-multi-vendor' ); ?>:</strong> <span class="text"><?php echo $refund_details['description']; ?></span></li>


            <li><strong><?php printf(__( 'Customer refund for order : <a href="%s" title="%s">#%s</a> ', 'dc-woocommerce-multi-vendor' ), admin_url( 'post.php?post=' . absint( $order ) . '&action=edit' ) , sanitize_title($order_data->get_status()), $order_data->get_order_number()  ); ?></span></li>
        </ul>


        <?php } else { ?>
        	<span class="text"><?php printf(__( 'Your refund request is ' . $status_refund . ' for order : <a href="%s" title="%s">#%s</a> ', 'dc-woocommerce-multi-vendor' ), $order_uri, sanitize_title($order_data->get_status()), $order_data->get_order_number()  ); 
			?>
        	</span>
        <?php } ?>
</div>

<?php do_action( 'wcmp_email_footer' ); ?>
