<?php

/**
 * Advanced product tab template
 *
  * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/views/html-product-data-advanced.php
 *
 * @author  WC Marketplace
 * @package     WCMp/Templates
 * @version   3.3.0
 */
defined( 'ABSPATH' ) || exit;
?>
<div role="tabpanel" class="tab-pane fade" id="advanced_product_data">
    <div class="row-padding"> 
        <div class="hide_if_external hide_if_grouped">
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="_purchase_note"><?php esc_html_e( 'Purchase note', 'woocommerce' ); ?></label>
                <div class="col-md-6 col-sm-9">
                    <textarea id="_purchase_note" name="_purchase_note" class="form-control"><?php esc_html_e( $product_object->get_purchase_note( 'edit' ) ); ?></textarea>
                </div>
            </div> 
        </div> 
        <div class="form-group">
            <label class="control-label col-sm-3 col-md-3" for="menu_order"><?php esc_html_e( 'Menu order', 'woocommerce' ); ?></label>
            <div class="col-md-6 col-sm-9">
                <input id="menu_order" name="menu_order" type="number" class="form-control" value="<?php esc_attr_e( $product_object->get_menu_order( 'edit' ) ); ?>" step="1">
            </div>
        </div> 

        <?php if ( post_type_supports( 'product', 'comments' ) ) : ?> 
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="comment_status"><?php esc_html_e( 'Enable reviews', 'woocommerce' ); ?></label>
                <div class="col-md-6 col-sm-9">
                    <input id="comment_status" name="comment_status" type="checkbox" class="form-control" value="<?php esc_attr_e( $product_object->get_reviews_allowed( 'edit' ) ? 'open' : 'closed'  ); ?>" <?php checked( $product_object->get_reviews_allowed( 'edit' ), true ); ?>>
                </div>
            </div> 
        <?php endif; ?>

        <?php do_action( 'wcmp_afm_product_options_advanced', $post->ID, $product_object, $post ); ?>
    </div>
</div>