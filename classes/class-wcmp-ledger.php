<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @class 		WCMp ledger Class
 *
 * @version		3.4.0
 * @package		WCMp/Classes
 * @author 		WC Marketplace
 */
class WCMp_Ledger {

    public function __construct() {
        add_action( 'wcmp_commission_after_save_commission_total', array( $this, 'wcmp_commission_after_save_commission_total' ), 10, 2 );
        add_action( 'wcmp_create_commission_refund_after_commission_note', array( $this, 'wcmp_create_commission_refund_after_commission_note' ), 10, 4 );
        add_action( 'wcmp_transaction_update_meta_data', array( $this, 'wcmp_transaction_update_meta_data' ), 10, 3 );
        add_action( 'before_delete_post', array( $this, 'before_commission_delete' ), 99 );
        add_action( 'wcmp_vendor_order_on_cancelled_commission', array( $this, 'before_commission_delete' ), 99 );
        // for BW order migration
        add_action( 'wcmp_orders_migration_order_created', array( $this, 'wcmp_orders_migration_order_created' ), 10, 2 );
    }
    
    public function wcmp_commission_after_save_commission_total( $commission_id, $order ){
        if( $order ){
            $vendor_id = get_post_meta( $order->get_id(), '_vendor_id', true);
            $vendor = get_wcmp_vendor( $vendor_id );
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => '_commission_vendor',
                        'value' => absint($vendor->term_id),
                        'compare' => '='
                    ),
                ),
            );
            $unpaid_commission_total = WCMp_Commission::get_commissions_total_data( $args, $vendor->id );
            $commission_total = get_post_meta( $commission_id, '_commission_total', true );
            $data = array(
                'vendor_id'     => $vendor_id,
                'order_id'      => $order->get_id(),
                'ref_id'        => $commission_id,
                'ref_type'      => 'commission',
                'ref_info'      => sprintf(__('Commission generated for Order &ndash; <a href="%s" target="_blank">#%s</a>', 'dc-woocommerce-multi-vendor'), esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders'), $order->get_id())), $order->get_id()),
                'ref_status'    => 'unpaid',
                'ref_updated'   => date('Y-m-d H:i:s', current_time('timestamp')),
                'credit'        => $commission_total,
                'balance'       => $unpaid_commission_total['total'] ? $unpaid_commission_total['total'] : 0,
            );
            $data_store = $this->load_ledger_data_store();
            $data_store->create($data);
        }
    }
    
    public function wcmp_orders_migration_order_created( $order_id, $tbl_vorder_data ){
        $order = wc_get_order( $order_id );
        if( $order ) :
            $commission_id = get_post_meta( $order->get_id(), '_commission_id', true );
            $commission = get_post( $commission_id );
            $commission_specific_orders = get_wcmp_vendor_orders(array('vendor_id' => $tbl_vorder_data->vendor_id, 'commission_id' => $commission_id, 'order_id' => $tbl_vorder_data->order_id ));
            $commission_total = 0;
            foreach ($commission_specific_orders as $corder) {
                $commission_total += $corder->commission_amount + $corder->shipping + $corder->tax + $corder->shipping_tax_amount;
            }
            $vendor = get_wcmp_vendor( $tbl_vorder_data->vendor_id );
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => '_commission_vendor',
                        'value' => absint($vendor->term_id),
                        'compare' => '='
                    ),
                ),
            );
            $unpaid_commission_total = WCMp_Commission::get_commissions_total_data( $args, $vendor->id );
            $data = array(
                'vendor_id'     => $tbl_vorder_data->vendor_id,
                'order_id'      => $order->get_id(),
                'ref_id'        => $commission_id,
                'ref_type'      => 'commission',
                'ref_info'      => sprintf(__('Commission generated for Order &ndash; <a href="%s" target="_blank">#%s</a>', 'dc-woocommerce-multi-vendor'), esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders'), $order->get_id())), $order->get_id()),
                'ref_status'    => $tbl_vorder_data->commission_status,
                'ref_updated'   => date('Y-m-d H:i:s', strtotime($commission->post_date)),
                'credit'        => $commission_total,
                'balance'       => $unpaid_commission_total['total'] ? $unpaid_commission_total['total'] : 0,
            );
            $data_store = $this->load_ledger_data_store();
            $data_store->create($data);
        endif;
    }
    
    public function wcmp_create_commission_refund_after_commission_note( $commission_id, $commissions_refunded_amt, $refund_id, $order ) {
        if( $order ){
            $vendor_id = get_post_meta( $order->get_id(), '_vendor_id', true);
            $refund_total = ( $commissions_refunded_amt ) ? abs( array_sum( $commissions_refunded_amt) ) : 0;
            $refund = new WC_Order_Refund($refund_id);
            $vendor = get_wcmp_vendor( $vendor_id );
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => '_commission_vendor',
                        'value' => absint($vendor->term_id),
                        'compare' => '='
                    ),
                ),
            );
            $unpaid_commission_total = WCMp_Commission::get_commissions_total_data( $args, $vendor->id );
            $data = array(
                'vendor_id'     => $vendor_id,
                'order_id'      => $order->get_id(),
                'ref_id'        => $refund_id,
                'ref_type'      => 'refund',
                'ref_info'      => sprintf(__('Refund generated for Commission &ndash; #%s', 'dc-woocommerce-multi-vendor'), $commission_id),
                'ref_status'    => $refund->get_status(),
                'ref_updated'   => date('Y-m-d H:i:s', current_time('timestamp')),
                'debit'         => $refund_total,
                'balance'       => $unpaid_commission_total['total'] ? $unpaid_commission_total['total'] : 0,
            );
            $data_store = $this->load_ledger_data_store();
            $data_store->create($data);
        }
    }
    
    public function wcmp_transaction_update_meta_data( $commission_status, $transaction_id, $vendor ) {
        if( $commission_status == 'wcmp_processing' ) return;
        
        if( $transaction_id ){
            $commissions = get_post_meta( $transaction_id, 'commission_detail', true );
            if( $commissions ){
                foreach ( $commissions as $commission_id ) {
                    $withdrawal_total = WCMp_Commission::commission_totals($commission_id, 'edit');
                    $order_id = get_post_meta( $commission_id, '_commission_order_id', true );
                    $args = array(
                        'meta_query' => array(
                            array(
                                'key' => '_commission_vendor',
                                'value' => absint($vendor->term_id),
                                'compare' => '='
                            ),
                        ),
                    );
                    $unpaid_commission_total = WCMp_Commission::get_commissions_total_data( $args, $vendor->id );
                    $data = array(
                        'vendor_id'     => $vendor->id,
                        'order_id'      => $order_id,
                        'ref_id'        => $transaction_id,
                        'ref_type'      => 'withdrawal',
                        'ref_info'      => sprintf(__('Withdrawal generated for Commission &ndash; <a href="%s" target="_blank">#%s</a>', 'dc-woocommerce-multi-vendor'), esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_transaction_details_endpoint', 'vendor', 'general', 'transaction-details'), $transaction_id)), $commission_id),
                        'ref_status'    => 'completed',
                        'ref_updated'   => date('Y-m-d H:i:s', current_time('timestamp')),
                        'debit'         => $withdrawal_total,
                        'balance'       => $unpaid_commission_total['total'] ? $unpaid_commission_total['total'] : 0,
                    );
                    $data_store = $this->load_ledger_data_store();
                    $data_store->create($data);
                }
            }
        }
    }
    
    public function before_commission_delete( $post_id ) {
        if( get_post_type( $post_id ) != 'dc_commission' ) return;
        if( get_post_meta( $post_id, '_paid_status', true ) != 'unpaid' ) return;
        
        $vendor_term_id = get_post_meta( $post_id, '_commission_vendor', true );
        $order_id = get_post_meta( $post_id, '_commission_order_id', true );
        $vendor = get_wcmp_vendor_by_term( $vendor_term_id );
        if( $vendor ) :
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => '_commission_vendor',
                        'value' => absint($vendor->term_id),
                        'compare' => '='
                    ),
                ),
            );
            $unpaid_commission_total = WCMp_Commission::get_commissions_total_data( $args, $vendor->id );
            $commission_total = get_post_meta( $post_id, '_commission_total', true );

            $data = array(
                'vendor_id'     => $vendor->id,
                'order_id'      => $order_id,
                'ref_id'        => $post_id,
                'ref_type'      => 'commission',
                'ref_info'      => sprintf(__('Commission cancelled for Order &ndash; <a href="%s" target="_blank">#%s</a>', 'dc-woocommerce-multi-vendor'), esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders'), $order_id)), $order_id),
                'ref_status'    => 'cancelled',
                'ref_updated'   => date('Y-m-d H:i:s', current_time('timestamp')),
                'debit'         => $commission_total,
                'balance'       => ( $unpaid_commission_total['total'] - $commission_total ),
            );
            $data_store = $this->load_ledger_data_store();
            $data_store->create($data);
        endif;
    }
    
    public function load_ledger_data_store(){
        global $WCMp;
        $WCMp->load_class( 'ledger', 'data-store');
        return new WCMp_Ledger_Data_Store();
    }
}