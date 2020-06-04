<?php
/**
 * The template for displaying widget content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/widget/vendor-product-list.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version     0.0.1
 */

global $WCMp;

$product_count = count($products); ?>
 <div id="wcmp_widget_vendor_product_search" class="vendor_product_search_wrap">
	<?php wp_nonce_field( 'wcmp_widget_vendor_product_search_form', 'wcmp_vendor_product_search_nonce' ); ?>
	<input type="search" class="search_keyword search-field" placeholder="<?php echo __('Search Vendor product…', 'dc-woocommerce-multi-vendor'); ?>" value="" name="s" style="width: 100%;margin-bottom: 10px;">
	<input type="hidden" class="wcmp_widget_vendor_id" value="<?php echo $vendor_id; ?>">
</div> 
<?php
if($product_count > 5 )	{ ?>
	<div id="wcmp_widget_vendor_product_list" style="height: 308px; overflow-y: scroll; width: 226px;" > 
<?php } else { ?>
	<div id="wcmp_widget_vendor_product_list" style=" height: auto; width: 226px;" > 
<?php }

if($products) {
	foreach($products as $product) {   
	$product = wc_get_product( $product->ID );
	 $image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id()), 'single-post-thumbnail' );?>
       
		<div style=" width: 100%; margin-bottom: 20px; clear: both; display: block;">
			<div style=" width: 75%;  display: inline-block;">
						<a href="<?php echo esc_attr( $product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $product->get_name() ); ?>
						</a>
						<br>
						<?php echo $product->get_price_html(); ?>
						
					
			</div>
			<div style=" width: 25%;  display: inline;  float: right;">		
				<img width="50" height="50" class="product_img" style="display: inline;" src="<?php  echo $image[0]; ?>" data-id="<?php echo $product->get_id(); ?>">
			</div>
		</div>
	<?php } 
	}?>
	</div>
