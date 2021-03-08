<?php
/**
 * The template for displaying vendor report
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-report.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
global $WCMp;
?>
<div class="col-md-12">
    
    <div class="panel panel-default panel-pading">
        <form name="wcmp_vendor_dashboard_stat_report" method="POST" class="stat-date-range form-inline">
            <div class="wcmp_form1 ">
                <div class="panel-heading d-lg-flex">
                    <h3><?php esc_html_e('Select Date Range :', 'dc-woocommerce-multi-vendor'); ?></h3> 
                    <div class="form-group">
                        <input type="date" name="wcmp_stat_start_dt" value="<?php echo isset($_POST['wcmp_stat_start_dt']) ? wc_clean($_POST['wcmp_stat_start_dt']) : date('Y-m-01'); ?>" class="pickdate gap1 wcmp_stat_start_dt form-control">
                    </div>
                    <div class="form-group">
                        <input type="date" name="wcmp_stat_end_dt" value="<?php echo isset($_POST['wcmp_stat_end_dt']) ? wc_clean($_POST['wcmp_stat_end_dt']) : date('Y-m-d'); ?>" class="pickdate wcmp_stat_end_dt form-control">
                    </div>
                    <div class="form-group">
                        <button name="submit_button" type="submit" value="Show" class="wcmp_black_btn btn btn-default"><?php esc_html_e('Show', 'dc-woocommerce-multi-vendor'); ?></button>
                    </div> 
                    <?php if (apply_filters('can_wcmp_vendor_export_orders_csv', true, get_current_vendor_id())) : ?>
                    <div class="form-group">
                        <button type="submit" class="wcmp_black_btn btn btn-default" name="wcmp_stat_export" value="export"><?php esc_html_e('Download CSV', 'dc-woocommerce-multi-vendor'); ?></button>
                    </div> 
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <div class="wcmp_ass_holder_box">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('Total Sales', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo wc_price($total_vendor_sales); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('My Earnings', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo wc_price($total_vendor_earning); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('Total number of Order placed', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo $total_order_count; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('Purchased Products', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo $total_purchased_products; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('Number of Coupons used', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo $total_coupon_used; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('Total Coupon Discount', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo wc_price($total_coupon_discount_value); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="wcmp_displaybox2 text-center">
                                    <h4><?php esc_html_e('Number of Unique Customers', 'dc-woocommerce-multi-vendor'); ?></h4>
                                    <h3><?php echo count($total_customers); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
