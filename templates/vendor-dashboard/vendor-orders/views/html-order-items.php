<?php
/**
 * Order details items template.
 *
 * Used by vendor-order-details.php template
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-orders/views/html-order-items.php.
 * 
 * @author 	WC Marketplace
 * @package 	WCMp/templates/vendor dashboard/vendor orders/views
 * @version     3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $WCMp, $wpdb;

$payment_gateway     = wc_get_payment_gateway_by_order( $order );
$line_items          = $order->get_items( apply_filters( 'wcmp_vendor_order_item_types', 'line_item' ) );
$discounts           = $order->get_items( 'discount' );
$line_items_fee      = $order->get_items( 'fee' );
$line_items_shipping = $order->get_items( 'shipping' );

if ( wc_tax_enabled() ) {
	$order_taxes      = $order->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = count( $order_taxes ) === 1;
}
?>
<style>
    .wcmp-order-data-row .wcmp-order-totals {
        float: right;
        width: 50%;
        margin: 0;
        padding: 0;
        text-align: right;
    }
    .single-order-detail-table table .order-item-img{
        margin: 0;
    }
    .single-order-detail-table{
        overflow: auto;
    }
</style>
<div class="wcmp_order_items_wrapper panel panel-default panel-pading pannel-outer-heading order-detail-table-wrap">
    <div class="panel-heading">
        <h3><?php _e('Items', 'woocommerce'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="single-order-detail-table">
            <table class="woocommerce_order_items table">
                <thead>
                    <tr>
                        <th colspan="2"><?php esc_html_e('Item', 'woocommerce'); ?>:</th>
                        <?php do_action('wcmp_vendor_dash_order_item_headers', $order); ?>
                        <th><?php esc_html_e('Cost', 'woocommerce'); ?></th>
                        <th><?php esc_html_e('Qty', 'woocommerce'); ?></th>
                        <th><?php esc_html_e('Total', 'woocommerce'); ?></th>
                        <?php
                        if (!empty($order_taxes)) :
                            foreach ($order_taxes as $tax_id => $tax_item) :
                                $tax_class = wc_get_tax_class_by_tax_id($tax_item['rate_id']);
                                $tax_class_name = isset($classes_options[$tax_class]) ? $classes_options[$tax_class] : __('Tax', 'woocommerce');
                                $column_label = !empty($tax_item['label']) ? $tax_item['label'] : __('Tax', 'woocommerce');
                                /* translators: %1$s: tax item name %2$s: tax class name  */
                                $column_tip = sprintf(esc_html__('%1$s (%2$s)', 'woocommerce'), $tax_item['name'], $tax_class_name);
                                ?>
                                <th class="line_tax tips" data-tip="<?php echo esc_attr($column_tip); ?>">
                                    <?php echo esc_attr($column_label); ?>
                                    <input type="hidden" class="order-tax-id" name="order_taxes[<?php echo esc_attr($tax_id); ?>]" value="<?php echo esc_attr($tax_item['rate_id']); ?>">
                                </th>
                                <?php
                            endforeach;
                        endif;
                        ?>
                        <th width="100px"><?php _e('Commission', 'dc-woocommerce-multi-vendor'); ?></th>
                    </tr>
                </thead>
                <tbody id="wcmp_order_line_items" class="wcmp-order-tbody">
                    <?php
                    foreach ($line_items as $item_id => $item) {
                        do_action('wcmp_vendor_dash_before_order_item_' . $item->get_type() . '_html', $item_id, $item, $order);

                        include 'html-order-item.php';

                        do_action('wcmp_vendor_dash_order_item_' . $item->get_type() . '_html', $item_id, $item, $order);
                    }
                    do_action('wcmp_vendor_dash_order_items_after_line_items', $order->get_id());
                    ?>
                </tbody>
                <tbody id="wcmp_order_shipping_line_items" class="wcmp-order-tbody">
                    <?php
                    $shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
                    foreach ($line_items_shipping as $item_id => $item) {
                        include 'html-order-shipping.php';
                    }
                    do_action('wcmp_vendor_dash_order_items_after_shipping', $order->get_id());
                    ?>
                </tbody>
                <tbody id="wcmp_order_refunds" class="wcmp-order-tbody">
                    <?php
                    $refunds = $order->get_refunds();

                    if ($refunds) {
                        foreach ($refunds as $refund) {
                            include 'html-order-refund.php';
                        }
                        do_action('wcmp_vendor_dash_order_items_after_refunds', $order->get_id());
                    }
                    ?>
                </tbody>
                <tbody id="wcmp_order_data_refunds" class="wcmp-order-tbody">
                    <tr class="wcmp-order-data-row">
                        <td colspan="7">
                            <div class="wcmp-order-totals-items wc-order-items-editable">
                                <?php
                                $coupons = $order->get_items('coupon');
                                if ($coupons) :
                                    ?>
                                    <div class="wc-used-coupons">
                                        <ul class="wc_coupon_list">
                                            <li><strong><?php esc_html_e('Coupon(s)', 'woocommerce'); ?></strong></li>
                                            <?php
                                            foreach ($coupons as $item_id => $item) :
                                                $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $item->get_code()));
                                                $class = $order->is_editable() ? 'code editable' : 'code';
                                                ?>
                                                <li class="<?php echo esc_attr($class); ?>">
                                                    <?php if ($post_id) : ?>
                                                        <?php
                                                        $post_url = apply_filters('wcmp_vendor_order_item_coupon_url', add_query_arg(
                                                                        array(
                                                            'post' => $post_id,
                                                            'action' => 'edit',
                                                                        ), admin_url('post.php')
                                                                ), $item, $order);
                                                        ?>
                                                        <a href="<?php echo esc_url($post_url); ?>" class="tips" data-tip="<?php echo esc_attr(wc_price($item->get_discount(), array('currency' => $order->get_currency()))); ?>">
                                                            <span><?php echo esc_html($item->get_code()); ?></span>
                                                        </a>
                                                    <?php else : ?>
                                                        <span class="tips" data-tip="<?php echo esc_attr(wc_price($item->get_discount(), array('currency' => $order->get_currency()))); ?>">
                                                            <span><?php echo esc_html($item->get_code()); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($order->is_editable()) : ?>
                                                        <a class="remove-coupon" href="javascript:void(0)" aria-label="Remove" data-code="<?php echo esc_attr($item->get_code()); ?>"></a>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <table class="wcmp-order-totals">
                                    <tr>
                                        <td class="label primary-color"><?php _e('Commission:', 'dc-woocommerce-multi-vendor'); ?></td>
                                        <td width="1%"></td>
                                        <td class="total primary-color">
                                            <?php echo $vendor_order->get_formatted_commission_total(); // WPCS: XSS ok.  ?>
                                        </td>
                                    </tr>
                                    <?php if (0 < $order->get_total_discount()) : ?>
                                        <tr>
                                            <td class="label"><?php esc_html_e('Discount:', 'woocommerce'); ?></td>
                                            <td width="1%"></td>
                                            <td class="total">
                                                <?php echo wc_price($order->get_total_discount(), array('currency' => $order->get_currency())); // WPCS: XSS ok.  ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php do_action('wcmp_vendor_order_totals_after_discount', $order->get_id()); ?>

                                    <?php if ($order->get_shipping_methods()) : ?>
                                        <tr>
                                            <td class="label"><?php esc_html_e('Shipping:', 'woocommerce'); ?></td>
                                            <td width="1%"></td>
                                            <td class="total">
                                                <?php
                                                $refunded = $order->get_total_shipping_refunded();
                                                if ($refunded > 0) {
                                                    echo '<del>' . strip_tags(wc_price($order->get_shipping_total(), array('currency' => $order->get_currency()))) . '</del> <ins>' . wc_price($order->get_shipping_total() - $refunded, array('currency' => $order->get_currency())) . '</ins>'; // WPCS: XSS ok.
                                                } else {
                                                    echo wc_price($order->get_shipping_total(), array('currency' => $order->get_currency())); // WPCS: XSS ok.
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php do_action('wcmp_vendor_order_totals_after_shipping', $order->get_id()); ?>

                                    <?php if (wc_tax_enabled()) : ?>
                                        <?php foreach ($order->get_tax_totals() as $code => $tax) : ?>
                                            <tr>
                                                <td class="label"><?php echo esc_html($tax->label); ?>:</td>
                                                <td width="1%"></td>
                                                <td class="total">
                                                    <?php
                                                    $refunded = $order->get_total_tax_refunded_by_rate_id($tax->rate_id);
                                                    if ($refunded > 0) {
                                                        echo '<del>' . strip_tags($tax->formatted_amount) . '</del> <ins>' . wc_price(WC_Tax::round($tax->amount, wc_get_price_decimals()) - WC_Tax::round($refunded, wc_get_price_decimals()), array('currency' => $order->get_currency())) . '</ins>'; // WPCS: XSS ok.
                                                    } else {
                                                        echo wp_kses_post($tax->formatted_amount);
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php do_action('wcmp_vendor_order_totals_after_tax', $order->get_id()); ?>

                                    <tr>
                                        <td class="label"><?php esc_html_e('Total', 'woocommerce'); ?>:</td>
                                        <td width="1%"></td>
                                        <td class="total">
                                            <?php echo $order->get_formatted_order_total(); // WPCS: XSS ok.  ?>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td class="label"><?php esc_html_e('Total Earned', 'dc-woocommerce-multi-vendor'); ?>:</td>
                                        <td width="1%"></td>
                                        <td class="total">
                                            <?php echo $vendor_order->get_formatted_order_total_earned(); // WPCS: XSS ok.  ?>
                                        </td>
                                    </tr>

                                    <?php do_action('wcmp_vendor_order_totals_after_total', $order->get_id()); ?>

                                    <?php if ($order->get_total_refunded()) : ?>
                                        <tr>
                                            <td class="label refunded-total"><?php esc_html_e('Refunded', 'woocommerce'); ?>:</td>
                                            <td width="1%"></td>
                                            <td class="total refunded-total">-<?php echo wc_price($order->get_total_refunded(), array('currency' => $order->get_currency())); // WPCS: XSS ok.  ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label refunded-total"><?php esc_html_e('Commission Refunded', 'dc-woocommerce-multi-vendor'); ?>:</td>
                                            <td width="1%"></td>
                                            <td class="total refunded-total"><?php echo $vendor_order->get_total_commission_refunded_amount(); // WPCS: XSS ok.  ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php do_action('wcmp_vendor_order_totals_after_refunded', $order->get_id()); ?>

                                </table>
                                <div class="clear"></div>
                            </div>

                            <div class="wcmp-order-actions  wcmp-order-data-row-toggle">
                                <?php if (0 < $order->get_total() - $order->get_total_refunded() || 0 < absint($order->get_item_count() - $order->get_item_count_refunded())) : ?>
                                <?php if( $order->get_status( 'edit' ) != 'cancelled' ) : ?>
                                    <button type="button" class="button refund-items btn btn-default"><?php esc_html_e('Refund', 'woocommerce'); ?></button>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php
                                // allow adding custom buttons
                                do_action('wcmp_order_details_add_order_action_buttons', $order);
                                ?>
                            </div>

                            <?php if (0 < $order->get_total() - $order->get_total_refunded() || 0 < absint($order->get_item_count() - $order->get_item_count_refunded())) : ?>
                                <div class="wcmp-order-refund-items wcmp-order-data-row-toggle" style="display: none;">
                                    <table class="wc-order-totals pull-right">
                                        <?php if ('yes' === get_option('woocommerce_manage_stock')) : ?>
                                            <tr>
                                                <td class="label"><label for="restock_refunded_items"><?php esc_html_e('Restock refunded items', 'woocommerce'); ?>:</label></td>
                                                <td class="total"><input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" <?php checked(apply_filters('woocommerce_restock_refunded_items', true)); ?> /></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="label"><?php esc_html_e('Amount already refunded', 'woocommerce'); ?>:</td>
                                            <td class="total">-<?php echo wc_price($order->get_total_refunded(), array('currency' => $order->get_currency())); // WPCS: XSS ok.   ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><?php esc_html_e('Total available to refund', 'woocommerce'); ?>:</td>
                                            <td class="total"><?php echo wc_price($order->get_total() - $order->get_total_refunded(), array('currency' => $order->get_currency())); // WPCS: XSS ok.   ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><label for="refund_amount"><?php esc_html_e('Refund amount', 'woocommerce'); ?>:</label></td>
                                            <td class="total">
                                                <input type="text" id="refund_amount" name="refund_amount" class="wc_input_price form-control" />
                                                <div class="clear"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label"><label for="refund_reason"><?php echo wc_help_tip(__('Note: the refund reason will be visible by the customer.', 'woocommerce')); ?> <?php esc_html_e('Reason for refund (optional):', 'woocommerce'); ?></label></td>
                                            <td class="total">
                                                <input type="text" id="refund_reason" name="refund_reason" class="form-control" />
                                                <div class="clear"></div>
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="clear"></div>
                                    <div class="refund-actions">
                                        <?php
                                        $refund_amount = '<span class="wc-order-refund-amount">' . wc_price(0, array('currency' => $order->get_currency())) . '</span>';
                                        $gateway_name = false !== $payment_gateway ? (!empty($payment_gateway->method_title) ? $payment_gateway->method_title : $payment_gateway->get_title() ) : __('Payment gateway', 'woocommerce');

                                        if (false !== $payment_gateway && $payment_gateway->can_refund_order($order)) {
                                            /* translators: refund amount, gateway name */
                                            //echo '<button type="button" class="button button-primary do-api-refund">' . sprintf(esc_html__('Refund %1$s via %2$s', 'woocommerce'), wp_kses_post($refund_amount), esc_html($gateway_name)) . '</button>';
                                        }
                                        ?>
                                        <?php /* translators: refund amount  */ ?>
                                        <button type="button" class="btn btn-default do-manual-refund tips" data-tip="<?php esc_attr_e('You will need to manually issue a refund through your payment gateway after using this.', 'woocommerce'); ?>"><?php printf(esc_html__('Refund %s manually', 'woocommerce'), wp_kses_post($refund_amount)); ?></button>
                                        <button type="button" class="btn btn-secondary cancel-action"><?php esc_html_e('Cancel', 'woocommerce'); ?></button>
                                        <input type="hidden" id="refunded_amount" name="refunded_amount" value="<?php echo esc_attr($order->get_total_refunded()); ?>" />
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody> 
            </table>
        </div>
    </div>
</div>