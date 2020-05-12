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
?>
<div class="wcmp-store-map-pagination">
    <p class="wcmp-pagination-count wcmp-pull-right">
        <?php
        wcmp_vendor_list_paging_info();
        ?>
    </p>
    
    <?php wcmp_vendor_list_form_wrapper(); ?>

    <?php wcmp_vendor_list_order_sort(); ?>
        
    <?php wcmp_vendor_list_form_wrapper_end(); ?>
</div>