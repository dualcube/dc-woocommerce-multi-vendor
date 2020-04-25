<?php
if (!defined('ABSPATH'))
    exit;

/**
 * @class 		WCMp Commission Post Class-
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Commission {

    private $post_type;
    public $dir;
    public $file;

    public function __construct() {
        $this->post_type = 'dc_commission';
        $this->register_post_type();
        if (is_admin()) {
            // Handle custom fields for post
            add_action('admin_menu', array($this, 'meta_box_setup'), 20);
            add_action('save_post', array($this, 'meta_box_save'));

            // Handle commission paid status
            add_action('post_submitbox_misc_actions', array($this, 'custom_actions_content'));
            add_action('save_post', array($this, 'custom_actions_save'));
            
            // Handle post columns
            add_filter('manage_edit-' . $this->post_type . '_columns', array($this, 'wcmp_register_custom_column_headings'), 10, 1);
            add_action('manage_pages_custom_column', array($this, 'wcmp_register_custom_columns'), 10, 2);

            add_action('restrict_manage_posts', array($this, 'wcmp_woocommerce_restrict_manage_orders'));
            add_filter('request', array(&$this, 'wcmp_woocommerce_orders_by_customer_query'));

            add_filter('pre_get_posts', array(&$this, 'commission_post_types_admin_order'));

            add_filter('bulk_actions-edit-dc_commission', array(&$this, 'register_commission_bulk_actions'));
            add_filter('handle_bulk_actions-edit-dc_commission', array(&$this, 'commission_bulk_action_handler'), 10, 3);
            add_action('admin_notices', array(&$this, 'wcmp_commission_update_notice'));
            // Commissions delete on order deleted
            add_action('deleted_post', array(&$this, 'wcmp_commission_delete_on_order_deleted'));
            add_action('admin_notices', array(&$this, 'wcmp_commission_notices') );
        }
    }

    /**
     * Register commission post type
     *
     * @access public
     * @return void
     */
    function register_post_type() {
        global $WCMp;
        if (post_type_exists($this->post_type))
            return;
        $labels = array(
            'name' => _x('Commissions', 'post type general name', 'dc-woocommerce-multi-vendor'),
            'singular_name' => _x('Commission', 'post type singular name', 'dc-woocommerce-multi-vendor'),
            'add_new' => _x('Add New', $this->post_type, 'dc-woocommerce-multi-vendor'),
            'add_new_item' => sprintf(__('Add New %s', 'dc-woocommerce-multi-vendor'), __('Commission', 'dc-woocommerce-multi-vendor')),
            'edit_item' => sprintf(__('Edit %s', 'dc-woocommerce-multi-vendor'), __('Commission', 'dc-woocommerce-multi-vendor')),
            'new_item' => sprintf(__('New %s', 'dc-woocommerce-multi-vendor'), __('Commission', 'dc-woocommerce-multi-vendor')),
            'all_items' => sprintf(__('All %s', 'dc-woocommerce-multi-vendor'), __('Commissions', 'dc-woocommerce-multi-vendor')),
            'view_item' => sprintf(__('View %s', 'dc-woocommerce-multi-vendor'), __('Commission', 'dc-woocommerce-multi-vendor')),
            'search_items' => sprintf(__('Search %a', 'dc-woocommerce-multi-vendor'), __('Commissions', 'dc-woocommerce-multi-vendor')),
            'not_found' => sprintf(__('No %s found', 'dc-woocommerce-multi-vendor'), __('Commissions', 'dc-woocommerce-multi-vendor')),
            'not_found_in_trash' => sprintf(__('No %s found in trash', 'dc-woocommerce-multi-vendor'), __('Commissions', 'dc-woocommerce-multi-vendor')),
            'parent_item_colon' => '',
            'all_items' => __('Commissions', 'dc-woocommerce-multi-vendor'),
            'menu_name' => __('Commissions', 'dc-woocommerce-multi-vendor')
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => current_user_can('manage_woocommerce') ? 'wcmp' : false,
            'show_in_nav_menus' => false,
            'query_var' => false,
            'rewrite' => true,
            'capability_type' => 'shop_order',
            //'capability_type' => 'post',
            //'capabilities' => array('create_posts' => false, 'delete_posts' => false, 'edit_others_posts' => false),
            'map_meta_cap' => true,
            'has_archive' => true,
            'hierarchical' => true,
            'supports' => array('title'),
            'menu_position' => 5,
            'menu_icon' => $WCMp->plugin_url . '/assets/images/dualcube.png'
        );

        register_post_type($this->post_type, $args);
    }

    /**
     * Add meta box to commission posts
     *
     * @return void
     */
    public function meta_box_setup() {
        add_meta_box('wcmp-commission-data', __('Commission Details', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_meta_box_content'), $this->post_type, 'normal', 'high');
        add_meta_box('wcmp-commission-note', __('Commission notes', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_meta_box_commission_notes'), $this->post_type, 'side', 'low');
        if (!is_wcmp_version_less_3_4_0())
            add_meta_box('woocommerce-order-items', __('Commission Order Details', 'dc-woocommerce-multi-vendor'), array(&$this, 'wcmp_commission_order_content'), $this->post_type, 'normal', 'high');
    }

    /**
     * Create commission
     * @param int $order_id
     * @param array $args
     * @return int $commission_id
     */
    public static function create_commission($order_id, $args = array()) {
        if ($order_id) {
            $vendor_id = get_post_meta($order_id, '_vendor_id', true);
            // create vendor commission
            $default = array(
                'post_type' => 'dc_commission',
                'post_title' => sprintf(__('Commission - %s', 'dc-woocommerce-multi-vendor'), strftime(_x('%B %e, %Y @ %I:%M %p', 'Commission date parsed by strftime', 'dc-woocommerce-multi-vendor'), current_time('timestamp'))),
                'post_status' => 'private',
                'ping_status' => 'closed',
                'post_excerpt' => '',
                'post_author' => $vendor_id
            );

            $commission_data = apply_filters('wcmp_create_vendor_commission_args', wp_parse_args($args, $default));

            $commission_id = wp_insert_post($commission_data);
            if ($commission_id) {
                // add order id with commission meta
                update_post_meta($commission_id, '_commission_order_id', $order_id);
                update_post_meta($commission_id, '_paid_status', 'unpaid');
                // for BW supports
                $vendor = get_wcmp_vendor( $vendor_id );
                update_post_meta($commission_id, '_commission_vendor', $vendor->term_id);
                /**
                 * Action hook to update commission meta data.
                 *
                 * @since 3.4.0
                 */
                do_action('wcmp_commission_update_commission_meta', $commission_id);

                self::add_commission_note($commission_id, sprintf(__('Commission for order <a href="%s">(ID : %s)</a> is created.', 'dc-woocommerce-multi-vendor'), get_admin_url() . 'post.php?post=' . $order_id . '&action=edit', $order_id), $vendor_id);
                return $commission_id;
            }
        }
        return false;
    }
    
    /**
     * Calculate commission
     * @param int $commission_id
     * @param object $order
     * @return void 
     */
    public static function calculate_commission( $commission_id, $order ) {
        global $WCMp;
        if ($commission_id && $order) {
            $vendor_id = get_post_meta($order->get_id(), '_vendor_id', true);
            $commission_rates = get_post_meta($order->get_id(), 'order_items_commission_rates', true);
            // line item commission
            $commission_amount = $shipping_amount = $tax_amount = $shipping_tax_amount = 0;
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $meta_data = $item->get_meta_data();
                // get item commission
                foreach ( $meta_data as $meta ) {
                    if($meta->key == '_vendor_item_commission'){
                        $commission_amount += floatval($meta->value);
                    }
                    if($meta->key == '_vendor_order_item_id'){
                        $order_item_id = absint($meta->value);
                        if(isset($commission_rates[$order_item_id])){
                            $rate = $commission_rates[$order_item_id];
                            $commission_rates[$item_id] = $rate;
                            unset($commission_rates[$order_item_id]); // update with vendor order item id for further use
                        }
                    }
                }
            }
            /**
             * Action hook to adjust items commission rates before save.
             *
             * @since 3.4.0
            */
            update_post_meta($order->get_id(), 'order_items_commission_rates', apply_filters('wcmp_vendor_order_items_commission_rates', $commission_rates, $order));
            
            // transfer shipping charges
            if ($WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($vendor_id, '_vendor_give_shipping', true)) {
                $shipping_amount = $order->get_shipping_total();
            }
            
            // transfer tax charges
            foreach ( $order->get_items( 'tax' ) as $key => $tax ) { 
                if ($WCMp->vendor_caps->vendor_payment_settings('give_tax') && $WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($vendor_id, '_vendor_give_shipping', true) && !get_user_meta($vendor_id, '_vendor_give_tax', true)) {
                    $tax_amount += $tax->get_tax_total();
                    $shipping_tax_amount = $tax->get_shipping_tax_total();
                } else if ($WCMp->vendor_caps->vendor_payment_settings('give_tax') && !get_user_meta($vendor_id, '_vendor_give_tax', true)) {
                    $tax_amount += $tax->get_tax_total();
                    $shipping_tax_amount = 0;
                } else {
                    $tax_amount = 0;
                    $shipping_tax_amount = 0;
                }
            }
            
            // update commission meta
            if (0 < $order->get_total_discount() && isset($WCMp->vendor_caps->payment_cap['commission_include_coupon']))
                update_post_meta($commission_id, '_commission_include_coupon', true);
            if ( 0 < $shipping_amount && $WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($vendor_id, '_vendor_give_shipping', true))
                update_post_meta( $commission_id, '_commission_total_include_shipping', true );
            if ( 0 < $tax_amount && $WCMp->vendor_caps->vendor_payment_settings('give_tax') && !get_user_meta($vendor_id, '_vendor_give_tax', true))
                update_post_meta( $commission_id, '_commission_total_include_tax', true );
            
            update_post_meta( $commission_id, '_commission_amount', $commission_amount );
            update_post_meta( $commission_id, '_shipping', $shipping_amount );
            update_post_meta( $commission_id, '_tax', ($tax_amount + $shipping_tax_amount) );
            /**
             * Action hook to update commission meta data.
             *
             * @since 3.4.0
             */
            do_action('wcmp_commission_before_save_commission_total', $commission_id);
            $commission_total = (float) $commission_amount + (float) $shipping_amount + (float) $tax_amount + (float) $shipping_tax_amount;
            $commission_total = apply_filters('wcmp_commission_total_amount', $commission_total, $commission_id);
            update_post_meta( $commission_id, '_commission_total', $commission_total );
            do_action( 'wcmp_commission_after_save_commission_total', $commission_id, $order );

        }
        return false;
    }

    /**
     * Add content to meta box to commission posts
     *
     * @return void
     */
    public function wcmp_meta_box_content() {
        global $WCMp, $post_id;
        $commission_order_id = get_post_meta($post_id, '_commission_order_id', true);
        $order = wc_get_order($commission_order_id);
        if(!$order) return;
        $commission_order_version = get_post_meta($commission_order_id, '_order_version', true);
        $post = get_post($post_id);
        $vendor = get_wcmp_vendor($post->post_author);
        if(!$vendor){
            $vendor_id = get_post_meta($commission_order_id, '_vendor_id', true);
            $vendor = get_wcmp_vendor($vendor_id);
        }
        $commission_type_object = get_post_type_object( $post->post_type );
        if($commission_order_version){ ?>
            <style type="text/css">
                #post-body-content, #titlediv { display:none }
            </style>
            <div id="order_data" class="woocommerce-order-data">
                <input type="hidden" name="<?php echo $this->post_type . '_nonce'; ?>" id="<?php echo $this->post_type . '_nonce'; ?>" value="<?php echo wp_create_nonce(plugin_basename($this->dir)); ?>" />
                <h2 class="woocommerce-order-data__heading">
                    <?php

                    /* translators: 1: commission type 2: commission id */
                    printf(
                            esc_html__( '%1$s #%2$s details', 'dc-woocommerce-multi-vendor' ),
                            esc_html( $commission_type_object->labels->singular_name ),
                            esc_html( $post_id )
                    );

                    ?>
                </h2>
                <p class="woocommerce-order-data__meta order_number">
                    <?php
                    $meta_list = array();

                    if ( $vendor ) {
                        /* translators: %s: associated vendor */
                        $vendor_string = sprintf(
                            __( 'Associated vendor %s', 'dc-woocommerce-multi-vendor' ),
                            '<a href="'.get_edit_user_link($vendor->id).'" target="_blank">'.$vendor->page_title.'</a>'
                        );

                        $meta_list[] = $vendor_string;
                    }

                    /* translators: %s: Commission status */
                    $status = self::get_status($post_id, 'edit');
                    $status_html = '';
                    if($status == 'paid'){
                        $status_html .= '<mark class="order-status status-processing tips"><span>'.self::get_status($post_id).'</span></mark>';
                    }else{
                        $status_html .= '<mark class="order-status status-refunded tips"><span>'.self::get_status($post_id).'</span></mark>';
                    }

                    $meta_list[] = sprintf(
                        __( 'Commission status: %s', 'dc-woocommerce-multi-vendor' ),
                        $status_html
                    );

                    echo wp_kses_post( implode( '. ', $meta_list ) );

                    ?>
                </p>
                <div class="order_data_column_container">
                    <div class="order_data_column">
                        <h3><?php esc_html_e( 'General', 'dc-woocommerce-multi-vendor' ); ?></h3>
                        <p class="form-field form-field-wide">
                            <label><strong><?php esc_html_e( 'Associated order', 'dc-woocommerce-multi-vendor' ); ?>:</strong></label> 
                            <a href="<?php echo get_edit_post_link($commission_order_id); ?>">#<?php echo esc_attr($commission_order_id); ?></a>
                        </p>
                        <p class="form-field form-field-wide">
                            <label><strong><?php esc_html_e( 'Order status', 'dc-woocommerce-multi-vendor' ); ?>:</strong></label>
                            <?php echo ucfirst($order->get_status()); ?>
                        </p>
                        <p class="form-field form-field-wide wc-order-status">
                            <label for="commission_status">
                                <?php _e( 'Commission Status:', 'dc-woocommerce-multi-vendor' ); ?>
                            </label>
                            <select id="commission_status" name="commission_status" class="wc-enhanced-select">
                                <?php
                                $statuses = wcmp_get_commission_statuses();
                                foreach ( $statuses as $status => $status_name ) {
                                    echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, self::get_status($post_id, 'edit'), false ) . '>' . esc_html( $status_name ) . '</option>';
                                }
                                ?>
                            </select>
                        </p>
                        
                    </div>
                    <div class="order_data_column">
                        <h3><?php esc_html_e( 'Vendor details', 'dc-woocommerce-multi-vendor' ); ?></h3>
                        <?php if($vendor) : ?>
                        <span class="commission-vendor">
                            <?php echo get_avatar($vendor->id, 50); ?>
                            <a href="<?php echo get_edit_user_link($vendor->id); ?>"><?php echo $vendor->page_title; ?></a>
                        </span>
                        
                        <p class="form-field form-field-wide">
                            <label><strong><?php esc_html_e( 'Email', 'dc-woocommerce-multi-vendor' ); ?>:</strong></label>
                            <a href="mailto:<?php echo $vendor->user_data->user_email; ?>"><?php echo $vendor->user_data->user_email; ?></a>
                        </p>
                        <p class="form-field form-field-wide">
                            <label><strong><?php esc_html_e( 'Payment mode', 'dc-woocommerce-multi-vendor' ); ?>:</strong></label>
                            <?php 
                            $payment_title = isset($WCMp->payment_gateway->payment_gateways[$vendor->payment_mode]) ? $WCMp->payment_gateway->payment_gateways[$vendor->payment_mode]->gateway_title : '';
                            echo $payment_title;
                            ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="order_data_column">
                        <h3><?php esc_html_e( 'Commission data', 'dc-woocommerce-multi-vendor' ); ?></h3>
                        <p class="form-field form-field-wide wcmp-commission-amount">
                            <label>
                                <strong><?php esc_html_e( 'Commission amount', 'dc-woocommerce-multi-vendor' ); ?>:</strong>
                                <a href="#" class="edit_commission_amount"><?php _e( 'Edit', 'dc-woocommerce-multi-vendor' ); ?></a>
                            </label>
                            <span class="commission-amount-view">
                                <?php 
                                $commission_amount = get_post_meta( $post_id, '_commission_amount', true );
                                if($commission_amount != self::commission_amount_totals($post_id, 'edit')){
                                    echo '<del>' . wc_price($commission_amount, array('currency' => $order->get_currency())) . '</del> <ins>' . self::commission_amount_totals($post_id).'</ins>'; 
                                }else{
                                    echo self::commission_amount_totals($post_id);
                                }

                                ?>
                            </span>
                            <input name="_commission_amount" type="text" id="_commission_amount" class="regular-text commission-amount-edit" value="<?php echo self::commission_amount_totals($post_id, 'edit'); ?>" style="display:none;" />
                        </p>
                        <p class="form-field form-field-wide">
                            <label><strong><?php esc_html_e( 'Shipping', 'dc-woocommerce-multi-vendor' ); ?>:</strong></label>
                            <?php 
                            $shipping_amount = get_post_meta( $post_id, '_shipping', true );
                            if($shipping_amount != self::commission_shipping_totals($post_id, 'edit')){
                                echo '<del>' . wc_price($shipping_amount, array('currency' => $order->get_currency())) . '</del> <ins>' . self::commission_shipping_totals($post_id).'</ins>'; 
                            }else{
                                echo self::commission_shipping_totals($post_id);
                            }
                            ?>
                        </p>
                        <p class="form-field form-field-wide">
                            <label><strong><?php esc_html_e( 'Tax', 'dc-woocommerce-multi-vendor' ); ?>:</strong></label>
                            <?php 
                            $tax_amount = get_post_meta( $post_id, '_tax', true );
                            if($tax_amount != self::commission_tax_totals($post_id, 'edit')){
                                echo '<del>' . wc_price($tax_amount, array('currency' => $order->get_currency())) . '</del> <ins>' . self::commission_tax_totals($post_id).'</ins>'; 
                            }else{
                                echo self::commission_tax_totals($post_id);
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
            
          
        <?php }else{
            // backward compatibilities
            $fields = get_post_custom($post_id);
            $field_data = $this->get_custom_fields_settings($post_id);

            $html = '';

            $html .= '<input type="hidden" name="' . $this->post_type . '_nonce" id="' . $this->post_type . '_nonce" value="' . wp_create_nonce(plugin_basename($this->dir)) . '" />';

            if (0 < count($field_data)) {
                $html .= '<table class="form-table">' . "\n";
                $html .= '<tbody>' . "\n";
                foreach ($field_data as $k => $v) {
                    $data = $v['default'];
                    if (isset($fields[$k]) && isset($fields[$k][0])) {
                        $data = $fields[$k][0];
                    }
                    if ($k == '_commission_order_id') {
                        $html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr($k) . '">' . $v['name'] . '</label></th><td><a href="' . get_edit_post_link($data) . '">#' . esc_attr($data) . ' </a>' . "\n";
                        //$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
                        $html .= '</td><tr/>' . "\n";
                    } else if ($k == '_commission_product') {
                        $option = '<option value=""></option>';
                        $product_ids = get_post_meta($post_id, '_commission_product', true);

                        if (!is_array($product_ids)) {
                            $fields[$k] = array($product_ids);
                        } else {
                            $fields[$k] = $product_ids;
                        }
                        $html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr($k) . '">' . $v['name'] . '</label></th><td>';
                        if (!empty($fields[$k])) {
                            foreach ($fields[$k] as $dat) {
                                $product = wc_get_product($dat);
                                if ($product) {
                                    $html .= '<table>';
                                    $html .= '<tr>';
                                    $html .= '<td style="padding:0">';
                                    $html .= get_the_post_thumbnail($product->get_id(), array('50', '50')) ? get_the_post_thumbnail($product->get_id(), array('50', '50')) : wc_placeholder_img(array('50', '50'));
                                    $html .= '</td>';
                                    $html .= '<td>';
                                    if ($product->get_type() == 'variation') {
                                        $html .= '<a href="' . get_edit_post_link($product->get_parent_id()) . '">' . $product->get_title() . '</a>';
                                    } else {
                                        $html .= '<a href="' . get_edit_post_link($product->get_id()) . '">' . $product->get_title() . '</a>';
                                    }
                                    $html .= '</td>';
                                    $html .= '</tr>';
                                    $html .= '</table>';
                                }
                            }
                        }
                        $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
                        $html .= '</td><tr/>' . "\n";
                    } elseif ($k == '_commission_vendor') {
                        $vendor = get_wcmp_vendor_by_term($data);
                        $vendor_term = get_term($data);
                        if ($data && strlen($data) > 0 && $vendor) {
                            $html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr($k) . '">' . $v['name'] . '</label></th><td>';
                            $html .= '<table>';
                            $html .= '<tr>';
                            $html .= '<td style="padding:0">';
                            $html .= get_avatar($vendor->id, 50); //get_the_post_thumbnail($product->get_id(), array('50', '50'));
                            $html .= '</td>';
                            $html .= '<td>';
                            $html .= '<a href="' . get_edit_user_link($vendor->id) . '">' . $vendor_term->name . '</a>';
                            $html .= '</td>';
                            $html .= '</tr>';
                            $html .= '</table>';
                            $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
                            $html .= '</td><tr/>' . "\n";
                        }
                    } else {
                        $val = esc_attr($data);
                       //if($k == '_commission_amount')
                           //$val = number_format( $data, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
                        $html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr($k) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr($k) . '" type="text" id="' . esc_attr($k) . '" class="regular-text" value="' . $val . '" />' . "\n";
                        $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
                        $html .= '</td><tr/>' . "\n";
                    }
                }

                $html .= '</tbody>' . "\n";
                $html .= '</table>' . "\n";
            }

            echo $html;
        }
    }
    
    /**
     * Get commission status
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function get_status( $commission_id, $context = 'view' ) {
        if($commission_id){
            $status = get_post_meta($commission_id, '_paid_status', true);
            $status_view = ucfirst(str_replace('_', ' ', $status));
            return $context == 'view' ? $status_view : $status;
        }
    }
    
    /**
     * Calculate commission total including refunds
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function commission_totals( $commission_id, $context = 'view' ) {
        if($commission_id){
            $order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $order = wc_get_order($order_id);
            $commission_total = get_post_meta( $commission_id, '_commission_total', true );
            // backward compatibility added
            if(!$commission_total){
                $commission_amt = get_post_meta($commission_id, '_commission_amount', true);
                $shipping_amt = get_post_meta($commission_id, '_shipping', true);
                $tax_amt = get_post_meta($commission_id, '_tax', true);
                $commission_total = (floatval($commission_amt) + floatval($shipping_amt) + floatval($tax_amt));
            }
            $commission_refunded_total = get_post_meta( $commission_id, '_commission_refunded', true );
            $total = floatval($commission_total) + floatval($commission_refunded_total);
            if($order)
                return $context == 'view' ? wc_price($total, array('currency' => $order->get_currency())) : $total;
        }
    }
    
    /**
     * Calculate commission amount total including refunds
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function commission_amount_totals( $commission_id, $context = 'view' ) {
        if($commission_id){
            $order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $order = wc_get_order($order_id);
            $commission_amount = get_post_meta( $commission_id, '_commission_amount', true );
            $commission_refunded_amount = get_post_meta( $commission_id, '_commission_refunded_items', true );
            $total = floatval($commission_amount) + floatval($commission_refunded_amount);
            if($order)
                return $context == 'view' ? wc_price($total, array('currency' => $order->get_currency())) : $total;
        }
    }
    
    /**
     * Calculate commission refunded amount total
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function commission_refunded_totals( $commission_id, $context = 'view' ) {
        if($commission_id){
            $order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $order = wc_get_order($order_id);
            $commission_refunded = get_post_meta( $commission_id, '_commission_refunded', true );
            return $context == 'view' ? wc_price($commission_refunded, array('currency' => $order->get_currency())) : $commission_refunded;
        }
    }
    
    /**
     * Calculate commission refunded amount total
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function commission_items_refunded_totals( $commission_id, $context = 'view' ) {
        if($commission_id){
            $order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $order = wc_get_order($order_id);
            $commission_refunded = get_post_meta( $commission_id, '_commission_refunded_items', true );
            return $context == 'view' ? wc_price($commission_refunded, array('currency' => $order->get_currency())) : $commission_refunded;
        }
    }
    
    /**
     * Calculate commission shipping total including refunds
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function commission_shipping_totals( $commission_id, $context = 'view' ) {
        if($commission_id){
            $order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $order = wc_get_order($order_id);
            $shipping_amount = get_post_meta( $commission_id, '_shipping', true );
            $commission_refunded_shipping = get_post_meta( $commission_id, '_commission_refunded_shipping', true );
            $total = floatval($shipping_amount) + floatval($commission_refunded_shipping);
            return $context == 'view' ? wc_price($total, array('currency' => $order->get_currency())) : $total;
        }
    }
    
    /**
     * Calculate commission tax total including refunds
     * @param int $commission_id
     * @param string $context
     * @return value 
     */
    public static function commission_tax_totals( $commission_id, $context = 'view' ) {
        if($commission_id){
            $order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $order = wc_get_order($order_id);
            $tax_amount = get_post_meta( $commission_id, '_tax', true );
            $commission_refunded_tax = get_post_meta( $commission_id, '_commission_refunded_tax', true );
            $total = floatval($tax_amount) + floatval($commission_refunded_tax);
            return $context == 'view' ? wc_price($total, array('currency' => $order->get_currency())) : $total;
        }
    }
    
    /**
     * Get commission totals array
     * @param array $args 
     * @param boolean $check_caps
     * @return array 
     */
    public static function get_commissions_total_data( $args = array(), $vendor_id = 0, $check_caps = true ) {
        global $WCMp;
        $default_args = array(
            'post_type' => 'dc_commission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'fields' => 'ids',
	);
        
        $args = wp_parse_args( $args, $default_args );
        
        if( isset( $args['meta_query'] ) ) {
            $args['meta_query'][] = array(
                'key' => '_paid_status',
                'value' => array('unpaid', 'partial_refunded'),
                'compare' => 'IN'
            );
        } else {
            $args['meta_query'] = array(
                array(
                    'key' => '_paid_status',
                    'value' => array('unpaid', 'partial_refunded'),
                    'compare' => 'IN'
                ),
            );
        }
   
        $commissions = new WP_Query( $args );
        if( $commissions->get_posts() ) :
            $commission_amount = $shipping_amount = $tax_amount = $total = 0;
            $commission_posts = apply_filters( 'wcmp_before_get_commissions_total_data_commission_posts', $commissions->get_posts(), $vendor_id, $args );
            foreach ( $commission_posts as $commission_id ) {
                $commission_amount += self::commission_amount_totals( $commission_id, 'edit' );
                $shipping_amount += self::commission_shipping_totals( $commission_id, 'edit' );
                $tax_amount += self::commission_tax_totals( $commission_id, 'edit' );
            }
            if( $check_caps && $vendor_id ){
                $amount = array(
                    'commission_amount' => $commission_amount,
                );
                if ($WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($vendor_id, '_vendor_give_shipping', true)) {
                    $amount['shipping_amount'] = $shipping_amount;
                } else {
                    $amount['shipping_amount'] = 0;
                }
                if ($WCMp->vendor_caps->vendor_payment_settings('give_tax') && !get_user_meta($vendor_id, '_vendor_give_tax', true)) {
                    $amount['tax_amount'] = $tax_amount;
                } else {
                    $amount['tax_amount'] = 0;
                }
                $amount['total'] = $amount['commission_amount'] + $amount['shipping_amount'] + $amount['tax_amount'];
                return $amount;
            }else{
                return array(
                    'commission_amount' => $commission_amount,
                    'shipping_amount' => $shipping_amount,
                    'tax_amount' => $tax_amount,
                    'total' => $commission_amount + $shipping_amount + $tax_amount
                );
            }
        endif;
    }

    /**
     * Add order data for commission posts
     *
     * @return void
     */
    public function wcmp_commission_order_content() {
        global $post_id, $WCMp;
        $order_id = get_post_meta($post_id, '_commission_order_id', true);
        $order = wc_get_order($order_id);
        $vendor_order = wcmp_get_order($order_id);
        if( $order ) :
        // Get line items
        $line_items = $order->get_items(apply_filters('wcmp_admin_commission_order_item_types', 'line_item'));
        $discounts = $order->get_items('discount');
        $line_items_fee = $order->get_items('fee');
        $line_items_shipping = $order->get_items('shipping');

        if (wc_tax_enabled()) {
            $order_taxes = $order->get_taxes();
            $tax_classes = WC_Tax::get_tax_classes();
            $classes_options = wc_get_product_tax_class_options();
            $show_tax_columns = count($order_taxes) === 1;
        }
        ?>
        <div class="wcmp-commission-order-data woocommerce_order_items_wrapper wc-order-items-editable">
            <table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
                <thead>
                    <tr>
                        <th class="item sortable" colspan="2" data-sort="string-ins"><?php esc_html_e('Item', 'dc-woocommerce-multi-vendor'); ?></th>
                        <?php do_action('wcmp_admin_commission_order_item_headers', $order); ?>
                        <th class="item_cost sortable" data-sort="float"><?php esc_html_e('Cost', 'dc-woocommerce-multi-vendor'); ?></th>
                        <th class="quantity sortable" data-sort="int"><?php esc_html_e('Qty', 'dc-woocommerce-multi-vendor'); ?></th>
                        <th class="line_cost sortable" data-sort="float"><?php esc_html_e('Total', 'dc-woocommerce-multi-vendor'); ?></th>
                        <?php
                        if (!empty($order_taxes)) :
                            foreach ($order_taxes as $tax_id => $tax_item) :
                                $tax_class = wc_get_tax_class_by_tax_id($tax_item['rate_id']);
                                $tax_class_name = isset($classes_options[$tax_class]) ? $classes_options[$tax_class] : __('Tax', 'dc-woocommerce-multi-vendor');
                                $column_label = !empty($tax_item['label']) ? $tax_item['label'] : __('Tax', 'dc-woocommerce-multi-vendor');
                                /* translators: %1$s: tax item name %2$s: tax class name  */
                                $column_tip = sprintf(esc_html__('%1$s (%2$s)', 'dc-woocommerce-multi-vendor'), $tax_item['name'], $tax_class_name);
                                ?>
                                <th class="line_tax tips" data-tip="<?php echo esc_attr($column_tip); ?>">
                                    <?php echo esc_attr($column_label); ?>
                                    <input type="hidden" class="order-tax-id" name="order_taxes[<?php echo esc_attr($tax_id); ?>]" value="<?php echo esc_attr($tax_item['rate_id']); ?>">
                                </th>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </tr>
                </thead>
                <tbody id="order_line_items">
                    <?php
                    foreach ($line_items as $item_id => $item) {
                        do_action('wcmp_admin_commission_before_order_item_' . $item->get_type() . '_html', $item_id, $item, $order);

                        $product = $item->get_product();
                        $product_link = $product ? admin_url('post.php?post=' . $item->get_product_id() . '&action=edit') : '';
                        $thumbnail = $product ? apply_filters('wcmp_admin_commission_order_item_thumbnail', $product->get_image('thumbnail', array('title' => ''), false), $item_id, $item) : '';
                        $row_class = apply_filters('wcmp_admin_commission_html_order_item_class', !empty($class) ? $class : '', $item, $order);
                        ?>
                        <tr class="item <?php echo esc_attr($row_class); ?>" data-order_item_id="<?php echo esc_attr($item_id); ?>">
                            <td class="thumb">
                                <?php echo '<div class="wc-order-item-thumbnail">' . wp_kses_post($thumbnail) . '</div>'; ?>
                            </td>
                            <td class="name" data-sort-value="<?php echo esc_attr($item->get_name()); ?>">
                                <?php
                                echo $product_link ? '<a href="' . esc_url($product_link) . '" class="wc-order-item-name">' . esc_html($item->get_name()) . '</a>' : '<div class="wc-order-item-name">' . esc_html($item->get_name()) . '</div>';

                                if ($product && $product->get_sku()) {
                                    echo '<div class="wc-order-item-sku"><strong>' . esc_html__('SKU:', 'dc-woocommerce-multi-vendor') . '</strong> ' . esc_html($product->get_sku()) . '</div>';
                                }

                                if ($item->get_variation_id()) {
                                    echo '<div class="wc-order-item-variation"><strong>' . esc_html__('Variation ID:', 'dc-woocommerce-multi-vendor') . '</strong> ';
                                    if ('product_variation' === get_post_type($item->get_variation_id())) {
                                        echo esc_html($item->get_variation_id());
                                    } else {
                                        /* translators: %s: variation id */
                                        printf(esc_html__('%s (No longer exists)', 'dc-woocommerce-multi-vendor'), $item->get_variation_id());
                                    }
                                    echo '</div>';
                                }
                                ?>
                                <input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr($item_id); ?>" />
                                <input type="hidden" name="order_item_tax_class[<?php echo absint($item_id); ?>]" value="<?php echo esc_attr($item->get_tax_class()); ?>" />

                                <?php do_action('wcmp_admin_commission_before_order_itemmeta', $item_id, $item, $product); ?>
                                <?php
                                $hidden_order_itemmeta = apply_filters(
                                        'wcmp_admin_commission_hidden_order_itemmeta', array(
                                    '_qty',
                                    '_tax_class',
                                    '_product_id',
                                    '_variation_id',
                                    '_line_subtotal',
                                    '_line_subtotal_tax',
                                    '_line_total',
                                    '_line_tax',
                                    'method_id',
                                    '_vendor_item_commission',
                                    'cost',
                                        )
                                );
                                ?><div class="view">
                                <?php if ($meta_data = $item->get_formatted_meta_data('')) : ?>
                                        <table cellspacing="0" class="display_meta">
                                            <?php
                                            foreach ($meta_data as $meta_id => $meta) :
                                                if (in_array($meta->key, $hidden_order_itemmeta, true)) {
                                                    continue;
                                                }
                                                ?>
                                                <tr>
                                                    <th><?php echo wp_kses_post($meta->display_key); ?>:</th>
                                                    <td><?php echo wp_kses_post(force_balance_tags($meta->display_value)); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                                
                                <?php do_action('wcmp_admin_commission_after_order_itemmeta', $item_id, $item, $product); ?>
                            </td>

                            <?php do_action('wcmp_admin_commission_admin_order_item_values', $product, $item, absint($item_id)); ?>

                            <td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr($order->get_item_subtotal($item, false, true)); ?>">
                                <div class="view">
                                    <?php
                                    echo wc_price($order->get_item_total($item, false, true), array('currency' => $order->get_currency()));

                                    if ($item->get_subtotal() !== $item->get_total()) {
                                        echo '<span class="wc-order-item-discount">-' . wc_price(wc_format_decimal($order->get_item_subtotal($item, false, false) - $order->get_item_total($item, false, false), ''), array('currency' => $order->get_currency())) . '</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="quantity" width="1%">
                                <div class="view">
                                    <?php
                                    echo '<small class="times">&times;</small> ' . esc_html($item->get_quantity());

                                    if ($refunded_qty = $order->get_qty_refunded_for_item($item_id)) {
                                        echo '<small class="refunded">-' . ( $refunded_qty * -1 ) . '</small>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr($item->get_total()); ?>">
                                <div class="view">
                                    <?php
                                    echo wc_price($item->get_total(), array('currency' => $order->get_currency()));

                                    if ($item->get_subtotal() !== $item->get_total()) {
                                        echo '<span class="wc-order-item-discount">-' . wc_price(wc_format_decimal($item->get_subtotal() - $item->get_total(), ''), array('currency' => $order->get_currency())) . '</span>';
                                    }

                                    if ($refunded = $order->get_total_refunded_for_item($item_id)) {
                                        echo '<small class="refunded">-' . wc_price($refunded, array('currency' => $order->get_currency())) . '</small>';
                                    }
                                    ?>
                                </div>
                            </td>

                            <?php
                            if (( $tax_data = $item->get_taxes() ) && wc_tax_enabled()) {
                                foreach ($order_taxes as $tax_item) {
                                    $tax_item_id = $tax_item->get_rate_id();
                                    $tax_item_total = isset($tax_data['total'][$tax_item_id]) ? $tax_data['total'][$tax_item_id] : '';
                                    $tax_item_subtotal = isset($tax_data['subtotal'][$tax_item_id]) ? $tax_data['subtotal'][$tax_item_id] : '';
                                    ?>
                                    <td class="line_tax" width="1%">
                                        <div class="view">
                                            <?php
                                            if ('' !== $tax_item_total) {
                                                echo wc_price(wc_round_tax_total($tax_item_total), array('currency' => $order->get_currency()));
                                            } else {
                                                echo '&ndash;';
                                            }

                                            if ($item->get_subtotal() !== $item->get_total()) {
                                                if ('' === $tax_item_total) {
                                                    echo '<span class="wc-order-item-discount">&ndash;</span>';
                                                } else {
                                                    echo '<span class="wc-order-item-discount">-' . wc_price(wc_round_tax_total($tax_item_subtotal - $tax_item_total), array('currency' => $order->get_currency())) . '</span>';
                                                }
                                            }

                                            if ($refunded = $order->get_tax_refunded_for_item($item_id, $tax_item_id)) {
                                                echo '<small class="refunded">-' . wc_price($refunded, array('currency' => $order->get_currency())) . '</small>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <?php
                                }
                            }
                            ?>
                        </tr>


                        <?php
                        do_action('wcmp_admin_commission_order_item_' . $item->get_type() . '_html', $item_id, $item, $order);
                    }
                    do_action('wcmp_admin_commission_order_items_after_line_items', $order->get_id());
                    ?>
                </tbody>
                <tbody id="order_shipping_line_items">
                    <?php
                    $shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
                    foreach ($line_items_shipping as $item_id => $item) {
                        ?>
                        <tr class="shipping <?php echo (!empty($class) ) ? esc_attr($class) : ''; ?>" data-order_item_id="<?php echo esc_attr($item_id); ?>">
                            <td class="thumb"><div></div></td>

                            <td class="name">
                                <div class="view">
                                    <?php echo esc_html($item->get_name() ? $item->get_name() : __('Shipping', 'dc-woocommerce-multi-vendor') ); ?>
                                </div>
                                <div class="edit" style="display: none;">
                                    <input type="hidden" name="shipping_method_id[]" value="<?php echo esc_attr($item_id); ?>" />
                                    <input type="text" class="shipping_method_name" placeholder="<?php esc_attr_e('Shipping name', 'dc-woocommerce-multi-vendor'); ?>" name="shipping_method_title[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($item->get_name()); ?>" />
                                    <select class="shipping_method" name="shipping_method[<?php echo esc_attr($item_id); ?>]">
                                        <optgroup label="<?php esc_attr_e('Shipping method', 'dc-woocommerce-multi-vendor'); ?>">
                                            <option value=""><?php esc_html_e('N/A', 'dc-woocommerce-multi-vendor'); ?></option>
                                            <?php
                                            $found_method = false;

                                            foreach ($shipping_methods as $method) {
                                                $current_method = ( 0 === strpos($item->get_method_id(), $method->id) ) ? $item->get_method_id() : $method->id;

                                                echo '<option value="' . esc_attr($current_method) . '" ' . selected($item->get_method_id() === $current_method, true, false) . '>' . esc_html($method->get_method_title()) . '</option>';

                                                if ($item->get_method_id() === $current_method) {
                                                    $found_method = true;
                                                }
                                            }

                                            if (!$found_method && $item->get_method_id()) {
                                                echo '<option value="' . esc_attr($item->get_method_id()) . '" selected="selected">' . esc_html__('Other', 'dc-woocommerce-multi-vendor') . '</option>';
                                            } else {
                                                echo '<option value="other">' . esc_html__('Other', 'dc-woocommerce-multi-vendor') . '</option>';
                                            }
                                            ?>
                                        </optgroup>
                                    </select>
                                </div>

                                <?php do_action('wcmp_admin_commission_before_order_itemmeta', $item_id, $item, null); ?>
                                <?php
                                $hidden_order_itemmeta = apply_filters(
                                        'wcmp_admin_commission_hidden_order_itemmeta', array(
                                    '_qty',
                                    '_tax_class',
                                    '_product_id',
                                    '_variation_id',
                                    '_line_subtotal',
                                    '_line_subtotal_tax',
                                    '_line_total',
                                    '_line_tax',
                                    'method_id',
                                    'cost',
                                        )
                                );
                                ?><div class="view">
                                <?php if ($meta_data = $item->get_formatted_meta_data('')) : ?>
                                        <table cellspacing="0" class="display_meta">
                                            <?php
                                            foreach ($meta_data as $meta_id => $meta) :
                                                if (in_array($meta->key, $hidden_order_itemmeta, true)) {
                                                    continue;
                                                }
                                                ?>
                                                <tr>
                                                    <th><?php echo wp_kses_post($meta->display_key); ?>:</th>
                                                    <td><?php echo wp_kses_post(force_balance_tags($meta->display_value)); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                           
                                <?php do_action('wcmp_admin_commission_after_order_itemmeta', $item_id, $item, null); ?>
                            </td>

                            <?php do_action('wcmp_admin_commission_order_item_values', null, $item, absint($item_id)); ?>

                            <td class="item_cost" width="1%">&nbsp;</td>
                            <td class="quantity" width="1%">&nbsp;</td>

                            <td class="line_cost" width="1%">
                                <div class="view">
                                    <?php
                                    echo wc_price($item->get_total(), array('currency' => $order->get_currency()));
                                    $refunded = $order->get_total_refunded_for_item($item_id, 'shipping');
                                    if ($refunded) {
                                        echo '<small class="refunded">-' . wc_price($refunded, array('currency' => $order->get_currency())) . '</small>';
                                    }
                                    ?>
                                </div>
                                <div class="edit" style="display: none;">
                                    <input type="text" name="shipping_cost[<?php echo esc_attr($item_id); ?>]" placeholder="<?php echo esc_attr(wc_format_localized_price(0)); ?>" value="<?php echo esc_attr(wc_format_localized_price($item->get_total())); ?>" class="line_total wc_input_price" />
                                </div>
                                <div class="refund" style="display: none;">
                                    <input type="text" name="refund_line_total[<?php echo absint($item_id); ?>]" placeholder="<?php echo esc_attr(wc_format_localized_price(0)); ?>" class="refund_line_total wc_input_price" />
                                </div>
                            </td>

                            <?php
                            if (( $tax_data = $item->get_taxes() ) && wc_tax_enabled()) {
                                foreach ($order_taxes as $tax_item) {
                                    $tax_item_id = $tax_item->get_rate_id();
                                    $tax_item_total = isset($tax_data['total'][$tax_item_id]) ? $tax_data['total'][$tax_item_id] : '';
                                    ?>
                                    <td class="line_tax" width="1%">
                                        <div class="view">
                                            <?php
                                            echo ( '' !== $tax_item_total ) ? wc_price(wc_round_tax_total($tax_item_total), array('currency' => $order->get_currency())) : '&ndash;';
                                            $refunded = $order->get_tax_refunded_for_item($item_id, $tax_item_id, 'shipping');
                                            if ($refunded) {
                                                echo '<small class="refunded">-' . wc_price($refunded, array('currency' => $order->get_currency())) . '</small>';
                                            }
                                            ?>
                                        </div>
                                        <div class="edit" style="display: none;">
                                            <input type="text" name="shipping_taxes[<?php echo absint($item_id); ?>][<?php echo esc_attr($tax_item_id); ?>]" placeholder="<?php echo esc_attr(wc_format_localized_price(0)); ?>" value="<?php echo ( isset($tax_item_total) ) ? esc_attr(wc_format_localized_price($tax_item_total)) : ''; ?>" class="line_tax wc_input_price" />
                                        </div>
                                        <div class="refund" style="display: none;">
                                            <input type="text" name="refund_line_tax[<?php echo absint($item_id); ?>][<?php echo esc_attr($tax_item_id); ?>]" placeholder="<?php echo esc_attr(wc_format_localized_price(0)); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr($tax_item_id); ?>" />
                                        </div>
                                    </td>
                                    <?php
                                }
                            }
                            ?>
                        </tr>
                        <?php
                    }
                    do_action('wcmp_admin_commission_order_items_after_shipping', $order->get_id());
                    ?>
                </tbody>
                <tbody id="order_fee_line_items">
                    <?php
                    foreach ($line_items_fee as $item_id => $item) {
                        //include 'html-order-fee.php';
                    }
                    do_action('wcmp_admin_commission_order_items_after_fees', $order->get_id());
                    ?>
                </tbody>
                <tbody id="order_refunds">
                    <?php
                    if ($refunds = $order->get_refunds()) {
                        foreach ($refunds as $refund) { $who_refunded = new WP_User( $refund->get_refunded_by() );
                        $commission_refunds = get_post_meta( $refund->get_id(), '_refunded_commissions', true ); ?>
                            <tr class="refund Zero Rate" data-order_refund_id="<?php echo esc_attr( $refund->get_id() ); ?>">
                                <td class="thumb"><div></div></td>

                                <td class="name">
                                <?php
                                if ( $who_refunded->exists() ) {
                                    printf(
                                        /* translators: 1: refund id 2: refund date 3: username */
                                        esc_html__( 'Refund #%1$s - %2$s by %3$s', 'dc-woocommerce-multi-vendor' ),
                                        $refund->get_id(),
                                        wc_format_datetime( $refund->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) ),
                                        sprintf(
                                                '<abbr class="refund_by" title="%1$s">%2$s</abbr>',
                                                /* translators: 1: ID who refunded */
                                                sprintf( esc_attr__( 'ID: %d', 'dc-woocommerce-multi-vendor' ), absint( $who_refunded->ID ) ),
                                                esc_html( $who_refunded->display_name )
                                        )
                                    );
                                } else {
                                    printf(
                                        /* translators: 1: refund id 2: refund date */
                                        esc_html__( 'Refund #%1$s - %2$s', 'dc-woocommerce-multi-vendor' ),
                                        $refund->get_id(),
                                        wc_format_datetime( $refund->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) )
                                    );
                                }
                                ?>
                                <?php if ( $refund->get_reason() ) : ?>
                                        <p class="description"><?php echo esc_html( $refund->get_reason() ); ?></p>
                                <?php endif; ?>
                                <input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( $refund->get_id() ); ?>" />
                                </td>

                                <td class="quantity" width="1%">&nbsp;</td>

                                <td class="line_cost" width="1%">
                                    <div class="view">
                                        <?php $refund_amt_data = ($commission_refunds && isset($commission_refunds[$post_id])) ? $commission_refunds[$post_id] : array();
                                        $refund_amt = array_sum($refund_amt_data);
                                        echo wc_price( $refund_amt ) ?>
                                    </div>
                                </td>

                                <td class="line_tax" width="1%"></td>
                                <td class="wc-order-edit-line-item"></td>
                            </tr>
                        <?php }
                        do_action('wcmp_admin_commission_order_items_after_refunds', $order->get_id());
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
            <div class="wc-used-coupons">
                <ul class="wc_coupon_list">
                    <?php if ( 0 < $order->get_total_discount() && get_post_meta($post_id, '_commission_include_coupon', true) ) : ?>
                    <li><em>* <?php esc_html_e( 'Commission calculated including coupon.', 'dc-woocommerce-multi-vendor' ); ?></em></li>
                    <?php endif; 
                    if ( 0 < get_post_meta($post_id, '_shipping', true) && get_post_meta($post_id, '_commission_total_include_shipping', true) ) : ?>
                    <li><em>** <?php esc_html_e( 'Commission total calcutated including shipping charges.', 'dc-woocommerce-multi-vendor' ); ?></em></li>
                    <?php endif; 
                    if ( 0 < get_post_meta($post_id, '_tax', true) && get_post_meta($post_id, '_commission_total_include_tax', true) ) : ?>
                    <li><em>** <?php esc_html_e( 'Commission total calcutated including tax charges.', 'dc-woocommerce-multi-vendor' ); ?></em></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <table class="wc-order-totals">
                <?php $commission_amount = get_post_meta( $post_id, '_commission_amount', true );
                if ($commission_amount != 0) : ?>
                    <tr>
                        <td class="label"><?php if ( 0 < $order->get_total_discount() && get_post_meta($post_id, '_commission_include_coupon', true) ) : ?>*<?php endif; ?><?php esc_html_e('Commission:', 'dc-woocommerce-multi-vendor'); ?></td>
                        <td width="1%"></td>
                        <td class="total">
                            <?php echo $vendor_order->get_formatted_commission_total(); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ($order->get_shipping_methods()) : ?>
                    <tr>
                        <td class="label"><?php esc_html_e('Shipping:', 'dc-woocommerce-multi-vendor'); ?></td>
                        <td width="1%"></td>
                        <td class="total">
                            <?php 
                            $refunded = $order->get_total_shipping_refunded();
                            if ($refunded > 0) {
                                echo '<del>' . strip_tags(wc_price($order->get_shipping_total(), array('currency' => $order->get_currency()))) . '</del> <ins>' . wc_price($order->get_shipping_total() - $refunded, array('currency' => $order->get_currency())) . '</ins>'; // WPCS: XSS ok.
                            } else {
                                echo wc_price($order->get_shipping_total(), array('currency' => $order->get_currency())); // WPCS: XSS ok.
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php do_action('wcmp_admin_commission_order_totals_after_shipping', $order->get_id()); ?>

                <?php if (wc_tax_enabled()) : ?>
                    <?php foreach ($order->get_tax_totals() as $code => $tax) : ?>
                        <tr>
                            <td class="label"><?php echo esc_html($tax->label); ?>:</td>
                            <td width="1%"></td>
                            <td class="total">
                                <?php
                                $refunded = $order->get_total_tax_refunded_by_rate_id($tax->rate_id);
                                if ($refunded > 0) {
                                    echo '<del>' . strip_tags($tax->formatted_amount) . '</del> <ins>' . wc_price(WC_Tax::round($tax->amount, wc_get_price_decimals()) - WC_Tax::round($refunded, wc_get_price_decimals()), array('currency' => $order->get_currency())) . '</ins>'; // WPCS: XSS ok.
                                } else {
                                    echo wp_kses_post($tax->formatted_amount);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php do_action('wcmp_admin_commission_order_totals_after_tax', $order->get_id()); ?>

                <tr>
                    <td class="label"><?php esc_html_e('**Total', 'dc-woocommerce-multi-vendor'); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
                        <?php $commission_total = get_post_meta( $post_id, '_commission_total', true );
                        $is_migration_order = get_post_meta($order_id, '_order_migration', true); // backward compatibility
                        if(!$is_migration_order && $commission_total != self::commission_totals($post_id, 'edit')){
                            echo '<del>' . wc_price($commission_total, array('currency' => $order->get_currency())) . '</del> <ins>' . self::commission_totals($post_id).'</ins>'; 
                        }else{
                            echo self::commission_totals($post_id);
                        }
                        ?>
                    </td>
                </tr>

                <?php do_action('wcmp_admin_commission_order_totals_after_total', $order->get_id()); ?>

                <?php if (get_post_meta( $post_id, '_commission_refunded', true )) : ?>
                    <tr>
                        <td class="label refunded-total"><?php esc_html_e('Refunded', 'dc-woocommerce-multi-vendor'); ?>:</td>
                        <td width="1%"></td>
                        <td class="total refunded-total"><?php echo wc_price(get_post_meta( $post_id, '_commission_refunded', true ), array('currency' => $order->get_currency())); ?></td>
                    </tr>
                <?php endif; ?>

                <?php do_action('wcmp_admin_commission_order_totals_after_refunded', $order->get_id()); ?>

            </table>
            <div class="clear"></div>
        </div>
        <?php
        endif;
    }

    /**
     * Display commission notes
     */
    public function wcmp_meta_box_commission_notes() {
        global $post;
        $notes = $this->get_commission_notes($post->ID);
        if ($notes) {
            foreach ($notes as $note) {
                echo '<div class="wcmp_commision_note_clm">';
                echo '<p>' . $note->comment_content . '</p>';
                echo '<small>' . $note->comment_date . '</small>';
                echo '</div>';
            }
        }
    }

    public static function add_commission_note($commission_id, $note, $vendor_id = 0) {
        if (!$commission_id) {
            return 0;
        }

        $comment_author = __('WCMp', 'dc-woocommerce-multi-vendor');
        $comment_author_email = strtolower(__('WCMp', 'dc-woocommerce-multi-vendor')) . '@';
        $comment_author_email .= isset($_SERVER['HTTP_HOST']) ? str_replace('www.', '', $_SERVER['HTTP_HOST']) : 'noreply.com';
        $comment_author_email = sanitize_email($comment_author_email);

        $commentdata = apply_filters('wcmp_new_commission_note_data', array(
            'comment_post_ID' => $commission_id,
            'comment_author' => $comment_author,
            'comment_author_email' => $comment_author_email,
            'comment_author_url' => '',
            'comment_content' => $note,
            'comment_agent' => 'WCMp',
            'comment_type' => 'commission_note',
            'comment_parent' => 0,
            'comment_approved' => 1,
                ), $commission_id, $vendor_id);
        $comment_id = wp_insert_comment($commentdata);
        if ($vendor_id) {
            add_comment_meta($comment_id, '_vendor_id', $vendor_id);

            do_action('wcmp_new_commission_note', $comment_id, $commission_id, $vendor_id);
        }
        return $comment_id;
    }

    public function get_commission_notes($commission_id) {
        global $WCMp;
        $args = array(
            'post_id' => $commission_id,
            'type' => 'commission_note',
            'status' => 'approve',
            'orderby' => 'comment_ID'
        );

        remove_filter('comments_clauses', array($WCMp, 'exclude_order_comments'), 10, 1);
        $notes = get_comments($args);
        add_filter('comments_clauses', array($WCMp, 'exclude_order_comments'), 10, 1);
        return $notes;
    }

    /**
     * Add custom field to commission posts
     *
     * @return arr Array of custom fields
     */
    public function get_custom_fields_settings($post_id) {
        $fields = array();

        $fields['_commission_order_id'] = array(
            'name' => __('Order ID:', 'dc-woocommerce-multi-vendor'),
            'description' => __('The order ID of Commission (' . get_woocommerce_currency_symbol() . ').', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'default' => '',
            'section' => 'wcmp-commission-data'
        );

        $fields['_commission_product'] = array(
            'name' => __('Product:', 'dc-woocommerce-multi-vendor'),
            'description' => __('The product purchased that generated this commission.', 'dc-woocommerce-multi-vendor'),
            'type' => 'select',
            'default' => '',
            'section' => 'wcmp-commission-data'
        );

        $fields['_commission_vendor'] = array(
            'name' => __('Vendor:', 'dc-woocommerce-multi-vendor'),
            'description' => __('The vendor who receives this commission.', 'dc-woocommerce-multi-vendor'),
            'type' => 'select',
            'default' => '',
            'section' => 'wcmp-commission-data'
        );

        $fields['_commission_amount'] = array(
            'name' => __('Amount:', 'dc-woocommerce-multi-vendor'),
            'description' => __('The total value of this commission (' . get_woocommerce_currency_symbol() . ').', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'default' => 0.00,
            'section' => 'wcmp-commission-data'
        );

        if (get_post_meta($post_id, '_paid_status', true) == 'paid') {
            $fields['_commission_amount']['type'] = 'price';
            $fields['_commission_amount']['description'] = __('The total value of this commission.', 'dc-woocommerce-multi-vendor');
        }

        $fields['_shipping'] = array(
            'name' => __('Shipping Amount:', 'dc-woocommerce-multi-vendor'),
            'description' => __('The total value of shipping.', 'dc-woocommerce-multi-vendor'),
            'type' => 'price',
            'default' => 0.00,
            'section' => 'wcmp-commission-data'
        );

        $fields['_tax'] = array(
            'name' => __('Tax Amount:', 'dc-woocommerce-multi-vendor'),
            'description' => __('The total value of this tax.', 'dc-woocommerce-multi-vendor'),
            'type' => 'price',
            'default' => 0.00,
            'section' => 'wcmp-commission-data'
        );

        return apply_filters('custom_fields_for_commission', $fields);
    }

    /**
     * Save meta box on commission posts
     *
     * @param  int $post_id Commission ID
     * @return void
     */
    public function meta_box_save($post_id) {
        global $wpdb;

        // Verify nonce
        if (( get_post_type() != $this->post_type ) || !wp_verify_nonce($_POST[$this->post_type . '_nonce'], plugin_basename($this->dir))) {
            return $post_id;
        }

        // Verify user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        $is_updated = false;
        $prev_commission_amount = get_post_meta($post_id, '_commission_amount', true);
        if (isset($_POST['_commission_amount'])) {
            $is_updated = update_post_meta($post_id, '_commission_amount', floatval($_POST['_commission_amount']));
        }
        
        $order_id = get_post_meta($post_id, '_commission_order_id', true);
        $order = wc_get_order($order_id);
        if (isset($_POST['commission_status']) && in_array($_POST['commission_status'], array('refunded', 'partial_refunded'))) {
            if($order && $order->get_refunds()){
                update_post_meta($post_id, '_paid_status', $_POST['commission_status']);
            }else{
                set_transient('wcmp_comm_save_status_'.$post_id, __('Please make order refundable first.', 'dc-woocommerce-multi-vendor'), MINUTE_IN_SECONDS);
            }
        }elseif(isset($_POST['commission_status'])){
            if( $_POST['commission_status'] == 'paid' ) {
                $this->wcmp_mark_commission_paid( array( $post_id ) ) ;
            }
            update_post_meta($post_id, '_paid_status', $_POST['commission_status']);
        }
        
        do_action('wcmp_save_vendor_commission', $post_id, $is_updated, $_POST);
    }

    /**
     * Add custom actions to commission posts
     * @return void
     */
    public function custom_actions_content() {
        global $post;
        if (get_post_type($post) == $this->post_type) {
            echo '<div class="misc-pub-section misc-pub-section-last">';
            wp_nonce_field(plugin_basename($this->file), 'paid_status_nonce');

            $status = get_post_meta($post->ID, '_paid_status', true) ? get_post_meta($post->ID, '_paid_status', true) : 'unpaid';
            if ($status == 'unpaid') {
                echo '<input type="checkbox" name="_paid_status" id="_paid_status-paid" value="paid" ' . checked($status, 'paid', false) . '/> <label for="_paid_status-paid" class="select-it">' . __("Mark as Paid", 'dc-woocommerce-multi-vendor') . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
            } else if ($status == 'paid') {
                echo '<input type="checkbox" name="_paid_status" id="_paid_status-reverse" value="reverse" ' . checked($status, 'reverse', false) . '/> <label for="_paid_status-reverse" class="select-it">' . __("Mark as Reverse", 'dc-woocommerce-multi-vendor') . '</label>';
            } else if ($status == 'reverse') {
                echo '<label class="select-it">'.__( "Reversed", 'dc-woocommerce-multi-vendor' ).'</label>';
            }
            echo '</div>';
        }
    }

    /**
     * Save custom actions for commission posts
     * @param  int $post_id Commission ID
     * @return void
     */
    public function custom_actions_save($post_id) {
        global $WCMp;

        if (get_post_type($post_id) != $this->post_type) {
            return;
        }

        if (isset($_POST['paid_status_nonce'])) {
            if (!wp_verify_nonce($_POST['paid_status_nonce'], plugin_basename($this->file))) {
                return $post_id;
            }
            if (isset($_POST['_paid_status'])) {
                $status = $_POST['_paid_status'];
                if ($status == 'paid') {
                    $commission = $this->get_commission($post_id);
                    $vendor = $commission->vendor;
                    $payment_method = get_user_meta($vendor->id, '_vendor_payment_mode', true);
                    if ($payment_method) {
                        if (array_key_exists($payment_method, $WCMp->payment_gateway->payment_gateways)) {
                            $WCMp->payment_gateway->payment_gateways[$payment_method]->process_payment($vendor, array($post_id), 'admin');
                        } else {
                            set_transient("wcmp_commission_save_{$post_id}", array('message' => __('Invalid payment method', 'dc-woocommerce-multi-vendor'), 'type' => 'error'), 120);
                        }
                    } else {
                        set_transient("wcmp_commission_save_{$post_id}", array('message' => __('Please set payment method for this commission vendor', 'dc-woocommerce-multi-vendor'), 'type' => 'error'), 120);
                    }
                } else if ($status == 'reverse') {
                    update_post_meta($post_id, '_paid_status', $status, 'paid');
                }
            }
        }
    }

    public function wcmp_commission_update_notice() {
        global $post;
        if ($post && $message = get_transient("wcmp_commission_save_{$post->ID}")) {
            echo '<div class="' . $message['type'] . '">';
            echo '<p>' . $message['message'] . '</p>';
            echo '</div>';
            delete_transient("wcmp_commission_save_{$post->ID}");
        }
    }

    /**
     * Add columns to commissions list table
     * @param  arr $defaults Default columns
     * @return arr           New columns
     */
    public function wcmp_register_custom_column_headings($defaults) {
        $new_columns = array(
            '_commission_order_id' => __('Order ID', 'dc-woocommerce-multi-vendor'),
            '_commission_product' => __('Product', 'dc-woocommerce-multi-vendor'),
            '_commission_vendor' => __('Vendor', 'dc-woocommerce-multi-vendor'),
            '_commission_amount' => __('Amount', 'dc-woocommerce-multi-vendor'),
            '_commission_earning' => __('Net Earning', 'dc-woocommerce-multi-vendor'),
            '_paid_status' => __('Status', 'dc-woocommerce-multi-vendor'),
        );

        $last_item = '';

        if (count($defaults) > 2) {
            $last_item = array_slice($defaults, -1);

            array_pop($defaults);
        }
        $defaults = array_merge($defaults, $new_columns);

        if ($last_item != '') {
            foreach ($last_item as $k => $v) {
                $defaults[$k] = $v;
                break;
            }
        }
        return $defaults;
    }

    /**
     * Register new columns for commissions list table
     * @param  str $column_name Name of column
     * @param  int $id          ID of commission
     * @return void
     */
    public function wcmp_register_custom_columns($column_name, $id) {

        $data = get_post_meta($id, $column_name, true);
        
        $order_id = get_post_meta($id, '_commission_order_id', true);
        $order = wc_get_order($order_id);
        $vendor_order = ( $order ) ? wcmp_get_order( $order->get_id() ) : array();
        
        switch ($column_name) {

            case '_commission_product':
                if( $order && !$data ){
                    $line_items = $order->get_items( 'line_item' );
                    foreach ($line_items as $item_id => $item) {
                        $product = $item->get_product();
                        $name = ( $product ) ? $product->get_formatted_name() : $item->get_name();
                        echo ' &nbsp;[&nbsp;<a href="' . esc_url(get_edit_post_link($item->get_product_id())) . '">' . $name . '</a>&nbsp;]&nbsp;';
                    }
                }else{ // BW compatibilities
                    if (is_array($data)) {
                        foreach ($data as $dat) {
                            if (function_exists('wc_get_product')) {
                                $product = wc_get_product($dat);
                            } else {
                                $product = new WC_Product($dat);
                            }
                            if (is_object($product) && $product->get_formatted_name()) {
                                echo ' &nbsp;[&nbsp;<a href="' . esc_url(get_edit_post_link($product->get_id())) . '">' . $product->get_formatted_name() . '</a>&nbsp;]&nbsp;';
                            }
                        }
                    } else {
                        // support for previous versions
                        if ($data && strlen($data) > 0) {
                            if (function_exists('wc_get_product')) {
                                $product = wc_get_product($data);
                            } else {
                                $product = new WC_Product($data);
                            }
                            if (is_object($product) && $product->get_formatted_name()) {
                                echo ' <a href="' . esc_url(get_edit_post_link($product->get_id())) . '">' . $product->get_formatted_name() . '</a>';
                            }
                        }
                    }
                }
                break;

            case '_commission_order_id':
                if ($data && strlen($data) > 0) {
                    $edit_url = 'post.php?post=' . $data . '&action=edit';
                    echo '<a href="' . esc_url($edit_url) . '">#' . $data . '</a>';
                }
                break;

            case '_commission_vendor':
                if( $vendor_order && !$data ){
                    $vendor = $vendor_order->get_vendor();
                    echo '<a href="' . esc_url($vendor->permalink) . '">' . $vendor->page_title . '</a>';
                }else{ // BW compatibilities
                    if ($data && strlen($data) > 0) {
                        $vendor_user_id = get_term_meta($data, '_vendor_user_id', true);
                        if ($vendor_user_id) {
                            $vendor = get_wcmp_vendor($vendor_user_id);
                            $edit_url = get_edit_user_link($vendor_user_id);
                            echo '<a href="' . esc_url($edit_url) . '">' . $vendor->page_title . '</a>';
                        }
                    }
                }
                break;

            case '_commission_amount':
                echo wc_price($data);
                break;
            
            case '_commission_earning':
                if( $vendor_order && !$data ){
                    echo $vendor_order->get_commission_total();
                }else{ // BW compatibilities
                    $commission_vendor = get_post_meta($id, '_commission_vendor', true);
                    $vendor_user_id = get_term_meta($commission_vendor, '_vendor_user_id', true);
                    $vendor = get_wcmp_vendor($vendor_user_id);
                    if($vendor){
                        $vendor_total = get_wcmp_vendor_order_amount(array('vendor_id' => $vendor->id, 'order_id' => $order_id));
                        echo wc_price($vendor_total['total']);
                    }
                }
               
                break;

            case '_paid_status':
                echo ucfirst($data);
                break;

            default:
                break;
        }
    }

    public function register_commission_bulk_actions($bulk_actions) {
        if (isset($bulk_actions['edit'])) {
            unset($bulk_actions['edit']);
        }
        if (isset($bulk_actions['untrash'])) {
            unset($bulk_actions['untrash']);
        }

        $bulk_actions['mark_paid'] = __('Mark paid', 'dc-woocommerce-multi-vendor');
        $bulk_actions['export'] = __('Export', 'dc-woocommerce-multi-vendor');
        return apply_filters('wcmp_commission_bulk_action', $bulk_actions);
    }

    public function commission_bulk_action_handler($redirect_to, $doaction, $post_ids) {
        if ($doaction == 'mark_paid') {
            $this->wcmp_mark_commission_paid($post_ids);
        } else if ($doaction == 'export') {
            $this->wcmp_generate_commissions_csv($post_ids);
        }
        return apply_filters('wcmp_commission_bulk_action_handler', $redirect_to, $doaction, $post_ids);
    }

    /**
     * Create export CSV for unpaid commissions
     * @return void
     */
    public function wcmp_generate_commissions_csv($post_ids) {
        // Security check
        check_admin_referer('bulk-posts');
        // Set filename
        $date = date('Y-m-d H:i:s');
        $filename = 'Commissions ' . $date . '.csv';
        // Set page headers to force download of CSV
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
        // Set CSV headers
        $headers = apply_filters('wcmp_vendor_commission_data_header',array(
            'Recipient',
            'Currency',
            'Commission',
            'Shipping',
            'Tax',
            'Total',
            'Status',
        ));
        $commissions_data = array();
        $currency = get_woocommerce_currency();
        foreach ($post_ids as $commission) {
            $commission_data = $this->get_commission($commission);
            $commission_staus = get_post_meta($commission, '_paid_status', true);
            
            //$commission_amounts = get_wcmp_vendor_order_amount(array('vendor_id' => $commission_data->vendor->id, 'commission_id' => $commission));
            $recipient = get_user_meta($commission_data->vendor->id, '_vendor_paypal_email', true) ? get_user_meta($commission_data->vendor->id, '_vendor_paypal_email', true) : $commission_data->vendor->page_title;
            $commission_amount = get_post_meta( $commission, '_commission_amount', true ) ? get_post_meta( $commission, '_commission_amount', true ) : 0;
            $shipping_amount = get_post_meta( $commission, '_shipping', true ) ? get_post_meta( $commission, '_shipping', true ) : 0;
            $tax_amount = get_post_meta( $commission, '_tax', true ) ? get_post_meta( $commission, '_tax', true ) : 0;
            $commission_total = get_post_meta( $commission, '_commission_total', true ) ? get_post_meta( $commission, '_commission_total', true ) : 0;
            $commission_order = get_post_meta($commission, '_commission_order_id', true) ? wc_get_order(get_post_meta($commission, '_commission_order_id', true)) : false;
            if($commission_order) $currency = $commission_order->get_currency();
            $commissions_data[] = apply_filters('wcmp_vendor_commission_data', array(
                $recipient,
                $currency,
                $commission_amount,
                $shipping_amount,
                $tax_amount,
                $commission_total,
                $commission_staus
            ), $commission_data);
        }
        // Initiate output buffer and open file
        ob_start();
        $file = fopen("php://output", 'w');
        // Add headers to file
        fputcsv($file, $headers);
        // Add data to file
        foreach ($commissions_data as $commission) {
            fputcsv($file, $commission);
        }
        // Close file and get data from output buffer
        fclose($file);
        $csv = ob_get_clean();
        // Send CSV to browser for download
        echo $csv;
        die();
    }

    /**
     * Pay commisssion by admin
     * @param array $post_ids
     */
    public function wcmp_mark_commission_paid($post_ids) {
        global $WCMp;
        $commission_to_pay = array();
        foreach ($post_ids as $post_id) {
            $commission = $this->get_commission($post_id);
            $vendor = $commission->vendor;
            $commission_status = get_post_meta($post_id, '_paid_status', true);
            if (in_array($commission_status, array( 'unpaid', 'partial_refunded' ))) {
                $commission_to_pay[$vendor->term_id][] = $post_id;
            }
        }
        if ($commission_to_pay) {
            foreach ($commission_to_pay as $vendor_term_id => $commissions) {
                $vendor = get_wcmp_vendor_by_term($vendor_term_id);
                $payment_method = get_user_meta($vendor->id, '_vendor_payment_mode', true);
                if ($payment_method) {
                    if (array_key_exists($payment_method, $WCMp->payment_gateway->payment_gateways)) {
                        $WCMp->payment_gateway->payment_gateways[$payment_method]->process_payment($vendor, $commissions, 'admin');
                    }
                }
            }
        }
    }

    /**
     * Get commission details
     * @param  int $commission_id Commission ID
     * @return obj                Commission object
     */
    function get_commission($commission_id = 0) {
        $commission = false;

        if ($commission_id > 0) {
            // Get post data
            $commission = get_post($commission_id);
            $commission_order_id = get_post_meta($commission_id, '_commission_order_id', true);
            $created_via_wcmp_order = get_post_meta($commission_order_id, '_created_via', true);
            $vendor_id = get_post_meta($commission_order_id, '_vendor_id', true);
            if($created_via_wcmp_order == 'wcmp_vendor_order'){
                $order = wc_get_order($commission_order_id);
                $line_items = $order->get_items( 'line_item' );
                $products = array();
                foreach ($line_items as $item_id => $item) {
                    $products[] = $item->get_product_id();
                }
                $vendor = get_wcmp_vendor($vendor_id);
                // Get meta data
                $commission->product = $products;
                $commission->vendor = $vendor;
            }else{
                // Get meta data
                $commission->product = get_post_meta($commission_id, '_commission_product', true);
                $commission->vendor = get_wcmp_vendor_by_term(get_post_meta($commission_id, '_commission_vendor', true));
            }
            
            $commission->amount = apply_filters('wcmp_post_commission_amount', get_post_meta($commission_id, '_commission_amount', true), $commission_id);
            $commission->paid_status = get_post_meta($commission_id, '_paid_status', true);
        }

        return $commission;
    }

    /**
     * Show custom filters to filter orders by status/customer.
     *
     * @access public
     * @return void
     */
    function wcmp_woocommerce_restrict_manage_orders() {
        global $woocommerce, $typenow, $wp_query, $WCMp;

        if ($typenow != $this->post_type)
            return;

        // Commission Satus
        ?>
        <select name='commission_status' id='dropdown_commission_status'>
            <option value=""><?php _e('Show Commission Status', 'dc-woocommerce-multi-vendor'); ?></option>
            <?php $commission_statuses = wcmp_get_commission_statuses(); 
            foreach( $commission_statuses as $key => $label ) { 
                echo "<option value='{$key}'>{$label}</option>";
            }
            ?>
        </select>
        <?php
        // By Commission vendor
        $vendor_dd_html = '<select name="commission_vendor" id="dropdown_commission_vendor"><option value="">'.__("Show All Vendors", "dc-woocommerce-multi-vendor").'</option>';
        $vendors = get_wcmp_vendors();
        if($vendors) :
            foreach ($vendors as $vendor) {
                $vendor_dd_html .= '<option value="'.$vendor->term_id.'">'.$vendor->page_title.'</option>';
            }
        endif;
        $vendor_dd_html .= '</select>';
        echo $vendor_dd_html;
    }

    /**
     * Filter the orders by the posted customer.
     *
     * @access public
     * @param mixed $vars
     * @return array
     */
    function wcmp_woocommerce_orders_by_customer_query($vars) {
        global $typenow, $wp_query;
        if ($typenow == $this->post_type && isset($_GET['commission_status']) && !empty($_GET['commission_status'])) {
            $vars['meta_key'] = '_paid_status';
            $vars['meta_value'] = $_GET['commission_status'];
        }
        // by vendor
        if ($typenow == $this->post_type && isset($_GET['commission_vendor']) && !empty($_GET['commission_vendor'])) {
            $vars['meta_key'] = '_commission_vendor';
            $vars['meta_value'] = $_GET['commission_vendor'];
        }
        return $vars;
    }

    function commission_post_types_admin_order($wp_query) {
        if (is_admin()) {
            // Get the post type from the query
            if (isset($wp_query->query['post_type'])) {
                $post_type = $wp_query->query['post_type'];
                if ($post_type == $this->post_type) {
                    $wp_query->set('orderby', 'ID');
                    $wp_query->set('order', 'DESC');
                }
            }
        }
    }

    function wcmp_commission_delete_on_order_deleted($order_id) {
        $vendor_order = wcmp_get_order($order_id);
        if($vendor_order){
            $commission_id = $vendor_order->get_prop('_commission_id');
            wp_delete_post( $commission_id, true );
        }
    }
    
    public function wcmp_commission_notices() {
        if(isset($_GET['post']) && get_transient('wcmp_comm_save_status_' .  absint($_GET['post']))){
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo get_transient('wcmp_comm_save_status_' .  absint($_GET['post'])); ?></p>
        </div>
        <?php
        delete_transient('wcmp_comm_save_status_' .  absint($_GET['post']));
        }
    }
    
    /**
     * Get Unpaid commission totals data
     * @param string $type
     * @return array 
     */
    public static function get_unpaid_commissions_total_data( $type = 'withdrawable' ) {
        global $WCMp;
        $vendor = get_wcmp_vendor( get_current_user_id() );
        if( !$vendor ) return false;
        $vendor_id = $vendor->id;
        $args = array(
            'post_type' => 'dc_commission',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_commission_vendor',
                    'value' => absint( $vendor->term_id ),
                    'compare' => '='
                ),
                array(
                    'key' => '_paid_status',
                    'value' => array('unpaid', 'partial_refunded'),
                    'compare' => 'IN'
                ),
            ),
	);
   
        $commissions = new WP_Query( apply_filters( 'wcmp_get_unpaid_commissions_total_data_query_args', $args, $type, $vendor ) );
        if( $commissions->get_posts() ) :
            $commission_amount = $shipping_amount = $tax_amount = $total = 0;
            $commission_posts = apply_filters( 'wcmp_get_unpaid_commissions_total_data_query_posts', $commissions->get_posts(), $vendor );
            foreach ( $commission_posts as $commission_id ) {
                if( $type == 'withdrawable' ){
                    $order_id = wcmp_get_commission_order_id( $commission_id );
                    $order = wc_get_order( $order_id );
                    if( $order ) {
                        if ( is_commission_requested_for_withdrawals( $commission_id ) || in_array( $order->get_status('edit'), array( 'on-hold', 'pending', 'failed', 'refunded', 'cancelled' ) ) ) {
                            continue; // calculate only available withdrawable balance
                        }
                    }
                }
                $commission_amount += self::commission_amount_totals( $commission_id, 'edit' );
                $shipping_amount += self::commission_shipping_totals( $commission_id, 'edit' );
                $tax_amount += self::commission_tax_totals( $commission_id, 'edit' );
            }
            $check_caps = apply_filters( 'wcmp_get_unpaid_commissions_total_data_vendor_check_caps', true, $vendor );
                    
            if( $check_caps && $vendor_id ){
                $amount = array(
                    'commission_amount' => $commission_amount,
                );
                if ($WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($vendor_id, '_vendor_give_shipping', true)) {
                    $amount['shipping_amount'] = $shipping_amount;
                } else {
                    $amount['shipping_amount'] = 0;
                }
                if ($WCMp->vendor_caps->vendor_payment_settings('give_tax') && !get_user_meta($vendor_id, '_vendor_give_tax', true)) {
                    $amount['tax_amount'] = $tax_amount;
                } else {
                    $amount['tax_amount'] = 0;
                }
                $amount['total'] = $amount['commission_amount'] + $amount['shipping_amount'] + $amount['tax_amount'];
                return $amount;
            }else{
                return array(
                    'commission_amount' => $commission_amount,
                    'shipping_amount' => $shipping_amount,
                    'tax_amount' => $tax_amount,
                    'total' => $commission_amount + $shipping_amount + $tax_amount
                );
            }
        endif;
    }

}
