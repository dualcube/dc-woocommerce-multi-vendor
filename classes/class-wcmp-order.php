<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @class 		WCMp Order Class
 *
 * @version		3.4.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Order {

    public function __construct() {
        global $WCMp;
        // Init WCMp Vendor Order class
        $WCMp->load_class('vendor-order');
        // Add extra vendor_id to shipping packages
        add_action('woocommerce_checkout_create_order_line_item', array(&$this, 'add_meta_date_in_order_line_item'), 10, 4);
        add_action('woocommerce_checkout_create_order_shipping_item', array(&$this, 'add_meta_date_in_shipping_package'), 10, 4);
        
        if (is_wcmp_version_less_3_4_0()) {
            
        } else {
            // filters order list table
            add_filter('request', array($this, 'wc_order_list_filter'), 10, 1);
            add_action('admin_head', array($this, 'count_processing_order'), 5);
            add_filter('admin_body_class', array( $this, 'add_admin_body_class' ));
            add_filter('views_edit-shop_order', array($this, 'shop_order_statuses_get_views') );
            add_filter('wp_count_posts', array($this, 'shop_order_count_orders'), 99, 3 );
            // customer's order list (my account)
            add_filter('woocommerce_my_account_my_orders_query', array($this, 'woocommerce_my_account_my_orders_query'), 99);
            add_filter('woocommerce_my_account_my_orders_columns', array($this, 'woocommerce_my_account_my_orders_columns'), 99);
            add_action('woocommerce_my_account_my_orders_column_wcmp_suborder', array($this, 'woocommerce_my_account_my_orders_column_wcmp_suborder'), 99);
            add_action('wcmp_frontend_enqueue_scripts', array($this, 'wcmp_frontend_enqueue_scripts'));
            if( !is_user_wcmp_vendor( get_current_user_id() ) ) {
                add_filter('manage_shop_order_posts_columns', array($this, 'wcmp_shop_order_columns'), 99);
                add_action('manage_shop_order_posts_custom_column', array($this, 'wcmp_show_shop_order_columns'), 99, 2);
            }
            if(apply_filters('wcmp_parent_order_to_vendor_order_status_synchronization', true))
                add_action('woocommerce_order_status_changed', array($this, 'wcmp_parent_order_to_vendor_order_status_synchronization'), 90, 3);
            if(apply_filters('wcmp_vendor_order_to_parent_order_status_synchronization', true))
                add_action('woocommerce_order_status_changed', array($this, 'wcmp_vendor_order_to_parent_order_status_synchronization'), 99, 3);
            // WCMp create orders
            add_action('woocommerce_saved_order_items', array(&$this, 'wcmp_create_orders_from_backend'), 10, 2 );
            add_action('woocommerce_checkout_order_processed', array(&$this, 'wcmp_create_orders'), 10, 3);
            add_action('woocommerce_after_checkout_validation', array($this, 'wcmp_check_order_awaiting_payment'));
            add_action('woocommerce_new_order', array($this, 'wcmp_create_orders_via_rest'), 10, 2);
            // Order Refund
            add_action('woocommerce_order_refunded', array($this, 'wcmp_order_refunded'), 10, 2);
            add_action('woocommerce_refund_deleted', array($this, 'wcmp_refund_deleted'), 10, 2);
            $this->init_prevent_trigger_vendor_order_emails();
            // Order Trash 
            add_action( 'trashed_post', array( $this, 'trash_wcmp_suborder' ), 10, 1 );
            // Order Delete 
            add_action( 'deleted_post', array( $this, 'delete_wcmp_suborder' ), 10, 1 );
            // Restrict default order edit caps for vendor
            add_action( 'admin_enqueue_scripts', array( $this, 'wcmp_vendor_order_backend_restriction' ), 99 );
            add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 99 );
            add_action( 'admin_menu', array( $this, 'remove_admin_menu' ), 99 );
            // restrict stock managements for sub-orders
            add_filter( 'woocommerce_can_reduce_order_stock', array($this, 'woocommerce_can_reduce_order_stock'), 99, 2 );
            add_filter( 'woocommerce_hidden_order_itemmeta', array($this, 'woocommerce_hidden_order_itemmeta'), 99 );
            add_filter( 'woocommerce_order_item_get_formatted_meta_data', array($this, 'woocommerce_hidden_order_item_get_formatted_meta_data'), 99 );
            add_action( 'woocommerce_order_status_changed', array($this, 'wcmp_vendor_order_status_changed_actions'), 99, 3 );
            add_action( 'woocommerce_rest_shop_order_object_query', array($this, 'wcmp_exclude_suborders_from_rest_api_call'), 99, 2 );
        }
    }

    /**
     * Add order line item meta
     *
     * @param item_id, cart_item
     * @return void 
     */

    public function add_meta_date_in_order_line_item($item, $item_key, $values, $order) {
        if ( $order && wp_get_post_parent_id( $order->get_id() ) == 0 ) {
            $general_cap = apply_filters('wcmp_sold_by_text', __('Sold By', 'dc-woocommerce-multi-vendor'));
            $vendor = get_wcmp_product_vendors($item['product_id']);
            if ($vendor) {
                $item->add_meta_data($general_cap, $vendor->page_title);
                $item->add_meta_data('_vendor_id', $vendor->id);
            }
        }
    }

    /**
     * 
     * @param object $item
     * @param sting $package_key as $vendor_id
     */
    public function add_meta_date_in_shipping_package($item, $package_key, $package, $order) {
        $vendor_id = ( isset( $package['vendor_id'] ) && $package['vendor_id'] ) ? $package['vendor_id'] : $package_key;
        if (!wcmp_get_order($order->get_id()) && is_user_wcmp_vendor($vendor_id)) {
            $item->add_meta_data('vendor_id', $vendor_id, true);
            $package_qty = array_sum(wp_list_pluck($package['contents'], 'quantity'));
            $item->add_meta_data('package_qty', $package_qty, true);
            do_action('wcmp_add_shipping_package_meta_data');
        }
    }

    public function wc_order_list_filter($query) {
        global $typenow;
        $user = wp_get_current_user();
        if ('shop_order' == $typenow) {
            if (current_user_can('administrator') && empty($_REQUEST['s'])) {
                $query['post_parent'] = 0;
            }elseif(in_array('dc_vendor', $user->roles)){
                $query['author'] = $user->ID;
            }
            return apply_filters("wcmp_shop_order_query_request", $query);
        }

        return $query;
    }
    
    public function init_prevent_trigger_vendor_order_emails(){
        $prevent_vendor_order_emails = apply_filters('wcmp_prevent_vendor_order_emails_trigger', array(
            'recipient' => array(
                'cancelled_order',
                ),
            'enabled' => array(
                'customer_on_hold_order', 
                'customer_processing_order', 
                'customer_refunded_order', 
                'customer_partially_refunded_order', 
                'customer_completed_order',
                ),
            'disabled' => array(
                'new_order',
                'customer_on_hold_order',
                'customer_processing_order',
            )
        ));
        if($prevent_vendor_order_emails) :
            foreach ($prevent_vendor_order_emails as $prevent => $email_ids) {
                switch ($prevent) {
                    case 'recipient':
                        if($email_ids){
                            foreach ($email_ids as $email_id) {
                                add_filter( 'woocommerce_email_recipient_'.$email_id, array($this, 'woocommerce_email_recipient'), 99, 2 );
                            }
                        }
                        break;
                    case 'enabled':
                        if($email_ids){
                            foreach ($email_ids as $email_id) {
                                add_filter( 'woocommerce_email_enabled_'.$email_id, array($this, 'woocommerce_email_enabled'), 99, 2 );
                            }
                        }
                        break;
                    case 'disabled':
                        if($email_ids){
                            foreach ($email_ids as $email_id) {
                                add_filter( 'woocommerce_email_enabled_'.$email_id, array($this, 'woocommerce_email_disabled'), 99, 2 );
                            }
                        }
                        break;
                    default:
                        do_action('wcmp_prevent_vendor_order_emails_trigger_action', $email_ids, $prevent);
                        break;
                }
            }
        endif;
    }
    
    public function woocommerce_email_recipient($recipient, $object ){
        if(!$object) return $recipient;
        $is_migrated_order = get_post_meta($object->get_id(), '_order_migration', true);
        if($is_migrated_order) return false;
        return $object instanceof WC_Order && wp_get_post_parent_id( $object->get_id() ) ? false : $recipient;
    }
    
    public function woocommerce_email_disabled($enabled, $object ){
        if(!$object) return $enabled;
        $is_vendor_order = ($object) ? wcmp_get_order($object->get_id()) : false;
        $is_migrated_order = get_post_meta($object->get_id(), '_order_migration', true);
        if($is_migrated_order) return false;
        return $object instanceof WC_Order && wp_get_post_parent_id( $object->get_id() ) && $is_vendor_order ? false : $enabled;
    }
    
    public function woocommerce_email_enabled($enabled, $object ){
//        $is_editpost_action = ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array('editpost','edit') );
//
//        if ( $is_editpost_action && ! empty( $_REQUEST['post_ID'] ) && wp_get_post_parent_id( $_REQUEST['post_ID'] ) == 0 && $object instanceof WC_Order && $_REQUEST['post_ID'] != $object->get_id() ) {
//            return false;
//        }
        if(!$object) return $enabled;
        $is_vendor_order = ($object) ? wcmp_get_order($object->get_id()) : false;
        $is_migrated_order = get_post_meta($object->get_id(), '_order_migration', true);
        if($is_migrated_order) return false;
        
        if ( $object instanceof WC_Order && wp_get_post_parent_id( $object->get_id() ) && $is_vendor_order ) return $enabled;

        return $enabled;
    }

    public function wcmp_shop_order_columns($columns) {

        $order_title_number = version_compare(WC_VERSION, '3.3.0', '>=') ? 'order_number' : 'order_title';
        if ((!isset($_GET['post_status']) || ( isset($_GET['post_status']) && 'trash' != $_GET['post_status'] ))) {
            $suborder = array('wcmp_suborder' => __('Suborders', 'dc-woocommerce-multi-vendor'));
            $title_number_pos = array_search($order_title_number, array_keys($columns));
            $columns = array_slice($columns, 0, $title_number_pos + 1, true) + $suborder + array_slice($columns, $title_number_pos + 1, count($columns) - 1, true);
        }
        return $columns;
    }

    /**
     * Output custom columns for orders
     *
     * @param  string $column
     */
    public function wcmp_show_shop_order_columns($column, $post_id) {
        switch ($column) {
            case 'wcmp_suborder' :
                $wcmp_suborders = get_wcmp_suborders($post_id);

                if ($wcmp_suborders) {
                    echo '<ul class="wcmp-order-vendor" style="margin:0px;">';
                    foreach ($wcmp_suborders as $suborder) {
                        $vendor = get_wcmp_vendor(get_post_field('post_author', $suborder->get_id()));
                        $order_uri = apply_filters('wcmp_admin_vendor_shop_order_edit_url', esc_url('post.php?post=' . $suborder->get_id() . '&action=edit'), $suborder->get_id());

                        printf('<li><mark class="%s tips" data-tip="%s">%s</mark> <strong><a href="%s">#%s</a></strong> &ndash; <small class="wcmp-order-for-vendor">%s %s</small></li>', sanitize_title($suborder->get_status()), $suborder->get_status(), $suborder->get_status(), $order_uri, $suborder->get_order_number(), _x('for', 'Order table details', 'dc-woocommerce-multi-vendor'), $vendor->page_title
                        );

                        do_action('wcmp_after_suborder_details', $suborder);
                    }
                    echo '<ul>';
                } else {
                    echo '<span class="na">&ndash;</span>';
                }
                break;
        }
    }

    public function wcmp_create_orders($order_id, $posted_data, $order, $backend = false) {
        global $WCMp;
        //check parent order exist
        if (wp_get_post_parent_id($order_id) != 0)
            return false;

        $order = wc_get_order($order_id);
        $items = $order->get_items();
        $vendor_items = array();

        foreach ($items as $item_id => $item) {
            if (isset($item['product_id']) && $item['product_id'] !== 0) {
                // check vendor product
                $has_vendor = get_wcmp_product_vendors($item['product_id']);
                if ($has_vendor) {
                    $variation_id = isset($item['variation_id']) && !empty($item['variation_id']) ? $item['variation_id'] : 0;
                    $variation = isset($item['variation']) && !empty($item['variation']) ? $item['variation'] : array();
                    $item_commission = $WCMp->commission->get_item_commission($item['product_id'], $variation_id, $item, $order_id, $item_id);
                    $commission_values = $WCMp->commission->get_commission_amount($item['product_id'], $has_vendor->term_id, $variation_id, $item_id, $order);
                    $commission_rate = array('mode' => $WCMp->vendor_caps->payment_cap['revenue_sharing_mode'], 'type' => $WCMp->vendor_caps->payment_cap['commission_type']);
                    $commission_rate['commission_val'] = isset($commission_values['commission_val']) ? $commission_values['commission_val'] : 0;
                    $commission_rate['commission_fixed'] = isset($commission_values['commission_fixed']) ? $commission_values['commission_fixed'] : 0;
                    $item['commission'] = $item_commission;
                    $item['commission_rate'] = $commission_rate;
                    $vendor_items[$has_vendor->id][$item_id] = $item;
                }
            }
        }
        // if there is no vendor available
        if (count($vendor_items) == 0)
            return false;
        // update parent order meta
        update_post_meta($order_id, 'has_wcmp_sub_order', true);
        $vendor_orders = array();
        foreach ($vendor_items as $vendor_id => $items) {
            if (!empty($items)) {
                $vendor_orders[] = self::create_vendor_order(array(
                            'order_id' => $order_id,
                            'vendor_id' => $vendor_id,
                            'posted_data' => $posted_data,
                            'line_items' => $items
                ), $backend);
            }
        }
        if ($vendor_orders) :
            foreach ($vendor_orders as $vendor_order_id) {
                do_action('wcmp_checkout_vendor_order_processed', $vendor_order_id, $posted_data, $order);
            }
        endif;
    }
    
    public function wcmp_create_orders_from_backend( $order_id, $items ){
        $order = wc_get_order($order_id);
        if(!$order) return;
        $has_sub_order = get_post_meta($order_id, 'has_wcmp_sub_order', true) ? true : false;
        if($has_sub_order) return;
        $this->wcmp_create_orders($order_id, array(), $order, true);
    }
    
    public function wcmp_create_orders_via_rest( $order_id, $order ) {
        if( $order && get_post_meta( $order_id, '_created_via', true ) !== 'rest-api' ) return;
        $this->wcmp_create_orders($order_id, array(), $order, true);
    }

    /**
     * Create a new vendor order programmatically
     *
     * Returns a new vendor_order object on success which can then be used to add additional data.
     *
     * @since 
     * @param array $args
     * @param boolean $data_migration (default: false) for data migration
     * @return WCMp_Order|WP_Error
     */
    public static function create_vendor_order($args = array(), $data_migration = false) {
        global $WCMp;
        $default_args = array(
            'vendor_id' => 0,
            'order_id' => 0,
            'posted_data' => array(),
            'vendor_order_id' => 0,
            'line_items' => array()
        );

        $args = wp_parse_args($args, $default_args);
        $order = wc_get_order($args['order_id']);
        if (!$order) return false;
        $data = array();

        if ($args['vendor_order_id'] > 0) {
            $updating = true;
            $data['ID'] = $args['vendor_order_id'];
        } else {
            $updating = false;
            $data = apply_filters('wcmp_create_vendor_order_new_order_data', array(
                'post_date' => gmdate('Y-m-d H:i:s', $order->get_date_created('edit')->getOffsetTimestamp()),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $order->get_date_created('edit')->getTimestamp()),
                'post_type' => 'shop_order',
                'post_status' => 'wc-' . ( $order->get_status('edit') ? $order->get_status('edit') : apply_filters('wcmp_create_vendor_order_default_order_status', 'pending') ),
                'ping_status' => 'closed',
                'post_author' => absint($args['vendor_id']),
                'post_title' => sprintf(__('Vendor Order &ndash; %s', 'dc-woocommerce-multi-vendor'), strftime(_x('%B %e, %Y @ %I:%M %p', 'Commission date parsed by strftime', 'dc-woocommerce-multi-vendor'), current_time('timestamp'))),
                'post_password' => uniqid('wcmp_order_'),
                'post_parent' => absint($args['order_id']),
                'post_excerpt' => isset($args['posted_data']['order_comments']) ? $args['posted_data']['order_comments'] : '',
                    )
            );
        }

        if ($updating) {
            $vendor_order_id = wp_update_post($data);
        } else {
            $vendor_order_id = wp_insert_post($data, true);
            $args['vendor_order_id'] = $vendor_order_id;
        }

        if (is_wp_error($vendor_order_id)) {
            return $vendor_order_id;
        }

        $vendor_order = wc_get_order($vendor_order_id);

        $checkout_fields = array();
        if( !$data_migration ){
            $wc_checkout = WC()->checkout();
            $checkout_fields = !is_admin() && !is_ajax() ? $wc_checkout->checkout_fields : array();
        }
        
        self::create_wcmp_order_line_items($vendor_order, $args);
        if( $data_migration ){
            self::create_wcmp_order_shipping_lines($vendor_order, array(), array(), $args, $data_migration);
            self::create_wcmp_order_coupon_lines( $vendor_order, array(), $args );
        }else{
            self::create_wcmp_order_shipping_lines($vendor_order, WC()->session->get('chosen_shipping_methods'), WC()->shipping->get_packages(), $args, $data_migration);
            self::create_wcmp_order_coupon_lines( $vendor_order, WC()->cart, $args );
        }
        
        //self::create_wcmp_order_tax_lines( $vendor_order, $args );
        // Add customer checkout fields data to vendor order
        if (empty($checkout_fields)) {
            $types = array('billing', 'shipping');
            foreach ($types as $type) {
                $vendor_order->set_address($order->get_address($type), $type);
            }
        }

        if (!empty($wc_checkout)) {
            foreach ($checkout_fields as $section => $checkout_meta_keys) {
                if ('account' != $section) {
                    foreach ($checkout_meta_keys as $order_meta_key => $order_meta_values) {
                        $meta_key = 'shipping' == $section || 'billing' == $section ? '_' . $order_meta_key : $order_meta_key;
                        $meta_value_to_save = isset($args['posted_data'][$order_meta_key]) ? $args['posted_data'][$order_meta_key] : get_post_meta($order->get_id(), $meta_key, true);
                        update_post_meta($vendor_order_id, $meta_key, $meta_value_to_save);
                    }
                }
            }
        }
        // Add vendor order meta data
        $order_meta = apply_filters('wcmp_vendor_order_meta_data', array(
            '_payment_method',
            '_payment_method_title',
            '_customer_user',
            '_prices_include_tax',
            '_order_currency',
            '_order_key',
            '_customer_ip_address',
            '_customer_user_agent',
        ));

        foreach ($order_meta as $key) {
            update_post_meta($vendor_order_id, $key, get_post_meta($order->get_id(), $key, true));
        }

        update_post_meta($vendor_order_id, '_wcmp_order_version', $WCMp->version);
        update_post_meta($vendor_order_id, '_vendor_id', absint($args['vendor_id']));
        update_post_meta($vendor_order_id, '_created_via', 'wcmp_vendor_order');
        
        if($data_migration)
            update_post_meta($vendor_order_id, '_order_migration', true);

        /**
         * Action hook to adjust order before save.
         *
         * @since 3.4.0
         */
        do_action('wcmp_checkout_create_order', $order, $vendor_order, $args);

        // Save the order.
        $v_order_id = $vendor_order->save();
        $vendor_order = wc_get_order($v_order_id);
        do_action('wcmp_checkout_update_order_meta', $v_order_id, $args);
        $vendor_order->calculate_totals();
        return $v_order_id;
    }

    /**
     * Add line items to the order.
     *
     * @param WC_Order $order Order instance.
     * @param WC_Cart  $cart  Cart instance.
     */
    public static function create_wcmp_order_line_items($order, $args) {
        $line_items = $args['line_items'];
        $commission_rate_items = array();
        foreach ($line_items as $item_id => $order_item) {
            if (isset($order_item['product_id']) && $order_item['product_id'] !== 0) {
                $item = new WC_Order_Item_Product();
                $product = wc_get_product($order_item['product_id']);

                $item->set_props(
                        array(
                            'quantity' => $order_item['quantity'],
                            'variation' => $order_item['variation'],
                            'subtotal' => $order_item['line_subtotal'],
                            'total' => $order_item['line_total'],
                            'subtotal_tax' => $order_item['line_subtotal_tax'],
                            'total_tax' => $order_item['line_tax'],
                            'taxes' => $order_item['line_tax_data'],
                        )
                );

                if ($product) {
                    $item->set_props(
                            array(
                                'name' => $order_item->get_name(),
                                'tax_class' => $order_item->get_tax_class(),
                                'product_id' => $order_item->get_product_id(),
                                'variation_id' => $order_item->get_variation_id(),
                            )
                    );
                }

                $item->set_backorder_meta();
                $item->add_meta_data('_vendor_order_item_id', $item_id);
                // Add commission data
                $item->add_meta_data('_vendor_item_commission', $order_item['commission']);
                
                $metadata = $order_item->get_meta_data();
                if ( $metadata ) {
                    foreach ( $metadata as $meta ) {
                        $item->add_meta_data( $meta->key, $meta->value );
                    }
                }

//                $item->add_meta_data('_vendor_id', $args['vendor_id']);
//                // BW compatibility with old meta.
//                $vendor = get_wcmp_vendor($args['vendor_id']);
//                $general_cap = apply_filters('wcmp_sold_by_text', __('Sold By', 'dc-woocommerce-multi-vendor'));
//                $item->add_meta_data($general_cap, $vendor->page_title);


                do_action('wcmp_vendor_create_order_line_item', $item, $item_id, $order_item, $order);
                // Add item to order and save.
                $order->add_item($item);
                // temporary commission rate save with order_item_id
                if(isset($order_item['commission_rate']) && $order_item['commission_rate'])
                    $commission_rate_items[$item_id] = $order_item['commission_rate'];
            }
        }
        /**
         * Temporary commission rates save for vendor order.
         *
         * @since 3.1.2.0
         */
        update_post_meta(absint($args['vendor_order_id']), 'order_items_commission_rates', $commission_rate_items);
        
    }

    /**
     * Add shipping lines to the order.
     *
     * @param WC_Order $order                   Order Instance.
     * @param array    $chosen_shipping_methods Chosen shipping methods.
     * @param array    $packages                Packages.
     */
    public static function create_wcmp_order_shipping_lines($order, $chosen_shipping_methods, $packages, $args = array(), $migration = false) {
        $vendor_id = isset($args['vendor_id']) ? $args['vendor_id'] : 0;
        $parent_order_id = isset($args['order_id']) ? $args['order_id'] : 0;

        if(!$migration){
        
            foreach ($packages as $package_key => $package) {
                $pkg_vendor_id = ( isset( $package['vendor_id'] ) && $package['vendor_id'] ) ? $package['vendor_id'] : $package_key;
                if ($pkg_vendor_id == $vendor_id && isset($chosen_shipping_methods[$package_key], $package['rates'][$chosen_shipping_methods[$package_key]])) {
                    $shipping_rate = $package['rates'][$chosen_shipping_methods[$package_key]];
                    $item = new WC_Order_Item_Shipping();
                    $item->legacy_package_key = $pkg_vendor_id; // @deprecated For legacy actions.
                    $item->set_props(
                            array(
                                'method_title' => $shipping_rate->label,
                                'method_id' => $shipping_rate->method_id,
                                'instance_id' => $shipping_rate->instance_id,
                                'total' => wc_format_decimal($shipping_rate->cost),
                                'taxes' => array(
                                    'total' => $shipping_rate->taxes,
                                ),
                            )
                    );

                    foreach ($shipping_rate->get_meta_data() as $key => $value) {
                        $item->add_meta_data($key, $value, true);
                    }

                    $item->add_meta_data('vendor_id', $pkg_vendor_id, true);
                    $package_qty = array_sum(wp_list_pluck($package['contents'], 'quantity'));
                    $item->add_meta_data('package_qty', $package_qty, true);
                    // add parent item_id in meta
                    $parent_shipping_item_id = get_vendor_parent_shipping_item_id( $parent_order_id, $vendor_id );
                    if( $parent_shipping_item_id ) $item->add_meta_data('_vendor_order_shipping_item_id', $parent_shipping_item_id );
                    
                    /**
                     * Action hook to adjust item before save.
                     *
                     * @since 3.4.0
                     */
                    do_action('wcmp_vendor_create_order_shipping_item', $item, $package_key, $package, $order);

                    // Add item to order and save.
                    $order->add_item($item);
                }
            }
        }else{
            // Backward compatibilities for WCMp old orders
            $parent_order = wc_get_order($parent_order_id);
            if($parent_order){
                $shipping_items = $parent_order->get_items('shipping');
                
                foreach ($shipping_items as $item_id => $item) {
                    $shipping_vendor_id = $item->get_meta('vendor_id', true);
                    if($shipping_vendor_id == $vendor_id){
                        $shipping = new WC_Order_Item_Shipping();
                        $shipping->set_props(
                                array(
                                    'method_title' => $item['method_title'],
                                    'method_id' => $item['method_id'],
                                    'instance_id' => $item['instance_id'],
                                    'total' => wc_format_decimal($item['total']),
                                    'taxes' => $item['taxes'],
                                )
                        );

                        foreach ($item->get_meta_data() as $key => $value) {
                            $shipping->add_meta_data($key, $value, true);
                        }

                        $shipping->add_meta_data('vendor_id', $vendor_id, true);
                        $package_qty = $item->get_meta('package_qty', true);
                        $shipping->add_meta_data('package_qty', $package_qty, true);
                        // add parent item_id in meta
                        $item->add_meta_data('_vendor_order_shipping_item_id', $item_id );
                        $order->add_item($shipping);
                    }
                }
            }
        }
    }
    
    /**
     * Add coupon lines to the order.
     *
     * @param WC_Order $order Order Instance.
     * @param WC_Cart  $cart  Cart instance.
     * @param WCMp Order $args  Arguments.
     */
    public static function create_wcmp_order_coupon_lines( $order, $cart, $args ) {
        if( $cart && $cart->get_coupons() ) :
            foreach ( $cart->get_coupons() as $code => $coupon ) {
                if( !in_array( $coupon->get_discount_type(), apply_filters( 'wcmp_order_available_coupon_types', array( 'fixed_product', 'percent' ), $order, $cart ) ) ) continue;
                if( absint( $args['vendor_id'] ) !== absint( get_post_field( 'post_author', $coupon->get_id() ) ) ) continue;
                $item = new WC_Order_Item_Coupon();
                $item->set_props(
                    array(
                        'code'         => $code,
                        'discount'     => $cart->get_coupon_discount_amount( $code ),
                        'discount_tax' => $cart->get_coupon_discount_tax_amount( $code ),
                    )
                );
                // Avoid storing used_by - it's not needed and can get large.
                $coupon_data = $coupon->get_data();
                unset( $coupon_data['used_by'] );
                $item->add_meta_data( 'coupon_data', $coupon_data );
                /**
                 * Action hook to adjust item before save.
                 *
                 * @since 3.4.3
                 */
                do_action( 'wcmp_checkout_create_order_coupon_item', $item, $code, $coupon, $order, $args );
                // Add item to order and save.
                $order->add_item( $item );
            }
        endif;
    }

    /**
     * Add tax lines to the order.
     *
     * @param WC_Order $order Order instance.
     * @param WC_Cart  $cart  Cart instance.
     */
    public static function create_wcmp_order_tax_lines($order, $vendor_order_data) {
        $line_items = $vendor_order_data['line_items'];
        $item_total_tax = 0;
        foreach ($line_items as $item_id => $order_item) {
            $item_total_tax += (float) $order_item['line_total'];
        }


        foreach (array_keys($cart->get_cart_contents_taxes() + $cart->get_shipping_taxes() + $cart->get_fee_taxes()) as $tax_rate_id) {
            if ($tax_rate_id && apply_filters('woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated') !== $tax_rate_id) {
                $item = new WC_Order_Item_Tax();
                $item->set_props(
                        array(
                            'rate_id' => $tax_rate_id,
                            'tax_total' => $cart->get_tax_amount($tax_rate_id),
                            'shipping_tax_total' => $cart->get_shipping_tax_amount($tax_rate_id),
                            'rate_code' => WC_Tax::get_rate_code($tax_rate_id),
                            'label' => WC_Tax::get_rate_label($tax_rate_id),
                            'compound' => WC_Tax::is_compound($tax_rate_id),
                        )
                );

                /**
                 * Action hook to adjust item before save.
                 *
                 * @since 3.0.0
                 */
                do_action('woocommerce_checkout_create_order_tax_item', $item, $tax_rate_id, $order);

                // Add item to order and save.
                $order->add_item($item);
            }
        }
    }

    public function wcmp_parent_order_to_vendor_order_status_synchronization($order_id, $old_status, $new_status) {
        if(!$order_id) return;
        // Check order have status
        if (empty($new_status)) {
            $order = wc_get_order($order_id);
            $new_status = $order->get_status('edit');
        }
        
        $status_to_sync = apply_filters('wcmp_parent_order_to_vendor_order_statuses_to_sync',array('on-hold', 'pending', 'processing', 'cancelled', 'failed'));
        if( in_array($new_status, $status_to_sync) ) :
            if (wp_get_post_parent_id( $order_id ) || get_post_meta($order_id, 'wcmp_vendor_order_status_synchronized', true))
                return false;
            
            remove_action( 'woocommerce_order_status_completed', 'wc_paying_customer' );
            // Check if order have sub-order
            $wcmp_suborders = get_wcmp_suborders($order_id);

            if ($wcmp_suborders) {
                foreach ($wcmp_suborders as $suborder) {
                    $suborder->update_status($new_status, _x('Update via parent order: ', 'Order note', 'dc-woocommerce-multi-vendor'));
                }
                update_post_meta($order_id, 'wcmp_vendor_order_status_synchronized', true);
                
                add_action( 'woocommerce_order_status_completed', 'wc_paying_customer' );
            }
        endif;
    }
    
    public function wcmp_vendor_order_to_parent_order_status_synchronization($order_id, $old_status, $new_status){
        // parent order synchronization
        $parent_order_id = wp_get_post_parent_id( $order_id );
        if($parent_order_id){
            remove_action('woocommerce_order_status_changed', array($this, 'wcmp_parent_order_to_vendor_order_status_synchronization'), 90, 3);
            $status_to_sync = apply_filters('wcmp_vendor_order_to_parent_order_statuses_to_sync',array('completed', 'refunded'));

            $wcmp_suborders = get_wcmp_suborders( $parent_order_id );
            $new_status_count  = 0;
            $suborder_count    = count( $wcmp_suborders );
            $suborder_statuses = array();
            $suborder_totals = 0;
            foreach ( $wcmp_suborders as $suborder ) {
                $suborder_totals += $suborder->get_total();
                $suborder_status = $suborder->get_status( 'edit' );
                if ( $new_status == $suborder_status ) {
                    $new_status_count ++;
                }

                if ( ! isset( $suborder_statuses[ $suborder_status ] ) ) {
                    $suborder_statuses[ $suborder_status ] = 1;
                } else {
                    $suborder_statuses[ $suborder_status ] ++;
                }
            }

            $parent_order = wc_get_order( $parent_order_id );
            if($parent_order->get_total() == $suborder_totals){
                if ( $suborder_count == $new_status_count && in_array( $new_status, $status_to_sync ) ) {
                    $parent_order->update_status( $new_status, _x( "Sync from vendor's suborders: ", 'Order note', 'dc-woocommerce-multi-vendor' ) );
                } elseif ( $suborder_count != 0 ) {
                    /**
                     * If the parent order have only 1 suborder I can sync it with the same status.
                     * Otherwise I set the parent order to processing
                     */
                    $status = array_unique(array_keys($suborder_statuses));
                    if ( $suborder_count == 1 ) {
                        $new_status = isset($status[0]) ? $status[0] : $new_status;
                        $parent_order->update_status( $new_status, _x( "Sync from vendor's suborders: ", 'Order note', 'dc-woocommerce-multi-vendor' ) );
                    } /**
                     * Check only for suborder > 1 to exclude orders without suborder
                     */
                    elseif ( $suborder_count > 1 ) {
                        $check = 0;
//                        foreach ( $status_to_sync as $status ) {
//                            if ( ! empty( $suborder_statuses[ $status ] ) ) {
//                                $check += $suborder_statuses[ $status ];
//                            }
//                        }
                        if( count($status) == 1 && isset($status[0]) ) {
                            $parent_order->update_status( $new_status, _x( "Sync from vendor's suborders: ", 'Order note', 'dc-woocommerce-multi-vendor' ) );
                        }
                    }
                }
            }
            add_action('woocommerce_order_status_changed', array($this, 'wcmp_parent_order_to_vendor_order_status_synchronization'), 90, 3);
        }
    }

    public function wcmp_check_order_awaiting_payment() {
        // Insert or update the post data
        $order_id = absint(WC()->session->order_awaiting_payment);

        // Resume the unpaid order if its pending
        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            if ($order && $order->has_status(array('pending', 'failed'))) {
                $wcmp_suborders = get_wcmp_suborders($order_id);
                if ($wcmp_suborders) {
                    foreach ($wcmp_suborders as $suborder) {
                        $commission_id = get_post_meta( $suborder->get_id(), '_commission_id', true );
                        wp_delete_post( $commission_id, true );
                        wc_delete_shop_order_transients($suborder->get_id());
                        wp_delete_post($suborder->get_id(), true);
                    }
                }
            }
        }
    }

    /**
     * Handle a refund via the edit order screen.
     * Called after wp_ajax_woocommerce_refund_line_items action
     *
     * @use woocommerce_order_refunded action
     * @see woocommerce\includes\class-wc-ajax.php:2295
     */
    public function wcmp_order_refunded($order_id, $parent_refund_id) {
        
        if (!wp_get_post_parent_id($order_id)) { 
            $create_vendor_refund = false;
            $create_refund = true;
            $refund = false;
            $parent_line_item_refund = 0;
            $refund_amount = wc_format_decimal(sanitize_text_field($_POST['refund_amount']));
            $refund_reason = !empty($_POST['refund_reason']) ? sanitize_text_field($_POST['refund_reason']) : '';
            $line_item_qtys = !empty($_POST['line_item_qtys']) ? json_decode(sanitize_text_field(stripslashes($_POST['line_item_qtys'])), true) : array();
            $line_item_totals = !empty($_POST['line_item_totals']) ? json_decode(sanitize_text_field(stripslashes($_POST['line_item_totals'])), true) : array();
            $line_item_tax_totals = !empty($_POST['line_item_tax_totals']) ? json_decode(sanitize_text_field(stripslashes($_POST['line_item_tax_totals'])), true) : array();
            $api_refund = !empty($_POST['api_refund']) && $_POST['api_refund'] === 'true' ? true : false;
            $restock_refunded_items = !empty($_POST['restock_refunded_items']) && $_POST['restock_refunded_items'] === 'true' ? true : false;
            $order = wc_get_order($order_id);
            $parent_order_total = wc_format_decimal($order->get_total());
            $wcmp_suborders = get_wcmp_suborders($order_id);

            //calculate line items total from parent order
            foreach ($line_item_totals as $item_id => $total) {
                // check if there have vendor line item to refund
                $item = $order->get_item($item_id);
                if( ( $item->get_meta('_vendor_id') || $item->get_meta('vendor_id') ) && $total != 0) $create_vendor_refund = true;
                $parent_line_item_refund += wc_format_decimal($total);
            }
            
            foreach ($wcmp_suborders as $suborder) {
                $suborder_items_ids = array_keys($suborder->get_items( array( 'line_item', 'fee', 'shipping' ) ));
                $suborder_total = wc_format_decimal($suborder->get_total());
                $max_refund = wc_format_decimal($suborder_total - $suborder->get_total_refunded());
                $child_line_item_refund = 0;

                // Prepare line items which we are refunding
                $line_items = array();
                $item_ids = array_unique(array_merge(array_keys($line_item_qtys, $line_item_totals)));

                foreach ($item_ids as $item_id) {
                    $child_item_id = $this->get_vendor_order_item_id($item_id);
                    if ($child_item_id && in_array($child_item_id, $suborder_items_ids)) {
                        $line_items[$child_item_id] = array(
                            'qty' => 0,
                            'refund_total' => 0,
                            'refund_tax' => array()
                        );
                    }
                }

                foreach ($line_item_qtys as $item_id => $qty) {
                    $child_item_id = $this->get_vendor_order_item_id($item_id);
                    if ($child_item_id && in_array($child_item_id, $suborder_items_ids)) {
                        $line_items[$child_item_id]['qty'] = max($qty, 0);
                    }
                }

                foreach ($line_item_totals as $item_id => $total) {
                    $child_item_id = $this->get_vendor_order_item_id($item_id);
                    if ($child_item_id && in_array($child_item_id, $suborder_items_ids)) {
                        $total = wc_format_decimal($total);
                        $child_line_item_refund += $total;
                        $line_items[$child_item_id]['refund_total'] = $total;
                    }
                }

                foreach ($line_item_tax_totals as $item_id => $tax_totals) {
                    // check if there have vendor line item to refund
                    $item = $order->get_item($item_id);
                    if($item->get_meta('vendor_id')){
                        foreach ($tax_totals as $value) {
                            if($value != 0) $create_vendor_refund = true;
                        }
                    }
                    $child_item_id = $this->get_vendor_order_item_id($item_id);
                    if ($child_item_id && in_array($child_item_id, $suborder_items_ids)) {
                        $line_items[$child_item_id]['refund_tax'] = array_map('wc_format_decimal', $tax_totals);
                    }
                }

                //calculate refund amount percentage
                $suborder_refund_amount = ( ( ( $refund_amount - $parent_line_item_refund ) * $suborder_total ) / $parent_order_total );
                $suborder_total_refund = wc_format_decimal($child_line_item_refund + $suborder_refund_amount);

                if (!$refund_amount || $max_refund < $suborder_total_refund || 0 > $suborder_total_refund) {
                    /**
                     * Invalid refund amount.
                     * Check if suborder total != 0 create a partial refund, exit otherwise
                     */
                    $surplus = wc_format_decimal($suborder_total_refund - $max_refund);
                    $suborder_total_refund = $suborder_total_refund - $surplus;
                    $create_refund = $suborder_total_refund > 0 ? true : false;
                }

                if ($create_vendor_refund && $create_refund) {
                    // Create the refund object
                    $refund = wc_create_refund(array(
                        'amount' => $suborder_total_refund,
                        'reason' => $refund_reason,
                        'order_id' => $suborder->get_id(),
                        'line_items' => $line_items,
                        )
                    );
                    do_action( 'wcmp_order_refunded', $order->get_id(), $refund->get_id() );
                    if($refund)
                        add_post_meta($refund->get_id(), '_parent_refund_id', $parent_refund_id);
                }
            }
        }
    }

    /**
     * Handle a refund via the edit order screen.
     */
    public static function wcmp_refund_deleted($refund_id, $parent_order_id) {
        check_ajax_referer('order-item', 'security');

        if (!current_user_can('edit_shop_orders')) {
            wp_die( -1 );
        }

        if (!wp_get_post_parent_id($parent_order_id)) {
            global $wpdb;
            $child_refund_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", '_parent_refund_id', $refund_id));

            foreach ($child_refund_ids as $child_refund_id) {
                if ($child_refund_id && 'shop_order_refund' === get_post_type($child_refund_id)) {
                    $order_id = wp_get_post_parent_id($child_refund_id);
                    $assoc_commission_id = get_post_meta( $order_id, '_commission_id', true );
                    // delete associated refund commission meta data
                    $commission_refunded_data = get_post_meta( $assoc_commission_id, '_commission_refunded_data', true );
                    if( isset($commission_refunded_data[$child_refund_id]) ) unset($commission_refunded_data[$child_refund_id]);
                    $commission_refunded_data = ( $commission_refunded_data ) ? $commission_refunded_data : array();
                    update_post_meta( $assoc_commission_id, '_commission_refunded_data', $commission_refunded_data );
                    $commission_refunded_items_data = get_post_meta( $assoc_commission_id, '_commission_refunded_items_data', true );
                    if( isset($commission_refunded_items_data[$child_refund_id]) ) unset($commission_refunded_items_data[$child_refund_id]);
                    $commission_refunded_items_data = ( $commission_refunded_items_data ) ? $commission_refunded_items_data : array();
                    update_post_meta( $assoc_commission_id, '_commission_refunded_items_data', $commission_refunded_items_data );
                    $refunded_commission_amount = get_refund_commission_amount($child_refund_id);
                    $commission_refunded_items = get_post_meta( $assoc_commission_id, '_commission_refunded_items', true );
                    if( $commission_refunded_items ){
                        update_post_meta( $assoc_commission_id, '_commission_refunded_items', ($commission_refunded_items - $refunded_commission_amount) );
                    }
                    $commission_refunded = get_post_meta( $assoc_commission_id, '_commission_refunded', true );
                    if( $commission_refunded ){
                        update_post_meta( $assoc_commission_id, '_commission_refunded', ($commission_refunded - $refunded_commission_amount) );
                    }
                    
                    wc_delete_shop_order_transients($order_id);
                    wp_delete_post($child_refund_id);
                }
            }
        }elseif(is_wcmp_vendor_order($parent_order_id)){
            $order_id = $parent_order_id;
            $assoc_commission_id = get_post_meta( $order_id, '_commission_id', true );
            // delete associated refund commission meta data
            $commission_refunded_data = get_post_meta( $assoc_commission_id, '_commission_refunded_data', true );
            if( isset($commission_refunded_data[$refund_id]) ) unset($commission_refunded_data[$refund_id]);
            $commission_refunded_data = ( $commission_refunded_data ) ? $commission_refunded_data : array();
            update_post_meta( $assoc_commission_id, '_commission_refunded_data', $commission_refunded_data );
            $commission_refunded_items_data = get_post_meta( $assoc_commission_id, '_commission_refunded_items_data', true );
            if( isset($commission_refunded_items_data[$refund_id]) ) unset($commission_refunded_items_data[$refund_id]);
            $commission_refunded_items_data = ( $commission_refunded_items_data ) ? $commission_refunded_items_data : array();
            update_post_meta( $assoc_commission_id, '_commission_refunded_items_data', $commission_refunded_items_data );
            $refunded_commission_amount = get_refund_commission_amount($refund_id);
            $commission_refunded_items = get_post_meta( $assoc_commission_id, '_commission_refunded_items', true );
            if( $commission_refunded_items ){
                update_post_meta( $assoc_commission_id, '_commission_refunded_items', ($commission_refunded_items - $refunded_commission_amount) );
            }
            $commission_refunded = get_post_meta( $assoc_commission_id, '_commission_refunded', true );
            if( $commission_refunded ){
                update_post_meta( $assoc_commission_id, '_commission_refunded', ($commission_refunded - $refunded_commission_amount) );
            }

            wc_delete_shop_order_transients($order_id);
            wp_delete_post($refund_id);
        }
    }
    
    public function get_vendor_order_item_id( $item_id ) {
        global $wpdb;
        $vendor_item_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_item_id FROM {$wpdb->order_itemmeta} WHERE meta_key=%s AND meta_value=%d", '_vendor_order_item_id', absint( $item_id ) ) );
        // check for shipping
        if( !$vendor_item_id ){
            $vendor_item_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_item_id FROM {$wpdb->order_itemmeta} WHERE meta_key=%s AND meta_value=%d", '_vendor_order_shipping_item_id', absint( $item_id ) ) );
        }
        return $vendor_item_id;
    }

    public static function exclude_coping_order_data() {
        return apply_filters('wcmp_exclude_coping_order_data', array(
            'id', 'parent_id', 'created_via', 'date_created', 'date_modified', 'status', 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax',
            'cart_tax', 'total', 'total_tax', 'order_key', 'date_completed', 'date_paid', 'number', 'meta_data', 'line_items', 'tax_lines', 'shipping_lines',
            'fee_lines', 'coupon_lines'
        ));
    }

    public function count_processing_order() {
        global $wpdb;

        $count = 0;
        $status = 'wc-processing';
        $order_statuses = array_keys(wc_get_order_statuses());

        if (!in_array($status, $order_statuses)) {
            return 0;
        }

        $cache_key = WC_Cache_Helper::get_cache_prefix('orders') . $status;
        $cache_group = 'wcmp_order';

        $cached = wp_cache_get($cache_group . '_' . $cache_key, $cache_group);

        if ($cached) {
            return 0;
        }

        foreach (wc_get_order_types('order-count') as $type) {
            $query = "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_parent = 0";
            $count += $wpdb->get_var($wpdb->prepare($query, $type, $status));
        }

        wp_cache_set($cache_key, $count, 'counts');
        wp_cache_set($cache_group . '_' . $cache_key, true, $cache_group);
    }
    
    public function shop_order_statuses_get_views($views){
        $user = wp_get_current_user();
        if(current_user_can( 'administrator' ) || in_array('administrator', $user->roles) || in_array('dc_vendor', $user->roles)){
            unset($views['mine']);
        }
        return $views;
    }
    
    public function shop_order_count_orders($counts, $type, $perm = ''){
        global $wpdb;
        $user = wp_get_current_user();
        if($type == 'shop_order' && current_user_can( 'administrator' )){
            $post_statuses = wc_get_order_statuses();
            foreach ($counts as $status => $count) {
                if( array_key_exists($status, $post_statuses) && $count > 0 ){
                    $actual_counts = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_parent = 0", $type, $status));
                    if($actual_counts != $count){
                        $counts->$status = $actual_counts;
                    }
                }
            }
        }elseif($type == 'shop_order' && in_array('dc_vendor', $user->roles)) {
            $post_statuses = wc_get_order_statuses();
            foreach ($counts as $status => $count) {
                if( array_key_exists($status, $post_statuses) && $count > 0 ){
                    $actual_counts = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_parent != 0 AND post_author = %s", $type, $status, $user->ID));
                    if($actual_counts != $count){
                        $counts->$status = $actual_counts;
                    }
                }
            }
        }
        return $counts;
    }
    
    public function trash_wcmp_suborder( $order_id ) {
        if ( wp_get_post_parent_id( $order_id ) == 0 ) {
            $wcmp_suborders = get_wcmp_suborders($order_id);
            if ( $wcmp_suborders ) {
                foreach ( $wcmp_suborders as $suborder ) {
                    wp_trash_post( $suborder->get_id() );
                }
            }
        }
    }
    
    public function delete_wcmp_suborder( $order_id ) {
        if ( wp_get_post_parent_id( $order_id ) == 0 ) {
            $wcmp_suborders = get_wcmp_suborders($order_id);
            if ( $wcmp_suborders ) {
                foreach ( $wcmp_suborders as $suborder ) {
                    $commission_id = get_post_meta( $suborder->get_id(), '_commission_id', true );
                    wp_delete_post( $commission_id, true );
                    wp_delete_post( $suborder->get_id(), true );
                }
            }
        }
    }
    
    public function wcmp_vendor_order_backend_restriction(){
        if(is_user_wcmp_vendor(get_current_user_id())){
            $inline_css = "
                #order_data .order_data_column a.edit_address { display: none; }
                #order_data .order_data_column .wc-customer-user label a{ display: none; }
                #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items th.line_tax .delete-order-tax{ display: none; }
                #woocommerce-order-items .wc-order-edit-line-item-actions a, #woocommerce-order-items .wc-order-edit-line-item-actions a { display: none; }
                #woocommerce-order-items .add-items .button.add-line-item, #woocommerce-order-items .add-items .button.add-coupon { display: none; }
                .wcmp_vendor_admin.post-type-shop_order .wrap .page-title-action{ display: none; }
                .wcmp_vendor_admin #menu-posts-shop_order .wp-submenu li:last-child, .wcmp_vendor_admin .menu-icon-shop_order.opensub li:last-child{ display: none; }
                ";
            wp_add_inline_style('woocommerce_admin_styles', $inline_css);
        }
    }
    
    public function remove_meta_boxes(){
        global $post;
        if( $post && $post->post_type != 'shop_order' ) return;
        if( !is_user_wcmp_vendor( get_current_user_id() ) ) return;
        remove_meta_box( 'postcustom', 'shop_order', 'normal' );
        remove_meta_box( 'woocommerce-order-downloads', 'shop_order', 'normal' );
    }
    
    public function remove_admin_menu(){
        global $submenu;
        if( isset( $submenu['edit.php?post_type=shop_order'] ) ){
            foreach ( $submenu['edit.php?post_type=shop_order'] as $key => $menu ) {
                if( $menu[2] == 'post-new.php?post_type=shop_order' ){
                    unset( $submenu['edit.php?post_type=shop_order'][$key] );
                }
            }
        }
    }

    public function woocommerce_my_account_my_orders_query( $query ){
        if(!isset($query['post_parent'])){
            $query['post_parent'] = 0;
        }
        return $query;
    }
    
    public function woocommerce_my_account_my_orders_columns( $columns ) {
        $suborder_column['wcmp_suborder'] = __( 'Suborders', 'dc-woocommerce-multi-vendor' );
        $columns = array_slice($columns, 0, 1, true) + $suborder_column + array_slice($columns, 1, count($columns) - 1, true);
        return $columns;
    }
    
    public function woocommerce_my_account_my_orders_column_wcmp_suborder( $order ) {
        $wcmp_suborders = get_wcmp_suborders($order->get_id());

        if ($wcmp_suborders) {
            echo '<ul class="wcmp-order-vendor" style="margin:0px;list-style:none;">';
            foreach ($wcmp_suborders as $suborder) {
                $vendor = get_wcmp_vendor(get_post_field('post_author', $suborder->get_id()));
                $order_uri = esc_url( $suborder->get_view_order_url() );
                printf('<li><strong><a href="%s" title="%s">#%s</a></strong> &ndash; <small class="wcmp-order-for-vendor">%s %s</small></li>', $order_uri, sanitize_title($suborder->get_status()), $suborder->get_order_number(), _x('for', 'Order table details', 'dc-woocommerce-multi-vendor'), $vendor->page_title
                );
                do_action('wcmp_after_suborder_details', $suborder);
            }
            echo '<ul>';
        } else {
            echo '<span class="na">&ndash;</span>';
        }
    }
    
    public function wcmp_frontend_enqueue_scripts(){
        if(is_account_page()){
            $styles = '/***********************  WCMp Suborder Icon ***********************/
            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark{
                display: block;
                text-indent: -9999px;
                position: relative;
                height: 1em;
                width: 1em;
                background: 0 0;
                font-size: 1.4em;
                margin: 0 auto
            }
            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark:after{
                font-family: WooCommerce;
                speak: none;
                font-weight: 400;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
                margin: 0;
                text-indent: 0;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                text-align: center
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark {
                float: left;
                margin-right: 8px;
                font-size: 1.1em;
                margin-top: 2px;
            }

            /* Suborder Icon */

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.pending:after{
                content: "\e012";
                color: #ffba00
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.trash:after{
                content: "\e602";
                color: #a00
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.completed:after{
                content: "\e015";
                color: #2ea2cc
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.on-hold:after{
                content: "\e033";
                color: #999
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.failed:after{
                content: "\e016";
                color: #d0c21f
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.cancelled:after{
                content: "\e013";
                color: #a00
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.processing:after{
                content: "\e011";
                color: #73a724
            }

            .woocommerce-MyAccount-orders.account-orders-table .wcmp-order-vendor mark.refunded:after {
                content: "\e014";
                color: #999
            }';
            wp_add_inline_style('woocommerce-inline', $styles);
        }
    }
    
    public function add_admin_body_class( $body_classes ){
        if ( is_user_wcmp_vendor(get_current_user_id() ) ) {
            $body_classes .= ' wcmp_vendor_admin';
        }
        return $body_classes;
    }
    
    public function woocommerce_can_reduce_order_stock( $reduce_stock, $order ){
        $is_vendor_order = ( $order ) ? wcmp_get_order( $order->get_id() ) : false;
        return $order instanceof WC_Order && wp_get_post_parent_id( $order->get_id() ) && $is_vendor_order ? false : $reduce_stock;
    }
    
    public function woocommerce_hidden_order_itemmeta( $itemmeta ) {
        if ( is_user_wcmp_vendor(get_current_user_id() ) ) {
            $itemmeta[] = '_vendor_item_commission';
            $itemmeta[] = 'commission';
            $itemmeta[] = '_vendor_id';
            $itemmeta[] = 'vendor_id';
            $itemmeta[] = '_vendor_order_item_id';
            $itemmeta[] = 'Sold By';
        }elseif (current_user_can( 'administrator' ) ){
            $itemmeta[] = 'commission';
        }
        return $itemmeta;
    }
    
    public function woocommerce_hidden_order_item_get_formatted_meta_data( $formatted_meta ) {
        if( $formatted_meta ){
            foreach ( $formatted_meta as $key => $meta ) {
                if( $meta->key == 'commission' ) unset($formatted_meta[$key]);
            }
        }
        return $formatted_meta;
    }
    
    public function wcmp_vendor_order_status_changed_actions( $order_id, $old_status, $new_status ){
        if( !$order_id || !is_wcmp_vendor_order( $order_id ) ) return;
        if( $new_status == 'cancelled' ){
            $commission_id = get_post_meta( $order_id, '_commission_id', true );
            do_action( 'wcmp_vendor_order_on_cancelled_commission', $commission_id, $order_id );
            if( $commission_id ) wp_trash_post( $commission_id );
        }
    }
    
    public function wcmp_exclude_suborders_from_rest_api_call( $args, $request ){
        if( apply_filters( 'wcmp_exclude_suborders_from_rest_api_call', true, $args, $request ) )
            $args['parent'] = ( isset( $args['parent'] ) && $args['parent'] ) ? $args['parent'][] = 0 : array( 0 );
        if( apply_filters( 'wcmp_fetch_all_suborders_from_rest_api_call', false, $args, $request ) )
            $args['parent_exclude'] = ( isset( $args['parent_exclude'] ) && $args['parent_exclude'] ) ? $args['parent_exclude'][] = 0 : array( 0 );
        return apply_filters( 'wcmp_exclude_suborders_from_rest_api_call_query_args', $args, $request );
    }

}
