<?php

/**
 * Linked products product tab template
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/views/html-product-data-linked-products.php
 *
 * @author  WC Marketplace
 * @package     WCMp/Templates
 * @version   3.3.0
 */
defined( 'ABSPATH' ) || exit;
?>
<div role="tabpanel" class="tab-pane fade" id="linked_product_data">
    <div class="row-padding">
        <?php if ( wcmp_is_allowed_product_type( 'grouped' ) ) : ?>
            <div class="show_if_grouped"> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="grouped_products"><?php esc_html_e( 'Grouped products', 'woocommerce' ); ?></label>
                    <div class="col-md-6 col-sm-9">
                        <select class="wc-product-search form-control" multiple="multiple" id="grouped_products" name="grouped_products[]" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-exclude="<?php echo intval( $post->ID ); ?>">
                            <?php
                            $product_ids = $product_object->is_type( 'grouped' ) ? $product_object->get_children( 'edit' ) : array();

                            foreach ( $product_ids as $product_id ) {
                                $product = wc_get_product( $product_id );
                                if ( is_object( $product ) ) {
                                    echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div> 
            </div>
        <?php endif; ?> 
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="upsell_ids"><?php esc_html_e( 'Upsells', 'woocommerce' ); ?></label>
                <div class="col-md-6 col-sm-9">
                    <select class="wc-product-search form-control" multiple="multiple" id="upsell_ids" name="upsell_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
                        <?php
                        $product_ids = $product_object->get_upsell_ids( 'edit' );

                        foreach ( $product_ids as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div> 

        <div class="hide_if_grouped hide_if_external">
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="crosssell_ids"><?php esc_html_e( 'Cross-sells', 'woocommerce' ); ?></label>
                <div class="col-md-6 col-sm-9">
                    <select class="wc-product-search form-control" multiple="multiple" id="crosssell_ids" name="crosssell_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
                        <?php
                        $product_ids = $product_object->get_cross_sell_ids( 'edit' );

                        foreach ( $product_ids as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div> 
    </div>
    <?php do_action( 'wcmp_afm_product_options_related', $post->ID, $product_object, $post ); ?> 
</div>