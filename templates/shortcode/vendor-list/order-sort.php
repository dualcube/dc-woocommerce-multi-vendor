<?php
/**
 * Vendor List Map filters
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/shortcode/vendor-list/catalog-ordering.php
 *
 * @package WCMp/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $WCMp, $vendor_list;
extract( $vendor_list );
?>

<div class="vendor_sort">
    <select class="select short" id="vendor_sort_type" name="vendor_sort_type">
        <?php
        $vendor_sort_type = apply_filters('wcmp_vendor_list_vendor_sort_type', array(
            'registered' => __('By date', 'dc-woocommerce-multi-vendor'),
            'name' => __('By Alphabetically', 'dc-woocommerce-multi-vendor'),
            'category' => __('By Category', 'dc-woocommerce-multi-vendor'),
        ));
        if ($vendor_sort_type && is_array($vendor_sort_type)) {
            foreach ($vendor_sort_type as $key => $label) {
                $selected = '';
                if (isset($request['vendor_sort_type']) && $request['vendor_sort_type'] == $key) {
                    $selected = 'selected="selected"';
                }
                echo '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
            }
        }
        ?>
    </select>
    <?php
    $product_category = get_terms('product_cat');
    $options_html = '';
    $sort_category = isset($request['vendor_sort_category']) ? $request['vendor_sort_category'] : '';
    foreach ($product_category as $category) {
        if ($category->term_id == $sort_category) {
            $options_html .= '<option value="' . esc_attr($category->term_id) . '" selected="selected">' . esc_html($category->name) . '</option>';
        } else {
            $options_html .= '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
        }
    }
    ?>
    <select name="vendor_sort_category" id="vendor_sort_category" class="select"><?php echo $options_html; ?></select>
    <?php do_action( 'wcmp_vendor_list_vendor_sort_extra_attributes', $request ); ?>
    <input value="<?php echo __('Sort', 'dc-woocommerce-multi-vendor'); ?>" type="submit">
</div>
