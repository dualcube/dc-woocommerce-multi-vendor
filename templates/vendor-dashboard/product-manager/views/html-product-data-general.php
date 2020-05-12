<?php

/**
 * General product tab template
 *
  * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/views/html-product-data-general.php
 *
 * @author  WC Marketplace
 * @package     WCMp/Templates
 * @version   3.3.0
 */
defined( 'ABSPATH' ) || exit;
global $WCMp;
?>
<div role="tabpanel" class="tab-pane fade" id="general_product_data">
    <div class="row-padding">
        <?php do_action( 'wcmp_afm_before_general_product_data', $post->ID, $product_object, $post ); ?>
        <?php
        $product_url_visibility = apply_filters( 'general_tab_product_url_section', array( 'external' ) );
        if ( call_user_func_array( "wcmp_is_allowed_product_type", $product_url_visibility ) ) :
            $show_classes = implode( ' ', preg_filter( '/^/', 'show_if_', $product_url_visibility ) );
            ?>
            <div class="form-group-row <?php echo $show_classes; ?>"> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_product_url"><?php _e( 'Product URL', 'woocommerce' ); ?></label>
                    <div class="col-md-6 col-sm-9">
                        <input id="_product_url" name="_product_url" value="<?php echo is_callable( array( $product_object, 'get_product_url' ) ) ? $product_object->get_product_url( 'edit' ) : ''; ?>" type="text" placeholder="http://" class="form-control">
                    </div>
                </div> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_button_text"><?php _e( 'Button text', 'woocommerce' ); ?></label>
                    <div class="col-md-6 col-sm-9">
                        <input id="_button_text" name="_button_text" value="<?php echo is_callable( array( $product_object, 'get_button_text' ) ) ? $product_object->get_button_text( 'edit' ) : ''; ?>" type="text" placeholder="<?php echo _x( 'Buy product', 'placeholder', 'woocommerce' ); ?>" class="form-control">
                    </div>
                </div> 
            </div>
        <?php endif; ?>
        <?php
        $pricing_visibility = apply_filters( 'general_tab_pricing_section', array( 'simple', 'external' ) );
        if ( call_user_func_array( "wcmp_is_allowed_product_type", $pricing_visibility ) ) :
            $show_classes = implode( ' ', preg_filter( '/^/', 'show_if_', $pricing_visibility ) );
            ?>
            <div class="form-group-row pricing <?php echo $show_classes; ?>"> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_regular_price"><?php echo __( 'Regular price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
                    <div class="col-md-6 col-sm-9">
                        <input type="text" id="_regular_price" name="_regular_price" value="<?php echo $product_object->get_regular_price( 'edit' ); ?>" class="form-control">
                    </div>
                </div>  
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_sale_price"><?php echo __( 'Sale price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
                    <div class="col-md-6 col-sm-9">
                        <input type="text" id="_sale_price" name="_sale_price" value="<?php echo $product_object->get_sale_price( 'edit' ); ?>" class="form-control">
                        <a href="#" class="pull-right sale_schedule form-text"><?php _e( 'Schedule', 'woocommerce' ); ?></a>
                    </div>
                </div> 
                <?php
                $sale_price_dates_from = $product_object->get_date_on_sale_from( 'edit' ) && ( $date = $product_object->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() ) ? date_i18n( 'Y-m-d', $date ) : '';
                $sale_price_dates_to = $product_object->get_date_on_sale_to( 'edit' ) && ( $date = $product_object->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() ) ? date_i18n( 'Y-m-d', $date ) : '';
                ?> 
                <div class="form-group sale_price_dates_fields">
                    <label class="control-label col-sm-3 col-md-3"><?php _e( 'Sale price dates', 'woocommerce' ); ?></label>
                    <div class="col-md-6 col-sm-9">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="date-inp-wrap">
                                    <input type="text" datepicker class="form-control sale_price_dates_from" name="_sale_price_dates_from" id="_sale_price_dates_from" value="<?php echo esc_attr( $sale_price_dates_from ); ?>" placeholder="<?php echo esc_html( _x( 'From&hellip;', 'placeholder', 'woocommerce' ) ) . ' YYYY-MM-DD'; ?>" maxlength="10" pattern="<?php echo esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />
                                </span>
                            </div>
                            <div class="col-md-6">
                                <span class="date-inp-wrap">
                                    <input type="text" datepicker class="form-control sale_price_dates_to" name="_sale_price_dates_to" id="_sale_price_dates_to" value="<?php echo esc_attr( $sale_price_dates_to ); ?>" placeholder="<?php echo esc_html( _x( 'To&hellip;', 'placeholder', 'woocommerce' ) ) . '  YYYY-MM-DD'; ?>" maxlength="10" pattern="<?php echo esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />
                                </span>
                            </div>
                        </div>
                        <a href="#" class="pull-right cancel_sale_schedule form-text"><?php _e( 'Cancel', 'woocommerce' ); ?></a>
                    </div>
                </div> 
                <?php do_action( 'wcmp_afm_product_options_pricing', $post->ID, $product_object, $post ); ?> 
            </div>
        <?php endif; ?>
        <?php if ( $WCMp->vendor_caps->vendor_can( 'downloadable' ) ) : ?>
            <div class="form-group-row show_if_downloadable"> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3"><?php esc_html_e( 'Downloadable files', 'woocommerce' ); ?></label>
                    <div class="clearfix"></div>
                    <div class="col-md-9">
                        <div class="downloadable_files">
                            <table class="table table-outer-border">
                                <thead>
                                    <tr>
                                        <th>&nbsp;</th>
                                        <th><?php _e( 'Name', 'woocommerce' ); ?></th>
                                        <th><?php _e( 'File URL', 'woocommerce' ); ?></th>
                                        <th>&nbsp;</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $downloadable_files = $product_object->get_downloads( 'edit' );
                                    if ( $downloadable_files ) {
                                        foreach ( $downloadable_files as $key => $file ) {
                                            include( 'html-product-download.php' );
                                        }
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5">
                                            <a href="#" class="btn btn-default insert" data-row="<?php
                                            $key = '';
                                            $file = array(
                                                'file' => '',
                                                'name' => '',
                                            );
                                            ob_start();
                                            include( 'html-product-download.php' );
                                            echo esc_attr( ob_get_clean() );
                                            ?>"><?php esc_html_e( 'Add File', 'woocommerce' ); ?></a>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>  
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_download_limit">
                        <?php echo __( 'Download limit', 'woocommerce' ); ?>
                        <span class="img_tip" data-desc="<?php esc_attr_e( 'Leave blank for unlimited re-downloads.', 'woocommerce' ) ?>"></span>
                    </label>
                    <div class="col-md-6 col-sm-9">
                        <input class="form-control" type="text" id="_download_limit" placeholder="<?php _e( 'Unlimited', 'woocommerce' ); ?>" name="_download_limit" value="<?php echo -1 === $product_object->get_download_limit( 'edit' ) ? '' : $product_object->get_download_limit( 'edit' ); ?>" />
                    </div>
                </div> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_download_expiry">
                        <?php echo __( 'Download expiry', 'woocommerce' ); ?>
                        <span class="img_tip" data-desc="<?php esc_attr_e( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ) ?>"></span> 
                    </label>
                    <div class="col-md-6 col-sm-9">
                        <input class="form-control" type="text" placeholder="<?php _e( 'Never', 'woocommerce' ); ?>" id="_download_expiry" name="_download_expiry" value="<?php echo -1 === $product_object->get_download_expiry( 'edit' ) ? '' : $product_object->get_download_expiry( 'edit' ); ?>" />
                    </div>
                </div> 
                <?php do_action( 'wcmp_afm_product_options_downloads', $post->ID, $product_object, $post ); ?>
            </div>
        <?php endif; ?>
        <?php
        $tax_visibility = apply_filters( 'general_tab_tax_section', array( 'simple', 'external', 'variable' ) );
        if ( apply_filters( 'wcmp_can_vendor_configure_tax', wc_tax_enabled() ) && call_user_func_array( "wcmp_is_allowed_product_type", $tax_visibility ) ) :
            $show_classes = implode( ' ', preg_filter( '/^/', 'show_if_', $tax_visibility ) );
            ?>
            <div class="form-group-row <?php echo $show_classes; ?>"> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_tax_status"><?php _e( 'Tax status', 'woocommerce' ); ?></label>
                    <div class="col-md-6 col-sm-9">
                        <select class="form-control" id="_tax_status" name="_tax_status">
                            <option value="taxable" <?php selected( $product_object->get_tax_status( 'edit' ), 'taxable' ); ?>><?php _e( 'Taxable', 'woocommerce' ); ?></option>
                            <option value="shipping" <?php selected( $product_object->get_tax_status( 'edit' ), 'shipping' ); ?>><?php _e( 'Shipping only', 'woocommerce' ); ?></option>
                            <option value="none" <?php selected( $product_object->get_tax_status( 'edit' ), 'none' ); ?>><?php _e( 'None', 'woocommerce' ); ?></option>
                        </select>
                    </div>
                </div> 
                <div class="form-group">
                    <label class="control-label col-sm-3 col-md-3" for="_tax_class"><?php _e( 'Tax class', 'woocommerce' ); ?></label>
                    <div class="col-md-6 col-sm-9">
                        <select class="form-control" id="_tax_class" name="_tax_class">
                            <?php foreach ( wc_get_product_tax_class_options() as $class => $class_label ) : ?>
                                <option value="<?php echo $class; ?>" <?php selected( $product_object->get_tax_class( 'edit' ), $class ); ?>><?php echo $class_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>  
            </div>
        <?php endif; ?>
        <?php do_action( 'wcmp_afm_after_general_product_data', $post->ID, $product_object, $post ); ?>
    </div>
</div>