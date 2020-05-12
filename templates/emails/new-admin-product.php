<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/emails/new-admin-product.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version   0.0.1
 */


if ( !defined( 'ABSPATH' ) ) exit; 
global $WCMp;
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p><?php printf( __( "Hi there! This is to notify that a new product has been submitted in %s.",  'dc-woocommerce-multi-vendor' ), get_option( 'blogname' ) ); ?></p>

	<p>
		<?php printf( __( "Product title: %s",  'dc-woocommerce-multi-vendor' ), $product_name ); ?><br/>
		<?php printf( __( "Submitted by: %s",  'dc-woocommerce-multi-vendor' ), 'Site Administrator' ); ?><br/>
		<?php 
                    $product_link = apply_filters( 'wcmp_email_admin_new_product_link', esc_url( wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_edit_product_endpoint', 'vendor', 'general', 'edit-product' ), $post_id ) ) );
			if($submit_product) {
				printf( __( "Edit product: %s",  'dc-woocommerce-multi-vendor' ), $product_link ); 
			} else {
				printf( __( "View product: %s",  'dc-woocommerce-multi-vendor' ), get_permalink($post_id)); 
			}
		?>
		<br/>
	</p>

<?php do_action( 'wcmp_email_footer' ); ?>