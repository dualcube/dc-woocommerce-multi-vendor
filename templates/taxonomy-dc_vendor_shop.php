<?php
/**
 * The Template for displaying products in a product category. Simply includes the archive template.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/taxonomy-dc_vendor_shop.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
global $WCMp;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// Get vendor 
$vendor = get_wcmp_vendor_by_term(get_queried_object()->term_id);
if(!$vendor){
    // Redirect if not vendor
    wp_safe_redirect(get_permalink( woocommerce_get_page_id( 'shop' ) ));
    exit();
}
$is_block = get_user_meta($vendor->id, '_vendor_turn_off' , true);
if($is_block) {
	get_header( 'shop' ); ?>
	<?php
		/**
		 * wcmp_before_main_content hook
		 *
		 */
		do_action( 'wcmp_before_main_content' );
	?>

		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

			<h1 class="page-title"><?php woocommerce_page_title(); ?></h1>

		<?php endif; ?>

		<?php do_action( 'woocommerce_archive_description' ); 
		$block_vendor_desc = apply_filters('wcmp_blocked_vendor_text', __('Site Administrator has blocked this vendor', 'dc-woocommerce-multi-vendor'), $vendor);
		?>
		<p class="blocked_desc">
			<?php echo esc_attr($block_vendor_desc); ?>
		<p>
		<?php
		/**
		 * wcmp_after_main_content hook
		 *
		 */
		do_action( 'wcmp_after_main_content' );
	?>

	<?php
		/**
		 * wcmp_sidebar hook
		 *
		 */
		do_action( 'wcmp_sidebar' );
	?>

<?php get_footer( 'shop' ); 
	
} else {
	$WCMp->template->get_template('wcmp-archive-page-vendor.php');
}
