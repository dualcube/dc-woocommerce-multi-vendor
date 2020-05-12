<?php

/**
 *  WCMPp Vendor Admin Dashboard - Vendor WP-Admin Dashboard Pages
 * 
 * @version	2.2.0
 * @package WCMp
 * @author  WC Marketplace
 */
Class WCMp_Admin_Dashboard {

    private $wcmp_vendor_order_page;
    /** @var string Currenct Step */
    private $step = '';
    /** @var array Steps for the setup wizard */
    private $steps = array();
    private $vendor;
    function __construct() {

        // Add Shop Settings page 
        add_action('admin_menu', array($this, 'vendor_dashboard_pages'));

        add_action('woocommerce_product_options_shipping', array($this, 'wcmp_product_options_shipping'), 5);

        add_action('wp_before_admin_bar_render', array($this, 'remove_admin_bar_links'));

        add_action('wp_footer', 'wcmp_remove_comments_section_from_vendor_dashboard');

        add_action('wcmp_dashboard_setup', array(&$this, 'wcmp_dashboard_setup'), 5);
        add_action('wcmp_dashboard_widget', array(&$this, 'do_wcmp_dashboard_widget'));
        // Vendor store updater info
        add_action('wcmp_dashboard_setup', array(&$this, 'wcmp_dashboard_setup_updater'), 6);
        // Vendor save product
        if ( current_user_can( 'edit_products' ) ) {
            add_action( 'template_redirect', array( &$this, 'save_product' ), 90 );
        }
        if ( current_vendor_can( 'edit_shop_coupon' ) ) {
            add_action( 'template_redirect', array( &$this, 'save_coupon' ), 90 );
        }
        
        add_filter( 'wcmp_vendor_dashboard_add_product_url', array( &$this, 'wcmp_vendor_dashboard_add_product_url' ), 10 );
        add_filter( 'wcmp_vendor_submit_product', array( &$this, 'wcmp_vendor_dashboard_add_product_url' ), 10 );

        // Init export functions
        $this->export_csv();

        // Init submit comment
        $this->submit_comment();

        $this->vendor_withdrawl();

        $this->export_vendor_orders_csv();
        // vendor tools handler
        $this->vendor_tools_handler();
        // vendor updater handler
        $this->vendor_updater_handler();
        // save shipping data
        $this->backend_shipping_handler();
        // vendor store setup wizard
        $this->vendor_setup_wizard();
    }

    function remove_admin_bar_links() {
        global $wp_admin_bar;
        if (!current_user_can('manage_options')) {
            $wp_admin_bar->remove_menu('new-post');
            $wp_admin_bar->remove_menu('new-dc_commission');
            $wp_admin_bar->remove_menu('comments');
        }
    }

    /**
     * Vendor Commission withdrawl
     */
    public function vendor_withdrawl() {
        global $WCMp;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['vendor_get_paid'])) {
                $vendor = get_wcmp_vendor(get_current_vendor_id());
                $commissions = isset($_POST['commissions']) ? $_POST['commissions'] : array();
                if (!empty($commissions)) {
                    $payment_method = get_user_meta($vendor->id, '_vendor_payment_mode', true);
                    if ($payment_method) {
                        if (array_key_exists($payment_method, $WCMp->payment_gateway->payment_gateways)) {
                            $response = $WCMp->payment_gateway->payment_gateways[$payment_method]->process_payment($vendor, $commissions, 'manual');
                            if ($response) {
                                if (isset($response['transaction_id'])) {
                                    do_action( 'wcmp_after_vendor_withdrawal_transaction_success', $response['transaction_id'] );
                                    $redirect_url = wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_transaction_details_endpoint', 'vendor', 'general', 'transaction-details'), $response['transaction_id']);
                                    $notice = $this->get_wcmp_transaction_notice($response['transaction_id']);
                                    if (isset($notice['type'])) {
                                        wc_add_notice($notice['message'], $notice['type']);
                                    }
                                    wp_safe_redirect($redirect_url);
                                    exit;
                                } else {
                                    foreach ($response as $message) {
                                        wc_add_notice($message['message'], $message['type']);
                                    }
                                }
                            } else {
                                wc_add_notice(__('Oops! Something went wrong please try again later', 'dc-woocommerce-multi-vendor'), 'error');
                            }
                        } else {
                            wc_add_notice(__('Invalid payment method', 'dc-woocommerce-multi-vendor'), 'error');
                        }
                    } else {
                        wc_add_notice(__('No payment method has been selected for commission withdrawal', 'dc-woocommerce-multi-vendor'), 'error');
                    }
                } else {
                    wc_add_notice(__('Please select atleast one or more commission.', 'dc-woocommerce-multi-vendor'), 'error');
                }
            }
        }
    }

    public function get_wcmp_transaction_notice($transaction_id) {
        $transaction = get_post($transaction_id);
        $notice = array();
        switch ($transaction->post_status) {
            case 'wcmp_processing':
                $notice = array('type' => 'success', 'message' => __('Your withdrawal request has been sent to the admin and your commission will be disbursed shortly!', 'dc-woocommerce-multi-vendor'));
                break;
            case 'wcmp_completed':
                $notice = array('type' => 'success', 'message' => __('Congrats! You have successfully received your commission amount.', 'dc-woocommerce-multi-vendor'));
                break;
            case 'wcmp_canceled':
                $notice = array('type' => 'error', 'message' => __('Oops something went wrong! Your commission withdrawal request was declined!', 'dc-woocommerce-multi-vendor'));
                break;
            default :
                break;
        }
        return apply_filters('wcmp_get_transaction_status_notice', $notice, $transaction);
    }

    /**
     * Export CSV from vendor dasboard page
     *
     * @access public
     * @return void
     */
    public function export_csv() {
        global $WCMp;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if (isset($_POST['export_transaction'])) {
                $transaction_details = array();
                if (!empty($_POST['transaction_ids'])) {
                    $date = date('Y-m-d');
                    $filename = 'TransactionReport-' . $date . '.csv';
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: application/download");
                    header("Content-Disposition: attachment;filename={$filename}");
                    header("Content-Transfer-Encoding: binary");
                    header("Content-Type: charset=UTF-8");

                    $headers = array(
                        'date' => __('Date', 'dc-woocommerce-multi-vendor'),
                        'trans_id' => __('Transaction ID', 'dc-woocommerce-multi-vendor'),
                        'commission_ids' => __('Commission IDs', 'dc-woocommerce-multi-vendor'),
                        'mode' => __('Mode', 'dc-woocommerce-multi-vendor'),
                        'commission' => __('Commission', 'dc-woocommerce-multi-vendor'),
                        'fee' => __('Fee', 'dc-woocommerce-multi-vendor'),
                        'credit' => __('Credit', 'dc-woocommerce-multi-vendor'),
                    );
                    if (!empty($_POST['transaction_ids'])) {
                        foreach ($_POST['transaction_ids'] as $transaction_id) {
                            $commission_details = get_post_meta($transaction_id, 'commission_detail', true);
                            $transfer_charge = get_post_meta($transaction_id, 'transfer_charge', true) + get_post_meta($transaction_id, 'gateway_charge', true);
                            $transaction_amt = get_post_meta($transaction_id, 'amount', true) - get_post_meta($transaction_id, 'transfer_charge', true) - get_post_meta($transaction_id, 'gateway_charge', true);
                            $transaction_commission = get_post_meta($transaction_id, 'amount', true);

                            $mode = get_post_meta($transaction_id, 'transaction_mode', true);
                            if ($mode == 'paypal_masspay' || $mode == 'paypal_payout') {
                                $transaction_mode = __('PayPal', 'dc-woocommerce-multi-vendor');
                            } else if ($mode == 'stripe_masspay') {
                                $transaction_mode = __('Stripe', 'dc-woocommerce-multi-vendor');
                            } else if ($mode == 'direct_bank') {
                                $transaction_mode = __('Direct Bank Transfer', 'dc-woocommerce-multi-vendor');
                            } else {
                                $transaction_mode = $mode;
                            }

                            $order_datas[] = array(
                                'date' => get_the_date('Y-m-d', $transaction_id),
                                'trans_id' => '#' . $transaction_id,
                                'commission_ids' => '#' . implode(', #', $commission_details),
                                'mode' => $transaction_mode,
                                'commission' => $transaction_commission,
                                'fee' => $transfer_charge,
                                'credit' => $transaction_amt,
                            );
                        }
                    }


                    // Initiate output buffer and open file
                    ob_start();
                    $file = fopen("php://output", 'w');

                    // Add headers to file
                    fputcsv($file, $headers);
                    // Add data to file
                    if (!empty($order_datas)) {
                        foreach ($order_datas as $order_data) {
                            fputcsv($file, $order_data);
                        }
                    } else {
                        fputcsv($file, array(__('Sorry. no transaction data is available upon your selection', 'dc-woocommerce-multi-vendor')));
                    }

                    // Close file and get data from output buffer
                    fclose($file);
                    $csv = ob_get_clean();

                    // Send CSV to browser for download
                    echo $csv;
                    die();
                } else {
                    wc_add_notice(__('Please select atleast one and more transactions.', 'dc-woocommerce-multi-vendor'), 'error');
                }
            }
            $user = wp_get_current_user();
            $vendor = get_wcmp_vendor($user->ID);
            if (isset($_POST['wcmp_stat_export']) && !empty($_POST['wcmp_stat_export']) && $vendor && apply_filters('can_wcmp_vendor_export_orders_csv', true, $vendor->id)) {
                $vendor = apply_filters('wcmp_order_details_export_vendor', $vendor);
                $start_date = isset($_POST['wcmp_stat_start_dt']) ? $_POST['wcmp_stat_start_dt'] : date('Y-m-01');
                $end_date = isset($_POST['wcmp_stat_end_dt']) ? $_POST['wcmp_stat_end_dt'] : date('Y-m-d');
                $start_date = strtotime('-1 day', strtotime($start_date));
                $end_date = strtotime('+1 day', strtotime($end_date));
                $query = array(
                    'date_query' => array(
                        array(
                            'after' => array('year' => date('Y', $start_date), 'month' => date('m', $start_date), 'day' => date('d', $start_date)),
                            'before' => array('year' => date('Y', $end_date), 'month' => date('m', $end_date), 'day' => date('d', $end_date)),
                            'inclusive' => true,
                        )
                    )
                );
                $records = $vendor->get_orders(false, false, $query);
                if (!empty($records) && is_array($records)) {
                    $vendor_orders = array_unique($records);
                    if (!empty($vendor_orders))
                        $this->generate_csv($vendor_orders, $vendor);
                }
            }
        }
    }

    public function generate_csv($customer_orders, $vendor, $args = array()) {
        global $WCMp;
        $order_datas = array();
        $index = 0;
        $date = date('Y-m-d');
        $default = array(
            'filename' => 'SalesReport-' . $date . '.csv',
            'iostream' => 'php://output',
            'buffer' => 'w',
            'action' => 'download',
        );
        $args = wp_parse_args($args, $default);

        $filename = $args['filename'];
        if ($args['action'] == 'download') {
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header("Content-Disposition: attachment;filename={$filename}");
            header("Content-Transfer-Encoding: binary");
        }

        $headers = apply_filters('wcmp_vendor_order_generate_csv_headers', array(
            'order' => __('Order', 'dc-woocommerce-multi-vendor'),
            'date_of_purchase' => __('Date of Purchase', 'dc-woocommerce-multi-vendor'),
            'time_of_purchase' => __('Time Of Purchase', 'dc-woocommerce-multi-vendor'),
            'vendor_name' => __('Vendor Name', 'dc-woocommerce-multi-vendor'),
            'product' => __('Items bought', 'dc-woocommerce-multi-vendor'),
            'qty' => __('Quantity', 'dc-woocommerce-multi-vendor'),
            'discount_used' => __('Discount Used', 'dc-woocommerce-multi-vendor'),
            'tax' => __('Tax', 'dc-woocommerce-multi-vendor'),
            'shipping' => __('Shipping', 'dc-woocommerce-multi-vendor'),
            'commission_share' => __('Earning', 'dc-woocommerce-multi-vendor'),
            'payment_system' => __('Payment System', 'dc-woocommerce-multi-vendor'),
            'buyer_name' => __('Customer Name', 'dc-woocommerce-multi-vendor'),
            'buyer_email' => __('Customer Email', 'dc-woocommerce-multi-vendor'),
            'buyer_contact' => __('Customer Contact', 'dc-woocommerce-multi-vendor'),
            'billing_address' => __('Billing Address Details', 'dc-woocommerce-multi-vendor'),
            'shipping_address' => __('Shipping Address Details', 'dc-woocommerce-multi-vendor'),
            'order_status' => __('Order Status', 'dc-woocommerce-multi-vendor'),
        ));

        if (!apply_filters('show_customer_details_in_export_orders', true, $vendor->id)) {
            unset($headers['buyer_name']);
            unset($headers['buyer_email']);
            unset($headers['buyer_contact']);
        }
        if (!apply_filters('show_customer_billing_address_in_export_orders', true, $vendor->id)) {
            unset($headers['billing_address']);
        }
        if (!apply_filters('show_customer_shipping_address_in_export_orders', true, $vendor->id)) {
            unset($headers['shipping_address']);
        }

        if ($vendor) {
            if (!empty($customer_orders)) {
                foreach ($customer_orders as $commission_id => $customer_order) {
                    $order = wc_get_order($customer_order);
                    $vendor_items = $vendor->get_vendor_items_from_order($customer_order, $vendor->term_id);
                    $item_names = $item_qty = array();
                    if (sizeof($vendor_items) > 0) {
                        foreach ($vendor_items as $item) {
                            $item_names[] = $item['name'];
                            $item_qty[] = $item['quantity'];
                        }

                        //coupons count
                        $coupon_used = '';
                        $coupons = $order->get_items('coupon');
                        foreach ($coupons as $coupon_item_id => $item) {
                            $coupon = new WC_Coupon(trim($item['name']));
                            $coupon_post = get_post($coupon->get_id());
                            $author_id = $coupon_post->post_author;
                            if ($vendor->id == $author_id) {
                                $coupon_used .= $item['name'] . ', ';
                            }
                        }

                        // Formatted Addresses
                        $formatted_billing_address = apply_filters('woocommerce_order_formatted_billing_address', array(
                            'address_1' => $order->get_billing_address_1(),
                            'address_2' => $order->get_billing_address_2(),
                            'city' => $order->get_billing_city(),
                            'state' => $order->get_billing_state(),
                            'postcode' => $order->get_billing_postcode(),
                            'country' => $order->get_billing_country()
                                ), $order);
                        $formatted_billing_address = WC()->countries->get_formatted_address($formatted_billing_address);

                        $formatted_shipping_address = apply_filters('woocommerce_order_formatted_shipping_address', array(
                            'address_1' => $order->get_shipping_address_1(),
                            'address_2' => $order->get_shipping_address_2(),
                            'city' => $order->get_shipping_city(),
                            'state' => $order->get_shipping_state(),
                            'postcode' => $order->get_shipping_postcode(),
                            'country' => $order->get_shipping_country()
                                ), $order);
                        $formatted_shipping_address = WC()->countries->get_formatted_address($formatted_shipping_address);

                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        $customer_email = $order->get_billing_email();
                        $customer_phone = $order->get_billing_phone();

                        $order_datas[$index] = apply_filters('wcmp_vendor_order_generate_csv_data', array(
                            'order' => '#' . $customer_order,
                            'date_of_purchase' => date_i18n('Y-m-d', strtotime($order->get_date_created())),
                            'time_of_purchase' => date_i18n('H', strtotime($order->get_date_created())) . ' : ' . date_i18n('i', strtotime($order->get_date_created())),
                            'vendor_name' => $vendor->page_title,
                            'product' => implode(', ', $item_names),
                            'qty' => implode(', ', $item_qty),
                            'discount_used' => apply_filters('wcmp_export_discount_used_in_order', $coupon_used),
                            'tax' => get_post_meta($commission_id, '_tax', true),
                            'shipping' => get_post_meta($commission_id, '_shipping', true),
                            'commission_share' => get_post_meta($commission_id, '_commission_amount', true),
                            'payment_system' => $order->get_payment_method_title(),
                            'buyer_name' => $customer_name,
                            'buyer_email' => $customer_email,
                            'buyer_contact' => $customer_phone,
                            'billing_address' => str_replace('<br/>', ', ', $formatted_billing_address),
                            'shipping_address' => str_replace('<br/>', ', ', $formatted_shipping_address),
                            'order_status' => $order->get_status(),
                                ), $customer_order, $vendor);
                        if (!apply_filters('show_customer_details_in_export_orders', true, $vendor->id)) {
                            unset($order_datas[$index]['buyer_name']);
                            unset($order_datas[$index]['buyer_email']);
                            unset($order_datas[$index]['buyer_contact']);
                        }
                        if (!apply_filters('show_customer_billing_address_in_export_orders', true, $vendor->id)) {
                            unset($order_datas[$index]['billing_address']);
                        }
                        if (!apply_filters('show_customer_shipping_address_in_export_orders', true, $vendor->id)) {
                            unset($order_datas[$index]['shipping_address']);
                        }
                        $index++;
                    }
                }
            }
        }
        // Initiate output buffer and open file
        ob_start();
        if ($args['action'] == 'download' && $args['iostream'] == 'php://output') {
            $file = fopen($args['iostream'], $args['buffer']);
        } elseif ($args['action'] == 'temp' && $args['filename']) {
            $filename = sys_get_temp_dir() . '/' . $args['filename'];
            $file = fopen($filename, $args['buffer']);
        }
        // Add headers to file
        fputcsv($file, $headers);
        // Add data to file
        foreach ($order_datas as $order_data) {
            if (!$WCMp->vendor_caps->vendor_capabilities_settings('is_order_show_email') || apply_filters('is_not_show_email_field', true)) {
                unset($order_data['buyer']);
            }
            fputcsv($file, $order_data);
        }

        // Close file and get data from output buffer
        fclose($file);
        $csv = ob_get_clean();
        if ($args['action'] == 'temp') {
            return $filename;
        } else {
            // Send CSV to browser for download
            echo $csv;
            die();
        }
    }

    /**
     * Submit Comment 
     *
     * @access public
     * @return void
     */
    public function submit_comment() {
        global $WCMp;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!empty($_POST['wcmp_submit_comment'])) {
                // verify nonce
                if ($_POST['vendor_add_order_nonce'] && !wp_verify_nonce($_POST['vendor_add_order_nonce'], 'dc-vendor-add-order-comment'))
                    return false;
                $vendor = get_current_vendor();
                // Don't submit empty comments
                if (empty($_POST['comment_text']))
                    return false;
                // Only submit if the order has the product belonging to this vendor
                $order = wc_get_order($_POST['order_id']);
                $comment = esc_textarea($_POST['comment_text']);
                $note_type = isset($_POST['note_type']) ? $_POST['note_type'] : '';
		$is_customer_note = ( 'customer' === $note_type ) ? 1 : 0;
                $comment_id = $order->add_order_note($comment, $is_customer_note, true);
                if( $is_customer_note ){
                    $email_note = WC()->mailer()->emails['WC_Email_Customer_Note'];
                    $email_note->trigger(array(
                        'order_id'      => $order,
                        'customer_note' => $comment,
                    ));
                }
                // update comment author & email
                wp_update_comment(array('comment_ID' => $comment_id, 'comment_author' => $vendor->page_title, 'comment_author_email' => $vendor->user_data->user_email));
                add_comment_meta($comment_id, '_vendor_id', $vendor->id);
                wc_add_notice(__('Order note added', 'dc-woocommerce-multi-vendor'), 'success');
                wp_redirect(esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders'), $order->get_id())));
                die();
            }
        }
    }

    /**
     * Vendor tools handler 
     *
     * @access public
     * @return void
     */
    public function vendor_tools_handler() {
        $vendor = get_current_vendor();
        $wpnonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
        $tools_action = isset($_REQUEST['tools_action']) ? $_REQUEST['tools_action'] : '';
        if ($wpnonce && wp_verify_nonce($wpnonce, 'wcmp_clear_vendor_transients') && $tools_action && $tools_action == 'clear_all_transients') {
            if (current_user_can('delete_published_products')) {
                if ($vendor->clear_all_transients($vendor->id)) {
                    wc_add_notice(__('Vendor transients cleared!', 'dc-woocommerce-multi-vendor'), 'success');
                }
                wp_redirect(esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_tools_endpoint', 'vendor', 'general', 'vendor-tools'))));
                die();
            }
        }
        // 
        do_action('wcmp_vendor_tools_handler', $tools_action, $wpnonce);
    }

    public function vendor_dashboard_pages() {
        $user = wp_get_current_user();
        $vendor = get_wcmp_vendor($user->ID);
        $vendor = apply_filters('wcmp_vendor_dashboard_pages_vendor', $vendor);
        if ($vendor) {
            $shipping_page = apply_filters('wcmp_vendor_view_shipping_page', true);
            if ($vendor->is_shipping_enable() && $shipping_page) {
                $shipping_hook = add_menu_page(__('Shipping', 'dc-woocommerce-multi-vendor'), __('Shipping', 'dc-woocommerce-multi-vendor'), 'read', 'dc-vendor-shipping', array($this, 'shipping_page'));
            }
        }
    }

    /**
     * HTML setup for the Orders Page 
     */
    public static function shipping_page() {
        global $WCMp;
        $zone_id = isset($_REQUEST['zone_id']) ? absint($_REQUEST['zone_id']) : 0;
        $zones = array();
        
        $vendor_user_id = apply_filters('wcmp_dashboard_shipping_vendor', get_current_vendor_id());

        ?>
        <div class="wrap">
            <div id="icon-woocommerce" class="icon32 icon32-woocommerce-reports"><br/></div>
            <h2><?php _e('Shipping', 'dc-woocommerce-multi-vendor'); ?></h2>
            <form name="vendor_shipping_form" method="post">
                <?php wp_nonce_field( 'backend_vendor_shipping_data', 'vendor_shipping_data' ); ?>
                <?php 
                if ($zone_id) {
                    if( !class_exists( 'WCMP_Shipping_Zone' ) ) {
                        $WCMp->load_vendor_shipping();
                    }
                    $zones = WCMP_Shipping_Zone::get_zone($zone_id);
                    if ($zones)
                        $zone = WC_Shipping_Zones::get_zone(absint($zone_id));
                    // Load scripts
                    $WCMp->localize_script('wcmp_vendor_shipping');
                    wp_enqueue_script('wcmp_vendor_shipping');

                if (!$zones) {
                    ?>
                    <p><?php _e('No shipping zone found for configuration. Please contact with admin for manage your store shipping', 'dc-woocommerce-multi-vendor'); ?></p>
                    <?php
                } elseif ($zones) {
                    // for specific zone shipping methods settings

                    $show_post_code_list = $show_state_list = $show_post_code_list = false;

                    $zone_id = $zones['data']['id'];
                    $zone_locations = $zones['data']['zone_locations'];

                    $zone_location_types = array_column(array_map('wcmp_convert_to_array', $zone_locations), 'type', 'code');

                    $selected_continent_codes = array_keys($zone_location_types, 'continent');

                    if (!$selected_continent_codes) {
                        $selected_continent_codes = array();
                    }

                    $selected_country_codes = array_keys($zone_location_types, 'country');
                    $all_states = WC()->countries->get_states();

                    $state_key_by_country = array();
                    $state_key_by_country = array_intersect_key($all_states, array_flip($selected_country_codes));

                    array_walk($state_key_by_country, 'wcmp_state_key_alter');

                    $state_key_by_country = call_user_func_array('array_merge', $state_key_by_country);

                    $show_limit_location_link = apply_filters('show_limit_location_link', (!in_array('postcode', $zone_location_types)));
                    $vendor_shipping_methods = $zones['shipping_methods'];

                    if ($show_limit_location_link) {
                        if (in_array('state', $zone_location_types)) {
                            $show_city_list = apply_filters('wcmp_city_select_dropdown_enabled', false);
                            $show_post_code_list = true;
                        } elseif (in_array('country', $zone_location_types)) {
                            $show_state_list = true;
                            $show_city_list = apply_filters('wcmp_city_select_dropdown_enabled', false);
                            $show_post_code_list = true;
                        }
                    }

                    $want_to_limit_location = !empty($zones['locations']);
                    $countries = $states = $cities = array();
                    $postcodes = '';
                    if ($want_to_limit_location) {
                        $postcodes = array();
                        foreach ($zones['locations'] as $each_location) {
                            switch ($each_location['type']) {
                                case 'state':
                                    $states[] = $each_location['code'];
                                    break;
                                case 'postcode':
                                    $postcodes[] = $each_location['code'];
                                    break;
                                default:
                                    break;
                            }
                        }
                        
                        $postcodes = implode(',', $postcodes);
                    }
                    
                    ?>
                    <input id="zone_id" class="form-control" type="hidden" name="<?php echo 'wcmp_shipping_zone[' . $zone_id . '][_zone_id]'; ?>" value="<?php echo $zone_id; ?>">
                    <table class="form-table wcmp-shipping-zone-settings wc-shipping-zone-settings">
                        <tbody>
                            <tr valign="top" class="">
                                <th scope="row" class="titledesc">
                                    <label for="">
                                        <?php _e('Zone Name', 'dc-woocommerce-multi-vendor'); ?>
                                    </label>
                                </th>
                                <td class="forminp"><?php _e($zones['data']['zone_name'], 'dc-woocommerce-multi-vendor'); ?></td>
                            </tr>
                            <tr valign="top" class="">
                                <th scope="row" class="titledesc">
                                    <label for="">
                                        <?php _e('Zone region', 'dc-woocommerce-multi-vendor'); ?>
                                    </label>
                                </th>
                                <td class="forminp"><?php _e($zones['formatted_zone_location'], 'dc-woocommerce-multi-vendor'); ?></td>
                            </tr>
                            <?php if ($show_limit_location_link && $zone_id !== 0) { ?>
                                <tr valign="top" class="">
                                    <th scope="row" class="titledesc">
                                        <label for="">
                                            <?php _e('Limit Zone Location', 'dc-woocommerce-multi-vendor'); ?>
                                        </label>
                                    </th>
                                    <td class="forminp"><input id="limit_zone_location" class="form-control" type="checkbox" name="<?php echo 'wcmp_shipping_zone[' . $zone_id . '][_limit_zone_location]'; ?>" value="1" <?php checked($want_to_limit_location, 1); ?>></td>
                                </tr>
                            <?php } ?>
                            <?php if ($show_state_list) { ?>
                                <tr valign="top" class="hide_if_zone_not_limited">
                                    <th scope="row" class="titledesc">
                                        <label for="">
                                            <?php _e('Select specific states', 'dc-woocommerce-multi-vendor'); ?>
                                        </label>
                                    </th>
                                    <td class="forminp">
                                        <select id="select_zone_states" class="form-control" name="<?php echo 'wcmp_shipping_zone[' . $zone_id . '][_select_zone_states][]'; ?>" multiple>
                                            <?php foreach ($state_key_by_country as $key => $value) { ?>
                                                <option value="<?php echo $key; ?>" <?php selected(in_array($key, $states), true); ?>><?php echo $value; ?></option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if ($show_post_code_list) { ?>
                                <tr valign="top" class="hide_if_zone_not_limited">
                                    <th scope="row" class="titledesc">
                                        <label for="">
                                            <?php _e('Set your postcode', 'dc-woocommerce-multi-vendor'); ?>
                                        </label>
                                    </th>
                                    <td class="forminp">
                                        <input id="select_zone_postcodes" class="form-control" type="text" name="<?php echo 'wcmp_shipping_zone[' . $zone_id . '][_select_zone_postcodes]'; ?>" value="<?php echo $postcodes; ?>" placholder="<?php _e('Postcodes need to be comma separated', 'dc-woocommerce-multi-vendor'); ?>">
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr valign="top" class="">
                                <th scope="row" class="titledesc">
                                    <label>
                                        <?php _e('Shipping methods', 'dc-woocommerce-multi-vendor'); ?>
                                        <?php echo wc_help_tip(__('Add your shipping method for appropiate zone', 'dc-woocommerce-multi-vendor')); // @codingStandardsIgnoreLine  ?>
                                    </label>
                                </th>
                                <td class="">
                                    <table class="wcmp-shipping-zone-methods wc-shipping-zone-methods widefat">
                                        <thead>
                                            <tr>   
                                                <th class="wcmp-title wc-shipping-zone-method-title"><?php _e('Title', 'dc-woocommerce-multi-vendor'); ?></th>
                                                <th class="wcmp-enabled wc-shipping-zone-method-enabled"><?php _e('Enabled', 'dc-woocommerce-multi-vendor'); ?></th> 
                                                <th class="wcmp-description wc-shipping-zone-method-description"><?php _e('Description', 'dc-woocommerce-multi-vendor'); ?></th>
                                                <th class="wcmp-action"><?php _e('Action', 'dc-woocommerce-multi-vendor'); ?></th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4">
                                                    <button type="submit" class="button wcmp-shipping-zone-show-method wc-shipping-zone-add-method" value="<?php esc_attr_e('Add shipping method', 'woocommerce'); ?>"><?php esc_html_e('Add shipping method', 'woocommerce'); ?></button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                            <?php if (empty($vendor_shipping_methods)) { ?> 
                                                <tr>
                                                    <td colspan="4"><?php _e('You can add multiple shipping methods within this zone. Only customers within the zone will see them.', 'dc-woocommerce-multi-vendor'); ?></td>
                                                </tr>
                                                <?php
                                            } else { 
                                                foreach ($vendor_shipping_methods as $vendor_shipping_method) {
                                                    ?>
                                                    <tr class="wcmp-shipping-zone-method">
                                                        <td><?php _e($vendor_shipping_method['title'], 'wcmp'); ?>
                                                            <div data-instance_id="<?php echo $vendor_shipping_method['instance_id']; ?>" data-method_id="<?php echo $vendor_shipping_method['id']; ?>" data-method-settings='<?php echo json_encode($vendor_shipping_method); ?>' class="row-actions edit_del_actions">
                                                            </div>
                                                        </td>
                                                        <td class="wcmp-shipping-zone-method-enabled wc-shipping-zone-method-enabled"> 
                                                            <span class="wcmp-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo ($vendor_shipping_method['enabled'] == "yes") ? 'enabled' : 'disabled'; ?>">
                                                                <input id="method_status" class="input-checkbox method-status" type="checkbox" name="method_status" value="<?php echo $vendor_shipping_method['instance_id']; ?>" <?php checked(( $vendor_shipping_method['enabled'] == "yes"), true); ?>>
                                                            </span>
                                                        </td>
                                                        <td><?php _e($vendor_shipping_method['settings']['description'], 'dc-woocommerce-multi-vendor'); ?></td>
                                                        <td>
                                                            <div class="col-actions edit_del_actions" data-instance_id="<?php echo $vendor_shipping_method['instance_id']; ?>" data-method_id="<?php echo $vendor_shipping_method['id']; ?>" data-method-settings='<?php echo json_encode($vendor_shipping_method); ?>'>
                                                                <span class="edit"><a href="javascript:void(0);" class="edit-shipping-method" data-zone_id="<?php echo $zone_id; ?>" data-method_id="<?php echo $vendor_shipping_method['id']; ?>" data-instance_id="<?php echo $vendor_shipping_method['instance_id']; ?>" title="<?php _e('Edit', 'dc-woocommerce-multi-vendor') ?>"><?php _e('Edit', 'dc-woocommerce-multi-vendor') ?></a>
                                                                </span>|
                                                                <span class="delete"><a class="delete-shipping-method" href="javascript:void(0);" data-method_id="<?php echo $vendor_shipping_method['id']; ?>" data-instance_id="<?php echo $vendor_shipping_method['instance_id']; ?>" title="<?php _e('Delete', 'dc-woocommerce-multi-vendor') ?>"><?php _e('Delete', 'dc-woocommerce-multi-vendor') ?></a></span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                        
                        <script type="text/template" id="tmpl-wcmp-modal-add-shipping-method">
                            <div class="wc-backbone-modal wcmp-modal-add-shipping-method-modal">
                            <div class="wc-backbone-modal-content">
                            <section class="wc-backbone-modal-main" role="main">
                            <header class="wc-backbone-modal-header">
                            <h1><?php esc_html_e('Add shipping method', 'dc-woocommerce-multi-vendor'); ?></h1>
                            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                            <span class="screen-reader-text"><?php esc_html_e('Close modal panel', 'dc-woocommerce-multi-vendor'); ?></span>
                            </button>
                            </header>
                            <article>
                            <form action="" method="post">
                            <input type="hidden" name="zone_id" value="<?php echo $zone_id; ?>"/>
                            <div class="wc-shipping-zone-method-selector">
                            <p><?php esc_html_e('Choose the shipping method you wish to add. Only shipping methods which support zones are listed.', 'dc-woocommerce-multi-vendor'); ?></p>
                            <?php $shipping_methods = wcmp_get_shipping_methods(); ?>
                            <select id="shipping_method" class="form-control mt-15" name="wcmp_shipping_method">
                            <?php foreach ($shipping_methods as $key => $method) { ?>
                                <option data-description="<?php echo esc_attr( wp_kses_post( wpautop( $method->get_method_description() ) ) ); ?>" value="<?php echo esc_attr( $method->id ); ?>"><?php echo esc_attr( $method->get_method_title() ); ?></option>
                            <?php } ?>
                            </select>
                            <div class="wc-shipping-zone-method-description"></div>
                            </div>
                            </form>
                            </article>
                            <footer>
                            <div class="inner">
                            <button id="btn-ok" class="button button-primary button-large wcmp-shipping-zone-add-method" data-zone_id="<?php echo $zone_id; ?>"><?php esc_html_e('Add shipping method', 'dc-woocommerce-multi-vendor'); ?></button>
                            </div>
                            </footer>
                            </section>
                            </div>
                            </div>
                            <div class="wc-backbone-modal-backdrop modal-close"></div>
                        </script>
                        <script type="text/template" id="tmpl-wcmp-modal-update-shipping-method">
                            <?php
                            global $WCMp;

                            $is_method_taxable_array = array(
                                'none' => __('None', 'dc-woocommerce-multi-vendor'),
                                'taxable' => __('Taxable', 'dc-woocommerce-multi-vendor')
                            );

                            $calculation_type = array(
                                'class' => __('Per class: Charge shipping for each shipping class individually', 'dc-woocommerce-multi-vendor'),
                                'order' => __('Per order: Charge shipping for the most expensive shipping class', 'dc-woocommerce-multi-vendor'),
                            );
                            ?>
                            <div class="wc-backbone-modal wcmp-modal-add-shipping-method-modal">
                            <div class="wc-backbone-modal-content">
                            <section class="wc-backbone-modal-main" role="main">
                            <header class="wc-backbone-modal-header">
                            <h1><?php _e( 'Edit Shipping Methods', 'dc-woocommerce-multi-vendor' ); ?></h1>
                            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                            <span class="screen-reader-text"><?php esc_html_e('Close modal panel', 'dc-woocommerce-multi-vendor'); ?></span>
                            </button>
                            </header>
                            <article class="wcmp-shipping-methods">
                            <form action="" method="post">
                            <input id="instance_id_selected" class="form-control" type="hidden" name="zone_id" value="<?php echo $zone_id; ?>"> 
                            <input id="method_id_selected" class="form-control" type="hidden" name="method_id" value="{{{ data.methodId }}}"> 
                            <input id="instance_id_selected" class="form-control" type="hidden" name="instance_id" value="{{{ data.instanceId }}}"> 
                            {{{ data.config_settings }}}
                 
                            </form>
                            </article>
                            <footer>
                            <div class="inner">
                            <button id="btn-ok" class="button button-primary button-large wcmp-shipping-zone-add-method" data-zone_id="<?php echo $zone_id; ?>"><?php esc_html_e('Save changes', 'dc-woocommerce-multi-vendor'); ?></button>
                            </div>
                            </footer>
                            </section>
                            </div>
                            </div>
                            <div class="wc-backbone-modal-backdrop modal-close"></div>
                        </script>
                    </table>
                <?php }
                    
                } else { ?>
                    <table class="wcmp-shipping-zones wc-shipping-zones widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Zone name', 'dc-woocommerce-multi-vendor'); ?></th> 
                                    <th><?php _e('Region(s)', 'dc-woocommerce-multi-vendor'); ?></th> 
                                    <th><?php _e('Shipping method(s)', 'dc-woocommerce-multi-vendor'); ?></th>
                                    <th><?php _e('Actions', 'dc-woocommerce-multi-vendor'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="wcmp-shipping-zone-rows wc-shipping-zone-rows">
                    <?php $vendor_all_shipping_zones = wcmp_get_shipping_zone();
                    if (!empty($vendor_all_shipping_zones)) {
                        foreach ($vendor_all_shipping_zones as $key => $vendor_shipping_zones) {
                    ?>
                                        <tr data-id="0" class="wc-shipping-zone-worldwide">
                                            <td class="wc-shipping-zone-name">
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=dc-vendor-shipping&zone_id=' . $vendor_shipping_zones['zone_id'])); ?>" data-zone-id="<?php echo $vendor_shipping_zones['zone_id']; ?>" class="vendor_edit_zone modify-shipping-methods"><?php _e($vendor_shipping_zones['zone_name'], 'dc-woocommerce-multi-vendor'); ?></a> 
                                            </td>
                                            <td class="wc-shipping-zone-region"><?php _e($vendor_shipping_zones['formatted_zone_location'], 'dc-woocommerce-multi-vendor'); ?></td>
                                            <td class="wc-shipping-zone-methods">
                                                <ul class="wcmp-shipping-zone-methods">
                    <?php
                    $vendor_shipping_methods = $vendor_shipping_zones['shipping_methods'];
                    $vendor_shipping_methods_titles = array();
                    if ($vendor_shipping_methods) :
                        foreach ($vendor_shipping_methods as $key => $shipping_method) {
                            $class_name = 'yes' === $shipping_method['enabled'] ? 'method_enabled' : 'method_disabled';
                            $vendor_shipping_methods_titles[] = "<li class='wcmp-shipping-zone-method wc-shipping-zone-method $class_name'>" . $shipping_method['title'] . "</li>";
                        }
                    endif;
                    //$vendor_shipping_methods_titles = array_column($vendor_shipping_methods, 'title');
                    $vendor_shipping_methods_titles = implode('', $vendor_shipping_methods_titles);

                    if (empty($vendor_shipping_methods)) {
                        ?>
                                                        <li class="wcmp-shipping-zone-method wc-shipping-zone-method"><?php _e('No shipping methods offered to this zone.', 'dc-woocommerce-multi-vendor'); ?> </li>
                                                    <?php } else { ?>
                                                        <?php _e($vendor_shipping_methods_titles, 'dc-woocommerce-multi-vendor'); ?>
                                                    <?php } ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <div class="col-actions">
                                                    <span class="view">
                                                        <a href="<?php echo esc_url(admin_url('admin.php?page=dc-vendor-shipping&zone_id=' . $vendor_shipping_zones['zone_id'])); ?>" data-zone-id="<?php echo $vendor_shipping_zones['zone_id']; ?>" class="vendor_edit_zone modify-shipping-methods" title="<?php _e('Edit', 'dc-woocommerce-multi-vendor'); ?>"><?php _e('Edit', 'dc-woocommerce-multi-vendor'); ?></a>
                                                    </span> 
                                                </div>
                                            </td>
                                        </tr>
                    <?php
                }
            } else {
                ?>
                                    <tr>
                                        <td colspan="3"><?php _e('No shipping zone found for configuration. Please contact with admin for manage your store shipping', 'dc-woocommerce-multi-vendor'); ?></td>
                                    </tr>
            <?php }
            ?>
                            </tbody>
                        </table>
                <?php }
                ?>
                    <?php do_action('wcmp_vendor_shipping_settings'); ?>
                <?php if(isset($_GET['zone_id'])) submit_button(); ?>
            </form>

            <br class="clear"/>
        </div>
        <?php
    }
    
    public function backend_shipping_handler(){
        global $WCMp;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ( isset( $_POST['vendor_shipping_data'] ) && wp_verify_nonce( $_POST['vendor_shipping_data'], 'backend_vendor_shipping_data' ) ) {
                $all_allowed_countries = WC()->countries->get_allowed_countries();
                $location = array();
                $zone_id = 0;
                if (!empty($_POST['wcmp_shipping_zone'])) {
                    foreach ($_POST['wcmp_shipping_zone'] as $shipping_zone) {
                        if (isset($shipping_zone['_zone_id']) && $shipping_zone['_zone_id'] != 0) {
                            $zone_id = $shipping_zone['_zone_id'];

                            if (isset($shipping_zone['_limit_zone_location']) && $shipping_zone['_limit_zone_location']) {
                                if (!empty($shipping_zone['_select_zone_states'])) {
                                    $state_array = array();
                                    foreach ($shipping_zone['_select_zone_states'] as $zone_state) {
                                        $state_array[] = array(
                                            'code' => $zone_state,
                                            'type' => 'state'
                                        );
                                    }

                                    $location = array_merge($location, $state_array);
                                }

                                if (!empty($shipping_zone['_select_zone_postcodes'])) {
                                    $postcode_array = array();
                                    $zone_postcodes = array_map('trim', explode(',', $shipping_zone['_select_zone_postcodes']));
                                    foreach ($zone_postcodes as $zone_postcode) {
                                        $postcode_array[] = array(
                                            'code' => $zone_postcode,
                                            'type' => 'postcode'
                                        );
                                    }

                                    $location = array_merge($location, $postcode_array);
                                }
                            }
                        }
                    }
                }
                if( !class_exists( 'WCMP_Shipping_Zone' ) ) {
                    $WCMp->load_vendor_shipping();
                }
                WCMP_Shipping_Zone::save_location($location, $zone_id);

                $WCMp->load_class('shipping-gateway');
                WCMp_Shipping_Gateway::load_class('shipping-method');
                $vendor_shipping = new WCMP_Vendor_Shipping_Method();
                $vendor_shipping->process_admin_options();
                // clear shipping transient
                WC_Cache_Helper::get_transient_version('shipping', true);
                echo '<div class="updated settings-error notice is-dismissible"><p><strong>' . __("Shipping Data Updated", 'dc-woocommerce-multi-vendor') . '</strong></p></div>';
            }
            
        }
    }

    /**
     *
     *
     * @param unknown $status
     * @param unknown $option
     * @param unknown $value
     *
     * @return unknown
     */
    public static function set_table_option($status, $option, $value) {
        if ($option == 'orders_per_page') {
            return $value;
        }
    }

    /**
     * Generate Orders Page view 
     */
    public function wcmp_vendor_orders_page() {
        $this->wcmp_vendor_order_page->wcmp_prepare_order_page_items();
        ?>
        <div class="wrap">

            <div id="icon-woocommerce" class="icon32 icon32-woocommerce-reports"><br/></div>
            <h2><?php _e('Orders', 'dc-woocommerce-multi-vendor'); ?></h2>

            <form id="posts-filter" method="get">

                <input type="hidden" name="page" value="dc-vendor-orders"/>
        <?php $this->wcmp_vendor_order_page->display(); ?>

            </form>
            <div id="ajax-response"></div>
            <br class="clear"/>
        </div>
        <?php
    }

    function wcmp_product_options_shipping() {
        global $post;
        if (!is_user_wcmp_vendor(get_current_user_id())) {
            return;
        }
        $product_object = wc_get_product($post->ID);
        $args = array(
            'taxonomy' => 'product_shipping_class',
            'hide_empty' => 0,
            'meta_query' => array(
                array(
                    'key' => 'vendor_id',
                    'value' => get_current_vendor_id(),
                    'compare' => '='
                )
            ),
            'show_option_none' => __('No shipping class', 'dc-woocommerce-multi-vendor'),
            'name' => 'product_shipping_class',
            'id' => 'product_shipping_class',
            'selected' => $product_object->get_shipping_class_id('edit'),
            'class' => 'select short',
        );
        ?>
        <p class="form-field dimensions_field">
            <label for="product_shipping_class"><?php _e('Shipping class', 'dc-woocommerce-multi-vendor'); ?></label>
        <?php wp_dropdown_categories($args); ?>
        <?php echo wc_help_tip(__('Shipping classes are used by certain shipping methods to group similar products.', 'dc-woocommerce-multi-vendor')); ?>
        </p>
        <script type="text/javascript">
            jQuery('#product_shipping_class').closest("p").remove();
        </script>
        <?php
    }

    public function export_vendor_orders_csv() {
        global $wpdb;
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['wcmp_download_vendor_order_csv'])) {
                $vendor = get_current_vendor();
                $order_data = array();
                $order_ids = isset($_POST['selected_orders']) ? $_POST['selected_orders'] : array();
                if ($order_ids && count($order_ids) > 0) {
                    foreach ($order_ids as $order_id) {
                        $vorder = wcmp_get_order($order_id);
                        if($vorder){
                            $commission_id = $vorder->get_prop('_commission_id');
                            $order_data[$commission_id] = $order_id;
                        }
                        
                    }
                    if (!empty($order_data)) {
                        $this->generate_csv($order_data, $vendor);
                    }
                } else {
                    wc_add_notice(__('Please select atleast one and more order.', 'dc-woocommerce-multi-vendor'), 'error');
                }
            }
        }
    }

    public function is_order_shipped($order_id, $vendor) {
        global $WCMp, $wpdb;
        $shipping_status = $wpdb->get_results("SELECT DISTINCT shipping_status from `{$wpdb->prefix}wcmp_vendor_orders` where vendor_id = " . $vendor->id . " AND order_id = " . $order_id, ARRAY_A);
        $shipping_status = $shipping_status[0]['shipping_status'];
        if ($shipping_status == 0)
            return false;
        if ($shipping_status == 1)
            return true;
    }

    public function save_store_settings($user_id, $post) {
        global $WCMp;
        $vendor = get_wcmp_vendor($user_id);
        $fields = $WCMp->user->get_vendor_fields($user_id);
        foreach ($fields as $fieldkey => $value) {

            if (isset($post[$fieldkey])) {
                if ($fieldkey == "vendor_page_slug" && !empty($post[$fieldkey])) {
                    if ($vendor && !$vendor->update_page_slug(wc_clean($_POST[$fieldkey]))) {
                        if (is_admin()) {
                            echo _e('Slug already exists', 'dc-woocommerce-multi-vendor');
                        } else {
                            $err_msg = __('Slug already exists', 'dc-woocommerce-multi-vendor');
                            return $err_msg;
                        }
                    } else {
                        update_user_meta($user_id, '_' . $fieldkey, wc_clean($post[$fieldkey]));
                    }
                    continue;
                }
                if ($fieldkey == "vendor_page_slug" && empty($post[$fieldkey])) {
                    if (is_admin()) {
                        echo _e('Slug can not be empty', 'dc-woocommerce-multi-vendor');
                    } else {
                        $err_msg = __('Slug can not be empty', 'dc-woocommerce-multi-vendor');
                        return $err_msg;
                    }
                }

                if ($fieldkey == 'vendor_description') {
                    update_user_meta($user_id, '_' . $fieldkey, $post[$fieldkey]);
                } elseif ($fieldkey == 'vendor_country') {
                    $country_code = $post[$fieldkey];
                    $country_data = WC()->countries->get_countries();
                    $country_name = ( isset($country_data[$country_code]) ) ? $country_data[$country_code] : $country_code; //To get country name by code
                    update_user_meta($user_id, '_' . $fieldkey, $country_name);
                    update_user_meta($user_id, '_' . $fieldkey . '_code', $country_code);
                } elseif ($fieldkey == 'vendor_state') {
                    $country_code = $post['vendor_country'];
                    $state_code = $post[$fieldkey];
                    $state_data = WC()->countries->get_states($country_code);
                    $state_name = ( isset($state_data[$state_code]) ) ? $state_data[$state_code] : $state_code; //to get State name by state code
                    update_user_meta($user_id, '_' . $fieldkey, $state_name);
                    update_user_meta($user_id, '_' . $fieldkey . '_code', $state_code);
                } else {
                    // social url validation
                    if (in_array($fieldkey, array('vendor_fb_profile', 'vendor_twitter_profile', 'vendor_google_plus_profile', 'vendor_linkdin_profile', 'vendor_youtube', 'vendor_instagram'))) {
                        if (!empty($post[$fieldkey]) && filter_var($post[$fieldkey], FILTER_VALIDATE_URL)) {
                            update_user_meta($user_id, '_' . $fieldkey, $post[$fieldkey]);
                        } else {
                            update_user_meta($user_id, '_' . $fieldkey, '');
                        }
                    } else {
                        update_user_meta($user_id, '_' . $fieldkey, $post[$fieldkey]);
                    }
                }
                if ($fieldkey == 'vendor_page_title' && empty($post[$fieldkey])) {
                    if (is_admin()) {
                        echo _e('Shop Title can not be empty', 'dc-woocommerce-multi-vendor');
                    } else {
                        $err_msg = __('Shop Title can not be empty', 'dc-woocommerce-multi-vendor');
                        return $err_msg;
                    }
                }
                if ($fieldkey == 'vendor_page_title') {
                    if (!$vendor->update_page_title(wc_clean($post[$fieldkey]))) {
                        if (is_admin()) {
                            echo _e('Shop Title Update Error', 'dc-woocommerce-multi-vendor');
                        } else {
                            $err_msg = __('Shop Title Update Error', 'dc-woocommerce-multi-vendor');
                            return $err_msg;
                        }
                    } else {
                        if (apply_filters('wcmp_update_user_display_name_with_vendor_store_name', false, $user_id)) {
                            wp_update_user(array('ID' => $user_id, 'display_name' => $post[$fieldkey]));
                        }
                    }
                }
            }
        }
        if (isset($_POST['_shop_template']) && !empty($_POST['_shop_template'])) {
            update_user_meta($user_id, '_shop_template', $_POST['_shop_template']);
        }
        if (isset($_POST['_store_location']) && !empty($_POST['_store_location'])) {
            update_user_meta($user_id, '_store_location', $_POST['_store_location']);
        }
        if (isset($_POST['store_address_components']) && !empty($_POST['store_address_components'])) {
            $address_components = wcmp_get_geocoder_components(json_decode(stripslashes($_POST['store_address_components']), true));
            if (isset($_POST['_store_location']) && !empty($_POST['_store_location'])) {
                $address_components['formatted_address'] = $_POST['_store_location'];
            }
            if (isset($_POST['_store_lat']) && !empty($_POST['_store_lat'])) {
                $address_components['latitude'] = $_POST['_store_lat'];
            }
            if (isset($_POST['_store_lng']) && !empty($_POST['_store_lng'])) {
                $address_components['longitude'] = $_POST['_store_lng'];
            }
            update_user_meta($user_id, '_store_address_components', $address_components);
        }
        if (isset($_POST['_store_lat']) && !empty($_POST['_store_lat'])) {
            update_user_meta($user_id, '_store_lat', $_POST['_store_lat']);
        }
        if (isset($_POST['_store_lng']) && !empty($_POST['_store_lng'])) {
            update_user_meta($user_id, '_store_lng', $_POST['_store_lng']);
        }
        if (isset($_POST['timezone_string']) && !empty($_POST['timezone_string'])) {
            if (!empty($_POST['timezone_string']) && preg_match('/^UTC[+-]/', $_POST['timezone_string'])) {
                $_POST['gmt_offset'] = $_POST['timezone_string'];
                $_POST['gmt_offset'] = preg_replace('/UTC\+?/', '', $_POST['gmt_offset']);
                $_POST['timezone_string'] = '';
            } else {
                $_POST['gmt_offset'] = 0;
            }
            update_user_meta($user_id, 'timezone_string', $_POST['timezone_string']);
            update_user_meta($user_id, 'gmt_offset', $_POST['gmt_offset']);
        }
    }

    /**
     * Save Vendor Shipping data
     * @global type $WCMp
     * @param type $vendor_user_id
     * @param type $post
     */
    public function save_vendor_shipping($vendor_user_id, $post) {
        global $WCMp;
        $all_allowed_countries = WC()->countries->get_allowed_countries();
        $location = array();
        $zone_id = 0;
        if (!empty($_POST['wcmp_shipping_zone'])) {
            foreach ($_POST['wcmp_shipping_zone'] as $shipping_zone) {
                if (isset($shipping_zone['_zone_id']) && $shipping_zone['_zone_id'] != 0) {
                    $zone_id = $shipping_zone['_zone_id'];

                    if (isset($shipping_zone['_limit_zone_location']) && $shipping_zone['_limit_zone_location']) {
                        if (!empty($shipping_zone['_select_zone_states'])) {
                            $state_array = array();
                            foreach ($shipping_zone['_select_zone_states'] as $zone_state) {
                                $state_array[] = array(
                                    'code' => $zone_state,
                                    'type' => 'state'
                                );
                            }

                            $location = array_merge($location, $state_array);
                        }

                        if (!empty($shipping_zone['_select_zone_postcodes'])) {
                            $postcode_array = array();
                            $zone_postcodes = array_map('trim', explode(',', $shipping_zone['_select_zone_postcodes']));
                            foreach ($zone_postcodes as $zone_postcode) {
                                $postcode_array[] = array(
                                    'code' => $zone_postcode,
                                    'type' => 'postcode'
                                );
                            }

                            $location = array_merge($location, $postcode_array);
                        }
                    }
                }
            }
        }
        if( !class_exists( 'WCMP_Shipping_Zone' ) ) {
            $WCMp->load_vendor_shipping();
        }
        WCMP_Shipping_Zone::save_location($location, $zone_id);

        $WCMp->load_class('shipping-gateway');
        WCMp_Shipping_Gateway::load_class('shipping-method');
        $vendor_shipping = new WCMP_Vendor_Shipping_Method();
        $vendor_shipping->process_admin_options();

        // clear shipping transient
        WC_Cache_Helper::get_transient_version('shipping', true);

    }

    /**
     * Save Vendor Profile data
     * @since 3.1.0
     * @global type $WCMp
     * @param type $vendor_user_id
     * @param type $post
     */
    public function save_vendor_profile($vendor_user_id, $post) {
        global $WCMp;
        if (isset($_POST['vendor_profile_data'])) {
            // preventing auth cookies from actually being sent to the client.
            add_filter('send_auth_cookies', '__return_false');

            $current_user = get_user_by('id', $vendor_user_id);
            $has_error = false;
            $userdata = array(
                'ID' => $vendor_user_id,
                //'user_email' => $_POST['vendor_profile_data']['user_email'],
                'first_name' => $_POST['vendor_profile_data']['first_name'],
                'last_name' => $_POST['vendor_profile_data']['last_name'],
            );

            $pass_cur = !empty( $_POST['vendor_profile_data']['password_current'] ) ? $_POST['vendor_profile_data']['password_current'] : '';
            $pass1 = !empty( $_POST['vendor_profile_data']['password_1'] ) ? $_POST['vendor_profile_data']['password_1'] : '';
            $pass2 = !empty( $_POST['vendor_profile_data']['password_2'] ) ? $_POST['vendor_profile_data']['password_2'] : '';
            $email = !empty( $_POST['vendor_profile_data']['user_email'] ) ? $_POST['vendor_profile_data']['user_email'] : '';
            $save_pass = true;
            
            if ( $email ) {
                $account_email = sanitize_email( $email );
                if ( ! is_email( $account_email ) ) {
                    $has_error = true;
                    wc_add_notice( __( 'Please provide a valid email address.', 'woocommerce' ), 'error' );
                } elseif ( email_exists( $account_email ) && $account_email !== $current_user->user_email ) {
                    $has_error = true;
                    wc_add_notice( __( 'This email address is already registered.', 'woocommerce' ), 'error' );
                }
                $userdata['user_email'] = $account_email;
            }

            if (!empty($pass_cur) && empty($pass1) && empty($pass2)) {
                $has_error = true;
                wc_add_notice( __('Please fill out all password fields.', 'dc-woocommerce-multi-vendor'), 'error' );
                $save_pass = false;
            } elseif (!empty($pass1) && empty($pass_cur)) {
                $has_error = true;
                wc_add_notice( __('Please enter your current password.', 'dc-woocommerce-multi-vendor'), 'error' );
                $save_pass = false;
            } elseif (!empty($pass1) && empty($pass2)) {
                $has_error = true;
                wc_add_notice( __('Please re-enter your password.', 'dc-woocommerce-multi-vendor'), 'error' );
                $save_pass = false;
            } elseif ((!empty($pass1) || !empty($pass2) ) && $pass1 !== $pass2) {
                $has_error = true;
                wc_add_notice( __('New passwords do not match.', 'dc-woocommerce-multi-vendor'), 'error' );
                $save_pass = false;
            } elseif (!empty($pass1) && !wp_check_password($pass_cur, $current_user->user_pass, $current_user->ID)) {
                $has_error = true;
                wc_add_notice( __('Your current password is incorrect.', 'dc-woocommerce-multi-vendor'), 'error' );
                $save_pass = false;
            }
            
            if( $has_error ) return;

            if ($pass1 && $save_pass) {
                $userdata['user_pass'] = $pass1;
            }

            $user_id = wp_update_user($userdata);

            $profile_updt = update_user_meta($vendor_user_id, '_vendor_profile_image', $_POST['vendor_profile_data']['vendor_profile_image']);

            if ($profile_updt || $user_id) {
                wc_add_notice( __('Profile Data Updated', 'dc-woocommerce-multi-vendor'), 'success' );
            }
        }
    }

    /**
     * Add vendor dashboard header navigation
     * @since 3.0.0
     */
    public function dashboard_header_nav() {
        $vendor = get_current_vendor();
        $header_nav = array(
            'shop-link' => array(
                'label' => __('My Shop', 'dc-woocommerce-multi-vendor')
                , 'url' => apply_filters('wcmp_vendor_shop_permalink', esc_url($vendor->permalink))
                , 'class' => ''
                , 'capability' => true
                , 'position' => 10
                , 'link_target' => '_blank'
                , 'nav_icon' => 'wcmp-font ico-my-shop-icon'
            ),
            'add-product' => array(
                'label' => __('Add Product', 'dc-woocommerce-multi-vendor')
                , 'url' => apply_filters('wcmp_vendor_submit_product', esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_add_product_endpoint', 'vendor', 'general', 'add-product'))))
                , 'class' => ''
                , 'capability' => apply_filters('wcmp_vendor_dashboard_menu_add_product_capability', 'edit_products')
                , 'position' => 20
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-product-icon'
            ),
            'orders' => array(
                'label' => __('Orders', 'dc-woocommerce-multi-vendor')
                , 'url' => esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders')))
                , 'class' => ''
                , 'capability' => true
                , 'position' => 30
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-orders-icon'
            ),
            'announcement' => array(
                'label' => __('Announcement', 'dc-woocommerce-multi-vendor')
                , 'url' => esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_announcements_endpoint', 'vendor', 'general', 'vendor-announcements')))
                , 'class' => ''
                , 'capability' => apply_filters('wcmp_show_vendor_announcements', true)
                , 'position' => 40
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-announcement-icon'
            )
        );
        return apply_filters('wcmp_vendor_dashboard_header_nav', $header_nav);
    }

    /**
     * Add vendor dashboard header right panel navigation
     * @since 3.0.0
     */
    public function dashboard_header_right_panel_nav() {
        $panel_nav = array(
            'storefront' => array(
                'label' => __('Storefront', 'dc-woocommerce-multi-vendor')
                , 'url' => esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_store_settings_endpoint', 'vendor', 'general', 'storefront')))
                , 'class' => ''
                , 'capability' => true
                , 'position' => 10
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-storefront-icon'
            ),
            'profile' => array(
                'label' => __('Profile management', 'dc-woocommerce-multi-vendor')
                , 'url' => esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_profile_endpoint', 'vendor', 'general', 'profile')))
                , 'class' => ''
                , 'capability' => true
                , 'position' => 20
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-user-icon'
            ),
            'wp-admin' => array(
                'label' => __('WordPress backend', 'dc-woocommerce-multi-vendor')
                , 'url' => esc_url(admin_url())
                , 'class' => ''
                , 'capability' => true
                , 'position' => 30
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-wp-backend-icon'
            ),
            'logout' => array(
                'label' => __('Logout', 'dc-woocommerce-multi-vendor')
                , 'url' => esc_url(wp_logout_url(get_permalink(wcmp_vendor_dashboard_page_id())))
                , 'class' => ''
                , 'capability' => true
                , 'position' => 40
                , 'link_target' => '_self'
                , 'nav_icon' => 'wcmp-font ico-logout-icon'
            )
        );
        return apply_filters('wcmp_vendor_dashboard_header_right_panel_nav', $panel_nav);
    }

    /**
     * Add vendor dashboard widgets
     * @since 3.0.0
     */
    public function wcmp_dashboard_setup() {
        $vendor = get_wcmp_vendor(get_current_user_id());
        $this->wcmp_add_dashboard_widget('wcmp_vendor_stats_reports', '', array(&$this, 'wcmp_vendor_stats_reports'), 'full');
        $trans_details_widget_args = array();
        if (apply_filters('wcmp_vendor_dashboard_menu_vendor_withdrawal_capability', false)) {
            $trans_details_widget_args['action'] = array('title' => __('Withdrawal', 'dc-woocommerce-multi-vendor'), 'link' => esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_withdrawal_endpoint', 'vendor', 'general', 'vendor-withdrawal'))));
        }
        $this->wcmp_add_dashboard_widget('wcmp_vendor_transaction_details', __('Transaction Details', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_vendor_transaction_details'), 'side', array(), $trans_details_widget_args);
        $visitor_map_filter_attr = apply_filters('wcmp_vendor_visitors_map_filter_attr', array(
            '7' => __('Last 7 days', 'dc-woocommerce-multi-vendor'),
            '30' => __('Last 30 days', 'dc-woocommerce-multi-vendor'),
        ));
        $visitor_map_filter = '<div class="widget-action-area pull-right">
            <select id="wcmp_visitor_stats_date_filter" class="form-control">';
        if ($visitor_map_filter_attr) {
            foreach ($visitor_map_filter_attr as $key => $value) {
                $visitor_map_filter .= '<option value="' . $key . '">' . $value . '</option>';
            }
        }
        $visitor_map_filter .= '</select>
        </div>';
        if(!apply_filters('wcmp_is_disable_store_visitors_stats', false))
            $this->wcmp_add_dashboard_widget('wcmp_vendor_visitors_map', __('Visitors Map', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_vendor_visitors_map'), 'normal', '', array('action' => array('html' => $visitor_map_filter)));
        if ($vendor->is_shipping_enable()):
            $this->wcmp_add_dashboard_widget('wcmp_vendor_pending_shipping', __('Pending Shipping', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_vendor_pending_shipping'));
        endif;
        if (current_user_can('edit_products')) {
            $this->wcmp_add_dashboard_widget('wcmp_vendor_product_stats', __('Product Stats', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_vendor_product_stats'), 'side', '', array('action' => array('title' => __('Add Product', 'dc-woocommerce-multi-vendor'), 'link' => apply_filters( 'wcmp_vendor_dashboard_add_product_url', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_add_product_endpoint', 'vendor', 'general', 'add-product' ))))));
            $this->wcmp_add_dashboard_widget('wcmp_vendor_product_sales_report', __('Product Sales Report', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_vendor_product_sales_report'));
        }
        if (get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable') {
            $this->wcmp_add_dashboard_widget('wcmp_customer_reviews', __('Reviews', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_customer_review'));
        }
        $this->wcmp_add_dashboard_widget('wcmp_vendor_products_cust_qna', __('Customer Questions', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_vendor_products_cust_qna'), 'side', '', array('action' => array('title' => __('Show All Q&As', 'dc-woocommerce-multi-vendor'), 'link' => esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_vendor_products_qnas_endpoint', 'vendor', 'general', 'products-qna'))))));
    }

    /**
     * Register new vendor dashboard widget
     * @global array $wcmp_dashboard_widget
     * @param string $widget_id
     * @param string $widget_title
     * @param callable $callback
     * @param string $context
     * @param int $priority
     * @param array $callback_args
     * @since 3.0.0
     */
    public function wcmp_add_dashboard_widget($widget_id, $widget_title, $callback, $context = 'normal', $callback_args = null, $args = array()) {
        global $wcmp_dashboard_widget;
        if (!is_user_wcmp_vendor(get_current_vendor_id())) {
            return;
        }
        if (!isset($wcmp_dashboard_widget)) {
            $wcmp_dashboard_widget = array();
        }
        if (!isset($wcmp_dashboard_widget[$context])) {
            $wcmp_dashboard_widget[$context] = array();
        }
        $wcmp_dashboard_widget[$context][$widget_id] = array(
            'id' => $widget_id,
            'title' => $widget_title,
            'callback' => $callback,
            'calback_args' => $callback_args,
            'args' => $args
        );
    }

    /**
     * Output vendor dashboard widgets
     * @global array $wcmp_dashboard_widget
     * @since 3.0.0
     */
    public function do_wcmp_dashboard_widget($place) {
        global $wcmp_dashboard_widget;
        if (!$wcmp_dashboard_widget) {
            return;
        }
        $wcmp_dashboard_widget = apply_filters('before_wcmp_dashboard_widget', $wcmp_dashboard_widget);
        if ($wcmp_dashboard_widget) {
            foreach ($wcmp_dashboard_widget as $context => $dashboard_widget) {
                if ($place == $context) {
                    foreach ($dashboard_widget as $widget_id => $widget) {
                        echo '<div class="panel panel-default pannel-outer-heading wcmp-dash-widget ' . $widget_id . '">';
                        $this->build_widget_header($widget['title'], $widget['args']);
                        echo '<div class="panel-body">';
                        call_user_func($widget['callback'], $widget['calback_args']);
                        echo '</div>';
                        $this->build_widget_footer($widget['args']);
                        echo '</div>';
                    }
                }
            }
        }
    }

    public function build_widget_header($title, $args = array()) {
        $default = array(
            'icon' => '',
            'action' => array()
        );
        $args = array_merge($default, $args);
        if (!empty($title)) {
            ?>
            <div class="panel-heading">
                <h3 class="pull-left">
            <?php if (!empty($args['icon'])) : ?>
                        <span class="icon_stand dashicons-before <?php echo $args['icon']; ?>"></span>
                    <?php endif; ?>
                    <?php echo $title; ?>
                </h3>
            </div>
            <div class="clearfix"></div>
            <?php
        }
    }

    public function build_widget_footer($args = array()) {
        $default = array(
            'icon' => '',
            'action' => array()
        );
        $args = array_merge($default, $args);
        if (!empty($args['action'])) {
            ?>
            <div class="panel-footer">
            <?php if (isset($args['action']['link']) && isset($args['action']['title'])) { ?>
                    <a href="<?php echo $args['action']['link']; ?>" class="footer-link">
                    <?php
                    if (isset($args['action']['icon'])) {
                        echo '<span class="icon_stand dashicons-before ' . $args['action']['icon'] . '"></span>';
                    }
                    ?>
                        <?php echo $args['action']['title']; ?>
                        <i class="wcmp-font ico-right-arrow-icon"></i>
                    </a>
                        <?php
                    } if (isset($args['action']['html'])) {
                        echo $args['action']['html'];
                    }
                    ?>
            </div>
            <div class="clearfix"></div>
                <?php
            }
        }

        public function wcmp_vendor_stats_reports($args = array()) {
            global $WCMp;
            $vendor = get_current_vendor();
            $vendor_report_data = get_wcmp_vendor_dashboard_stats_reports_data();
            $default_data = array();
            $default_data['stats_reports_periods'] = apply_filters('wcmp_vendor_stats_reports_periods', array(
                '7' => __('Last 7 days', 'dc-woocommerce-multi-vendor'),
                '30' => __('Last 30 days', 'dc-woocommerce-multi-vendor'),
            ));
            $default_data['vendor_report_data'] = $vendor_report_data;
            $default_data['payment_mode'] = ucwords(str_replace('_', ' ', $vendor->payment_mode));
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_stats_reports.php', $default_data);
        }

        public function wcmp_vendor_pending_shipping($args = array()) {
            global $WCMp;
            $vendor = get_wcmp_vendor(get_current_user_id());
            $today = @date('Y-m-d 00:00:00', strtotime("+1 days"));
            $last_seven_day_date = date('Y-m-d H:i:s', strtotime('-7 days'));
            // Mark as shipped
            if (isset($_POST['wcmp-submit-mark-as-ship'])) {
                $order_id = $_POST['order_id'];
                $tracking_id = $_POST['tracking_id'];
                $tracking_url = $_POST['tracking_url'];
                $vendor->set_order_shipped($order_id, $tracking_id, $tracking_url);
            }

            $default_headers = apply_filters('wcmp_vendor_pending_shipping_table_header', array(
                'order_id' => __('Order ID', 'dc-woocommerce-multi-vendor'),
                'products_name' => __('Product', 'dc-woocommerce-multi-vendor'),
                'order_date' => __('Order Date', 'dc-woocommerce-multi-vendor'),
                'shipping_address' => __('Address', 'dc-woocommerce-multi-vendor'),
                'shipping_amount' => __('Charges', 'dc-woocommerce-multi-vendor'),
                'action' => __('Action', 'dc-woocommerce-multi-vendor'),
            ));
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_pending_shipping.php', array('default_headers' => $default_headers));
        }

        public function wcmp_customer_review() {
            global $WCMp, $wpdb;
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_customer_review.php');
        }

        public function wcmp_vendor_product_stats($args = array()) {
            global $WCMp, $wpdb;
            $publish_products_count = $pending_products_count = $draft_products_count = $trashed_products_count = 0;
            $vendor = get_wcmp_vendor(get_current_user_id());
            $args = array('post_status' => array('publish', 'pending', 'draft', 'trash'));
            $product_stats = array();
            if($vendor) :
                $where = "AND ({$wpdb->prefix}posts.post_status = 'publish' OR {$wpdb->prefix}posts.post_status = 'draft' OR {$wpdb->prefix}posts.post_status = 'pending' OR {$wpdb->prefix}posts.post_status = 'trash')";
                $products = $vendor->get_products_ids( array( 'where' => $where ) );
                $product_stats['total_products'] = count($products);
                foreach ( $products as $product) {
                    if ( get_post_status( $product->ID ) == 'publish' )
                        $publish_products_count += 1;
                    if ( get_post_status( $product->ID ) == 'pending' )
                        $pending_products_count += 1;
                    if ( get_post_status( $product->ID ) == 'draft' )
                        $draft_products_count += 1;
                    if ( get_post_status( $product->ID ) == 'trash' )
                        $trashed_products_count += 1;
                }
            endif;
            $product_stats['publish_products_count'] = $publish_products_count;
            $product_stats['pending_products_count'] = $pending_products_count;
            $product_stats['draft_products_count'] = $draft_products_count;
            $product_stats['trashed_products_count'] = $trashed_products_count;
            $product_stats['product_page_url'] = wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_products_endpoint', 'vendor', 'general', 'products'));

            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_product_stats.php', $product_stats);
            
        }

        public function wcmp_vendor_product_sales_report() {
            global $WCMp;
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_product_sales_report.php');
        }

        function wcmp_vendor_transaction_details() {
            global $WCMp;
            $total_amount = 0;
            $transaction_display_array = array();
            $vendor = get_wcmp_vendor(get_current_vendor_id());
            $requestData = $_REQUEST;
            $vendor = apply_filters('wcmp_transaction_vendor', $vendor);
            $start_date = isset($requestData['from_date']) ? $requestData['from_date'] : date('01-m-Y');
            $end_date = isset($requestData['to_date']) ? $requestData['to_date'] : date('t-m-Y');
            $transaction_details = $WCMp->transaction->get_transactions($vendor->term_id);
            $unpaid_commission_total = WCMp_Commission::get_unpaid_commissions_total_data( 'withdrawable' );

            $count = 0; // varible for counting 5 transaction details
            foreach ($transaction_details as $transaction_id => $details) {
                $count++;
                if ($count <= 5) {
                    //$transaction_display_array[$transaction_id] = $details['total_amount'];
                    //$transaction_display_array['id'] = $transaction_id;
                    $transaction_display_array[$transaction_id]['transaction_date'] = wcmp_date($details['post_date']);
                    $transaction_display_array[$transaction_id]['total_amount'] = $details['total_amount'];
                }

                $total_amount = $total_amount + $details['total_amount'];
            }
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_transaction_details.php', apply_filters( 'wcmp_widget_vendor_transaction_details_data', array('total_amount' => $unpaid_commission_total['total'], 'transaction_display_array' => $transaction_display_array), $vendor, $requestData ) );
        }

        public function wcmp_vendor_products_cust_qna() {
            global $WCMp;
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_products_cust_qna.php');
        }

        public function wcmp_vendor_visitors_map() {
            global $WCMp;
            $WCMp->library->load_jqvmap_script_lib();
            $vendor = get_current_vendor();
            $visitor_map_stats = get_wcmp_vendor_dashboard_visitor_stats_data($vendor->id);
            $visitor_map_stats['init'] = array('map' => 'world_en', 'background_color' => false, 'color' => '#a0a0a0', 'hover_color' => false, 'hover_opacity' => 0.7);
            //wp_enqueue_script('wcmp_gchart_loader', '//www.gstatic.com/charts/loader.js');
            wp_enqueue_script('wcmp_visitor_map_data', $WCMp->plugin_url . 'assets/frontend/js/wcmp_vendor_map_widget_data.js', apply_filters('wcmp_vendor_visitors_map_script_dependancies', array('jquery', 'wcmp-vmap-world-script')));
            wp_localize_script('wcmp_visitor_map_data', 'visitor_map_stats', apply_filters('wcmp_vendor_visitors_map_script_data', $visitor_map_stats));
            $WCMp->template->get_template('vendor-dashboard/dashboard-widgets/wcmp_vendor_visitors_map.php');
        }

        public function wcmp_dashboard_setup_updater() {
            global $WCMp;
            $has_updated_store_addresses = get_user_meta(get_current_user_id(), '_vendor_store_country_state_updated', true);
            $has_rejected_store_updater = get_user_meta(get_current_user_id(), '_vendor_rejected_store_country_state_update', true);
            $has_country = get_user_meta(get_current_user_id(), '_vendor_country', true);
            $has_country_code = get_user_meta(get_current_user_id(), '_vendor_country_code', true);
            if ($has_country && !$has_country_code && !$has_updated_store_addresses && !$has_rejected_store_updater && !$WCMp->endpoints->get_current_endpoint()) {
                ?>
            <div class="modal fade" id="vendor-setuo-updater-info-modal" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header">
                                <h4 class="modal-title"><?php _e("Update your store country and state.", 'dc-woocommerce-multi-vendor'); ?></h4>
                            </div>
                            <div class="modal-body">
            <?php wp_nonce_field('wcmp-vendor-store-updater'); ?>
                                <div class="form-group">
                                    <label><?php _e('Store Country', 'dc-woocommerce-multi-vendor'); ?></label>
                                    <select name="vendor_country" id="vendor_country" class="country_to_state user-profile-fields form-control inp-btm-margin regular-select" rel="vendor_country">
                                        <option value=""><?php _e('Select a country&hellip;', 'dc-woocommerce-multi-vendor'); ?></option>
            <?php
            $country_code = get_user_meta(get_current_user_id(), '_vendor_country_code', true);
            foreach (WC()->countries->get_allowed_countries() as $key => $value) {
                echo '<option value="' . esc_attr($key) . '"' . selected(esc_attr($country_code), esc_attr($key), false) . '>' . esc_html($value) . '</option>';
            }
            ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Store state', 'dc-woocommerce-multi-vendor'); ?></label>
            <?php
            $country_code = get_user_meta(get_current_user_id(), '_vendor_country_code', true);
            $states = WC()->countries->get_states($country_code);
            ?>
                                    <select name="vendor_state" id="vendor_state" class="state_select user-profile-fields form-control inp-btm-margin regular-select" rel="vendor_state">
                                        <option value=""><?php esc_html_e('Select a state&hellip;', 'dc-woocommerce-multi-vendor'); ?></option>
                                    <?php
                                    $state_code = get_user_meta(get_current_user_id(), '_vendor_state_code', true);
                                    if ($states):
                                        foreach ($states as $ckey => $cvalue) {
                                            echo '<option value="' . esc_attr($ckey) . '" ' . selected($state_code, $ckey, false) . '>' . esc_html($cvalue) . '</option>';
                                        }
                                    endif;
                                    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="submit" class="update btn btn-default" name="do_update_store_address" value="<?php _e("Update", 'dc-woocommerce-multi-vendor'); ?>"/>
                                <input type="submit" class="skip btn btn-secondary" name="do_reject_store_updater" value="<?php _e("Skip", 'dc-woocommerce-multi-vendor'); ?>"/>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    //this remove the close button on top if you need
                    $('#vendor-setuo-updater-info-modal').find('.close').remove();
                    //this unbind the event click on the shadow zone
                    $('#vendor-setuo-updater-info-modal').unbind('click');
                    $("#vendor-setuo-updater-info-modal").modal('show');
                });
            </script>
            <?php
        }
    }

    public function vendor_updater_handler() {
        $wpnonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
        if ($wpnonce && wp_verify_nonce($wpnonce, 'wcmp-vendor-store-updater')) {
            $do_update = filter_input(INPUT_POST, 'do_update_store_address');
            $do_skip = filter_input(INPUT_POST, 'do_reject_store_updater');
            $country_code = filter_input(INPUT_POST, 'vendor_country');
            $state_code = filter_input(INPUT_POST, 'vendor_state');

            if ($do_update) {
                $country_data = WC()->countries->get_countries();
                $state_data = WC()->countries->get_states($country_code);
                $country_name = ( isset($country_data[$country_code]) ) ? $country_data[$country_code] : $country_code; //To get country name by code
                $state_name = ( isset($state_data[$state_code]) ) ? $state_data[$state_code] : $state_code; //to get State name by state code

                update_user_meta(get_current_user_id(), '_vendor_country', $country_name);
                update_user_meta(get_current_user_id(), '_vendor_country_code', $country_code);
                update_user_meta(get_current_user_id(), '_vendor_state', $state_name);
                update_user_meta(get_current_user_id(), '_vendor_state_code', $state_code);
                update_user_meta(get_current_user_id(), '_vendor_store_country_state_updated', true);
            } elseif ($do_skip) {
                update_user_meta(get_current_user_id(), '_vendor_rejected_store_country_state_update', true);
            }
            wp_redirect(esc_url_raw(get_permalink(wcmp_vendor_dashboard_page_id())));
            die();
        }
    }
    
    /**
     * Save product
     * @ since version 3.2.3
     */
    public function save_product() {
        global $WCMp;
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) { 
            $current_endpoint_key = $WCMp->endpoints->get_current_endpoint();
            // retrive the actual endpoint name in case admn changes that from settings
            $current_endpoint = get_wcmp_vendor_settings( 'wcmp_' . str_replace( '-', '_', $current_endpoint_key ) . '_endpoint', 'vendor', 'general', $current_endpoint_key );
            // retrive edit-product endpoint name in case admn changes that from settings
            $edit_product_endpoint = get_wcmp_vendor_settings( 'wcmp_edit_product_endpoint', 'vendor', 'general', 'edit-product' );
            //Return if not edit product endpoint
            if ( $current_endpoint !== $edit_product_endpoint || ! isset( $_POST['wcmp_product_nonce'] ) ) {
                return;
            }
            
            $vendor_id = get_current_user_id();

            if ( !is_user_wcmp_vendor($vendor_id) || ! current_user_can( 'edit_products' ) || empty( $_POST['post_ID'] ) || ! wp_verify_nonce( $_POST['wcmp_product_nonce'], 'wcmp-product' ) ) {
                wp_die( -1 );
            }
            $errors = array();
            $product_id = intval( $_POST['post_ID'] );
            $post_object = get_post( $product_id );
            $product = wc_get_product( $product_id );

            if ( ! $product->get_id() || ! $post_object || 'product' !== $post_object->post_type ) {
                wp_die( __( 'Invalid product.', 'woocommerce' ) );
            }

            if ( ! $product->get_date_created( 'edit' ) ) {
                $product->set_date_created( current_time( 'timestamp', true ) );
            }

            $title = ( ( is_product_wcmp_spmv($product_id) && isset( $_POST['original_post_title'] ) ) ? wc_clean( $_POST['original_post_title'] ) : isset( $_POST['post_title'] ) ) ? wc_clean( $_POST['post_title'] ) : '';

            if ( isset( $_POST['status'] ) && $_POST['status'] === 'draft' ) {
                $status = 'draft';
            } elseif ( isset( $_POST['status'] ) && $_POST['status'] === 'publish' ) {
                if ( ! current_user_can( 'publish_products' ) ) {
                    $status = 'pending';
                } else {
                    $status = 'publish';
                }
            } else {
                wp_die( __( 'Invalid product status.', 'dc-woocommerce-multi-vendor' ) );
            }

            $post_data = apply_filters( 'wcmp_submitted_product_data', array(
                'ID'            => $product_id,
                'post_title'    => $title,
                'post_content'  => stripslashes( html_entity_decode( $_POST['product_description'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ),
                'post_excerpt'  => stripslashes( html_entity_decode( $_POST['product_excerpt'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ),
                'post_status'   => $status,
                'post_type'     => 'product',
                'post_author'   => $vendor_id,
                'post_date'     => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
                'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
                ), $_POST );

            do_action( 'wcmp_before_post_update' );

            $post_id = wp_update_post( $post_data, true );

            if ( $post_id && ! is_wp_error( $post_id ) ) {

                // Set Product Featured Image
                $featured_img = ! empty( $_POST['featured_img'] ) ? wc_clean( absint( $_POST['featured_img'] ) ) : '';
                if ( $featured_img ) {
                    set_post_thumbnail( $post_id, $featured_img );
                } else {
                    delete_post_thumbnail( $post_id );
                }

                // Set Product Image Gallery
                $attachment_ids = isset( $_POST['product_image_gallery'] ) ? explode( ',', wc_clean( $_POST['product_image_gallery'] ) ) : array();

                $attachment_ids = array_filter( $attachment_ids, function( $attachment_id ) {
                    //image validity check
                    $attachment = wp_get_attachment_image( $attachment_id );
                    return ! empty( $attachment );
                } );

                update_post_meta( $post_id, '_product_image_gallery', implode( ',', $attachment_ids ) );

                // Policy tab data save
                if ( get_wcmp_vendor_settings( 'is_policy_on', 'general' ) == 'Enable' && apply_filters( 'wcmp_vendor_can_overwrite_policies', true ) ) {
                    if ( apply_filters( 'can_vendor_edit_shipping_policy_field', true ) && isset( $_POST['_wcmp_shipping_policy'] ) ) {
                        update_post_meta( $post_id, '_wcmp_shipping_policy', stripslashes( html_entity_decode( $_POST['_wcmp_shipping_policy'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
                    }
                    if ( apply_filters( 'can_vendor_edit_refund_policy_field', true ) && isset( $_POST['_wcmp_refund_policy'] ) ) {
                        update_post_meta( $post_id, '_wcmp_refund_policy', stripslashes( html_entity_decode( $_POST['_wcmp_refund_policy'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
                    }
                    if ( apply_filters( 'can_vendor_edit_cancellation_policy_field', true ) && isset( $_POST['_wcmp_cancallation_policy'] ) ) {
                        update_post_meta( $post_id, '_wcmp_cancallation_policy', stripslashes( html_entity_decode( $_POST['_wcmp_cancallation_policy'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
                    }
                }
                
                // Process product type first so we have the correct class to run setters.
                $product_type = empty( $_POST['product-type'] ) ? WC_Product_Factory::get_product_type( $post_id ) : sanitize_title( stripslashes( $_POST['product-type'] ) );

                wp_set_object_terms( $post_id, $product_type, 'product_type' );

                // Set Product Catagories
                $catagories = isset( $_POST['tax_input']['product_cat'] ) ? array_filter( array_map( 'intval', (array) $_POST['tax_input']['product_cat'] ) ) : array();
                wp_set_object_terms( $post_id, $catagories, 'product_cat' );
                // if product has different multi level categories hierarchy, save the default
                if( isset( $_POST['_default_cat_hierarchy_term_id'] ) && in_array( $_POST['_default_cat_hierarchy_term_id'], $catagories ) && get_wcmp_vendor_settings('is_disable_marketplace_plisting', 'general') != 'Enable' ){
                    update_post_meta( $post_id, '_default_cat_hierarchy_term_id', absint( $_POST['_default_cat_hierarchy_term_id'] ) );
                }else{
                    delete_post_meta( $post_id, '_default_cat_hierarchy_term_id' );
                }
                // Set Product Tags
                $tags = isset( $_POST['tax_input']['product_tag'] ) ? wp_parse_id_list( $_POST['tax_input']['product_tag'] ) : array();
                wp_set_object_terms( $post_id, $tags, 'product_tag' );

                $custom_terms = isset( $_POST['tax_input'] ) ? array_diff_key( $_POST['tax_input'], array_flip( array( 'product_cat', 'product_tag' ) ) ) : array();
                // Set Product Custom Terms
                if ( ! empty( $custom_terms ) ) {
                    foreach ( $custom_terms as $term => $value ) {
                        $custom_term = isset( $_POST['tax_input'][$term] ) ? array_filter( array_map( 'intval', (array) $_POST['tax_input'][$term] ) ) : array();
                        wp_set_object_terms( $post_id, $custom_term, $term );
                    }
                }
                
                // Set Product GTIN
                if( isset( $_POST['_wcmp_gtin_type'] ) && !empty( $_POST['_wcmp_gtin_type'] ) ){
                    $term = get_term( $_POST['_wcmp_gtin_type'], $WCMp->taxonomy->wcmp_gtin_taxonomy );
                    if ($term && !is_wp_error( $term )) {
                        wp_delete_object_term_relationships( $post_id, $WCMp->taxonomy->wcmp_gtin_taxonomy );
                        wp_set_object_terms( $post_id, $term->term_id, $WCMp->taxonomy->wcmp_gtin_taxonomy, true );
                    }
                }
                if ( isset( $_POST['_wcmp_gtin_code'] ) ) {
                    update_post_meta( $post_id, '_wcmp_gtin_code', wc_clean( wp_unslash( $_POST['_wcmp_gtin_code'] ) ) );
                }

                //get the correct class
                $classname = WC_Product_Factory::get_product_classname( $post_id, $product_type ? $product_type : 'simple' );
                $product = new $classname( $post_id );
                $attributes = isset( $_POST['wc_attributes'] ) ? wcmp_woo()->prepare_attributes( $_POST['wc_attributes'] ) : array();
                $stock = null;
                // Handle stock changes.
                if ( isset( $_POST['_stock'] ) ) {
                    if ( isset( $_POST['_original_stock'] ) && wc_stock_amount( $product->get_stock_quantity( 'edit' ) ) !== wc_stock_amount( $_POST['_original_stock'] ) ) {
                        $error_msg = sprintf( __( 'The stock has not been updated because the value has changed since editing. Product %1$d has %2$d units in stock.', 'woocommerce' ), $product->get_id(), $product->get_stock_quantity( 'edit' ) );
                        $errors[] = $error_msg;
                    } else {
                        $stock = wc_stock_amount( $_POST['_stock'] );
                    }
                }
                // Group Products
                $grouped_products = isset( $_POST['grouped_products'] ) ? array_filter( array_map( 'intval', (array) $_POST['grouped_products'] ) ) : array();

                // file paths will be stored in an array keyed off md5(file path)
                $downloads = array();
                if ( isset( $_POST['_downloadable'] ) && isset( $_POST['_wc_file_urls'] ) ) {
                    $file_urls = $_POST['_wc_file_urls'];
                    $file_names = isset( $_POST['_wc_file_names'] ) ? $_POST['_wc_file_names'] : array();
                    $file_hashes = isset( $_POST['_wc_file_hashes'] ) ? $_POST['_wc_file_hashes'] : array();

                    $file_url_size = sizeof( $file_urls );
                    for ( $i = 0; $i < $file_url_size; $i ++ ) {
                        if ( ! empty( $file_urls[$i] ) ) {
                            $downloads[] = array(
                                'name'        => wc_clean( $file_names[$i] ),
                                'file'        => wp_unslash( trim( $file_urls[$i] ) ),
                                'download_id' => wc_clean( $file_hashes[$i] ),
                            );
                        }
                    }
                }

                $error = $product->set_props(
                    array(
                        'virtual'            => isset( $_POST['_virtual'] ),
                        'downloadable'       => isset( $_POST['_downloadable'] ),
                        'featured'           => isset( $_POST['_featured'] ),
                        'catalog_visibility' => wc_clean( wp_unslash( $_POST['_visibility'] ) ),
                        'product_url'        => isset( $_POST['_product_url'] ) ? esc_url_raw( $_POST['_product_url'] ) : null,
                        'button_text'        => isset( $_POST['_button_text'] ) ? wc_clean( $_POST['_button_text'] ) : null,
                        'children'           => 'grouped' === $product_type ? $grouped_products : null,
                        'regular_price'      => isset( $_POST['_regular_price'] ) ? wc_clean( $_POST['_regular_price'] ) : null,
                        'sale_price'         => isset( $_POST['_sale_price'] ) ? wc_clean( $_POST['_sale_price'] ) : null,
                        'date_on_sale_from'  => isset( $_POST['_sale_price_dates_from'] ) ? wc_clean( $_POST['_sale_price_dates_from'] ) : null,
                        'date_on_sale_to'    => isset( $_POST['_sale_price_dates_to'] ) ? wc_clean( $_POST['_sale_price_dates_to'] ) : null,
                        'download_limit'     => empty( $_POST['_download_limit'] ) ? '' : absint( $_POST['_download_limit'] ),
                        'download_expiry'    => empty( $_POST['_download_expiry'] ) ? '' : absint( $_POST['_download_expiry'] ),
                        'downloads'          => $downloads,
                        'tax_status'         => isset( $_POST['_tax_status'] ) ? wc_clean( $_POST['_tax_status'] ) : null,
                        'tax_class'          => isset( $_POST['_tax_class'] ) ? wc_clean( $_POST['_tax_class'] ) : null,
                        'sku'                => isset( $_POST['_sku'] ) ? wc_clean( $_POST['_sku'] ) : null,
                        'manage_stock'       => ! empty( $_POST['_manage_stock'] ),
                        'stock_quantity'     => $stock,
                        'low_stock_amount'   => isset( $_POST['_low_stock_amount'] ) && '' !== $_POST['_low_stock_amount'] ? wc_stock_amount( wp_unslash( $_POST['_low_stock_amount'] ) ) : '',
                        'backorders'         => isset( $_POST['_backorders'] ) ? wc_clean( $_POST['_backorders'] ) : null,
                        'stock_status'       => isset( $_POST['_stock_status'] ) ? wc_clean( $_POST['_stock_status'] ) : null,
                        'sold_individually'  => ! empty( $_POST['_sold_individually'] ),
                        'weight'             => isset( $_POST['_weight'] ) ? wc_clean( $_POST['_weight'] ) : null,
                        'length'             => isset( $_POST['_length'] ) ? wc_clean( $_POST['_length'] ) : null,
                        'width'              => isset( $_POST['_width'] ) ? wc_clean( $_POST['_width'] ) : null,
                        'height'             => isset( $_POST['_height'] ) ? wc_clean( $_POST['_height'] ) : null,
                        'shipping_class_id'  => isset( $_POST['product_shipping_class'] ) ? absint( $_POST['product_shipping_class'] ) : null,
                        'upsell_ids'         => isset( $_POST['upsell_ids'] ) ? array_map( 'intval', (array) $_POST['upsell_ids'] ) : array(),
                        'cross_sell_ids'     => isset( $_POST['crosssell_ids'] ) ? array_map( 'intval', (array) $_POST['crosssell_ids'] ) : array(),
                        'purchase_note'      => isset( $_POST['_purchase_note'] ) ? wp_kses_post( stripslashes( $_POST['_purchase_note'] ) ) : null,
                        'menu_order'         => isset( $_POST['menu_order'] ) ? wc_clean( $_POST['menu_order'] ) : null,
                        'reviews_allowed'    => ! empty( $_POST['comment_status'] ) && 'open' === $_POST['comment_status'],
                        'attributes'         => $attributes,
                        'default_attributes' => wcmp_woo()->prepare_set_attributes( $attributes, 'default_attribute_', $_POST ),
                    )
                );

                if ( is_wp_error( $error ) ) {
                    $errors[] = $error->get_error_message();
                }

                do_action( 'wcmp_process_product_object', $product, $_POST );

                $product->save();

                if ( $product->is_type( 'variable' ) ) {
                    $product->get_data_store()->sync_variation_names( $product, wc_clean( $_POST['original_post_title'] ), wc_clean( $_POST['post_title'] ) );
                    $error = wcmp_woo()->save_product_variations( $post_id, $_POST );
                    $errors = array_merge( $errors, $error );
                }

                // Notify Admin on New Product Creation
                if ( ( ! $_POST['is_update'] || $_POST['original_post_status'] == 'draft' ) && $status != 'draft' ) {
                    $WCMp->product->on_all_status_transitions( $status, '', get_post( $post_id ) );
                }

                do_action( 'wcmp_process_product_meta_' . $product_type, $post_id, $_POST );

                foreach ( $errors as $error ) {
                    wc_add_notice( $error, 'error' );
                }
                $status_msg = '';
                switch ( $status ) {
                    case 'draft': $status_msg = __( 'Product is successfully drafted', 'dc-woocommerce-multi-vendor' );
                        break;
                    case 'pending': $status_msg = __( 'Product is successfully submitted for review', 'dc-woocommerce-multi-vendor' );
                        break;
                    case 'publish': $status_msg = sprintf( __( 'Product updated and live. <a href="%s" target="_blank">View Product</a>', 'dc-woocommerce-multi-vendor' ), esc_attr( get_permalink( $post_id ) ) );
                        break;
                }
                wc_add_notice( $status_msg, 'success' );
                wp_redirect( apply_filters( 'wcmp_vendor_save_product_redirect_url', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_edit_product_endpoint', 'vendor', 'general', 'edit-product' ), $post_id ) ) );
                exit;
            } else {
                $error_msg = ( $post_id->get_error_code() === 'empty_content' ) ? __( 'Content, title, and excerpt are empty.', 'dc-woocommerce-multi-vendor' ) : $post_id->get_error_message();
                wc_add_notice( $error_msg, 'error' );
            }
        }
    }
    
    public function save_coupon() {
        global $WCMp;
        $current_endpoint_key = $WCMp->endpoints->get_current_endpoint();
        // retrive the actual endpoint name in case admn changes that from settings
        $current_endpoint = get_wcmp_vendor_settings( 'wcmp_' . str_replace( '-', '_', $current_endpoint_key ) . '_endpoint', 'vendor', 'general', $current_endpoint_key );
        // retrive add-coupon endpoint name in case admn changes that from settings
        $add_coupon_endpoint = get_wcmp_vendor_settings( 'wcmp_add_coupon_endpoint', 'vendor', 'general', 'add-coupon' );
        //Return if not add coupon endpoint
        if ( $current_endpoint !== $add_coupon_endpoint || ! isset( $_POST['wcmp_afm_coupon_nonce'] ) ) {
            return;
        }

        $vendor_id = get_current_user_id();

        if ( ! $vendor_id || ! current_vendor_can( 'edit_shop_coupon' ) || empty( $_POST['post_ID'] ) || ! wp_verify_nonce( $_POST['wcmp_afm_coupon_nonce'], 'wcmp-afm-coupon' ) ) {
            wp_die( -1 );
        }

        if ( empty( $_POST['post_title'] ) ) {
            if ( empty( $_POST['post_title'] ) ) {
                wc_add_notice( __( "Coupon code can't be empty.", 'dc-woocommerce-multi-vendor' ), 'error' );
            }
            return;
        }

        $post_id = absint( $_POST['post_ID'] );
        $post = get_post( $post_id );
        $coupon = new WC_Coupon( $post_id );
        // Check for dupe coupons.
        $coupon_code = wc_format_coupon_code( $_POST['post_title'] );
        $id_from_code = wc_get_coupon_id_by_code( $coupon_code, $post_id );

        if ( $id_from_code ) {
            if ( is_current_vendor_coupon( $id_from_code ) ) {
                wc_add_notice( __( 'Coupon code already exists - customers will use the latest coupon with this code.', 'woocommerce' ), 'error' );
            } else {
                wc_add_notice( __( 'Coupon code already exists - provide a different coupon code.', 'dc-woocommerce-multi-vendor' ), 'error' );
                return;
            }
        }

        if ( isset( $_POST['status'] ) && $_POST['status'] === 'draft' ) {
            $status = 'draft';
        } elseif ( isset( $_POST['status'] ) && $_POST['status'] === 'publish' ) {
            if ( ! current_vendor_can( 'publish_shop_coupons' ) ) {
                $status = 'pending';
            } else {
                $status = 'publish';
            }
        } else {
            wp_die( __( 'Invalid coupon status.', 'dc-woocommerce-multi-vendor' ) );
        }

        if ( ! $coupon->get_date_created( 'edit' ) ) {
            $coupon->set_date_created( current_time( 'timestamp', true ) );
        }

        $title = ( isset( $_POST['post_title'] ) ) ? wc_clean( $_POST['post_title'] ) : '';

        $post_data = apply_filters( 'afm_submitted_coupon_data', array(
            'ID'            => $post_id,
            'post_title'    => $title,
            'post_excerpt'  => stripslashes( html_entity_decode( $_POST['coupon_description'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ),
            'post_status'   => $status,
            'post_type'     => 'shop_coupon',
            'post_author'   => $vendor_id,
            'post_date'     => gmdate( 'Y-m-d H:i:s', $coupon->get_date_created( 'edit' )->getOffsetTimestamp() ),
            'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $coupon->get_date_created( 'edit' )->getTimestamp() ),
            ), $_POST );

        do_action( 'wcmp_afm_before_coupon_post_update' );

        $post_id = wp_update_post( $post_data, true );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            $product_categories = isset( $_POST['product_categories'] ) ? (array) $_POST['product_categories'] : array();
            $exclude_product_categories = isset( $_POST['exclude_product_categories'] ) ? (array) $_POST['exclude_product_categories'] : array();

            $errors = array();
            $coupon = new WC_Coupon( $post_id );
            $error = $coupon->set_props(
                array(
                    'code'                        => $title,
                    'discount_type'               => wc_clean( $_POST['discount_type'] ),
                    'amount'                      => wc_format_decimal( $_POST['coupon_amount'] ),
                    'date_expires'                => wc_clean( $_POST['expiry_date'] ),
                    'individual_use'              => isset( $_POST['individual_use'] ),
                    'product_ids'                 => isset( $_POST['product_ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['product_ids'] ) ) : array(),
                    'excluded_product_ids'        => isset( $_POST['exclude_product_ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['exclude_product_ids'] ) ) : array(),
                    'usage_limit'                 => absint( $_POST['usage_limit'] ),
                    'usage_limit_per_user'        => absint( $_POST['usage_limit_per_user'] ),
                    'limit_usage_to_x_items'      => absint( $_POST['limit_usage_to_x_items'] ),
                    'free_shipping'               => isset( $_POST['free_shipping'] ),
                    'product_categories'          => array_filter( array_map( 'intval', $product_categories ) ),
                    'excluded_product_categories' => array_filter( array_map( 'intval', $exclude_product_categories ) ),
                    'exclude_sale_items'          => isset( $_POST['exclude_sale_items'] ),
                    'minimum_amount'              => wc_format_decimal( $_POST['minimum_amount'] ),
                    'maximum_amount'              => wc_format_decimal( $_POST['maximum_amount'] ),
                    'email_restrictions'          => array_filter( array_map( 'trim', explode( ',', wc_clean( $_POST['customer_email'] ) ) ) ),
                )
            );
            if ( is_wp_error( $error ) ) {
                $errors[] = $error->get_error_message();
            }
            $coupon->save();
            do_action( 'wcmp_afm_coupon_options_save', $post_id, $coupon );

            foreach ( $errors as $error ) {
                wc_add_notice( $error, 'error' );
            }
            $status_msg = '';
            switch ( $status ) {
                case 'draft': $status_msg = __( 'Coupon is successfully drafted', 'dc-woocommerce-multi-vendor' );
                    break;
                case 'pending': $status_msg = __( 'Coupon is successfully submitted for review', 'dc-woocommerce-multi-vendor' );
                    break;
                case 'publish': $status_msg = __( 'Coupon updated and live.', 'dc-woocommerce-multi-vendor' );
                    break;
            }
            wc_add_notice( $status_msg, 'success' );

            wp_redirect( apply_filters( 'wcmp_vendor_save_coupon_redirect_url', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_add_coupon_endpoint', 'vendor', 'general', 'add-coupon' ), $post_id ) ) );
            exit;
        } else {
            wc_add_notice( $post_id->get_error_message(), 'error' );
        }
    }
    
    public function wcmp_vendor_dashboard_add_product_url( $url ) {
        if( !get_wcmp_vendor_settings('is_singleproductmultiseller', 'general') == 'Enable' && get_wcmp_vendor_settings('is_disable_marketplace_plisting', 'general') == 'Enable' ){
            return esc_url(wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_edit_product_endpoint', 'vendor', 'general', 'edit-product')));
        }
        return $url;
    }
    
    public function vendor_setup_wizard(){
        global $WCMp;
        
        if (filter_input(INPUT_GET, 'page') != 'vendor-store-setup' || !apply_filters('wcmp_vendor_store_setup_wizard_enabled', true)) {
            return;
        }
        if ( !is_user_wcmp_vendor( get_current_user_id() ) ) {
            return;
        }
     
        $this->steps = $this->vendor_setup_wizard_steps();
        $current_step = filter_input(INPUT_GET, 'step');
        $this->step = $current_step ? sanitize_key($current_step) : current(array_keys($this->steps));
        $this->vendor = get_current_vendor();
        
        // skip setup
        if (filter_input(INPUT_GET, 'page') == 'vendor-store-setup' && filter_input(INPUT_GET, 'skip_setup') ) { 
            update_user_meta( $this->vendor->id, '_vendor_skipped_setup_wizard', true );
            wp_redirect( wcmp_get_vendor_dashboard_endpoint_url( 'dashboard' ) );
            exit;
        }
        
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array('jquery'), '2.70', true);
        wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
        wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.0' );
        wp_register_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array('jquery', 'selectWoo'), WC_VERSION);
        wp_localize_script('wc-enhanced-select', 'wc_enhanced_select_params', array(
            'i18n_no_matches' => _x('No matches found', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_ajax_error' => _x('Loading failed', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_input_too_short_1' => _x('Please enter 1 or more characters', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_input_too_short_n' => _x('Please enter %qty% or more characters', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_input_too_long_1' => _x('Please delete 1 character', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_input_too_long_n' => _x('Please delete %qty% characters', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_selection_too_long_1' => _x('You can only select 1 item', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_selection_too_long_n' => _x('You can only select %qty% items', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_load_more' => _x('Loading more results&hellip;', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'i18n_searching' => _x('Searching&hellip;', 'enhanced select', 'dc-woocommerce-multi-vendor'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'search_products_nonce' => wp_create_nonce('search-products'),
            'search_customers_nonce' => wp_create_nonce('search-customers'),
        ));

        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
        wp_enqueue_style('wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array('dashicons', 'install'), WC_VERSION);
        wp_register_script('wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup' . $suffix . '.js', array('jquery', 'wc-enhanced-select', 'jquery-blockui', 'jquery-tiptip'), WC_VERSION);
        wp_register_script('wcmp-setup', $WCMp->plugin_url . '/assets/admin/js/setup-wizard.js', array('wc-setup'), WC_VERSION);
        wp_localize_script('wc-setup', 'wc_setup_params', array(
            'locale_info' => json_encode(include( WC()->plugin_path() . '/i18n/locale-info.php' )),
            'states'                  => WC()->countries->get_states(),
        ));
        
        if (!empty($_POST['save_step']) && isset($this->steps[$this->step]['handler'])) {
            call_user_func($this->steps[$this->step]['handler'], $this);
        }
        
        ob_start();
        $this->setup_wizard_header();
        $this->setup_wizard_steps();
        $this->setup_wizard_content();
        $this->setup_wizard_footer();
        exit();
    }
    
    /**
     * Get the URL for the next step's screen.
     * @param string step   slug (default: current step)
     * @return string       URL for next step if a next step exists.
     *                      Admin URL if it's the last step.
     *                      Empty string on failure.
     * @since 2.7.7
     */
    public function get_next_step_link($step = '') {
        if (!$step) {
            $step = $this->step;
        }

        $keys = array_keys($this->steps);
        if (end($keys) === $step) {
            return admin_url();
        }

        $step_index = array_search($step, $keys);
        if (false === $step_index) {
            return '';
        }

        return add_query_arg('step', $keys[$step_index + 1]);
    }
    
    public function vendor_setup_wizard_steps(){
        $default_steps = array(
            'introduction' => array(
                'name' => __('Introduction', 'dc-woocommerce-multi-vendor'),
                'view' => array($this, 'vendor_setup_introduction'),
                'handler' => '',
            ),
            'store_setup' => array(
                'name' => __('Store setup', 'dc-woocommerce-multi-vendor'),
                'view' => array($this, 'vendor_store_setup'),
                'handler' => array( $this, 'wcmp_setup_store_setup_save' ),
            ),
            'payment'     => array(
                'name'    => __( 'Payment', 'woocommerce' ),
                'view'    => array( $this, 'vendor_payment_setup' ),
                'handler' => array( $this, 'wcmp_setup_payment_save' ),
            ),
            'next_steps'  => array(
                'name'    => __( 'Ready!', 'woocommerce' ),
                'view'    => array( $this, 'wcmp_store_setup_ready' ),
                'handler' => '',
            ),
        );
        return apply_filters('wcmp_vendor_setup_wizard_steps', $default_steps);
    }
    
    /**
     * Setup Wizard Header.
     */
    public function setup_wizard_header() {
        global $WCMp;
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
            <head>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>
                    <?php 
                    printf(
                        __( '%s &rsaquo; Store Setup Wizard', 'dc-woocommerce-multi-vendor' ),
                        wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
                    );
                    ?>
                </title>
                <?php wp_print_scripts('wc-setup'); ?>
                <?php wp_print_scripts('wcmp-setup'); ?>
                <?php do_action('admin_print_styles'); ?>
                <?php do_action('wcmp_vendor_head'); ?>
                <style type="text/css">
                    .wc-setup-steps {
                        justify-content: center;
                    }
                </style>
            </head>
            <body class="wcmp-vendor-wizard wc-setup wp-core-ui">
                <h1 id="wc-logo">
                    <a href="<?php echo apply_filters( 'wcmp_vendor_setup_wizard_site_logo_link', site_url(), get_current_user_id() ); ?>">
                        <?php $site_logo = get_wcmp_vendor_settings('wcmp_dashboard_site_logo', 'vendor', 'dashboard') ? get_wcmp_vendor_settings('wcmp_dashboard_site_logo', 'vendor', 'dashboard') : '';
                        if ($site_logo) { ?>
                        <img src="<?php echo get_url_from_upload_field_value($site_logo); ?>" alt="<?php echo bloginfo(); ?>">
                        <?php } else {
                            echo bloginfo();
                        } ?>
                    </a>
                </h1>
        <?php
    }

    /**
     * Output the steps.
     */
    public function setup_wizard_steps() {
        $ouput_steps = $this->steps;
        array_shift($ouput_steps);
        ?>
        <ol class="wc-setup-steps">
            <?php foreach ($ouput_steps as $step_key => $step) : ?>
                <li class="<?php
                if ($step_key === $this->step) {
                    echo 'active';
                } elseif (array_search($this->step, array_keys($this->steps)) > array_search($step_key, array_keys($this->steps))) {
                    echo 'done';
                }
                ?>"><?php echo esc_html($step['name']); ?></li>
        <?php endforeach; ?>
        </ol>
        <?php
    }

    /**
     * Output the content for the current step.
     */
    public function setup_wizard_content() {
        echo '<div class="wc-setup-content">';
        call_user_func($this->steps[$this->step]['view'], $this);
        echo '</div>';
    }
    
    /**
     * Setup Wizard Footer.
     */
    public function setup_wizard_footer() {
        do_action( 'wcmp_vendor_setup_wizard_footer', $this->step, $this->vendor );
        ?>
        </body>
    </html>
    <?php
    }

    /**
     * Introduction step.
     */
    public function vendor_setup_introduction() {
        $setup_wizard_introduction = get_wcmp_vendor_settings('setup_wizard_introduction');
        if($setup_wizard_introduction){
            echo htmlspecialchars_decode( wpautop( $setup_wizard_introduction ), ENT_QUOTES );
        }else{
        ?>
        <h1><?php 
        printf(
            __( 'Welcome to the %s family!', 'dc-woocommerce-multi-vendor' ),
            wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
        );
        ?></h1>
        <p><?php _e('Thank you for being the part of us. This quick setup wizard will help you configure the basic store settings and you will have your marketplace ready in no time. <strong>It’s completely optional and shouldn’t take longer than five minutes.</strong>', 'dc-woocommerce-multi-vendor'); ?></p>
        <p><?php esc_html_e("If you don't want to go through the wizard right now, you can skip and return to the dashboard. Come back anytime if you change your mind!", 'dc-woocommerce-multi-vendor'); ?></p>
        <?php } ?>
        <p class="wc-setup-actions step">
            <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="button-primary button button-large button-next"><?php esc_html_e("Let's go!", 'dc-woocommerce-multi-vendor'); ?></a>
            <a href="<?php echo wcmp_get_vendor_dashboard_endpoint_url( 'dashboard' ) . '?page=vendor-store-setup&skip_setup=1'; ?>" class="button button-large"><?php esc_html_e('Not right now', 'dc-woocommerce-multi-vendor'); ?></a>
        </p>
        <?php
    }
    
    /**
     * Store setup step.
     */
    public function vendor_store_setup() {
        
        $store_name     = ( $this->vendor->page_title ) ? $this->vendor->page_title : '';
        $address        = ( $this->vendor->address_1 ) ? $this->vendor->address_1 : WC()->countries->get_base_address();
        $address_2      = ( $this->vendor->address_2 ) ? $this->vendor->address_2 : WC()->countries->get_base_address_2();
        $city           = ( $this->vendor->city ) ? $this->vendor->city : WC()->countries->get_base_city();
        $state          = ( $this->vendor->state_code ) ? $this->vendor->state_code : WC()->countries->get_base_state();
        $country        = ( $this->vendor->country_code ) ? $this->vendor->country_code : WC()->countries->get_base_country();
        $postcode       = ( $this->vendor->postcode ) ? $this->vendor->postcode : WC()->countries->get_base_postcode();
        $store_phone    = ( $this->vendor->phone ) ? $this->vendor->phone : '';
        if ( empty( $country ) ) {
            $user_location = WC_Geolocation::geolocate_ip();
            $country       = $user_location['country'];
            $state         = $user_location['state'];
        }
        $locale_info         = include WC()->plugin_path() . '/i18n/locale-info.php';
        $currency_by_country = wp_list_pluck( $locale_info, 'currency_code' );
        $current_offset = get_user_meta($this->vendor->id, 'gmt_offset', true);
        $tzstring = get_user_meta($this->vendor->id, 'timezone_string', true);
        // Remove old Etc mappings. Fallback to gmt_offset.
        if (false !== strpos($tzstring, 'Etc/GMT')) {
            $tzstring = '';
        }

        if (empty($tzstring)) { // Create a UTC+- zone if no timezone string exists
            $check_zone_info = false;
            if (0 == $current_offset) {
                $tzstring = 'UTC+0';
            } elseif ($current_offset < 0) {
                $tzstring = 'UTC' . $current_offset;
            } else {
                $tzstring = 'UTC+' . $current_offset;
            }
        }
        ?>
        <!--h1><?php esc_html_e('Store Setup', 'dc-woocommerce-multi-vendor'); ?></h1-->
        <form method="post" class="store-address-info">
            <?php wp_nonce_field( 'wcmp-vendor-setup' ); ?>
            <p class="store-setup"><?php esc_html_e( 'The following wizard will help you configure your store and get you started quickly.', 'woocommerce' ); ?></p>
            
            <div class="store-address-container">
                
                <label class="location-prompt" for="store_name"><?php esc_html_e('Store Name', 'dc-woocommerce-multi-vendor'); ?></label>
                <input type="text" id="store_name" class="location-input" name="store_name" value="<?php echo esc_attr( $store_name ); ?>"  placeholder="<?php _e('Enter your Store Name here', 'dc-woocommerce-multi-vendor'); ?>" />
                
                <label for="store_country" class="location-prompt"><?php esc_html_e( 'Where is your store based?', 'woocommerce' ); ?></label>
                <select id="store_country" name="store_country" data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'woocommerce' ); ?>" class="location-input wc-enhanced-select dropdown">
                <?php foreach ( WC()->countries->get_countries() as $code => $label ) : ?>
                    <option <?php selected( $code, $country ); ?> value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
                </select>

                <label class="location-prompt" for="store_address_1"><?php esc_html_e( 'Address', 'woocommerce' ); ?></label>
                <input type="text" id="store_address_1" class="location-input" name="store_address_1" value="<?php echo esc_attr( $address ); ?>" />

                <label class="location-prompt" for="store_address_2"><?php esc_html_e( 'Address line 2', 'woocommerce' ); ?></label>
                <input type="text" id="store_address_2" class="location-input" name="store_address_2" value="<?php echo esc_attr( $address_2 ); ?>" />

                <div class="city-and-postcode">
                    <div>
                        <label class="location-prompt" for="store_city"><?php esc_html_e( 'City', 'woocommerce' ); ?></label>
                        <input type="text" id="store_city" class="location-input" name="store_city" value="<?php echo esc_attr( $city ); ?>" />
                    </div>
                    <div class="store-state-container hidden">
                        <label for="store_state" class="location-prompt">
                                <?php esc_html_e( 'State', 'woocommerce' ); ?>
                        </label>
                        <select id="store_state" name="store_state" data-placeholder="<?php esc_attr_e( 'Choose a state&hellip;', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'State', 'woocommerce' ); ?>" class="location-input wc-enhanced-select dropdown"></select>
                    </div>
                    <div>
                        <label class="location-prompt" for="store_postcode"><?php esc_html_e( 'Postcode / ZIP', 'woocommerce' ); ?></label>
                        <input type="text" id="store_postcode" class="location-input" name="store_postcode" value="<?php echo esc_attr( $postcode ); ?>" />
                    </div>
                </div>
                <div class="city-and-postcode">
                    <div>
                        <label class="location-prompt" for="store_phone"><?php esc_html_e( 'Phone', 'dc-woocommerce-multi-vendor' ); ?></label>
                        <input type="text" id="store_phone" class="location-input" name="store_phone" value="<?php echo esc_attr( $store_phone ); ?>" />
                    </div>
                    <div>
                        <label class="location-prompt" for="timezone_string"><?php esc_html_e( 'Timezone', 'dc-woocommerce-multi-vendor' ); ?></label>
                        <select id="timezone_string" name="timezone_string" class="location-input wc-enhanced-select dropdown" aria-describedby="timezone-description">
                            <?php echo wp_timezone_choice($tzstring, get_user_locale()); ?>
                        </select>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                var wc_setup_currencies = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $currency_by_country ) ); ?>' ) );
                var wc_base_state       = "<?php echo esc_js( $state ); ?>";
            </script>
            
            <p class="wc-setup-actions step">
                <input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e('Continue', 'dc-woocommerce-multi-vendor'); ?>" name="save_step" />
                <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="button button-large button-next"><?php esc_html_e('Skip this step', 'dc-woocommerce-multi-vendor'); ?></a>
            </p>
        </form>
        <?php
    }
    
    /**
     * Save initial store settings.
     */
    public function wcmp_setup_store_setup_save(){
        global $WCMp;
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wcmp-vendor-setup' ) ) return;

        $storename      = isset( $_POST['store_name'] ) ? wc_clean( wp_unslash( $_POST['store_name'] ) ) : '';
        $address_1      = isset( $_POST['store_address_1'] ) ? wc_clean( wp_unslash( $_POST['store_address_1'] ) ) : '';
        $address_2      = isset( $_POST['store_address_2'] ) ? wc_clean( wp_unslash( $_POST['store_address_2'] ) ) : '';
        $city           = isset( $_POST['store_city'] ) ? wc_clean( wp_unslash( $_POST['store_city'] ) ) : '';
        $country        = isset( $_POST['store_country'] ) ? wc_clean( wp_unslash( $_POST['store_country'] ) ) : '';
        $state          = isset( $_POST['store_state'] ) ? wc_clean( wp_unslash( $_POST['store_state'] ) ) : '';
        $postcode       = isset( $_POST['store_postcode'] ) ? wc_clean( wp_unslash( $_POST['store_postcode'] ) ) : '';
        $storephone     = isset( $_POST['store_phone'] ) ? wc_clean( wp_unslash( $_POST['store_phone'] ) ) : '';
        $tzstring       = isset( $_POST['timezone_string'] ) ? wc_clean( wp_unslash( $_POST['timezone_string'] ) ) : '';

        if ( $storename ) {
            wp_update_term( $this->vendor->term_id, $WCMp->taxonomy->taxonomy_name, array('name' => $storename) );
            update_user_meta( $this->vendor->id, '_vendor_page_title', $storename );
        }
        if ( $address_1 ) update_user_meta( $this->vendor->id, '_vendor_address_1', $address_1 );
        if ( $address_2 ) update_user_meta( $this->vendor->id, '_vendor_address_2', $address_2 );
        if ( $city ) update_user_meta( $this->vendor->id, '_vendor_city', $city );
        if( $country ) {
            $country_code = $country;
            $country_data = WC()->countries->get_countries();
            $country_name = ( isset($country_data[$country_code]) ) ? $country_data[$country_code] : $country_code; //To get country name by code
            update_user_meta( $this->vendor->id, '_vendor_country', $country_name );
            update_user_meta( $this->vendor->id, '_vendor_country_code', $country_code );
        }
        if ( $state ) {
            $country_code = $country;
            $state_code = $state;
            $state_data = WC()->countries->get_states($country_code);
            $state_name = ( isset($state_data[$state_code]) ) ? $state_data[$state_code] : $state_code; //to get State name by state code
            update_user_meta( $this->vendor->id, '_vendor_state', $state_name );
            update_user_meta( $this->vendor->id, '_vendor_state_code', $state_code );
        }
        if ( $postcode ) update_user_meta( $this->vendor->id, '_vendor_postcode', $postcode );
        if ( $storephone ) update_user_meta( $this->vendor->id, '_vendor_phone', $storephone );
        if ( $tzstring ) {
            if ( !empty( $tzstring ) && preg_match('/^UTC[+-]/', $tzstring ) ) {
                $gmt_offset = $tzstring;
                $gmt_offset = preg_replace( '/UTC\+?/', '', $gmt_offset );
                $tzstring = '';
            } else {
                $gmt_offset = 0;
            }
            update_user_meta( $this->vendor->id, 'timezone_string', $tzstring );
            update_user_meta( $this->vendor->id, 'gmt_offset', $gmt_offset );
        }
        // set flag
        update_user_meta( $this->vendor->id, '_vendor_is_completed_setup_wizard', true );
        wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }
    
    /**
     * Payment setup step.
     */
    public function vendor_payment_setup() { 
        $vendor_payment_mode = ( $this->vendor->payment_mode ) ? $this->vendor->payment_mode : '';
        $available_gateways   = apply_filters( 'wcmp_vendor_setup_wizard_available_payment_gateways', get_wcmp_available_payment_gateways(), $this->vendor );
        ?>
        <h1><?php esc_html_e( 'Payment Method', 'dc-woocommerce-multi-vendor' ); ?></h1>
        <form method="post" class="wc-wizard-payment-gateway-form">
            <?php wp_nonce_field( 'wcmp-vendor-setup' ); ?>
            <p>
                <?php
                printf(
                    __( '%s offers the following payment methods for you.', 'dc-woocommerce-multi-vendor' ),
                    wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
                );
                ?>
            </p>
         
            <div class="product-type-container">
                <label class="location-prompt" for="product_type">
                        <?php esc_html_e( 'Choose Payment Method', 'dc-woocommerce-multi-vendor' ); ?>
                </label>
                <select id="vendor_payment_mode" name="vendor_payment_mode" class="location-input wc-enhanced-select dropdown">
                <?php
                foreach ( $available_gateways as $gateway_id => $gateway ) { ?>
                    <option <?php selected( $gateway_id, $vendor_payment_mode ); ?> value="<?php echo esc_attr( $gateway_id ); ?>"><?php echo esc_html( $gateway ); ?></option>
                <?php }
                ?>
                </select>
            </div>
            <p class="wc-setup-actions step">
                <input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e('Continue', 'dc-woocommerce-multi-vendor'); ?>" name="save_step" />
                <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="button button-large button-next"><?php esc_html_e('Skip this step', 'dc-woocommerce-multi-vendor'); ?></a>
            </p>
        </form>
        <?php
    }
    
    /**
     * Save initial payment settings.
     */
    public function wcmp_setup_payment_save(){
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wcmp-vendor-setup' ) ) return;

        $payment_mode      = isset( $_POST['vendor_payment_mode'] ) ? wc_clean( wp_unslash( $_POST['vendor_payment_mode'] ) ) : '';
        if ( $payment_mode ) update_user_meta( $this->vendor->id, '_vendor_payment_mode', $payment_mode );
        wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }
    
    /**
     * Final setup step.
     */
    public function wcmp_store_setup_ready() { 
        ?>
        <h1><?php esc_html_e( "You're ready to start selling!", 'woocommerce' ); ?></h1>

        <ul class="wc-wizard-next-steps">
            <li class="wc-wizard-next-step-item">
                <div class="wc-wizard-next-step-description">
                    <p class="next-step-heading"><?php esc_html_e( 'Next step', 'woocommerce' ); ?></p>
                    <h3 class="next-step-description"><?php esc_html_e( 'Create some products', 'woocommerce' ); ?></h3>
                    <p class="next-step-extra-info"><?php esc_html_e( "You're ready to add products to your store.", 'woocommerce' ); ?></p>
                </div>
                <div class="wc-wizard-next-step-action">
                    <p class="wc-setup-actions step">
                        <a class="button button-primary button-large" href="<?php echo apply_filters( 'wcmp_vendor_setup_wizard_ready_add_product_url', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_add_product_endpoint', 'vendor', 'general', 'add-product' ) ) ); ?>">
                            <?php esc_html_e( 'Create a product', 'woocommerce' ); ?>
                        </a>
                    </p>
                </div>
            </li>
            <li class="wc-wizard-additional-steps">
                <div class="wc-wizard-next-step-description">
                    <p class="next-step-heading"><?php esc_html_e( 'You can also:', 'woocommerce' ); ?></p>
                </div>
                <div class="wc-wizard-next-step-action">
                    <p class="wc-setup-actions step">
                        <a class="button button-large" href="<?php echo wcmp_get_vendor_dashboard_endpoint_url( 'dashboard' ); ?>">
                            <?php esc_html_e( 'Visit Dashboard', 'woocommerce' ); ?>
                        </a>
                        <a class="button button-large" href="<?php echo wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_billing_endpoint', 'vendor', 'general', 'vendor-billing' ) ); ?>">
                            <?php esc_html_e( 'Payment Configure', 'dc-woocommerce-multi-vendor' ); ?>
                        </a>
                        <a class="button button-large" href="<?php echo wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_store_settings_endpoint', 'vendor', 'general', 'storefront' ) ); ?>">
                            <?php esc_html_e( 'Store Customize', 'dc-woocommerce-multi-vendor' ); ?>
                        </a>
                    </p>
                </div>
            </li>
        </ul>
        <?php
    }

}
