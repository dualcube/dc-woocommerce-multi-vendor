<?php
/**
 * Order details items template.
 *
 * Used by vendor-order-details.php template
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-orders/views/html-order-downloadable-permissions.php.
 * 
 * @author 	WC Marketplace
 * @package 	WCMp/templates/vendor dashboard/vendor orders/views
 * @version     3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $WCMp;
?>
<div class="panel panel-default panel-pading pannel-outer-heading download-product-permission">
    <div class="panel-heading d-flex">
        <h3><?php _e('Downloadable product permissions', 'woocommerce'); ?></h3>
    </div>
    <div class="order_download_permissions wc-metaboxes-wrapper panel-body panel-content-padding">
        <div class="wc-metaboxes" id="vorder-dwnld-accordion">
            <?php
            $data_store = WC_Data_Store::load('customer-download');
            $download_permissions = $data_store->get_downloads(
                    array(
                        'order_id' => $order->get_id(),
                        'orderby' => 'product_id',
                    )
            );

            $product = null;
            $loop = 0;
            $file_counter = 1;

            if ($download_permissions && sizeof($download_permissions) > 0) {
                foreach ($download_permissions as $download) {
                    if (!$product || $product->get_id() !== $download->get_product_id()) {
                        $product = wc_get_product($download->get_product_id());
                        $file_counter = 1;
                    }

                    // don't show permissions to files that have since been removed.
                    if (!$product || !$product->exists() || !$product->has_file($download->get_download_id())) {
                        continue;
                    }

                    // Show file title instead of count if set.
                    $file = $product->get_file($download->get_download_id());
                    $file_count = isset($file['name']) ? $file['name'] : sprintf(__('File %d', 'woocommerce'), $file_counter);

                    include 'html-order-download-permission.php';

                    $loop++;
                    $file_counter++;
                }
            }
            ?>
        </div>
        <div class="toolbar">
            <div class="form-group mb-0">
                <select id="grant_access_id" class="wc-product-search" name="grant_access_id[]" multiple="multiple" style="width: 400px;" data-placeholder="<?php esc_attr_e('Search for a downloadable product&hellip;', 'woocommerce'); ?>" data-action="wcmp_json_search_downloadable_products_and_variations"></select>
                <button class="button grant_access btn btn-default"><?php _e('Grant access', 'woocommerce'); ?></button>
            </div>
        </div>
    </div>
</div>