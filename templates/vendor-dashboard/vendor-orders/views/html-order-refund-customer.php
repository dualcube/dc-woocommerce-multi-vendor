<?php
/**
 * Order details items template.
 *
 * Used by vendor-order-details.php template
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-orders/views/html-order-refund-customer.php.
 * 
 * @author 	WC Marketplace
 * @package 	WCMp/templates/vendor dashboard/vendor orders/views
 * @version     3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $WCMp;
?>
<div class="panel panel-default panel-pading pannel-outer-heading download-product-permission">
    <div class="panel-heading">
        <h3><?php _e('Customer Refund Request', 'woocommerce'); ?></h3>
    </div>
    <div class="order_download_permissions wc-metaboxes-wrapper panel-body panel-content-padding">
        <form method="post">
            <?php
            $meta_field_data = get_post_meta( $order->get_id(), '_customer_refund_order', true ) ? get_post_meta( $order->get_id(), '_customer_refund_order', true ) : '';
            $refund_statuses = array( 'status_refund' => 'Refund Status' ,'refund_request' => 'Refund Requested' , 'refund_accept' => 'Refund Accepted' );

            echo '<input type="hidden" name="mv_other_meta_field_nonce" value="' . wp_create_nonce() . '">'
            ?>
            <div class="form-group">
            <select id="refund_order_customer" name="refund_order_customer">
                <?php
                foreach ($refund_statuses as $key => $value) {
                    ?>
                    <option value="<?php echo $key; ?>" <?php if($key == $meta_field_data ) echo 'selected="selected"'; ?> ><?php echo $value; ?></option>
                    <?php
                }
                ?>
            </select>
            </div>
            <div class="form-group">
                <input type="hidden" name="order_id" id="wcmp-marke-ship-order-id" value="<?php echo $order->get_id(); ?>" />
                <button type="submit" class="btn btn-primary" name="wcmp-submit-refund-customer-request"><?php _e('Submit', 'dc-woocommerce-multi-vendor'); ?></button>
            </div>
        </form>
    </div>
</div>