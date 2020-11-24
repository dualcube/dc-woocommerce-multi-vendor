<?php
/**
 * The template for displaying vendor dashboard
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor_message_to_buyer.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.3.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $WCMp;
?>
<table style="width:100%; color: #737373; border: 1px solid #e4e4e4; background:none;" border="0" cellpadding="8" cellspacing="0">
    <tbody>
        <?php
        $cc = 0; 
        foreach ($vendor_array as $vendor_id => $products) {
            if(is_user_wcmp_vendor($vendor_id)){
            $vendor_meta = get_user_meta($vendor_id);
            $vendor = get_wcmp_vendor($vendor_id);
            $vendor_message_to_buyer = apply_filters('wcmp_display_vendor_message_to_buyer', get_user_meta($vendor_id, '_vendor_message_to_buyers', true), $vendor_id);
            ?>
        <?php if ($cc == 0) { ?>
                    <tr>
                        <th style="padding:10px 10px; background:none; border-right: 1px solid #e4e4e4; border-bottom: 1px solid #e4e4e4; width:50%;" align="left" valign="top"><?php echo esc_html_e('Vendor Details', 'dc-woocommerce-multi-vendor'); ?></th>
                    <?php if( $vendor_message_to_buyer ) : ?>    
                        <th style="padding:10px 10px;width: 50%; border-bottom: 1px solid #e4e4e4;  background:none;" align="left" valign="top"><?php echo esc_html_e('Message', 'dc-woocommerce-multi-vendor'); ?></th>
                    <?php endif; ?>
                    </tr>
        <?php } ?>
                <tr>
                    <td style="padding:10px 10px; background:none; border-right: 1px solid #e4e4e4; border-bottom: 1px solid #e4e4e4; width:50%;" align="left" valign="top">
                        <p><strong><?php echo esc_html_e('Vendor Name', 'dc-woocommerce-multi-vendor'); ?> </strong><br>
                            <?php echo $vendor->page_title; ?> </p>
                        <p><strong><?php echo esc_html_e('Product Name', 'dc-woocommerce-multi-vendor'); ?> </strong> <br>
                        <?php echo esc_html($products); ?>
                        </p>
                        <?php
                        if (get_wcmp_vendor_settings('is_customer_support_details', 'general') == 'Enable') {
                            if (apply_filters('wcmp_vendor_can_overwrite_customer_support', true)) {
                                ?>
                            <?php if( isset( $vendor_meta['_vendor_customer_email'][0] ) || isset( $vendor_meta['_vendor_customer_phone'][0] ) ) : ?>
                                <p style="border-bottom:1px solid #eeeeee; padding-bottom:10px"> <strong><?php echo esc_html_e('Customer Support Details', 'dc-woocommerce-multi-vendor'); ?></strong></p>
                            <?php endif; ?>
                                <?php if (isset($vendor_meta['_vendor_customer_email'][0])) { ?>
                                    <p><strong><?php echo esc_html_e('Email : ', 'dc-woocommerce-multi-vendor'); ?></strong><br>
                                        <a style="color:#505050;font-weight:normal;text-decoration:underline" href="mailto:<?php echo esc_html($vendor_meta['_vendor_customer_email'][0]); ?>" target="_blank"><?php echo esc_html($vendor_meta['_vendor_customer_email'][0]); ?></a>
                                    </p>
                                    <?php } ?>
                                <?php if (isset($vendor_meta['_vendor_customer_phone'][0])) { ?>
                                    <p><strong><?php echo esc_html_e('Phone : ', 'dc-woocommerce-multi-vendor'); ?></strong> <br>
                                        <?php echo esc_html($vendor_meta['_vendor_customer_phone'][0]); ?></p>
                                    <?php } ?>
                                <?php if( isset( $vendor_meta['_vendor_csd_return_address1'][0] ) || isset( $vendor_meta['_vendor_csd_return_city'][0] ) || isset( $vendor_meta['_vendor_csd_return_country'][0] ) ) : ?>
                                <p><strong><?php echo esc_html_e('Return Address ', 'dc-woocommerce-multi-vendor'); ?></strong></p>
                                <?php endif; ?>
                                <p>
                                    <?php if (isset($vendor_meta['_vendor_csd_return_address1'][0])) {
                                        echo esc_html($vendor_meta['_vendor_csd_return_address1'][0]); ?><br> <?php } ?>
                                    <?php if (isset($vendor_meta['_vendor_csd_return_address2'][0])) {
                                        echo esc_html($vendor_meta['_vendor_csd_return_address2'][0]); ?><br> <?php } ?>
                                <?php if (isset($vendor_meta['_vendor_csd_return_city'][0])) {
                                    echo esc_html($vendor_meta['_vendor_csd_return_city'][0]); ?><br> <?php } ?>
                                <?php if (isset($vendor_meta['_vendor_csd_return_state'][0])) {
                                    echo esc_html($vendor_meta['_vendor_csd_return_state'][0]); ?><br> <?php } ?>
                <?php if (isset($vendor_meta['_vendor_csd_return_country'][0])) {
                    echo esc_html($vendor_meta['_vendor_csd_return_country'][0]); ?><br> <?php } ?>
                                <?php if (isset($vendor_meta['_vendor_csd_return_zip'][0])) {
                                    echo esc_html($vendor_meta['_vendor_csd_return_zip'][0]);
                                } ?>
                                </p>
                            <?php } else { ?>
                                <?php if( isset( $customer_support_details_settings['csd_email'] ) || isset( $customer_support_details_settings['csd_phone'] ) ) : ?>
                                <p style="border-bottom:1px solid #eeeeee; padding-bottom:10px"> <strong><?php echo esc_html_e('Customer Support Details', 'dc-woocommerce-multi-vendor'); ?></strong></p>
                                <?php endif; ?>
                                    <?php if (isset($customer_support_details_settings['csd_email'])) { ?>
                                    <p><strong><?php echo esc_html_e('Email : ', 'dc-woocommerce-multi-vendor'); ?></strong><br>
                                        <a style="color:#505050;font-weight:normal;text-decoration:underline" href="mailto:<?php echo $customer_support_details_settings['csd_email']; ?>" target="_blank"><?php echo esc_html($customer_support_details_settings['csd_email']); ?></a>					
                                    </p>
                                    <?php } ?>
                                    <?php if (isset($customer_support_details_settings['csd_phone'])) { ?>
                                    <p><strong><?php echo esc_html_e('Phone : ', 'dc-woocommerce-multi-vendor'); ?></strong> <br>
                                        <?php echo esc_html($customer_support_details_settings['csd_phone']); ?></p>
                                    <?php } ?>
                                <?php if( isset( $customer_support_details_settings['csd_return_address_1'] ) || isset( $customer_support_details_settings['csd_return_city'] ) || isset( $customer_support_details_settings['csd_return_country'] ) ) : ?>
                                <p><strong><?php echo esc_html_e('Return Address ', 'dc-woocommerce-multi-vendor'); ?></strong></p>
                                <?php endif; ?>
                                <p>
                                    <?php if (isset($customer_support_details_settings['csd_return_address_1'])) { ?>
                                        <?php echo esc_html($customer_support_details_settings['csd_return_address_1']); ?><br>
                                    <?php } ?>
                                    <?php if (isset($customer_support_details_settings['csd_return_address_2'])) { ?>
                                        <?php echo esc_html($customer_support_details_settings['csd_return_address_2']); ?><br>
                                    <?php } ?>
                                    <?php if (isset($customer_support_details_settings['csd_return_city'])) { ?>
                                        <?php echo esc_html($customer_support_details_settings['csd_return_city']); ?> <br>
                                <?php } ?>
                                <?php if (isset($customer_support_details_settings['csd_return_state'])) { ?>
                    <?php echo esc_html($customer_support_details_settings['csd_return_state']); ?><br>
                                <?php } ?>
                                <?php if (isset($customer_support_details_settings['csd_return_country'])) { ?>
                    <?php echo esc_html($customer_support_details_settings['csd_return_country']); ?><br>
                        <?php } ?>
                        <?php if (isset($customer_support_details_settings['csd_return_zipcode'])) { ?>
                    <?php echo esc_html($customer_support_details_settings['csd_return_zipcode']); ?>
                <?php } ?>					
                                </p>						
            <?php }
        } ?>							
                    </td>
                    <?php if( $vendor_message_to_buyer ) : ?>   
                    <td style="padding:10px 10px;width: 50%; border-bottom: 1px solid #e4e4e4;  background:none;" align="left" valign="top">					
        <?php echo wp_kses_post($vendor_message_to_buyer); ?>
                    </td>
                    <?php endif; ?>
                </tr>
        <?php $cc++;
    }
} ?>                           
    </tbody>
</table>




