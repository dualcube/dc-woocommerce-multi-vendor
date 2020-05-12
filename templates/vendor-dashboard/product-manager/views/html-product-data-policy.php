<?php

/**
 * Policies product tab template
 *
 * Used by wcmp-afm-add-product.php template
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/views/html-product-data-policy.php
 *
 * @author  WC Marketplace
 * @package     WCMp/Templates
 * @version   3.3.0
 */
defined( 'ABSPATH' ) || exit;

$_wcmp_shipping_policy = get_post_meta( $id, '_wcmp_shipping_policy', true );
$_wcmp_refund_policy = get_post_meta( $id, '_wcmp_refund_policy', true );
$_wcmp_cancallation_policy = get_post_meta( $id, '_wcmp_cancallation_policy', true );
?>
<div role="tabpanel" class="tab-pane fade" id="product_policy_data">
    <div class="row-padding"> 
        <?php do_action( 'wcmp_afm_before_vendor_policies', $post->ID, $product_object, $post ); ?>
        <?php if ( apply_filters( 'can_vendor_edit_shipping_policy_field', true ) ) : ?>
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="_wcmp_shipping_policy"><?php esc_attr_e( 'Shipping Policy', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-6 col-sm-9"><?php
                    $shipping_policy_settings = array(
                        'textarea_name' => '_wcmp_shipping_policy',
                        'textarea_rows' => get_option('default_post_edit_rows', 10),
                        'quicktags'     => array( 'buttons' => 'em,strong,link' ),
                        'tinymce'       => array(
                            'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                            'theme_advanced_buttons2' => '',
                        ),
                        'editor_css'    => '<style>#wp-_wcmp_shipping_policy-editor-container .wp-editor-area{height:100px; width:100%;}</style>',
                    );
                    wp_editor( $_wcmp_shipping_policy, '_wcmp_shipping_policy', $shipping_policy_settings );
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( apply_filters( 'can_vendor_edit_refund_policy_field', true ) ) : ?>
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="_wcmp_refund_policy"><?php esc_attr_e( 'Refund Policy', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-6 col-sm-9"><?php
                    $refund_policy_settings = array(
                        'textarea_name' => '_wcmp_refund_policy',
                        'textarea_rows' => get_option('default_post_edit_rows', 10),
                        'quicktags'     => array( 'buttons' => 'em,strong,link' ),
                        'tinymce'       => array(
                            'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                            'theme_advanced_buttons2' => '',
                        ),
                        'editor_css'    => '<style>#wp-_wcmp_refund_policy-editor-container .wp-editor-area{height:100px; width:100%;}</style>',
                    );
                    wp_editor( $_wcmp_refund_policy, '_wcmp_refund_policy', $refund_policy_settings );
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( apply_filters( 'can_vendor_edit_cancellation_policy_field', true ) ) : ?>
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="_wcmp_cancallation_policy"><?php esc_attr_e( 'Cancellation/Return/Exchange Policy', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-6 col-sm-9"><?php
                    $cancallation_policy_settings = array(
                        'textarea_name' => '_wcmp_cancallation_policy',
                        'textarea_rows' => get_option('default_post_edit_rows', 10),
                        'quicktags'     => array( 'buttons' => 'em,strong,link' ),
                        'tinymce'       => array(
                            'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                            'theme_advanced_buttons2' => '',
                        ),
                        'editor_css'    => '<style>#wp-_wcmp_cancallation_policy-editor-container .wp-editor-area{height:100px; width:100%;}</style>',
                    );
                    wp_editor( $_wcmp_cancallation_policy, '_wcmp_cancallation_policy', $cancallation_policy_settings );
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php do_action( 'wcmp_afm_after_vendor_policies', $post->ID, $product_object, $post ); ?>
    </div>
</div>