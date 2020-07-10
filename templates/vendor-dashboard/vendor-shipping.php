<?php
/**
 * The template for displaying vendor dashboard
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-shipping.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
global $woocommerce, $WCMp, $wpdb;

$vendor = get_wcmp_vendor(get_current_user_id());
$vendor_all_shipping_zones = wcmp_get_shipping_zone();
$vendor_shipping_data = get_user_meta($vendor->id, 'vendor_shipping_data', true);
?>
<div class="col-md-12">
    <form name="vendor_shipping_form" class="wcmp_shipping_form form-horizontal" method="post">
        <div class="panel panel-default panel-pading pannel-outer-heading">
            <div class="panel-heading d-flex">
                <h3><?php _e('Shipping zones', 'dc-woocommerce-multi-vendor'); ?></h3>
            </div>
            <div class="panel-body">
                <div id="wcmp_settings_form_shipping_by_zone" class="wcmp-content shipping_type by_zone hide_if_shipping_disabled">
                    <table class="table wcmp-table shipping-zone-table">
                        <thead>
                            <tr>
                                <th><?php _e('Zone name', 'dc-woocommerce-multi-vendor'); ?></th> 
                                <th><?php _e('Region(s)', 'dc-woocommerce-multi-vendor'); ?></th> 
                                <th><?php _e('Shipping method(s)', 'dc-woocommerce-multi-vendor'); ?></th>
                                <th><?php _e('Actions', 'dc-woocommerce-multi-vendor'); ?></th>
                            </tr>
                        </thead> 
                        <tbody>
                            <?php
                            if (!empty($vendor_all_shipping_zones)) {
                                foreach ($vendor_all_shipping_zones as $key => $vendor_shipping_zones) {
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="JavaScript:void(0);" data-zone-id="<?php echo $vendor_shipping_zones['zone_id']; ?>" class="vendor_edit_zone modify-shipping-methods"><?php _e($vendor_shipping_zones['zone_name'], 'dc-woocommerce-multi-vendor'); ?></a> 
                                        </td> 
                                        <td><?php _e($vendor_shipping_zones['formatted_zone_location'], 'dc-woocommerce-multi-vendor'); ?></td> 
                                        <td>
                                            <div class="wcmp-shipping-zone-methods">
                                                <?php
                                                $vendor_shipping_methods = $vendor_shipping_zones['shipping_methods'];
                                                $vendor_shipping_methods_titles = array();
                                                if ($vendor_shipping_methods) :
                                                    foreach ($vendor_shipping_methods as $key => $shipping_method) {
                                                        $class_name = 'yes' === $shipping_method['enabled'] ? 'method_enabled' : 'method_disabled';
                                                        $vendor_shipping_methods_titles[] = "<span class='wcmp-shipping-zone-method $class_name'>" . $shipping_method['title'] . "</span>";
                                                    }
                                                endif;
                                                //$vendor_shipping_methods_titles = array_column($vendor_shipping_methods, 'title');
                                                $vendor_shipping_methods_titles = implode(', ', $vendor_shipping_methods_titles);

                                                if (empty($vendor_shipping_methods)) {
                                                    ?>
                                                    <span><?php _e('No shipping methods offered to this zone.', 'dc-woocommerce-multi-vendor'); ?> </span>
                                                <?php } else { ?>
                                                    <?php _e($vendor_shipping_methods_titles, 'dc-woocommerce-multi-vendor'); ?>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="col-actions">
                                                <span class="view">
                                                    <a href="JavaScript:void(0);" data-zone-id="<?php echo $vendor_shipping_zones['zone_id']; ?>" class="vendor_edit_zone modify-shipping-methods" title="View"><i class="wcmp-font ico-eye-icon"></i></a>
                                                </span> 
                                            </div>
                                            <div class="row-actions">
                                            </div>
                                        </td>
                                    </tr>
    <?php }
} else {
    ?>
                                <tr>
                                    <td colspan="3"><?php _e('No shipping zone found for configuration. Please contact with admin for manage your store shipping', 'dc-woocommerce-multi-vendor'); ?></td>
                                </tr>
    <?php }
?>
                        </tbody>
                    </table>
                    <div id="vendor-shipping-methods"></div>
                </div>
            </div>
        </div>
        <?php do_action('wcmp_before_shipping_form_end_vendor_dashboard'); ?>
        <div class="wcmp-action-container">
            <button class="wcmp_orange_btn btn btn-default" name="shipping_save"><?php _e('Save Options', 'dc-woocommerce-multi-vendor'); ?></button>
        </div>
        <div class="clear"></div>
    </form>

</div>