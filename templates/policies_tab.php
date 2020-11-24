<?php
/**
 * The template for displaying single product page vendor tab 
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/policies_tab.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version   2.3.0
 */
global $product, $WCMp, $post;
$policies = get_wcmp_product_policies($product->get_id());
?>
<div class="wcmp-product-policies">
    <?php if(isset($policies['shipping_policy']) && !empty($policies['shipping_policy'])){ ?>
    <div class="wcmp-shipping-policies policy">
        <h2 class="wcmp_policies_heading heading"><?php echo apply_filters('wcmp_shipping_policies_heading', esc_html_e('Shipping Policy', 'dc-woocommerce-multi-vendor')); ?></h2>
        <div class="wcmp_policies_description description" ><?php echo wp_kses_post($policies['shipping_policy']); ?></div>
    </div>
    <?php } if(isset($policies['refund_policy']) && !empty($policies['refund_policy'])){ ?>
    <div class="wcmp-refund-policies policy">
        <h2 class="wcmp_policies_heading heading heading"><?php echo apply_filters('wcmp_refund_policies_heading', esc_html_e('Refund Policy', 'dc-woocommerce-multi-vendor')); ?></h2>
        <div class="wcmp_policies_description description" ><?php echo wp_kses_post($policies['refund_policy']); ?></div>
    </div>
    <?php } if(isset($policies['cancellation_policy']) && !empty($policies['cancellation_policy'])){ ?>
    <div class="wcmp-cancellation-policies policy">
        <h2 class="wcmp_policies_heading heading"><?php echo apply_filters('wcmp_cancellation_policies_heading', esc_html_e('Cancellation / Return / Exchange Policy', 'dc-woocommerce-multi-vendor')); ?></h2>
        <div class="wcmp_policies_description description" ><?php echo wp_kses_post($policies['cancellation_policy']); ?></div>
    </div>
    <?php } ?>
</div>