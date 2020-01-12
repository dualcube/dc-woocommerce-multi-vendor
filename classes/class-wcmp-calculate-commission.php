<?php

/**
 * WCMp Calculate Commission Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Calculate_Commission {

    public $completed_statuses;
    public $reverse_statuses;

    public function __construct() {

        // WC order complete statues
        $this->completed_statuses = apply_filters('wcmp_completed_commission_statuses', array('completed', 'processing'));

        // WC order reverse statues
        $this->reverse_statuses = apply_filters('wcmp_reversed_commission_statuses', array('pending', 'refunded', 'cancelled', 'failed'));

        if (is_wcmp_version_less_3_4_0()) {
            $this->wcmp_order_reverse_action();
            $this->wcmp_order_complete_action();
        } else {
            add_action( 'wcmp_checkout_vendor_order_processed', array( $this, 'wcmp_create_commission' ), 10, 3);
            add_action( 'woocommerce_order_refunded', array( $this, 'wcmp_create_commission_refunds' ), 99, 2);
        }
        add_action( 'woocommerce_order_status_changed', array( $this, 'wcmp_vendor_new_order_mail' ), 99, 3 );

        // support of WooCommerce subscription plugin
        //add_filter('wcs_renewal_order_meta_query', array(&$this, 'wcs_renewal_order_meta_query'), 10, 1);
    }

    /**
     * Create vendor commissions
     * @param int $vendor_order_id
     * @param array $posted_data
     * @param object $order
     * @return void
     */
    public function wcmp_create_commission($vendor_order_id, $posted_data, $order) {
        global $WCMp;
        $processed = get_post_meta($vendor_order_id, '_commissions_processed', true);
        if (!$processed) {
            //$commission_ids = get_post_meta($vendor_order_id, '_commission_ids', true) ? get_post_meta($vendor_order_id, '_commission_ids', true) : array();
            $vendor_order = wc_get_order($vendor_order_id);
            $vendor_id = get_post_meta($vendor_order_id, '_vendor_id', true);

            // create vendor commission
            $commission_id = WCMp_Commission::create_commission($vendor_order_id);
            if ($commission_id) {
                // Calculate commission
                WCMp_Commission::calculate_commission($commission_id, $vendor_order);
                //update_post_meta($commission_id, '_paid_status', 'unpaid'); // moved to create_commission() for proper ledger update
                
                // add commission id with associated vendor order
                update_post_meta($vendor_order_id, '_commission_id', $commission_id);
                // Mark commissions as processed
                update_post_meta($vendor_order_id, '_commissions_processed', 'yes');
                
                do_action( 'after_wcmp_calculate_commission', $commission_id, $vendor_order_id );
            }
        }
    }
    
    public function wcmp_vendor_new_order_mail( $order_id, $from_status, $to_status ){
        if( !$order_id ) return;
        if( !in_array( $from_status, apply_filters( 'wcmp_vendor_new_order_mail_statuses_transition_from', array(
            'pending',
            'failed',
            'cancelled',
        ), $order_id, $from_status, $to_status ) ) ) return;
        
        if( !wp_get_post_parent_id( $order_id ) && get_post_meta( $order_id, 'has_wcmp_sub_order', true ) ) {
            $suborders = get_wcmp_suborders( $order_id, false, false);
            if( $suborders ) {
                foreach ( $suborders as $v_order_id ) {
                    $wcmp_order_version = get_post_meta( $v_order_id, '_wcmp_order_version', true );
                    $already_triggered = get_post_meta( $v_order_id, '_wcmp_vendor_new_order_mail_triggered', true );
                    if( version_compare( $wcmp_order_version, '3.4.2', '>=') && !$already_triggered ){
                        $email_admin = WC()->mailer()->emails['WC_Email_Vendor_New_Order'];
                        $result = $email_admin->trigger( $v_order_id );
                        if( $result ) update_post_meta( $v_order_id, '_wcmp_vendor_new_order_mail_triggered', true );
                    }
                }
            }
        }elseif( is_wcmp_vendor_order( $order_id ) ){
            $wcmp_order_version = get_post_meta( $order_id, '_wcmp_order_version', true );
            $already_triggered = get_post_meta( $order_id, '_wcmp_vendor_new_order_mail_triggered', true );
            if( version_compare( $wcmp_order_version, '3.4.2', '>=') && !$already_triggered ){
                $email_admin = WC()->mailer()->emails['WC_Email_Vendor_New_Order'];
                $result = $email_admin->trigger( $order_id );
                if( $result ) update_post_meta( $order_id, '_wcmp_vendor_new_order_mail_triggered', true );
            }
        }
        
    }

    /**
     * Create vendor commission refund
     * @param int $vendor_order_id
     * @param int $refund_id
     * @return void
     */
    public function wcmp_create_commission_refunds($vendor_order_id, $refund_id) {
        $order = wc_get_order($vendor_order_id);
        $refund = new WC_Order_Refund($refund_id);
        $commission_id = get_post_meta($vendor_order_id, '_commission_id', true);
        $vendor_id = get_post_meta($vendor_order_id, '_vendor_id', true);
        $commission_amount = get_post_meta($commission_id, '_commission_amount', true);
        $included_coupon = get_post_meta($commission_id, '_commission_include_coupon', true) ? true : false;
        $included_tax = get_post_meta($commission_id, '_commission_total_include_tax', true) ? true : false;
        $items_commission_rates = get_post_meta($vendor_order_id, 'order_items_commission_rates', true);
        
        $refunded_total = $refunds = $global_refunds = $commission_refunded_items = array();

        if($commission_id){
            $line_items_commission_refund = $global_commission_refund = 0;
            foreach ($order->get_refunds() as $_refund) {
                $line_items_refund = $shipping_item_refund = $tax_item_refund = $amount = $refund_item_totals = 0;
                // if commission refund exists
                if (get_post_meta($_refund->get_id(), '_refunded_commissions', true)) {
                    $commission_amt = get_post_meta($_refund->get_id(), '_refunded_commissions', true);
                    $refunds[$_refund->get_id()][$commission_id] = $commission_amt[$commission_id];
                }
                /** WC_Order_Refund items **/
                foreach ($_refund->get_items() as $item_id => $item) { 
                    $refunded_item_id = $item['refunded_item_id'];
                    $refund_amount = $item['line_total'];
                    $refunded_item_id = $item['refunded_item_id'];
                    
                    if ($refund_amount != 0) { 
                        $refunded_total[$commission_id] += $refund_amount;
                        $line_items_refund += $refund_amount;
                        
                        if(isset($items_commission_rates[$refunded_item_id])){
                            if ($items_commission_rates[$refunded_item_id]['type'] == 'fixed_with_percentage') {
                                $amount = (float) $refund_amount * ( (float) $items_commission_rates[$refunded_item_id]['commission_val'] / 100 ) + (float) $items_commission_rates[$refunded_item_id]['commission_fixed'];
                            } else if ($items_commission_rates[$refunded_item_id]['type'] == 'fixed_with_percentage_qty') {
                                $amount = (float) $refund_amount * ( (float) $items_commission_rates[$refunded_item_id]['commission_val'] / 100 ) + ((float) $items_commission_rates[$refunded_item_id]['commission_fixed'] * $item['quantity']);
                            } else if ($items_commission_rates[$refunded_item_id]['type'] == 'percent') {
                                $amount = (float) $refund_amount * ( (float) $items_commission_rates[$refunded_item_id]['commission_val'] / 100 );
                            } else if ($items_commission_rates[$refunded_item_id]['type'] == 'fixed') {
                                $amount = (float) $items_commission_rates[$refunded_item_id]['commission_val'] * $item['quantity'];
                            }
                            if (isset($items_commission_rates[$refunded_item_id]['mode']) && $items_commission_rates[$refunded_item_id]['mode'] == 'admin') {
                                $amount = (float) $refund_amount - (float) $amount;
                            }
                            $line_items_commission_refund += $amount;
                            $refund_item_totals += $amount;
                            $commission_refunded_items[$_refund->get_id()][$refunded_item_id] = $amount;
                        }
                    }
                }
                // add items total refunds
                $refunds[$_refund->get_id()][$commission_id]['line_item'] = $refund_item_totals;
                
                if($line_items_commission_refund != 0){
                    update_post_meta( $commission_id, '_commission_refunded_items', $line_items_commission_refund );
                    update_post_meta( $commission_id, '_commission_refunded_items_data', $commission_refunded_items );
                }
                
                /** WC_Order_Refund shipping **/
                $refund_shipping_totals = 0;
                foreach ($_refund->get_items('shipping') as $item_id => $item) { 
                    if ( 0 < get_post_meta($commission_id, '_shipping', true) && get_post_meta($commission_id, '_commission_total_include_shipping', true) ){
                        if($item['total'] != 0){
                            $shipping_item_refund += $item['total'];
                            $refund_shipping_totals += $item['total'];
                        }
                    }
                }
                if($shipping_item_refund != 0){
                    $amount = $shipping_item_refund;
                    if( $refund_shipping_totals )
                        $refunds[$_refund->get_id()][$commission_id]['shipping'] = $refund_shipping_totals;
                    update_post_meta( $commission_id, '_commission_refunded_shipping', $shipping_item_refund );
                }
                
                /** WC_Order_Refund tax **/
                $refund_tax_totals = 0;
                foreach ($_refund->get_items('tax') as $item_id => $item) { 
                    if ( 0 < get_post_meta($commission_id, '_tax', true) && get_post_meta($commission_id, '_commission_total_include_tax', true) ){
                        if($item['tax_total'] != 0 || $item['shipping_tax_total'] != 0){
                            $tax_item_refund += $item['tax_total'] + $item['shipping_tax_total'];
                            $refund_tax_totals += $item['tax_total'] + $item['shipping_tax_total'];
                        }
                    }
                }
                if($tax_item_refund != 0){
                    $amount = $tax_item_refund;
                    if( $refund_tax_totals )
                        $refunds[$_refund->get_id()][$commission_id]['tax'] = $refund_tax_totals;
                    update_post_meta( $commission_id, '_commission_refunded_tax', $tax_item_refund );
                }
                
                // if global refund applied in this refund
                $refund_amount = $_refund->get_amount() - abs( $line_items_refund );
                if ( !$_refund->get_items() && !$_refund->get_items('shipping') && !$_refund->get_items('tax') ) {
                    $global_refunds[$_refund->get_id()] = $_refund;
                }
                
            }
   
            // global refund calculation
            foreach ( $global_refunds as $_refund ) {
                //$rate_to_refund = $_refund->get_amount() / $order->get_total();
                //$commission_total = WCMp_Commission::commission_totals($commission_id, 'edit');

                if(!get_post_meta($_refund->get_id(), '_refunded_commissions', true)){
                    $refunds[$_refund->get_id()][$commission_id]['global'] = $_refund->get_amount() * -1;
                    $global_commission_refund += $_refund->get_amount() * -1;
                }else{
                    $refunded_commission = get_post_meta($_refund->get_id(), '_refunded_commissions', true);
                    $refunded_commission_amt_data = isset($refunded_commission[$commission_id]) ? $refunded_commission[$commission_id] : array();
                    $refunded_commission_amt = array_sum($refunded_commission_amt_data);
                    $global_commission_refund += $refunded_commission_amt;
                }
            }
            if($global_commission_refund != 0){
                update_post_meta( $commission_id, '_commission_refunded_global', $global_commission_refund );
            }
       
            // update the refunded commissions in the order to easy manage these in future
            $refunded_amt_total = 0;
            if($refunds) :
                foreach ( $refunds as $_refund_id => $commissions_refunded ) {
                    $comm_refunded_amt = $commissions_refunded_total = 0;
                    foreach ( $commissions_refunded as $commission_id => $data_amount ) {
                        $amount = array_sum($data_amount);
                        $commissions_refunded_total = $amount;
                        if( -($amount) != 0 ){
                            $comm_refunded_amt += $amount;
                            $note = sprintf( __( 'Refunded %s from commission', 'dc-woocommerce-multi-vendor' ), wc_price( abs( $amount ) ) );
                            if($_refund_id == $refund_id){
                                WCMp_Commission::add_commission_note($commission_id, $note, $vendor_id);
                                /**
                                 * Action hook after add commission refund note.
                                 *
                                 * @since 3.4.0
                                 */
                                do_action( 'wcmp_create_commission_refund_after_commission_note', $commission_id, $data_amount, $refund_id, $order );
                            }
                            //update_post_meta( $commission_id, '_commission_amount', $amount );

                            //if( $amount == 0 ) update_post_meta($commission_id, '_paid_status', 'cancelled');
                        }
                    }
                    $refunded_amt_total += $comm_refunded_amt;

                    update_post_meta( $_refund_id, '_refunded_commissions', $commissions_refunded );
                    update_post_meta( $_refund_id, '_refunded_commissions_total', $commissions_refunded_total );
                }
                
                update_post_meta( $commission_id, '_commission_refunded_data', $refunds );
                update_post_meta( $commission_id, '_commission_refunded', $refunded_amt_total );
                // Trigger notification emails.
                if ( WCMp_Commission::commission_totals($commission_id, 'edit') == 0  ) {
                    do_action( 'wcmp_commission_fully_refunded', $commission_id, $order );
                    update_post_meta($commission_id, '_paid_status', 'refunded'); 
                } else {
                    do_action( 'wcmp_commission_partially_refunded', $commission_id, $order );
                    update_post_meta($commission_id, '_paid_status', 'partial_refunded');
                }
                /**
                 * Action hook after commission refund save.
                 *
                 * @since 3.4.0
                 */
                do_action('wcmp_after_create_commission_refunds', $order, $commission_id);
            endif;
        }
    }
    
    /**
     * Remove meta key from renewal order
     * Support WooCommerce subscription plugin
     * @param string $meta_query
     * @return string
     */
    public function wcs_renewal_order_meta_query($meta_query) {
        $meta_query .= " AND `meta_key` NOT LIKE '_wcmp_order_processed' AND `meta_key` NOT LIKE '_commissions_processed' ";
        return $meta_query;
    }

    /**
     * Add action hook when an order is reversed
     *
     * @author WC Marketplace
     * @return void
     */
    public function wcmp_order_reverse_action() {
        foreach ($this->completed_statuses as $cmpltd) {
            foreach ($this->reverse_statuses as $revsed) {
                add_action("woocommerce_order_status_{$cmpltd}_to_{$revsed}", array($this, 'wcmp_due_commission_reverse'));
            }
        }
    }

    /**
     * WCMp reverse vendor due commission for an order
     *
     * @param int $order_id
     */
    public function wcmp_due_commission_reverse($order_id) {
        $args = array(
            'post_type' => 'dc_commission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_commission_order_id',
                    'value' => absint($order_id),
                    'compare' => '='
                )
            )
        );
        $commissions = get_posts($args);
        if ($commissions) {
            foreach ($commissions as $commission) {
                update_post_meta($commission->ID, '_paid_status', 'reverse');
            }
        }
    }

    /**
     * Add action hook only when an order manually updated
     *
     * @author WC Marketplace
     * @return void
     */
    public function wcmp_order_complete_action() {
        foreach ($this->completed_statuses as $cmpltd) {
            add_action('woocommerce_order_status_' . $cmpltd, array($this, 'wcmp_process_commissions'));
        }
    }

    /**
     * Process commission
     * @param  int $order_id ID of order for commission
     * @return void
     */
    public function wcmp_process_commissions($order_id) {
        global $wpdb;
        // Only process commissions once
        $order = wc_get_order($order_id);
        $processed = get_post_meta($order_id, '_commissions_processed', true);
        $order_processed = get_post_meta($order_id, '_wcmp_order_processed', true);
        if (!$order_processed) {
            wcmp_process_order($order_id, $order);
        }
        $commission_ids = get_post_meta($order_id, '_commission_ids', true) ? get_post_meta($order_id, '_commission_ids', true) : array();
        if (!$processed) {
            $vendor_array = array();
            $items = $order->get_items('line_item');
            foreach ($items as $item_id => $item) {
                $vendor_id = wc_get_order_item_meta($item_id, '_vendor_id', true);
                if (!$vendor_id) {
                    $is_vendor_product = get_wcmp_product_vendors($item['product_id']);
                    if (!$is_vendor_product) {
                        continue;
                    }
                }
                $product_id = $item['product_id'];
                $variation_id = isset($item['variation_id']) && !empty($item['variation_id']) ? $item['variation_id'] : 0;
                if ($vendor_id) {
                    $vendor_obj = get_wcmp_vendor($vendor_id);
                } else {
                    $vendor_obj = get_wcmp_product_vendors($product_id);
                }
                if (in_array($vendor_obj->term_id, $vendor_array)) {
                    if ($variation_id) {
                        $query_id = $variation_id;
                    } else {
                        $query_id = $product_id;
                    }
                    $commission = $vendor_obj->get_vendor_commissions_by_product($order_id, $query_id);
                    $previous_ids = get_post_meta($commission[0], '_commission_product', true);
                    if (is_array($previous_ids)) {
                        array_push($previous_ids, $query_id);
                    }
                    update_post_meta($commission[0], '_commission_product', $previous_ids);

                    $item_commission = $this->get_item_commission($product_id, $variation_id, $item, $order_id, $item_id);

                    $wpdb->query("UPDATE `{$wpdb->prefix}wcmp_vendor_orders` SET commission_id = " . $commission[0] . ", commission_amount = '" . $item_commission . "' WHERE order_id =" . $order_id . " AND order_item_id = " . $item_id . " AND product_id = " . $product_id);
                } else {
                    $vendor_id = wc_get_order_item_meta($item_id, '_vendor_id', true);
                    if ($product_id) {
                        $commission_id = $this->record_commission($product_id, $order_id, $variation_id, $order, $vendor_obj, $item_id, $item);
                        if ($commission_id) {
                            $commission_ids[] = $commission_id;
                            update_post_meta($order_id, '_commission_ids', $commission_ids);
                        }
                        $vendor_array[] = $vendor_obj->term_id;
                    }
                }
            }
            $email_admin = WC()->mailer()->emails['WC_Email_Vendor_New_Order'];
            $email_admin->trigger($order_id);
        }
        // Mark commissions as processed
        update_post_meta($order_id, '_commissions_processed', 'yes');
        if (!empty($commission_ids) && is_array($commission_ids)) {
            foreach ($commission_ids as $commission_id) {
                $commission_amount = get_wcmp_vendor_order_amount(array('commission_id' => $commission_id, 'order_id' => $order_id));
                update_post_meta($commission_id, '_commission_amount', (float) $commission_amount['commission_amount']);
            }
        }
    }

    /**
     * Record individual commission
     * @param  int $product_id ID of product for commission
     * @param  int $line_total Line total of product
     * @return void
     */
    public function record_commission($product_id = 0, $order_id = 0, $variation_id = 0, $order, $vendor, $item_id = 0, $item) {
        if ($product_id > 0) {
            if ($vendor) {
                $vendor_due = $vendor->wcmp_get_vendor_part_from_order($order, $vendor->term_id);
                return $this->create_commission($vendor->term_id, $product_id, $vendor_due, $order_id, $variation_id, $item_id, $item, $order);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Create new commission post
     *
     * @param  int $vendor_id  ID of vendor for commission
     * @param  int $product_id ID of product for commission
     * @param  int $amount     Commission total
     * @return void
     */
    public function create_commission($vendor_id = 0, $product_id = 0, $amount = 0, $order_id = 0, $variation_id = 0, $item_id = 0, $item, $order) {
        global $wpdb;
        if ($vendor_id == 0) {
            return false;
        }
        $commission_data = array(
            'post_type' => 'dc_commission',
            'post_title' => sprintf(__('Commission - %s', 'dc-woocommerce-multi-vendor'), strftime(_x('%B %e, %Y @ %I:%M %p', 'Commission date parsed by strftime', 'dc-woocommerce-multi-vendor'), current_time('timestamp'))),
            'post_status' => 'private',
            'ping_status' => 'closed',
            'post_excerpt' => '',
            'post_author' => 1
        );
        $commission_id = wp_insert_post($commission_data);
        // Add meta data
        if ($vendor_id > 0) {
            update_post_meta($commission_id, '_commission_vendor', $vendor_id);
        }
        if ($variation_id > 0) {
            update_post_meta($commission_id, '_commission_product', array($variation_id));
        } else {
            update_post_meta($commission_id, '_commission_product', array($product_id));
        }
        $shipping = (float) $amount['shipping'];
        $tax = (float) ($amount['tax'] + $amount['shipping_tax']);
        update_post_meta($commission_id, '_shipping', $shipping);
        update_post_meta($commission_id, '_tax', $tax);
        if ($order_id > 0) {
            update_post_meta($commission_id, '_commission_order_id', $order_id);
        }
        // Mark commission as unpaid
        update_post_meta($commission_id, '_paid_status', 'unpaid');
        $item_commission = $this->get_item_commission($product_id, $variation_id, $item, $order_id, $item_id);
        $wpdb->query("UPDATE `{$wpdb->prefix}wcmp_vendor_orders` SET commission_id = " . $commission_id . ", commission_amount = '" . $item_commission . "' WHERE order_id =" . $order_id . " AND order_item_id = " . $item_id . " AND product_id = " . $product_id);
        do_action('wcmp_vendor_commission_created', $commission_id);
        return $commission_id;
    }

    /**
     * Get vendor commission per item for an order
     *
     * @param int $product_id
     * @param int $variation_id
     * @param array $item
     * @param int $order_id
     *
     * @return $commission_amount
     */
    public function get_item_commission($product_id, $variation_id, $item, $order_id, $item_id = '') {
        global $WCMp;
        $order = wc_get_order($order_id);
        $amount = 0;
        $commission = array();
        $product_value_total = 0;
        if (isset($WCMp->vendor_caps->payment_cap['commission_include_coupon'])) {
            $line_total = $order->get_item_total($item, false, false) * $item['qty'];
        } else {
            $line_total = $order->get_item_subtotal($item, false, false) * $item['qty'];
        }
        if ($product_id) {
            $vendor_id = wc_get_order_item_meta($item_id, '_vendor_id', true);
            if ($vendor_id) {
                $vendor = get_wcmp_vendor($vendor_id);
            } else {
                $vendor = get_wcmp_product_vendors($product_id);
            }
            if ($vendor) {
                $commission = $this->get_commission_amount($product_id, $vendor->term_id, $variation_id, $item_id, $order);
                $commission = apply_filters('wcmp_get_commission_amount', $commission, $product_id, $vendor->term_id, $variation_id, $item_id, $order);
                if (!empty($commission)) {
                    if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage') {
                        $amount = (float) $line_total * ( (float) $commission['commission_val'] / 100 ) + (float) $commission['commission_fixed'];
                    } else if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage_qty') {
                        $amount = (float) $line_total * ( (float) $commission['commission_val'] / 100 ) + ((float) $commission['commission_fixed'] * $item['qty']);
                    } else if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'percent') {
                        $amount = (float) $line_total * ( (float) $commission['commission_val'] / 100 );
                    } else if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed') {
                        $amount = (float) $commission['commission_val'] * $item['qty'];
                    }
                    if (isset($WCMp->vendor_caps->payment_cap['revenue_sharing_mode'])) {
                        if ($WCMp->vendor_caps->payment_cap['revenue_sharing_mode'] == 'admin') {
                            $amount = (float) $line_total - (float) $amount;
                            if ($amount < 0) {
                                $amount = 0;
                            }
                        }
                    }
                    if ($variation_id == 0 || $variation_id == '') {
                        $product_id_for_value = $product_id;
                    } else {
                        $product_id_for_value = $variation_id;
                    }

                    $product_value_total += $item->get_total();
                    if ($amount > $product_value_total) {
                        $amount = $product_value_total;
                    }
                    return apply_filters('vendor_commission_amount', $amount, $product_id, $variation_id, $item, $order_id, $item_id);
                }
            }
        }
        return apply_filters('vendor_commission_amount', $amount, $product_id, $variation_id, $item, $order_id, $item_id);
    }

    /**
     * Get assigned commission percentage
     *
     * @param  int $product_id ID of product
     * @param  int $vendor_id  ID of vendor
     * @return int             Relevent commission percentage
     */
    public function get_commission_amount($product_id = 0, $vendor_id = 0, $variation_id = 0, $item_id = '', $order = array()) {
        global $WCMp;

        $data = array();
        if ($product_id > 0 && $vendor_id > 0) {

            $vendor_idd = wc_get_order_item_meta($item_id, '_vendor_id', true);
            if ($vendor_idd) {
                $vendor = get_wcmp_vendor($vendor_idd);
            } else {
                $vendor = get_wcmp_product_vendors($product_id);
            }

            if ( isset( $WCMp->vendor_caps->payment_cap['default_percentage'] ) ) {
                $WCMp->vendor_caps->payment_cap['default_percentage'] = apply_filters(
                    'wcmp_default_percentage_commission_val',
                    $WCMp->vendor_caps->payment_cap['default_percentage'],
                    $vendor_idd
                );
            }
            
            if ( isset( $WCMp->vendor_caps->payment_cap['default_commission'] ) ) {
                $WCMp->vendor_caps->payment_cap['default_commission'] = apply_filters(
                    'wcmp_default_percentage_commission_val',
                    $WCMp->vendor_caps->payment_cap['default_commission'],
                    $vendor_idd
                );
            }

            if ( isset( $WCMp->vendor_caps->payment_cap['fixed_with_percentage'] ) ) {
                $WCMp->vendor_caps->payment_cap['fixed_with_percentage'] = apply_filters(
                    'wcmp_default_fixed_commission_val',
                    $WCMp->vendor_caps->payment_cap['fixed_with_percentage'],
                    $vendor_idd
                );
            }
           
            if ( isset( $WCMp->vendor_caps->payment_cap['fixed_with_percentage_qty'] ) ) {
                $WCMp->vendor_caps->payment_cap['fixed_with_percentage_qty'] = apply_filters(
                    'wcmp_default_fixed_commission_val',
                    $WCMp->vendor_caps->payment_cap['fixed_with_percentage_qty'],
                    $vendor_idd
                );
            }

            if ($vendor->term_id == $vendor_id) {

                if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage') {

                    if ($variation_id > 0) {
                        $data['commission_val'] = get_post_meta($variation_id, '_product_vendors_commission_percentage', true);
                        $data['commission_fixed'] = get_post_meta($variation_id, '_product_vendors_commission_fixed_per_trans', true);
                        if (empty($data)) {
                            $data['commission_val'] = get_post_meta($product_id, '_commission_percentage_per_product', true);
                            $data['commission_fixed'] = get_post_meta($product_id, '_commission_fixed_with_percentage', true);
                        }
                    } else {
                        $data['commission_val'] = get_post_meta($product_id, '_commission_percentage_per_product', true);
                        $data['commission_fixed'] = get_post_meta($product_id, '_commission_fixed_with_percentage', true);
                    }
                    if (!empty($data['commission_val'])) {
                        return $data; // Use product commission percentage first
                    } else {
                        $category_wise_commission = $this->get_category_wise_commission($product_id);
                        if ($category_wise_commission->commission_percentage || $category_wise_commission->fixed_with_percentage) {
                            return array('commission_val' => $category_wise_commission->commission_percentage, 'commission_fixed' => $category_wise_commission->fixed_with_percentage);
                        }
                        $vendor_commission_percentage = 0;
                        $vendor_commission_percentage = get_user_meta($vendor->id, '_vendor_commission_percentage', true);
                        $vendor_commission_fixed_with_percentage = 0;
                        $vendor_commission_fixed_with_percentage = get_user_meta($vendor->id, '_vendor_commission_fixed_with_percentage', true);
                        if ($vendor_commission_percentage > 0) {
                            return array('commission_val' => $vendor_commission_percentage, 'commission_fixed' => $vendor_commission_fixed_with_percentage); // Use vendor user commission percentage 
                        } else {
                            if (isset($WCMp->vendor_caps->payment_cap['default_percentage'])) {
                                return array('commission_val' => $WCMp->vendor_caps->payment_cap['default_percentage'], 'commission_fixed' => $WCMp->vendor_caps->payment_cap['fixed_with_percentage']);
                            } else
                                return false;
                        }
                    }
                } else if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage_qty') {

                    if ($variation_id > 0) {
                        $data['commission_val'] = get_post_meta($variation_id, '_product_vendors_commission_percentage', true);
                        $data['commission_fixed'] = get_post_meta($variation_id, '_product_vendors_commission_fixed_per_qty', true);
                        if (!$data) {
                            $data['commission_val'] = get_post_meta($product_id, '_commission_percentage_per_product', true);
                            $data['commission_fixed'] = get_post_meta($product_id, '_commission_fixed_with_percentage_qty', true);
                        }
                    } else {
                        $data['commission_val'] = get_post_meta($product_id, '_commission_percentage_per_product', true);
                        $data['commission_fixed'] = get_post_meta($product_id, '_commission_fixed_with_percentage_qty', true);
                    }
                    if (!empty($data['commission_val'])) {
                        return $data; // Use product commission percentage first
                    } else {
                        $category_wise_commission = $this->get_category_wise_commission($product_id);
                        if ($category_wise_commission->commission_percentage || $category_wise_commission->fixed_with_percentage_qty) {
                            return array('commission_val' => $category_wise_commission->commission_percentage, 'commission_fixed' => $category_wise_commission->fixed_with_percentage_qty);
                        }
                        $vendor_commission_percentage = 0;
                        $vendor_commission_fixed_with_percentage = 0;
                        $vendor_commission_percentage = get_user_meta($vendor->id, '_vendor_commission_percentage', true);
                        $vendor_commission_fixed_with_percentage = get_user_meta($vendor->id, '_vendor_commission_fixed_with_percentage_qty', true);
                        if ($vendor_commission_percentage > 0) {
                            return array('commission_val' => $vendor_commission_percentage, 'commission_fixed' => $vendor_commission_fixed_with_percentage); // Use vendor user commission percentage 
                        } else {
                            if (isset($WCMp->vendor_caps->payment_cap['default_percentage'])) {
                                return array('commission_val' => $WCMp->vendor_caps->payment_cap['default_percentage'], 'commission_fixed' => $WCMp->vendor_caps->payment_cap['fixed_with_percentage_qty']);
                            } else
                                return false;
                        }
                    }
                } else {
                    if ($variation_id > 0) {
                        $data['commission_val'] = get_post_meta($variation_id, '_product_vendors_commission', true);
                        if (!$data) {
                            $data['commission_val'] = get_post_meta($product_id, '_commission_per_product', true);
                        }
                    } else {
                        $data['commission_val'] = get_post_meta($product_id, '_commission_per_product', true);
                    }
                    if (!empty($data['commission_val'])) {
                        return $data; // Use product commission percentage first
                    } else {
                        if ($category_wise_commission = $this->get_category_wise_commission($product_id)->commision) {
                            return array('commission_val' => $category_wise_commission);
                        }
                        $vendor_commission = get_user_meta($vendor->id, '_vendor_commission', true);
                        if ($vendor_commission) {
                            return array('commission_val' => $vendor_commission); // Use vendor user commission percentage 
                        } else {
                            return isset($WCMp->vendor_caps->payment_cap['default_commission']) ? array('commission_val' => $WCMp->vendor_caps->payment_cap['default_commission']) : false; // Use default commission
                        }
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Fetch category wise commission
     * @param id $product_id
     * @return Object
     */
    public function get_category_wise_commission($product_id = 0) {
        $terms = get_the_terms($product_id, 'product_cat');
        $category_wise_commission = new stdClass();
        $category_wise_commission->commision = 0;
        $category_wise_commission->commission_percentage = 0;
        $category_wise_commission->fixed_with_percentage = 0;
        $category_wise_commission->fixed_with_percentage_qty = 0;
        if ($terms) {
            if (1 == count($terms)) {
                $category_wise_commission->commision = get_term_meta($terms[0]->term_id, 'commision', true) ? get_term_meta($terms[0]->term_id, 'commision', true) : 0;
                $category_wise_commission->commission_percentage = get_term_meta($terms[0]->term_id, 'commission_percentage', true) ? get_term_meta($terms[0]->term_id, 'commission_percentage', true) : 0;
                $category_wise_commission->fixed_with_percentage = get_term_meta($terms[0]->term_id, 'fixed_with_percentage', true) ? get_term_meta($terms[0]->term_id, 'fixed_with_percentage', true) : 0;
                $category_wise_commission->fixed_with_percentage_qty = get_term_meta($terms[0]->term_id, 'fixed_with_percentage_qty', true) ? get_term_meta($terms[0]->term_id, 'fixed_with_percentage_qty', true) : 0;
            }else{
                $category_wise_commission = $this->get_multiple_category_wise_commission( $terms, $product_id );
            }
        }
        return apply_filters('wcmp_category_wise_commission', $category_wise_commission, $product_id);
    }
    
    /**
     * Fetch multiple category wise commission
     * @param terms $terms
     * @param id $product_id
     * @return Object
     */
    public function get_multiple_category_wise_commission( $terms, $product_id = 0 ) {
        global $WCMp;
        $terms_commission_values = array();
        foreach ( $terms as $term ) {
            if ( $WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage' ) {
                $commission_percentage = get_term_meta( $term->term_id, 'commission_percentage', true ) ? get_term_meta( $term->term_id, 'commission_percentage', true ) : 0;
                $fixed_with_percentage = get_term_meta( $term->term_id, 'fixed_with_percentage', true ) ? get_term_meta( $term->term_id, 'fixed_with_percentage', true ) : 0;
                $terms_commission_values[$term->term_id] = $commission_percentage + $fixed_with_percentage;
            } else if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage_qty') {
                $commission_percentage = get_term_meta( $term->term_id, 'commission_percentage', true ) ? get_term_meta( $term->term_id, 'commission_percentage', true ) : 0;
                $fixed_with_percentage_qty = get_term_meta( $term->term_id, 'fixed_with_percentage_qty', true ) ? get_term_meta( $term->term_id, 'fixed_with_percentage_qty', true ) : 0;
                $terms_commission_values[$term->term_id] = $commission_percentage + $fixed_with_percentage_qty;
            } else {
                $commision = get_term_meta( $term->term_id, 'commision', true ) ? get_term_meta( $term->term_id, 'commision', true ) : 0;
                $terms_commission_values[$term->term_id] = $commision;
            }
        }
        $max_comm_val_term_id = '';
        if( $terms_commission_values ){
            $max_comm_val = max( $terms_commission_values );
            $max_comm_val_term_id = array_search ( $max_comm_val, $terms_commission_values );
        }
        $term_id = apply_filters( 'wcmp_get_multiple_category_wise_max_commission_term_id', $max_comm_val_term_id, $terms_commission_values, $product_id );
        $category_wise_commission = new stdClass();
        $category_wise_commission->commision = get_term_meta( $term_id, 'commision', true ) ? get_term_meta( $term_id, 'commision', true ) : 0;
        $category_wise_commission->commission_percentage = get_term_meta( $term_id, 'commission_percentage', true ) ? get_term_meta( $term_id, 'commission_percentage', true ) : 0;
        $category_wise_commission->fixed_with_percentage = get_term_meta( $term_id, 'fixed_with_percentage', true ) ? get_term_meta( $term_id, 'fixed_with_percentage', true ) : 0;
        $category_wise_commission->fixed_with_percentage_qty = get_term_meta( $term_id, 'fixed_with_percentage_qty', true ) ? get_term_meta( $term_id, 'fixed_with_percentage_qty', true ) : 0;
        return apply_filters( 'wcmp_multiple_category_wise_commission', $category_wise_commission, $product_id );
    }

}
