<?php
/**
 * Admin Overview Report
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $WCMp;

?>
<div id="poststuff" class="woocommerce-reports-wide">
    <div class="postbox">
        <h3 class="stats_range">
            <ul>
                <?php
                    foreach ( $ranges as $range => $name ) {
                        echo '<li class="' . ( $current_range == $range ? 'active' : '' ) . '"><a href="' . esc_url( remove_query_arg( array( 'start_date', 'end_date' ), add_query_arg( 'range', $range ) ) ) . '">' . $name . '</a></li>';
                    }
                ?>
                <li class="custom <?php echo $current_range == 'custom' ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Custom', 'dc-woocommerce-multi-vendor' ); ?>
                    <form method="GET">
                        <div>
                            <?php
                                // Maintain query string
                                foreach ( $_GET as $key => $value ) {
                                    if ( is_array( $value ) ) {
                                        foreach ( $value as $v ) {
                                            echo '<input type="hidden" name="' . esc_attr( sanitize_text_field( $key ) ) . '[]" value="' . esc_attr( sanitize_text_field( $v ) ) . '" />';
                                        }
                                    } else {
                                        echo '<input type="hidden" name="' . esc_attr( sanitize_text_field( $key ) ) . '" value="' . esc_attr( sanitize_text_field( $value ) ) . '" />';
                                    }
                                }
                            ?>
                            <input type="hidden" name="range" value="custom" />
                            <input type="text" size="9" placeholder="yyyy-mm-dd" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ); ?>" name="start_date" class="range_datepicker from" id="from_product" />
                            <input type="text" size="9" placeholder="yyyy-mm-dd" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ); ?>" name="end_date" class="range_datepicker to" id="to_product" />
                            <input type="submit" class="button" value="<?php esc_attr_e( 'Go', 'dc-woocommerce-multi-vendor' ); ?>" />
                        </div>
                    </form>
                </li>
            </ul>
        </h3>
    </div>
    <div class="postbox sort_chart box_data">
        <div class="wcmp_product_admin_overview">
            <div class="col-md-6">
                <div class="panel panel-default panel-pading">
                    <form name="wcmp_vendor_dashboard_stat_report" method="POST" class="stat-date-range form-inline">
                        <div class="wcmp_form1 ">
                            <h2 class="panel-header"><?php esc_html_e('Sales Overview', 'dc-woocommerce-multi-vendor'); ?></h2>
                            <div class="panel-body">
                                <div class="wcmp_ass_holder_box">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-chart-bar"></span>
                                                    <a href="#"><?php esc_html_e('Total Sales', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo wc_price($sales); ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-money-alt"></span>
                                                    <a href ="#"><?php esc_html_e('Number of orders', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo $order_count; ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center ">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-admin-users"></span>
                                                    <a href ="<?php echo esc_url(admin_url('admin.php?page=vendors')); ?>"><?php esc_html_e('Awaiting Withdrawals', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo wc_price($transactions); ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-backup"></span>
                                                        <a href ="#"><?php esc_html_e('Admin Commision', 'dc-woocommerce-multi-vendor'); ?></a>
                                                    </h4>
                                                <h3 class="amountCls"><?php echo wc_price($admin_earning); ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center bordrNoneCls">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-id"></span>
                                                    <a href ="#"><?php _e('Vendor Commission', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo wc_price($paid_vendor_commission); ?></h3>
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
            <div class="col-md-6">
                <div class="panel panel-default panel-pading">
                    <form name="wcmp_vendor_dashboard_stat_report" method="POST" class="stat-date-range form-inline">
                        <div class="wcmp_form1 ">
                            <h2 class="panel-header"><?php esc_html_e('Highest Selling Vendors', 'dc-woocommerce-multi-vendor'); ?></h2>
                                <div class="panel-body">
                                    <div class="wcmp_ass_holder_box">
                                        <div class="row">
                                            <?php foreach($vendor_sales as $id => $price) { 
                                                $seller = get_wcmp_vendor($id);
                                                ?>
                                                <div class="col-md-6">
                                                    <div class="wcmp_displaybox2 text-center">
                                                        <h4 class="titleCls">
                                                            <span class="dashicons dashicons-chart-bar"></span>
                                                            <a href="#"><?php echo $seller->user_data->data->display_name; ?></a>
                                                        </h4>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default panel-pading">
                    <form name="wcmp_vendor_dashboard_stat_report" method="POST" class="stat-date-range form-inline">
                        <div class="wcmp_form1 ">
                            <h2 class="panel-header"><?php esc_html_e('Product Overview', 'dc-woocommerce-multi-vendor'); ?></h2>
                                <div class="panel-body">
                                    <div class="wcmp_ass_holder_box">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="wcmp_displaybox2 text-center">
                                                    <h4 class="titleCls">
                                                        <span class="dashicons dashicons-chart-bar"></span>
                                                        <a href="#"><?php esc_html_e('Total Products', 'dc-woocommerce-multi-vendor'); ?></a>
                                                    </h4>
                                                <h3 class="amountCls"><?php echo $total_products; ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-money-alt"></span>
                                                    <a href ="#"><?php esc_html_e('Awaiting Products', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo $products; ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center ">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-admin-users"></span>
                                                    <a href ="#"><?php esc_html_e('Highest Selling Product', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo $best_selling_product[0]->post_title; ?></h3>
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
            <div class="col-md-6">
                <div class="panel panel-default panel-pading">
                    <form name="wcmp_vendor_dashboard_stat_report" method="POST" class="stat-date-range form-inline">
                        <div class="wcmp_form1 ">
                            <h2 class="panel-header"><?php esc_html_e('Vendor Overview', 'dc-woocommerce-multi-vendor'); ?></h2>
                                <div class="panel-body">
                                    <div class="wcmp_ass_holder_box">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="wcmp_displaybox2 text-center">
                                                    <h4 class="titleCls">
                                                        <span class="dashicons dashicons-chart-bar"></span>
                                                        <a href="#"><?php esc_html_e('Active Vendors', 'dc-woocommerce-multi-vendor'); ?></a>
                                                    </h4>
                                                    <h3 class="amountCls"><?php echo $active_vendors; ?></h3>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="wcmp_displaybox2 text-center">
                                                    <h4 class="titleCls">
                                                        <span class="dashicons dashicons-money-alt"></span>
                                                        <a href ="#"><?php esc_html_e('Signup vendors', 'dc-woocommerce-multi-vendor'); ?></a>
                                                    </h4>
                                                <h3 class="amountCls"><?php echo $vendors; ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wcmp_displaybox2 text-center ">
                                                <h4 class="titleCls">
                                                    <span class="dashicons dashicons-admin-users"></span>
                                                    <a href ="#"><?php esc_html_e('Pending Vendors', 'dc-woocommerce-multi-vendor'); ?></a>
                                                </h4>
                                                <h3 class="amountCls"><?php echo $pending_vendors; ?></h3>
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
            <?php do_action('wcmp_report_admin_overview',$this); ?>         
        </div> 
    </div>
</div>
