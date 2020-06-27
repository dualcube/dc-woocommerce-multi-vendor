<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/widget/vendor-product-list.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version     0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="dc-woocommerce-vendor-product-search" action="">
	<label class="screen-reader-text" for="dc-woocommerce-vendor-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>"><?php esc_html_e( 'Search for:', 'dc-woocommerce-multi-vendor' ); ?></label>

	<input type="search" id="dc-woocommerce-vendor-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>" placeholder="<?php echo esc_attr__( ' Search vendor products&hellip;', 'dc-woocommerce-multi-vendor' ); ?>" value="<?php echo get_search_query(); ?>" name="s"/>
	<button type="submit"><i class="fa fa-search"></i></button>
	<input type="hidden" name="post_type" value="product"/>
</form>
<style>
/* Style the search field */
form.dc-woocommerce-vendor-product-search input[type=search] {
  float: left;
  width: 80%;
}
/* Style the submit button */
form.dc-woocommerce-vendor-product-search button {
  float: right;
  width: 20%;
  cursor: pointer;
}
/* Clear floats */
form.dc-woocommerce-vendor-product-search::after {
  content: "";
  clear: both;
  display: table;
}
</style>