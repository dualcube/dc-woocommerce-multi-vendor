<?php

/**
 * WCMp Report Sales By Vendor
 *
 * @author      WC Marketplace
 * @category    Vendor
 * @package     WCMp/Reports
 * @version     2.2.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WCMp_Report_Vendor extends WC_Admin_Report {

    /**
     * Output the report
     */
    public function output_report() {
        global $wpdb, $woocommerce, $WCMp;

        $vendor = $vendor_id = $order_items = false;

        $ranges = array(
            'year' => __('Year', 'dc-woocommerce-multi-vendor'),
            'last_month' => __('Last Month', 'dc-woocommerce-multi-vendor'),
            'month' => __('This Month', 'dc-woocommerce-multi-vendor'),
            '7day' => __('Last 7 Days', 'dc-woocommerce-multi-vendor')
        );

        $current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : '7day';

        if (!in_array($current_range, array('custom', 'year', 'last_month', 'month', '7day'))) {
            $current_range = '7day';
        }

        $this->calculate_current_range($current_range);

        if (isset($_POST['vendor'])) {
            $vendor_id = $_POST['vendor'];
            $vendor = get_wcmp_vendor_by_term($vendor_id);
            if ($vendor)
                $products = $vendor->get_products_ids();
            if (!empty($products)) {
                foreach ($products as $product) {
                    $chosen_product_ids[] = $product->ID;
                }
            }
        }

        if ($vendor_id && $vendor) {
            $option = '<option value="' . $vendor_id . '" selected="selected">' . $vendor->page_title . '</option>';
        } else {
            $option = '<option></option>';
        }

        $all_vendors = get_wcmp_vendors();

        $start_date = $this->start_date;
        $end_date = $this->end_date;

        $total_sales = $admin_earning = $vendor_report = $report_bk = array();

        if (!empty($all_vendors) && is_array($all_vendors)) {
            foreach ($all_vendors as $vendor) {
                $gross_sales = $my_earning = $vendor_earning = 0;
                $chosen_product_ids = array();
                $vendor_id = $vendor->id;

                $args = apply_filters('wcmp_report_admin_vendor_tab_query_args', array(
                    'post_type' => 'shop_order',
                    'posts_per_page' => -1,
                    'author' => $vendor_id,
                    'post_status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed'),
                    'meta_query' => array(
                        array(
                            'key' => '_commissions_processed',
                            'value' => 'yes',
                            'compare' => '='
                        ),
                        array(
                            'key' => '_vendor_id',
                            'value' => $vendor_id,
                            'compare' => '='
                        )
                    ),
                    'date_query' => array(
                        'inclusive' => true,
                        'after' => array(
                            'year' => date('Y', $this->start_date),
                            'month' => date('n', $this->start_date),
                            'day' => date('j', $this->start_date),
                        ),
                        'before' => array(
                            'year' => date('Y', $this->end_date),
                            'month' => date('n', $this->end_date),
                            'day' => date('j', $this->end_date),
                        ),
                    )
                ) );

                $qry = new WP_Query($args);

                $orders = apply_filters('wcmp_filter_orders_report_vendor', $qry->get_posts());

                if ( !empty( $orders ) ) {
                    foreach ( $orders as $order_obj ) {
                        try {
                            $order = wc_get_order($order_obj->ID);
                            if ($order) :
                                $vendor_order = wcmp_get_order($order->get_id());
                                $gross_sales += $order->get_total( 'edit' );
                                $vendor_earning += $vendor_order->get_commission_total('edit');
                            endif;
                        } catch (Exception $ex) {

                        }
                        
                    }
                }
                
                $total_sales[$vendor_id]['total_sales'] = $gross_sales;
                $total_sales[$vendor_id]['vendor_earning'] = $vendor_earning;
                $total_sales[$vendor_id]['admin_earning'] = $gross_sales - $vendor_earning;
                $total_sales[$vendor_id]['vendor_id'] = $vendor_id; // for report filter
            }

            wp_localize_script('wcmp_report_js', 'wcmp_report_vendor', array(
                'total_sales_arr' => $total_sales,
                'start_date' => $start_date,
                'end_date' => $end_date
            ));

            $chart_arr = $html_chart = '';
            
            foreach ($total_sales as $vendor_id => $report) {
                $vendor = get_wcmp_vendor( $vendor_id );
                $total_sales_width = ( $report['total_sales'] > 0 ) ? round($report['total_sales']) / round($report['total_sales']) * 100 : 0;
                $admin_earning_width = ( $report['admin_earning'] > 0 ) ? ( $report['admin_earning'] / round($report['total_sales']) ) * 100 : 0;
                $vendor_earning_width = ( $report['vendor_earning'] > 0 ) ? ( $report['vendor_earning'] / round($report['total_sales']) ) * 100 : 0;
                $chart_arr .= '<tr><th><a href="user-edit.php?user_id=' . $vendor_id . '">' . $vendor->page_title . '</a></th>
					<td class="sales_prices" width="1%">
                                            <span>' . wc_price($report['total_sales']) . '</span>'
                                            . '<span class="alt">' . wc_price($report['admin_earning']) . '</span>'
                                            . '<span class="alt">' . wc_price($report['vendor_earning']) . '</span>
                                        </td>
					<td class="bars">
						<span class="gross_bar" style="width:' . esc_attr($total_sales_width) . '%">&nbsp;</span>
						<span class="admin_bar alt" style="width:' . esc_attr($admin_earning_width) . '%">&nbsp;</span>
                                                <span class="vendor_bar alt" style="width:' . esc_attr($vendor_earning_width) . '%">&nbsp;</span>
					</td></tr>';
                $html_chart = '
					<h4>' . __("Sales and Earnings", 'dc-woocommerce-multi-vendor') . '</h4>
					<div class="bar_indecator">
						<div class="bar1">&nbsp;</div>
						<span class="">' . __('Gross Sales', 'dc-woocommerce-multi-vendor') . '</span>
						<div class="bar2">&nbsp;</div>
						<span class="">' . __('Admin Earnings', 'dc-woocommerce-multi-vendor') . '</span>
                                                <div class="bar3">&nbsp;</div>
						<span class="">' . __('Vendor Earnings', 'dc-woocommerce-multi-vendor') . '</span>
					</div>
					<table class="bar_chart">
						<thead>
							<tr>
								<th>' . __("Vendors", 'dc-woocommerce-multi-vendor') . '</th>
								<th colspan="2">' . __("Sales Report", 'dc-woocommerce-multi-vendor') . '</th>
							</tr>
						</thead>
						<tbody>
							' . $chart_arr . '
						</tbody>
					</table>
				';
            }

        } else {
            $html_chart = '<tr><td colspan="3">' . __('Your store has no vendors.', 'dc-woocommerce-multi-vendor') . '</td></tr>';
        }

        include( $WCMp->plugin_path . '/classes/reports/views/html-wcmp-report-by-vendor.php');
    }

}
