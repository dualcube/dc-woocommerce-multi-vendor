<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/emails/plain/suspend-vendor-account.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version   0.0.1
 */
 
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $WCMp;

echo "= " . $email_heading . " =\n\n";
echo sprintf( __("Sorry! Your account is suspended on %s ", 'dc-woocommerce-multi-vendor' ), get_option( 'blogname' ) );
echo '\n';
echo sprintf( __( "Application Status: %s", 'dc-woocommerce-multi-vendor' ), 'Approved' );
echo '\n';
echo sprintf( __( "Applicant Username: %s", 'dc-woocommerce-multi-vendor' ), $user_login ); 
echo '\n';
echo _e('Your account has been suspended due to some reason!', 'dc-woocommerce-multi-vendor', 'dc-woocommerce-multi-vendor');

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo apply_filters( 'wcmp_email_footer_text', get_option( 'wcmp_email_footer_text' ) );

?>