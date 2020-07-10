<?php
/**
 * The template for displaying vendor report
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-policy.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
global $WCMp;
$wcmp_policy_settings = get_option("wcmp_general_policies_settings_name");
$wcmp_capabilities_settings_name = get_option("wcmp_general_policies_settings_name");
$can_vendor_edit_policy_tab_label_field = apply_filters('can_vendor_edit_policy_tab_label_field', true);
$can_vendor_edit_cancellation_policy_field = apply_filters('can_vendor_edit_cancellation_policy_field', true);
$can_vendor_edit_refund_policy_field = apply_filters('can_vendor_edit_refund_policy_field', true);
$can_vendor_edit_shipping_policy_field = apply_filters('can_vendor_edit_shipping_policy_field', true);

$vendor_shipping_policy = isset($vendor_shipping_policy['value']) ? $vendor_shipping_policy['value'] : $wcmp_policy_settings['shipping_policy'];
$vendor_refund_policy = isset($vendor_refund_policy['value']) ? $vendor_refund_policy['value'] : $wcmp_policy_settings['refund_policy'];
$vendor_cancellation_policy = isset($vendor_cancellation_policy['value']) ? $vendor_cancellation_policy['value'] : $wcmp_policy_settings['cancellation_policy'];

$_wp_editor_settings = array('tinymce' => true);
if (!$WCMp->vendor_caps->vendor_can('is_upload_files')) {
    $_wp_editor_settings['media_buttons'] = false;
}
$_wp_editor_settings = apply_filters('wcmp_vendor_policies_wp_editor_settings', $_wp_editor_settings);
?>
<div class="col-md-12">
    <form method="post" name="shop_settings_form" class="wcmp_policy_form form-horizontal">
        <?php do_action('wcmp_before_vendor_policy'); ?>
        <?php if (apply_filters('wcmp_vendor_can_overwrite_policies', true) && get_wcmp_vendor_settings('is_policy_on', 'general') == 'Enable'): ?>
            <div class="panel panel-default pannel-outer-heading">
                <div class="panel-heading d-flex">
                    <h3><?php _e('Policy Details', 'dc-woocommerce-multi-vendor'); ?></h3>
                </div>
                <div class="panel-body panel-content-padding">
                        <div class="form-group">
                            <label class="control-label col-sm-3"><?php _e('Shipping Policy', 'dc-woocommerce-multi-vendor'); ?></label>
                            <div class="col-md-6 col-sm-9">
                                <?php $WCMp->wcmp_wp_fields->dc_generate_form_field(array("vendor_shipping_policy" => array('name' => 'vendor_shipping_policy', 'type' => 'wpeditor', 'class' => 'regular-textarea', 'value' => $vendor_shipping_policy, 'settings' => $_wp_editor_settings))); ?>
                                <!--textarea  class="no_input form-control" name="vendor_shipping_policy" cols="" rows=""><?php echo isset($vendor_shipping_policy['value']) ? $vendor_shipping_policy['value'] : $wcmp_policy_settings['shipping_policy']; ?></textarea-->
                            </div>  
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3"><?php _e('Refund Policy', 'dc-woocommerce-multi-vendor'); ?></label>
                            <div class="col-md-6 col-sm-9">
                                <?php $WCMp->wcmp_wp_fields->dc_generate_form_field(array("vendor_refund_policy" => array('name' => 'vendor_refund_policy', 'type' => 'wpeditor', 'class' => 'regular-textarea', 'value' => $vendor_refund_policy, 'settings' => $_wp_editor_settings))); ?>
                                <!--textarea  class="no_input form-control" name="vendor_refund_policy" cols="" rows=""><?php echo isset($vendor_refund_policy['value']) ? $vendor_refund_policy['value'] : $wcmp_policy_settings['refund_policy']; ?></textarea-->
                            </div>  
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3"><?php _e('Cancellation/Return/Exchange Policy', 'dc-woocommerce-multi-vendor'); ?></label>
                            <div class="col-md-6 col-sm-9">
                                <?php $WCMp->wcmp_wp_fields->dc_generate_form_field(array("vendor_cancellation_policy" => array('name' => 'vendor_cancellation_policy', 'type' => 'wpeditor', 'class' => 'regular-textarea', 'value' => $vendor_cancellation_policy, 'settings' => $_wp_editor_settings))); ?>
                                <!--textarea class="no_input form-control" type="text" name="vendor_cancellation_policy" cols="" rows=""><?php echo isset($vendor_cancellation_policy['value']) ? $vendor_cancellation_policy['value'] : ''; ?></textarea-->
                            </div>  
                        </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (apply_filters('wcmp_vendor_can_overwrite_customer_support', true) && get_wcmp_vendor_settings('is_customer_support_details', 'general') == 'Enable') { ?>
            <div class="panel panel-default pannel-outer-heading">
                <div class="panel-heading d-flex">
                    <h3><?php _e('Customer Support Details', 'dc-woocommerce-multi-vendor'); ?></h3>
                </div>
                <div class="panel-body panel-content-padding">
                    <div class="form-group">
                        <label class="control-label col-sm-3"><?php _e('Phone', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input  class="no_input form-control" type="text" name="vendor_customer_phone" placeholder="" value="<?php echo isset($vendor_customer_phone['value']) ? $vendor_customer_phone['value'] : ''; ?>">
                        </div>  
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3"><?php _e('Email', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input  class="no_input form-control" type="email" name="vendor_customer_email" placeholder="" value="<?php echo isset($vendor_customer_email['value']) ? $vendor_customer_email['value'] : ''; ?>">
                        </div>  
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3"><?php _e('Address', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <div class="form-group">
                                <div class="col-sm-6 inp-btm-margin">
                                    <input  class="no_input form-control" type="text" placeholder="<?php _e('Address line 1', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_csd_return_address1"  value="<?php echo isset($vendor_csd_return_address1['value']) ? $vendor_csd_return_address1['value'] : ''; ?>">
                                </div>
                                <div class="col-sm-6 inp-btm-margin">
                                    <input  class="no_input form-control" type="text" placeholder="<?php _e('Address line 2', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_csd_return_address2"  value="<?php echo isset($vendor_csd_return_address2['value']) ? $vendor_csd_return_address2['value'] : ''; ?>">
                                </div>
                                <div class="col-sm-6 inp-btm-margin">
                                    <input  class="no_input form-control" type="text" placeholder="<?php _e('Country', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_csd_return_country" value="<?php echo isset($vendor_csd_return_country['value']) ? $vendor_csd_return_country['value'] : ''; ?>">
                                </div>
                                <div class="col-sm-6 inp-btm-margin">
                                    <input  class="no_input form-control" type="text" placeholder="<?php _e('State', 'dc-woocommerce-multi-vendor'); ?>"  name="vendor_csd_return_state" value="<?php echo isset($vendor_csd_return_state['value']) ? $vendor_csd_return_state['value'] : ''; ?>">
                                </div>
                                <div class="col-sm-6 inp-btm-margin">
                                    <input  class="no_input form-control" type="text" placeholder="<?php _e('City', 'dc-woocommerce-multi-vendor'); ?>"  name="vendor_csd_return_city" value="<?php echo isset($vendor_csd_return_city['value']) ? $vendor_csd_return_city['value'] : ''; ?>">
                                </div>
                                <div class="col-sm-6 inp-btm-margin">
                                    <input  class="no_input form-control" type="text" placeholder="<?php _e('Zip code', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_csd_return_zip" value="<?php echo isset($vendor_csd_return_zip['value']) ? $vendor_csd_return_zip['value'] : ''; ?>">
                                </div>
                            </div>
                        </div>  
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php do_action('wcmp_after_vendor_policy'); ?>
        <?php do_action('other_exta_field_dcmv'); ?>
        <div class="wcmp-action-container">
            <button class="btn btn-default" name="store_save_policy"><?php _e('Save Options', 'dc-woocommerce-multi-vendor'); ?></button>
            <div class="clear"></div>
        </div>
    </form>
</div>