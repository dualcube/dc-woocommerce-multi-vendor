<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/widget/vendor-product-search.php
 * 
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version     0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="dc-woocommerce-product-search" action="">
  <input type="search" class="search-field" placeholder="<?php echo esc_attr__( 'Search products&hellip;', 'dc-woocommerce-multi-vendor' ); ?>" value="<?php echo get_search_query(); ?>" name="s" style="width:100%;"/>
  <button type="submit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'dc-woocommerce-multi-vendor' ); ?>"><?php echo esc_html_x( 'Search', 'submit button', 'dc-woocommerce-multi-vendor' ); ?></button>
  <input type="hidden" name="post_type" value="product" />
</form>
