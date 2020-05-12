<?php
/**
 * Vendor List Map
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/shortcode/vendor-list/content-vendor.php
 *
 * @package WCMp/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $WCMp, $vendor_list;
$vendor = get_wcmp_vendor( $vendor_id );
$image = $vendor->get_image() ? $vendor->get_image('image', array(125, 125)) : $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
$banner = $vendor->get_image('banner') ? $vendor->get_image('banner') : '';


$vendor_phone = $vendor->phone ? $vendor->phone : __('No number yet','dc-woocommerce-multi-vendor');
$description = $vendor->description ? substr($vendor->description,0,10 ). '..'  : __('No description yet', 'dc-woocommerce-multi-vendor');

$rating_info = wcmp_get_vendor_review_info($vendor->term_id);
$rating = round($rating_info['avg_rating'], 2);
$count = intval($rating_info['total_rating']);
if ($count > 0) {
  $rating_html = '<div itemprop="reviewRating" class="star-rating" style="float:none;">
  <span style="width:' . (( $rating / 5 ) * 100) . '%"><strong itemprop="ratingValue">' . $rating . '</strong> </span>
</div>';
} else {
  $rating_html = __('No Rating Yet', 'dc-woocommerce-multi-vendor');
}

$query_args = array(
  'posts_per_page' => 3,
  'no_found_rows'  => 1,
  'post_status'    => 'publish',
  'post_type'      => 'product',
  'author__in'   => $vendor->ID,
  'meta_key'       => '_wc_average_rating',
  'orderby'        => 'meta_value_num',
  'order'          => 'DESC',
  'meta_query'     => WC()->query->get_meta_query(),
  'tax_query'      => WC()->query->get_tax_query(),
); // WPCS: slow query ok.

$r = new WP_Query( $query_args );
$product_images = '';
if( !empty( $r->posts ) ){
  foreach ($r->posts as $step_key => $step) {
    $product5 = wc_get_product( $step->ID );
    $product_images .=  '    <div class="gray">'. $product5->get_image().' </div>
    '; 
  }
}

?>
<div class="wcmp-store-list wcmp-store-list-vendor">
  <?php do_action('wcmp_vendor_lists_single_before_image', $vendor->term_id, $vendor->id); ?>

  <div class="wcmp-vendorblocks">

    <div class="wcmp-vendor-details">
      <div class="vendor-heading">
        <div class="wcmp-store-picture">
          <img class="vendor_img" src="<?php echo $image; ?>" id="vendor_image_display">

          <div class="wcmp-vendor-name"><?php $button_text = apply_filters('wcmp_vendor_lists_single_button_text', $vendor->page_title); ?>
          </div></div>
          <a href="<?php echo $vendor->get_permalink(); ?>" class="store-name"><?php echo $button_text; ?></a>
          <?php do_action('wcmp_vendor_lists_single_after_button', $vendor->term_id, $vendor->id); ?>
          <?php do_action('wcmp_vendor_lists_vendor_after_title', $vendor); ?></div>
          <a class="share-detail" href="#"><i class="fa fa-share-alt" aria-hidden="true"></i></a>
          <!-- star rating -->
          <div class="wcmp-rating-block">
            <div class="wcmp-rating-rate"><?php echo $rating; ?></div>
            <?php
            $rating_info = wcmp_get_vendor_review_info($vendor->term_id);
            $WCMp->template->get_template('review/rating_vendor_lists.php', array('rating_val_array' => $rating_info));
            ?>
            <div class="wcmp-rating-review"><?php echo $count; ?></div>
          </div>
          <!-- star rating -->
          <div class="add-call-block">
            <div class="wcmp-detail-block">


              <i class="wcmp-font ico-call_icon"></i>
              <span class="vendor-call"><?php echo $vendor_phone; ?></span></span>
            </div>
            <div class="wcmp-detail-block">
              <i class="wcmp-font ico-at_icon" aria-hidden="true"></i>
              <span class="add-address"><?php echo substr($vendor->get_formatted_address(),0,10 ). '..' ; ?><a href="#">(Map)</a>
              </span>
            </div>
            <div class="wcmp-detail-block">
              <i class="wcmp-font ico-location-icon2" aria-hidden="true"></i>
              <span class=""><?php echo $description; ?></span>
            </div>
          </div>
          <div class="wcmp-headline">
            <div class="wcmp-topProduct"><?php echo __( 'Top Products' , 'dc-woocommerce-multi-vendor' ); ?></div>
          </div>
          <div class="wcmp-productImg">
            <?php echo $product_images; ?>
          </div>
          <a href="<?php echo esc_url($vendor->permalink); ?>" class="wcmp-contactNow"><?php echo __( 'Contact Now' , 'dc-woocommerce-multi-vendor' ); ?></a>
        </div>
      </div>
    </div>
