<?php

/**
 * Usage restriction coupon tab template
 *
 * Used by add-coupon.php template
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vendor-dashboard/coupon-manager/views/html-coupon-data-usage-restriction.php.
 *
 * HOWEVER, on occasion WCMp will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 		WC Marketplace
 * @package 	WCMp/templates/vendor dashboard/coupon manager/views
 * @version     3.3.0
 */
defined( 'ABSPATH' ) || exit;
$vendor = apply_filters( 'wcmp_vendor_select_product_for_add_coupon' , get_wcmp_vendor( get_current_user_id() ) );
?>
<div role="tabpanel" class="tab-pane fade" id="usage_restriction_coupon_data">
    <div class="row-padding">
        <?php do_action( 'wcmp_afm_before_usage_restriction_coupon_data', $post->ID, $coupon ); ?>
        <div class="form-group-row"> 
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="minimum_amount">
                    <?php esc_html_e( 'Minimum spend', 'woocommerce' ); ?>
                    <span class="img_tip" data-desc="<?php esc_html_e( 'This field allows you to set the minimum spend (subtotal) allowed to use the coupon.', 'woocommerce' ); ?>"></span>
                </label>
                <div class="col-md-6 col-sm-9">
                    <input type="text" id="minimum_amount" name="minimum_amount" class="form-control" value="<?php esc_attr_e( $coupon->get_minimum_amount( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'No minimum', 'woocommerce' ); ?>">
                </div>
            </div> 
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="maximum_amount">
                    <?php esc_html_e( 'Maximum spend', 'woocommerce' ); ?>
                    <span class="img_tip" data-desc="<?php esc_html_e( 'This field allows you to set the maximum spend (subtotal) allowed when using the coupon.', 'woocommerce' ); ?>"></span>
                </label>
                <div class="col-md-6 col-sm-9">
                    <input type="text" id="maximum_amount" name="maximum_amount" class="form-control" value="<?php esc_attr_e( $coupon->get_maximum_amount( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'No maximum', 'woocommerce' ); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="individual_use">
                    <?php esc_html_e( 'Individual use only', 'woocommerce' ); ?>
                </label>
                <div class="col-md-6 col-sm-9">
                    <input type="checkbox" id="individual_use" name="individual_use" class="form-control" value="yes" <?php checked( wc_bool_to_string( $coupon->get_individual_use( 'edit' ) ), 'yes' ); ?>>
                    <span class="form-text"><?php esc_html_e( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce' ); ?></span>
                </div>
            </div> 
            <div class="form-group ">
                <label class="control-label col-sm-3 col-md-3" for="exclude_sale_items">
                    <?php esc_html_e( 'Exclude sale items', 'woocommerce' ); ?>
                </label>
                <div class="col-md-6 col-sm-9">
                    <input type="checkbox" id="exclude_sale_items" name="exclude_sale_items" class="form-control" value="yes" <?php checked( wc_bool_to_string( $coupon->get_exclude_sale_items( 'edit' ) ), 'yes' ); ?>>
                    <span class="form-text"><?php esc_html_e( 'Check this box if the coupon should not apply to items on sale. Per-item coupons will only work if the item is not on sale. Per-cart coupons will only work if there are items in the cart that are not on sale.', 'woocommerce' ); ?></span>
                </div>
            </div> 
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3">
                    <?php esc_html_e( 'Products', 'woocommerce' ); ?>
                    <span class="img_tip" data-desc="<?php esc_html_e( 'Products that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.', 'woocommerce' ); ?>"></span>
                </label>
                <div class="col-md-6 col-sm-9 coupon-products-wrap">
                    <select id="products" class="form-control wc-enhanced-select" multiple="multiple" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Any products', 'dc-woocommerce-multi-vendor' ); ?>">
                        <?php
                        $vendor_product_ids = wp_list_pluck( $vendor->get_products_ids(), 'ID' );
                        $product_ids = $coupon->get_product_ids( 'edit' );
                        foreach ( $vendor_product_ids as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '"' . wc_selected( $product_id, $product_ids ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <button type="button" class="button plus btn btn-secondary select_all_attributes"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></button>
                    <button type="button" class="button minus btn btn-secondary select_no_attributes"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></button>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3">
                    <?php esc_html_e( 'Exclude products', 'woocommerce' ); ?>
                    <span class="img_tip" data-desc="<?php esc_html_e( 'Products that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.', 'woocommerce' ); ?>"></span>
                </label>
                <div class="col-md-6 col-sm-9">
                    <select id="exclude_products" class="form-control wc-enhanced-select" multiple="multiple" name="exclude_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>">
                        <?php
                        $product_ids = $coupon->get_excluded_product_ids( 'edit' );
                        $vendor_product_ids = wp_list_pluck( $vendor->get_products_ids(), 'ID' );
                        foreach ( $vendor_product_ids as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '"' . wc_selected( $product_id, $product_ids ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group-row">
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="product_categories">
                    <?php esc_html_e( 'Product categories', 'woocommerce' ); ?>
                    <span class="img_tip" data-desc="<?php esc_html_e( 'Product categories that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.', 'woocommerce' ); ?>"></span>
                </label>
                <div class="col-md-6 col-sm-9">
                    <select class="form-control wc-enhanced-select" multiple="multiple" id="product_categories" name="product_categories[]" data-placeholder="<?php esc_attr_e( 'Any category', 'woocommerce' ); ?>">
                        <?php
                        $category_ids = $coupon->get_product_categories( 'edit' );
                        $categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                        if ( $categories ) {
                            foreach ( $categories as $cat ) {
                                echo '<option value="' . esc_attr( $cat->term_id ) . '"' . wc_selected( $cat->term_id, $category_ids ) . '>' . esc_html( $cat->name ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3 col-md-3" for="exclude_product_categories">
                    <?php esc_html_e( 'Exclude categories', 'woocommerce' ); ?>
                    <span class="img_tip" data-desc="<?php esc_html_e( 'Product categories that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.', 'woocommerce' ); ?>"></span>
                </label>
                <div class="col-md-6 col-sm-9">
                    <select class="form-control wc-enhanced-select" multiple="multiple" id="exclude_product_categories" name="exclude_product_categories[]" data-placeholder="<?php esc_attr_e( 'No categories', 'woocommerce' ); ?>">
                        <?php
                        $category_ids = $coupon->get_excluded_product_categories( 'edit' );
                        $categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                        if ( $categories ) {
                            foreach ( $categories as $cat ) {
                                echo '<option value="' . esc_attr( $cat->term_id ) . '"' . wc_selected( $cat->term_id, $category_ids ) . '>' . esc_html( $cat->name ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div> 
        <div class="form-group">
            <label class="control-label col-sm-3 col-md-3" for="customer_email">
                <?php esc_html_e( 'Allowed emails', 'woocommerce' ); ?>
                <span class="img_tip" data-desc="<?php esc_html_e( 'Whitelist of billing emails to check against when an order is placed. Separate email addresses with commas. You can also use an asterisk (*) to match parts of an email. For example "*@gmail.com" would match all gmail addresses.', 'woocommerce' ); ?>"></span>
            </label>
            <div class="col-md-6 col-sm-9">
                <input type="email" id="customer_email" name="customer_email" class="form-control" value="<?php esc_attr_e( implode( ', ', (array) $coupon->get_email_restrictions( 'edit' ) ) ); ?>" placeholder="<?php esc_attr_e( 'No restrictions', 'woocommerce' ); ?>" multiple="multiple">
            </div>
        </div> 
        <?php do_action( 'wcmp_afm_after_usage_restriction_coupon_data', $post->ID, $coupon ); ?>
    </div>
</div>