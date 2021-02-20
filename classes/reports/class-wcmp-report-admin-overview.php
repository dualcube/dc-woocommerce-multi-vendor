<?php

/**
 * WCMp Report Admin Overview
 *
 * @author      WC Marketplace
 * @category    Vendor
 * @package     WCMp/Reports
 * @version     3.5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WCMp_Report_Admin_overview extends WC_Admin_Report {

    function __construct() {}

    /**
     * Output the report
     */
    public function output_report() {
        global $wpdb, $woocommerce, $WCMp;

        $ranges = array(
            'year' => __('Year', 'dc-woocommerce-multi-vendor'),
            'last_month' => __('Last Month', 'dc-woocommerce-multi-vendor'),
            'month' => __('This Month', 'dc-woocommerce-multi-vendor'),
            '7day' => __('Last 7 Days', 'dc-woocommerce-multi-vendor')
        );

        $current_range = ( isset($_GET['range']) && !empty($_GET['range']) ) ? sanitize_text_field($_GET['range']) : '7day';

        if (!in_array($current_range, array('custom', 'year', 'last_month', 'month', '7day'))) {
            $current_range = '7day';
        }

        $this->calculate_current_range($current_range);

        $start_date = $this->start_date;
        $end_date = $this->end_date;
        $end_date = strtotime('+1 day', $end_date);

        $sales = $gross_sales = $order_count = $paid_vendor_commission = $vendor_earning = $admin_earning = $pending_vendors = $active_vendors = $vendors = $products = $transactions = 0;
        
        $args = apply_filters('wcmp_report_admin_overview_query_args', array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'post_parent' => 0,
                'post_status' => array('wc-processing', 'wc-completed'),
                'date_query' => array(
                    'inclusive' => true,
                    'after' => array(
                        'year' => date('Y', $start_date),
                        'month' => date('n', $start_date),
                        'day' => date('1'),
                    ),
                    'before' => array(
                        'year' => date('Y', $end_date),
                        'month' => date('n', $end_date),
                        'day' => date('j', $end_date),
                    ),
                )
            ));

        $qry = new WP_Query($args);
        $orders = apply_filters('wcmp_report_admin_overview_orders', $qry->get_posts());

         if ( !empty( $orders ) ) {
            foreach ( $orders as $order_obj ) {
                $order = wc_get_order($order_obj->ID);
                $sales += $order->get_total();
                $wcmp_suborders = get_wcmp_suborders($order_obj->ID);
                if(!empty($wcmp_suborders)) {
                    foreach ($wcmp_suborders as $suborder) {
                        $vendor_order = wcmp_get_order($suborder->get_id());
                        if( $vendor_order ){
                            $gross_sales += $suborder->get_total( 'edit' );
                            $vendor_earning += $vendor_order->get_commission_total('edit');
                            $commission_id = $vendor_order->get_prop('_commission_id');
                            if( get_post_meta( $commission_id, '_paid_status', true ) == 'paid' )
                                $paid_vendor_commission += $vendor_order->get_commission_total('edit');
                        }
                    }
                }
                ++$order_count;
            }
            $admin_earning = $gross_sales - $vendor_earning;
        }

        $user_args = array(
            'role' => 'dc_vendor',
            'date_query' => array(
                'inclusive' => true,
                'after' => array(
                    'year' => date('Y', $start_date),
                    'month' => date('n', $start_date),
                    'day' => date('1'),
                ),
                'before' => array(
                    'year' => date('Y', $end_date),
                    'month' => date('n', $end_date),
                    'day' => date('j', $end_date),
                ),
            )
        );
        $user_query = new WP_User_Query($user_args);
        if (!empty($user_query->results)) 
            $vendors = count($user_query->results);
        
        $pending_user_args = array(
            'role' => 'dc_pending_vendor',
            'date_query' => array(
                'inclusive' => true,
                'after' => array(
                    'year' => date('Y', $start_date),
                    'month' => date('n', $start_date),
                    'day' => date('1'),
                ),
                'before' => array(
                    'year' => date('Y', $end_date),
                    'month' => date('n', $end_date),
                    'day' => date('j', $end_date),
                ),
            )
        );
        $pending_user_query = new WP_User_Query($pending_user_args);
        if (!empty($pending_user_query->results)) 
            $pending_vendors = count($pending_user_query->results);

        $all_vendors = get_wcmp_vendors();
        if($all_vendors) {
            $total_products = 0;
            $vendor_sales = array();
            foreach($all_vendors as $vendor) {
                $total_sales = 0;
                $vendor_products = $vendor->get_products_ids();
                $total_products += count($vendor_products);
                $is_block = get_user_meta($vendor->id, '_vendor_turn_off', true);
                if(isset($vendor->user_data->roles) && in_array('dc_vendor', $vendor->user_data->roles) && !$is_block) 
                    $active_vendors++;
                $vendor_args =  array(
                    'post_type' => 'shop_order',
                    'posts_per_page' => -1,
                    'post_status' => array('wc-processing', 'wc-completed'),
                    'meta_query' => array(
                        array(
                            'key' => '_commissions_processed',
                            'value' => 'yes',
                            'compare' => '='
                        ),
                        array(
                            'key' => '_vendor_id',
                            'value' => $vendor->id,
                            'compare' => '='
                        )
                    ),
                    'date_query' => array(
                        'inclusive' => true,
                        'after' => array(
                            'year' => date('Y', $start_date),
                            'month' => date('n', $start_date),
                            'day' => date('1'),
                        ),
                        'before' => array(
                            'year' => date('Y', $end_date),
                            'month' => date('n', $end_date),
                            'day' => date('j', $end_date),
                        ),
                    )
                );
                $vendor_orders = new WP_Query($vendor_args);
                if (!empty($vendor_orders->get_posts())) {
                    foreach ($vendor_orders->get_posts() as $order_obj) {
                        $order = new WC_Order($order_obj->ID);
                        if ($order) {
                            $total_sales += $order->get_total();
                        }
                    }
                    $vendor_sales[$vendor->id] = $total_sales;
                }
            }
        }
        arsort($vendor_sales);
        if(count($vendor_sales) > 6) 
            array_slice($vendor_sales, 0, 6);

        $product_args = array(
            'posts_per_page' => -1,
            //'author__in' => $vendor_ids,
            'post_type' => 'product',
            'post_status' => 'pending',
            'date_query' => array(
                'inclusive' => true,
                'after' => array(
                    'year' => date('Y', $start_date),
                    'month' => date('n', $start_date),
                    'day' => date('1'),
                ),
                'before' => array(
                    'year' => date('Y', $end_date),
                    'month' => date('n', $end_date),
                    'day' => date('j', $end_date),
                ),
            )
        );
        $get_pending_products = new WP_Query($product_args);
        if (!empty($get_pending_products->get_posts())) 
            $products = count($get_pending_products->get_posts());

        $selling_args = array(
            'post_type' => 'product',
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'posts_per_page' => 1,
        );
        $selling_product = new WP_Query( $selling_args );
        if (!empty($selling_product->get_posts()))
            $best_selling_product = $selling_product->get_posts();

        $transactions_args = array(
            'post_type' => 'wcmp_transaction',
            'post_status' => 'wcmp_processing',
            'meta_key' => 'transaction_mode',
            'meta_value' => 'direct_bank',
            'posts_per_page' => -1,
            'date_query' => array(
                'inclusive' => true,
                'after' => array(
                    'year' => date('Y', $start_date),
                    'month' => date('n', $start_date),
                    'day' => date('1'),
                ),
                'before' => array(
                    'year' => date('Y', $end_date),
                    'month' => date('n', $end_date),
                    'day' => date('j', $end_date),
                ),
            )
        );
        $transactions = get_posts($transactions_args);
        if (!empty($transactions)) 
            $transactions = count($transactions);
            
        include( $WCMp->plugin_path . '/classes/reports/views/html-wcmp-report-by-admin-overview.php');
    }

}
