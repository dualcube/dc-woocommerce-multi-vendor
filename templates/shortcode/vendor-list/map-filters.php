<?php
/**
 * Vendor List Map filters
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/shortcode/vendor-list/map-filters.php
 *
 * @package WCMp/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $WCMp, $vendor_list;
extract($vendor_list);
?>
<input type="hidden" id="wcmp_vlist_center_lat" name="wcmp_vlist_center_lat" value=""/>
<input type="hidden" id="wcmp_vlist_center_lng" name="wcmp_vlist_center_lng" value=""/>
<div class="wcmp-store-map-filter">
    <div class="wcmp-inp-wrap">
        <input type="text" name="locationText" id="locationText" placeholder="<?php _e('Enter Address', 'dc-woocommerce-multi-vendor'); ?>" value="<?php echo isset($request['locationText']) ? $request['locationText'] : ''; ?>">
    </div>
    <div class="wcmp-inp-wrap">
        <select name="radiusSelect" id="radiusSelect">
            <option value=""><?php _e('Within', 'dc-woocommerce-multi-vendor'); ?></option>
            <?php if($radius) :
            $selected_radius = isset($request['radiusSelect']) ? $request['radiusSelect'] : '';
            foreach ($radius as $value) {
                echo '<option value="'.$value.'" '.selected( esc_attr( $selected_radius ), $value, false ).'>'.$value.'</option>';
            }
            endif;
            ?>
        </select>
    </div>
    <div class="wcmp-inp-wrap">
        <select name="distanceSelect" id="distanceSelect">
            <?php $selected_distance = isset($request['distanceSelect']) ? $request['distanceSelect'] : ''; ?>
            <option value="M" <?php echo selected( $selected_distance, "M", false ); ?>><?php _e('Miles', 'dc-woocommerce-multi-vendor'); ?></option>
            <option value="K" <?php echo selected( $selected_distance, "K", false ); ?>><?php _e('Kilometers', 'dc-woocommerce-multi-vendor'); ?></option>
            <option value="N" <?php echo selected( $selected_distance, "N", false ); ?>><?php _e('Nautical miles', 'dc-woocommerce-multi-vendor'); ?></option>
            <?php do_action('wcmp_vendor_list_sort_distanceSelect_extra_options'); ?>
        </select>
    </div>
    <?php do_action( 'wcmp_vendor_list_vendor_sort_map_extra_filters', $request ); ?>
    <input type="submit" name="vendorListFilter" value="<?php _e('Submit', 'dc-woocommerce-multi-vendor'); ?>">
</div>
