<?php
if (!defined('ABSPATH'))
    exit;

/**
 * @class 		WCMp Vendor Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Vendor {

    public $id;
    public $taxonomy;
    public $term;
    public $user_data;
    public $shipping_class_id;

    /**
     * Get the vendor if UserID is passed, otherwise the vendor is new and empty.
     *
     * @access public
     * @param string $id (default: '')
     * @return void
     */
    public function __construct($id = '') {

        $this->taxonomy = 'dc_vendor_shop';

        $this->term = false;

        if ($id > 0) {
            $this->get_vendor($id);
        }
    }

    public function get_reviews_and_rating($offset = 0, $posts_per_page = 0, $args = array()) {
        global $WCMp, $wpdb;
        $vendor_id = $this->id;
        $posts_per_page = $posts_per_page ? $posts_per_page : get_option('posts_per_page');
        if (empty($vendor_id) || $vendor_id == '' || $vendor_id == 0) {
            return 0;
        } else {
            $args_default = array(
                'status' => 'approve',
                'type' => 'wcmp_vendor_rating',
                'count' => false,
                'number' => $posts_per_page,
                'offset' => $offset,
                'meta_key' => 'vendor_rating_id',
                'meta_value' => $vendor_id,
                'author__not_in' => array($this->id)
            );
            $args_default = wp_parse_args($args, $args_default);
            $args = apply_filters('wcmp_vendor_review_rating_args_to_fetch', $args_default, $this);
            return get_comments($args);
        }
    }

    public function get_review_count() {
        global $WCMp, $wpdb;
        $vendor_id = $this->id;
        if (empty($vendor_id) || $vendor_id == '' || $vendor_id == 0) {
            return 0;
        } else {
            $args_default = array(
                'status' => 'approve',
                'type' => 'wcmp_vendor_rating',
                'count' => true,
                'meta_key' => 'vendor_rating_id',
                'meta_value' => $vendor_id,
                'author__not_in' => array($this->id)
            );
            $args = apply_filters('wcmp_vendor_review_rating_args_to_fetch', $args_default, $this);
            return get_comments($args);
        }
    }

    /**
     * Gets an Vendor User from the database.
     *
     * @access public
     * @param int $id (default: 0)
     * @return bool
     */
    public function get_vendor($id = 0) {
        if (!$id) {
            return false;
        }

        if (!is_user_wcmp_vendor($id)) {
            return false;
        }

        if ($result = get_userdata($id)) {
            $this->populate($result);
            return true;
        }
        return false;
    }

    /**
     * Populates an Vendor from the loaded user data.
     *
     * @access public
     * @param mixed $result
     * @return void
     */
    public function populate($result) {

        $this->id = $result->ID;
        $this->user_data = $result;
    }

    /**
     * __isset function.
     *
     * @access public
     * @param mixed $key
     * @return bool
     */
    public function __isset($key) {
        global $WCMp;

        if (!$this->id) {
            return false;
        }

        if (in_array($key, array('term_id', 'page_title', 'page_slug', 'link'))) {
            if ($term_id = get_user_meta($this->id, '_vendor_term_id', true)) {
                return term_exists(absint($term_id), $WCMp->taxonomy->taxonomy_name);
            } else {
                return false;
            }
        }

        return metadata_exists('user', $this->id, '_' . $key);
    }

    /**
     * __get function.
     *
     * @access public
     * @param mixed $key
     * @return mixed
     */
    public function __get($key) {
        if (!$this->id) {
            return false;
        }

        if ($key == 'page_title') {

            $value = $this->get_page_title();
        } elseif ($key == 'page_slug') {

            $value = $this->get_page_slug();
        } elseif ($key == 'permalink') {

            $value = $this->get_permalink();
        } else {
            // Get values or default if not set
            $value = get_user_meta($this->id, '_vendor_' . $key, true);
        }

        return $value;
    }

    /**
     * generate_term function
     * @access public
     * @return void
     */
    public function generate_term() {
        global $WCMp;
        if (!$this->term_id) {
            $term = wp_insert_term($this->user_data->user_login, $WCMp->taxonomy->taxonomy_name);
            if (!is_wp_error($term)) {
                update_user_meta($this->id, '_vendor_term_id', $term['term_id']);
                // insert page_title meta @ initial term generate
                update_user_meta($this->id, '_vendor_page_title', $this->user_data->user_login);
                update_term_meta($term['term_id'], '_vendor_user_id', $this->id);
                $this->term_id = $term['term_id'];
            } else if ($term->get_error_code() == 'term_exists') {
                update_user_meta($this->id, '_vendor_term_id', $term->get_error_data());
                update_term_meta($term->get_error_data(), '_vendor_user_id', $this->id);
                $this->term_id = $term->get_error_data();
            }
        }
    }

    public function generate_shipping_class() {
        if (!$this->shipping_class_id && apply_filters('wcmp_add_vendor_shipping_class', true)) {
            $shipping_term = wp_insert_term($this->user_data->user_login . '-' . $this->id, 'product_shipping_class');
            if (!is_wp_error($shipping_term)) {
                update_user_meta($this->id, 'shipping_class_id', $shipping_term['term_id']);
                add_term_meta($shipping_term['term_id'], 'vendor_id', $this->id);
                add_term_meta($shipping_term['term_id'], 'vendor_shipping_origin', get_option('woocommerce_default_country'));
            } else if ($shipping_term->get_error_code() == 'term_exists') {
                update_user_meta($this->id, 'shipping_class_id', $shipping_term->get_error_data());
                add_term_meta($shipping_term->get_error_data(), 'vendor_id', $this->id);
                add_term_meta($shipping_term->get_error_data(), 'vendor_shipping_origin', get_option('woocommerce_default_country'));
            }
        }
    }

    /**
     * update_page_title function
     * @access public
     * @param $title
     * @return boolean
     */
    public function update_page_title($title = '') {
        global $WCMp;
        $this->term_id = get_user_meta($this->id, '_vendor_term_id', true);
        if (!$this->term_id) {
            $this->generate_term();
        }
        if (!empty($title) && isset($this->term_id)) {
            if (!is_wp_error(wp_update_term($this->term_id, $WCMp->taxonomy->taxonomy_name, array('name' => $title)))) {
                return true;
            }
        }
        return false;
    }

    /**
     * update_page_slug function
     * @access public
     * @param $slug
     * @return boolean
     */
    public function update_page_slug($slug = '') {
        global $WCMp;
        $this->term_id = get_user_meta($this->id, '_vendor_term_id', true);
        if (!$this->term_id) {
            $this->generate_term();
        }
        if (!empty($slug) && isset($this->term_id)) {
            if (!is_wp_error(wp_update_term($this->term_id, $WCMp->taxonomy->taxonomy_name, array('slug' => $slug)))) {
                return true;
            }
        }
        return false;
    }

    /**
     * set_term_data function
     * @access public
     * @return void
     */
    public function set_term_data() {
        global $WCMp;
        //return if term is already set
        if ($this->term)
            return;

        if (isset($this->term_id)) {
            $term = get_term($this->term_id, $WCMp->taxonomy->taxonomy_name);
            if (!is_wp_error($term)) {
                $this->term = $term;
            }
        }
    }

    /**
     * get_page_title function
     * @access public
     * @return string
     */
    public function get_page_title() {
        $this->set_term_data();
        if ($this->term) {
            return $this->term->name;
        } else {
            return '';
        }
    }

    /**
     * get_page_slug function
     * @access public
     * @return string
     */
    public function get_page_slug() {
        $this->set_term_data();
        if ($this->term) {
            return $this->term->slug;
        } else {
            return '';
        }
    }

    /**
     * get_permalink function
     * @access public
     * @return string
     */
    public function get_permalink() {
        global $WCMp;

        $link = '';
        if (isset($this->term_id)) {
            $link = get_term_link(absint($this->term_id), $WCMp->taxonomy->taxonomy_name);
        }

        return apply_filters( 'wcmp_vendor_permalink', $link, $this );
    }
    
    /**
     * get_id function
     * @access public
     * @return integer 
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * get_term_id function
     * @access public
     * @return integer 
     */
    public function get_term_id() {
        return $this->term_id;
    }

    /**
     * Get all products belonging to vendor
     * @param  $args (default=array())
     * @return arr Array of product post objects
     */
    public function get_products($args = array()) {
        global $WCMp;
        $default = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'author' => $this->id,
            'tax_query' => array(
                array(
                    'taxonomy' => $WCMp->taxonomy->taxonomy_name,
                    'field' => 'term_id',
                    'terms' => absint($this->term_id)
                )
            )
        );

        $args = wp_parse_args($args, $default);
        return get_posts( apply_filters( 'wcmp_get_vendor_products_query_args', $args, $this->term_id ) );
    }
    
    /**
     * Get all products ids belonging to vendor
     * @param $clauses SQL clauses
     * @return arr Array of product ids
     */
    public function get_products_ids( $clauses = array() ) {
        global $wpdb;
        $default_clauses = array(
            'fields'    => $wpdb->prefix.'posts.ID',
            'where'     => "AND ".$wpdb->prefix."posts.post_status = 'publish' ",
            'groupby'   => $wpdb->prefix.'posts.ID',
            'orderby'   => $wpdb->prefix.'posts.post_date DESC',
            'limits'    => ''
        );
        $clauses = apply_filters( 'wcmp_get_products_ids_clauses_request', wp_parse_args( $clauses, $default_clauses ) );
        $fields   = isset( $clauses['fields'] ) ? $clauses['fields'] : '';
        $where    = isset( $clauses['where'] ) ? $clauses['where'] : '';
        $groupby  = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';
        $orderby  = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
        $limits   = isset( $clauses['limits'] ) ? $clauses['limits'] : '';
        $sql = "SELECT
                $fields
            FROM
                {$wpdb->prefix}posts
            LEFT JOIN {$wpdb->prefix}term_relationships ON(
                    {$wpdb->prefix}posts.ID = {$wpdb->prefix}term_relationships.object_id
                )
            WHERE
                1 = 1 AND(
                    {$wpdb->prefix}term_relationships.term_taxonomy_id IN( $this->term_id )
                ) AND {$wpdb->prefix}posts.post_author IN( $this->id ) AND {$wpdb->prefix}posts.post_type = 'product' $where
            GROUP BY
                $groupby
            ORDER BY
                $orderby $limits";

        return apply_filters( 'wcmp_get_products_ids', $wpdb->get_results( $sql ), $clauses, $this->id );
    }

    /**
     * get_orders function
     * @access public
     * @return array with order id
     */
    public function get_orders($no_of = false, $offset = false, $more_args = false) {
        if (!$no_of) {
            $no_of = -1;
        }
        $vendor_id = $this->id;
        $order_ids = $vendor_orders = array();
        if ($vendor_id > 0) {
            $args = array(
                'author'            => $vendor_id,
                'posts_per_page'    => $no_of,
                'meta_query' => array(
                    array(
                        'key' => '_commissions_processed',
                        'value' => 'yes',
                        'compare' => '='
                    )
                )
            );
            if ( $offset ) {
                $args['offset'] = $offset;
            }
            if ( $more_args ) {
                $args = wp_parse_args( $more_args, $args );
            }
            
            $vendor_orders = wcmp_get_orders( $args );
        }

        if ( $vendor_orders ) {
            foreach ( $vendor_orders as $order_id ) {
                if(get_post_status( $order_id ) === 'wc-cancelled') continue;
                $commission_id = get_post_meta( $order_id, '_commission_id', true );
                $order_ids[$commission_id] = $order_id;
            }
        }
        return $order_ids;
    }
    
    /**
     * get_unpaid_orders function
     * @access public
     * @return array with order id
     */
    public function get_unpaid_orders($no_of = false, $offset = false, $more_args = false) {
        if (!$no_of) {
            $no_of = -1;
        }
        $vendor_id = $this->id;
        $order_ids = $commissions = array();
        if ($vendor_id > 0) {
            $args = array(
                'post_type'         => 'dc_commission',
                'post_status'       => array('publish', 'private'),
                'posts_per_page'    => (int) $no_of,
                'fields'            => 'ids',
                'meta_query'        => array(
                    array(
                        'key'       => '_commission_vendor',
                        'value'     => absint( $this->term_id ),
                        'compare'   => '='
                    )
                )
            );
            if ( $offset ) {
                $args['offset'] = $offset;
            }
            if ( $more_args ) {
                $args = wp_parse_args( $more_args, $args );
            }
            
            $commissions = get_posts($args);
        }

        if ( $commissions ) {
            foreach ( $commissions as $commission_id ) {
                $order_id = get_post_meta( $commission_id, '_commission_order_id', true );
                $order_ids[$commission_id] = $order_id;
            }
        }
        return $order_ids;
    }

    /**
     * get_vendor_items_from_order function get items of a order belongs to a vendor
     * @access public
     * @param order_id , vendor term id 
     * @return array with order item detail
     */
    public function get_vendor_items_from_order($order_id, $term_id) {
        $item_dtl = array();
        $order = new WC_Order($order_id);
        if ($order) {
            $items = $order->get_items('line_item');
            if ($items) {
                foreach ($items as $item_id => $item) {
                    $product_id = wc_get_order_item_meta($item_id, '_product_id', true);

                    if ($product_id) {
                        if ($term_id > 0) {
                            $product_vendors = get_wcmp_product_vendors($product_id);
                            if (!empty($product_vendors) && $product_vendors->term_id == $term_id) {
                                $item_dtl[$item_id] = $item;
                            }
                        }
                    }
                }
            }
        }
        return $item_dtl;
    }

    /**
     * get_vendor_items_from_order function get items of a order belongs to a vendor
     * @access public
     * @param order_id , vendor term id 
     * @return array with order item detail
     */
    public function get_vendor_shipping_from_order($order_id, $term_id) {
        $order = new WC_Order($order_id);
        if ($order) {
            $items = $order->get_items('shipping');
        }
        return $items;
    }

    /**
     * get_vendor_orders_by_product function to get orders belongs to a vendor and a product
     * @access public
     * @param product id , vendor term id 
     * @return array with order id
     */
    public function get_vendor_orders_by_product($vendor_term_id, $product_id) {
        $order_dtl = array();
        if ($product_id && $vendor_term_id) {
            $commissions = false;
            $args = array(
                'post_type' => 'dc_commission',
                'post_status' => array('publish', 'private'),
                'posts_per_page' => -1,
                'order' => 'asc',
                'meta_query' => array(
                    array(
                        'key' => '_commission_vendor',
                        'value' => absint($vendor_term_id),
                        'compare' => '='
                    ),
                    array(
                        'key' => '_commission_product',
                        'value' => absint($product_id),
                        'compare' => 'LIKE'
                    ),
                ),
            );
            $commissions = get_posts($args);
            if (!empty($commissions)) {
                foreach ($commissions as $commission) {
                    $order_dtl[] = get_post_meta($commission->ID, '_commission_order_id', true);
                }
            }
        }
        return $order_dtl;
    }

    /**
     * get_vendor_commissions_by_product function to get orders belongs to a vendor and a product
     * @access public
     * @param product id , vendor term id 
     * @return array with order id
     */
    public function get_vendor_commissions_by_product($order_id, $product_id) {
        $order_dtl = array();
        if ($product_id && $order_id) {
            $commissions = false;
            $args = array(
                'post_type' => 'dc_commission',
                'post_status' => array('publish', 'private'),
                'posts_per_page' => -1,
                'order' => 'asc',
                'meta_query' => array(
                    array(
                        'key' => '_commission_order_id',
                        'value' => absint($order_id),
                        'compare' => '='
                    ),
                    array(
                        'key' => '_commission_vendor',
                        'value' => absint($this->term_id),
                        'compare' => '='
                    ),
                ),
            );
            $commissions = get_posts($args);

            if (!empty($commissions)) {
                foreach ($commissions as $commission) {
                    $order_dtl[] = $commission->ID;
                }
            }
        }
        return $order_dtl;
    }

    /**
     * vendor_order_item_table function to get the html of item table of a vendor.
     * @access public
     * @param order id , vendor term id 
     */
    public function vendor_order_item_table($order, $vendor_id, $is_ship = false) {
        global $WCMp;
        $vendor_items = $order->get_items( 'line_item' );
        foreach ($vendor_items as $item_id => $item) {
            $product = $item->get_product();
            $_product = apply_filters('wcmp_woocommerce_order_item_product', $product, $item);
            ?>
            <tr class="">
                <?php do_action('wcmp_before_vendor_order_item_table', $item, $order, $vendor_id, $is_ship); ?>
                <td scope="col" style="text-align:left; border: 1px solid #eee;" class="product-name">
                    <?php
                    if ($_product && !$_product->is_visible()) {
                        echo apply_filters('wcmp_woocommerce_order_item_name', $item->get_name(), $item);
                    } else {
                        echo apply_filters('woocommerce_order_item_name', sprintf('<a href="%s">%s</a>', get_permalink($item->get_product_id()), $item->get_name()), $item);
                    }
                    wc_display_item_meta($item);
                    ?>
                </td>
                <td scope="col" style="text-align:left; border: 1px solid #eee;">	
                    <?php
                    echo $item->get_quantity();
                    ?>
                </td>
                <td scope="col" style="text-align:left; border: 1px solid #eee;">
                    <?php
                    if ($is_ship) {
                        echo $order->get_formatted_line_subtotal($item);
                    } else {
                        $commission = $item->get_meta('_vendor_item_commission', true);
                        echo wc_price($commission);
                    }
                    ?>
                </td>
                <?php do_action('wcmp_after_vendor_order_item_table', $item, $order, $vendor_id, $is_ship); ?>
            </tr>
            <?php
        }
    }

    /**
     * plain_vendor_order_item_table function to get the plain html of item table of a vendor.
     * @access public
     * @param order id , vendor term id 
     */
    public function plain_vendor_order_item_table($order, $vendor_id, $is_ship = false) {
        global $WCMp;
        $vendor_items = $this->get_vendor_items_from_order($order->get_id(), $vendor_id);
        foreach ($vendor_items as $item_id => $item) {
            $_product = apply_filters('woocommerce_order_item_product', $order->get_product_from_item($item), $item);

            // Title
            echo apply_filters('woocommerce_order_item_name', $item['name'], $item);


            // Variation
            wc_display_item_meta($item);

            // Quantity
            echo "\n" . sprintf(__('Quantity: %s', 'dc-woocommerce-multi-vendor'), $item['qty']);
            $variation_id = 0;
            if (isset($item['variation_id']) && !empty($item['variation_id'])) {
                $variation_id = $item['variation_id'];
            }
            $product_id = $item['product_id'];
            $commission_amount = get_wcmp_vendor_order_amount(array('order_id' => $order->get_id(), 'product_id' => $product_id, 'variation_id' => $variation_id, 'order_item_id' => $item_id));
            if ($is_ship)
                echo "\n" . sprintf(__('Total: %s', 'dc-woocommerce-multi-vendor'), $order->get_formatted_line_subtotal($item));
            else
                echo "\n" . sprintf(__('Commission: %s', 'dc-woocommerce-multi-vendor'), wc_price($commission_amount['commission_amount']));

            echo "\n\n";
        }
    }

    /**
     * wcmp_get_vendor_part_from_order function to get vendor due from an order.
     * @access public
     * @param order , vendor term id 
     */
    public function wcmp_get_vendor_part_from_order($order, $vendor_term_id) {
        global $WCMp;
        $order_id = $order->get_id();
        $vendor = get_wcmp_vendor_by_term($vendor_term_id);
        $args = array(
            'meta_query' => array(
                array(
                    'key' => '_commission_vendor',
                    'value' => absint($vendor->term_id),
                    'compare' => '='
                ),
                array(
                    'key' => '_commission_order_id',
                    'value' => absint($order_id),
                    'compare' => '='
                ),
            ),
        );
        $unpaid_commission_total = WCMp_Commission::get_commissions_total_data( $args, $vendor->id );
        $vendor_due = array(
            'commission' => $unpaid_commission_total['commission_amount'],
            'shipping' => $unpaid_commission_total['shipping_amount'],
            'tax' => $unpaid_commission_total['tax_amount']
        );
        return apply_filters('vendor_due_per_order', $vendor_due, $order, $vendor_term_id);
    }

    /**
     * wcmp_vendor_get_total_amount_due function to get vendor due from an order.
     * @access public
     * @param order , vendor term id 
     */
    public function wcmp_vendor_get_total_amount_due() {
        global $WCMp;
        $vendor = get_wcmp_vendor_by_term($this->term_id);
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
        return (float) ($unpaid_commission_total['total']);
    }

    /**
     * wcmp_get_vendor_part_from_order function to get vendor due from an order.
     * @access public
     * @param order , vendor term id 
     */
    public function wcmp_vendor_transaction() {
        global $WCMp;
        $transactions = $paid_array = array();
        $vendor = get_wcmp_vendor_by_term($this->term_id);
        if ($this->term_id > 0) {
            $args = array(
                'post_type' => 'wcmp_transaction',
                'post_status' => array('publish', 'private'),
                'posts_per_page' => -1,
                'post_author' => $vendor->id
            );
            $transactions = get_posts($args);
        }

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $paid_array[] = $transaction->ID;
            }
        }
        return $paid_array;
    }

    /**
     * wcmp_vendor_get_order_item_totals function to get order item table of a vendor.
     * @access public
     * @param order id , vendor term id 
     */
    public function wcmp_vendor_get_order_item_totals($order, $term_id) {
        global $WCMp;
        $vendor = get_wcmp_vendor_by_term($term_id);
        $vendor_order = wcmp_get_order( $order->get_id() );
        //$vendor_totals = get_wcmp_vendor_order_amount(array('vendor_id' => $vendor->id, 'order_id' => $order));
        $vendor_shipping_method = get_wcmp_vendor_order_shipping_method($order->get_id(), $vendor->id);
        $order_item_totals = array();
        $order_item_totals['commission_subtotal'] = array(
            'label' => __('Commission Subtotal:', 'dc-woocommerce-multi-vendor'),
            'value' => $vendor_order->get_commission()
        );
        $order_item_totals['tax_subtotal'] = array(
            'label' => __('Tax Subtotal:', 'dc-woocommerce-multi-vendor'),
            'value' => $vendor_order->get_tax()
        );
        if ($vendor_shipping_method) {
            $order_item_totals['shipping_method'] = array(
                'label' => __('Shipping Method:', 'dc-woocommerce-multi-vendor'),
                'value' => $vendor_shipping_method->get_name()
            );
        }
        $order_item_totals['shipping_subtotal'] = array(
            'label' => __('Shipping Subtotal:', 'dc-woocommerce-multi-vendor'),
            'value' => $vendor_order->get_shipping()
        );
        $order_item_totals['total'] = array(
            'label' => __('Total:', 'dc-woocommerce-multi-vendor'),
            'value' => $vendor_order->get_commission_total()
        );
        return apply_filters( 'wcmp_vendor_get_order_item_totals', $order_item_totals, $order, $vendor );
    }

    /**
     * @deprecated since version 2.6.6
     * @param object | id $order
     * @param object | id $product
     * @return array
     */
    public function get_vendor_total_tax_and_shipping($order, $product = false) {
        _deprecated_function('get_vendor_total_tax_and_shipping', '2.6.6', 'get_wcmp_vendor_order_amount');
        return get_wcmp_vendor_order_amount(array('vendor_id' => $this->id, 'order_id' => $order, 'product_id' => $product));
    }

    public function is_shipping_enable() {
        global $WCMp;
        $is_enable = false;
        // omitted from if condition -- $WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($this->id, '_vendor_give_shipping', true) and replace with get_wcmp_vendor_settings( 'is_vendor_shipping_on', 'general' )
        if ('Enable' === get_wcmp_vendor_settings( 'is_vendor_shipping_on', 'general' ) && wc_shipping_enabled()) {
            $is_enable = true;
        }
        return apply_filters('is_wcmp_vendor_shipping_enable', $is_enable, $this->id);
    }
    
    public function is_transfer_shipping_enable() {
        global $WCMp;
        $is_enable = false;
        
        if ($WCMp->vendor_caps->vendor_payment_settings('give_shipping') && !get_user_meta($this->id, '_vendor_give_shipping', true) && wc_shipping_enabled()) {
            $is_enable = true;
        }
        return apply_filters('is_wcmp_vendor_transfer_shipping_enable', $is_enable);
    }
    
    public function is_transfer_tax_enable() {
        global $WCMp;
        $is_enable = false;
        
        if ($WCMp->vendor_caps->vendor_payment_settings('give_tax') && !get_user_meta($this->id, '_vendor_give_tax', true) && wc_tax_enabled()) {
            $is_enable = true;
        }
        return apply_filters('is_wcmp_vendor_transfer_shipping_enable', $is_enable);
    }

    public function is_shipping_tab_enable() {
        $is_enable_flat_rate = false;
        $raw_zones = WC_Shipping_Zones::get_zones();
        $raw_zones[] = array('id' => 0);
        foreach ($raw_zones as $raw_zone) {
            $zone = new WC_Shipping_Zone($raw_zone['id']);
            $raw_methods = $zone->get_shipping_methods();
            foreach ($raw_methods as $raw_method) {
                if ($raw_method->id == 'flat_rate') {
                    $is_enable_flat_rate = true;
                }
            }
        }
        $is_shipping_flat_rate_enable = false;
        if ($this->is_shipping_enable() && $is_enable_flat_rate) {
            $is_shipping_flat_rate_enable = true;
        }
        return apply_filters('is_wcmp_vendor_shipping_tab_enable', $is_shipping_flat_rate_enable, $this->is_shipping_enable());
    }

    /**
     * format_order_details function
     * @access public
     * @param order id , product_id
     * @return array of order details
     */
    public function format_order_details($orders, $product_id) {
        $body = $items = array();
        $product = wc_get_product($product_id)->get_title();
        foreach (array_unique($orders) as $order) {
            $i = $order;
            $order = new WC_Order($i);
            $body[$i] = array(
                'order_number' => $order->get_order_number(),
                'product' => $product,
                'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'address' => $order->get_shipping_address_1(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'zip' => $order->get_shipping_postcode(),
                'email' => $order->get_billing_email(),
                'date' => $order->get_date_created(),
                'comments' => wptexturize($order->get_customer_note()),
            );

            $items[$i]['total_qty'] = 0;
            foreach ($order->get_items() as $line_id => $item) {
                if ($item['product_id'] != $product_id && $item['variation_id'] != $product_id) {
                    continue;
                }

                $items[$i]['items'][] = $item;
                $items[$i]['total_qty'] += $item['qty'];
            }
        }

        return array('body' => $body, 'items' => $items, 'product_id' => $product_id);
    }

    /**
     * get_vendor_orders_reports_of function
     * @access public
     * @param report_type string
     * @param args array()
     * @return array of order details
     */
    public function get_vendor_orders_reports_of($report_type = 'vendor_stats', $args = array()) {
        global $wpdb;
        $today = @date('Y-m-d 00:00:00', strtotime("+1 days"));
        $last_seven_day_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $reports = array();
        switch ($report_type) {
            case 'vendor_stats':
                $defaults = array(
                    'vendor_id' => $this->id,
                    'end_date' => $today,
                    'start_date' => $last_seven_day_date,
                    'is_trashed' => ''
                );
                $args = apply_filters('get_vendor_orders_reports_of_vendor_stats_query_args', wp_parse_args($args, $defaults));
                
                $query = array(
                    'author' => $args['vendor_id'],
                    'date_query' => array(
                        array(
                            'after'     => $args['start_date'],
                            'before'    => $args['end_date'],
                            'inclusive' => true,
                        ),
                    )
                );
                $vendor_orders = wcmp_get_orders( $query, 'object' );

                $sales_total = $commission_total = $discount_amount = $net_withdrawal_balance = 0;
                
                $vendor_sales_results = array('traffic_no' => 0, 'coupon_total' => 0, 'withdrawal' => 0, 'earning' => 0, 'sales_total' => 0, 'orders_no' => 0);
                if( $vendor_orders ){
                    foreach ( $vendor_orders as $key => $order ) {
                        try{
                            $vendor_order = wcmp_get_order( $order->get_id() );
                            if(!$vendor_order) continue;
                            $sales_total += $order->get_total();
                            $discount_amount += $order->get_total_discount();
                            $commission_id = $vendor_order->get_prop('_commission_id');
                            $commission_total += $vendor_order->get_commission_total( 'edit' );

                            if( get_post_meta( $commission_id, '_paid_status', true ) == 'paid' ){
                                 $net_withdrawal_balance += $vendor_order->get_commission_total( 'edit' );
                            }
                        } catch (Exception $ex) {

                        }
                        
                    }
                }
                $vendor_sales_results['sales_total'] = $sales_total;
                $vendor_sales_results['earning'] = $commission_total;
                $vendor_sales_results['withdrawal'] = $net_withdrawal_balance;
                $where = "created BETWEEN '{$args['start_date']}' AND '{$args['end_date']}' AND ";
                $visitor_data = wcmp_get_visitor_stats($this->id, $where);
                $vendor_sales_results['traffic_no'] = count($visitor_data);
                $vendor_sales_results['coupon_total'] = $discount_amount;
                $vendor_sales_results['orders_no'] = count($vendor_orders);

                $reports = $vendor_sales_results;
                break;

            case 'pending_shipping':
                $defaults = array(
                    'vendor_id' => $this->id,
                    'end_date' => $today,
                    'start_date' => $last_seven_day_date
                );
                $args = apply_filters('get_vendor_orders_reports_of_pending_shipping_query_args', wp_parse_args($args, $defaults));
                
                $query = array(
                    'author' => $args['vendor_id'],
                    'date_query' => array(
                        array(
                            'after'     => $args['start_date'],
                            'before'    => $args['end_date'],
                            'inclusive' => true,
                        ),
                    ),
                    'meta_query'    => array(
                        'relation' => 'OR',
                        array(
                            'key'       => 'dc_pv_shipped',
                            'compare'   => 'NOT EXISTS',
                        ),
                        array(
                            'key'       => 'wcmp_vendor_order_shipped',
                            'compare'   => 'NOT EXISTS',
                        )
                    )
                );
                $vendor_orders = wcmp_get_orders( $query, 'object' );

                $reports = $vendor_orders;
                break;

            default:
                $defaults = array(
                    'vendor_id' => $this->id,
                    'end_date' => $today,
                    'start_date' => $last_seven_day_date,
                    'is_trashed' => ''
                );
                $args = apply_filters('get_vendor_orders_reports_of_default_query_args', wp_parse_args($args, $defaults));
                $vendor_orders = $wpdb->get_results(
                        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wcmp_vendor_orders WHERE vendor_id=%d AND `created` BETWEEN %s AND %s AND `is_trashed`=%d", $args['vendor_id'], $args['start_date'], $args['end_date'], $args['is_trashed']
                        )
                );
                $reports = $vendor_orders;
                break;
        }
        return apply_filters('wcmp_vendor_order_report_of_data', $reports, $report_type, $args);
    }

    /**
     * Mark as shipped vendor order 
     * @global object $wpdb
     * @param int $order_id
     * @param srting $tracking_id
     * @param string $tracking_url
     */
    public function set_order_shipped($order_id, $tracking_id = '', $tracking_url = '') {
        global $wpdb;
        $shippers = get_post_meta($order_id, 'dc_pv_shipped', true) ? get_post_meta($order_id, 'dc_pv_shipped', true) : array();
        if (!in_array($this->id, $shippers)) {
            $shippers[] = $this->id;
            $mails = WC()->mailer()->emails['WC_Email_Notify_Shipped'];
            if (!empty($mails)) {
                $customer_email = get_post_meta($order_id, '_billing_email', true);
                $mails->trigger($order_id, $customer_email, $this->term_id, array('tracking_id' => $tracking_id, 'tracking_url' => $tracking_url));
            }
            update_post_meta($order_id, 'dc_pv_shipped', $shippers);
            // set new meta shipped
            update_post_meta($order_id, 'wcmp_vendor_order_shipped', 1);
        }
        //$wpdb->query("UPDATE {$wpdb->prefix}wcmp_vendor_orders SET shipping_status = '1' WHERE order_id = $order_id and vendor_id = $this->id");
        do_action('wcmp_vendors_vendor_ship', $order_id, $this->term_id);
        $order = wc_get_order($order_id);
        $comment_id = $order->add_order_note(__('Vendor ', 'dc-woocommerce-multi-vendor') . $this->page_title . __(' has shipped his part of order to customer.', 'dc-woocommerce-multi-vendor') . '<br><span>' . __('Tracking Url : ', 'dc-woocommerce-multi-vendor') . '</span> <a target="_blank" href="' . $tracking_url . '">' . $tracking_url . '</a><br><span>' . __('Tracking Id : ', 'dc-woocommerce-multi-vendor') . '</span>' . $tracking_id, '1', true);
        // update comment author & email
        wp_update_comment(array('comment_ID' => $comment_id, 'comment_author' => $this->page_title, 'comment_author_email' => $this->user_data->user_email));
        add_comment_meta($comment_id, '_vendor_id', $this->id);
    }

    /**
     * Returns vendor image/banner.
     *
     * @param string $type (default: 'image')
     * @param string/array $size (default: 'full')
     * @param boolean $protocol (default: false)
     * @return string
     */
    public function get_image($type = 'image', $size = 'full', $protocol = false) {
        $image = false;
        $id = $this->__get($type);

        if (!is_numeric($id)) {
            $id = get_attachment_id_by_url($id);
        }
        if ($id == 0) { /* if no attachment id found from attachment url */
            $image = $this->__get($type);
        } else {
            $image_attributes = wp_get_attachment_image_src($id, $size, true);
            if (is_array($image_attributes) && count($image_attributes)) {
                $image = $image_attributes[0];
            }
        }
        $image = apply_filters('wcmp_vendor_get_image_src', $image);
        if(!$protocol)
            return str_replace( array( 'https://', 'http://' ), '//', $image );
        else
            return $image;
    }

    /**
     * Get Announcements.
     *
     * @param int $id (default: current vendor)
     * @param array $args
     * @return array
     */
    public function get_announcements($id = '', $args = array()) {
        $vendor_id = '';
        $announcements = array();
        if ($id) {
            $vendor_id = $id;
        } else {
            $vendor_id = $this->id;
        }
        $default = array(
            'posts_per_page' => -1,
            'post_type' => 'wcmp_vendor_notice',
            'post_status' => 'publish',
            'suppress_filters' => true
        );
        $args = wp_parse_args($args, $default);
        $posts_array = get_posts($args);
        $dismiss_notices_ids = get_user_meta($vendor_id, '_wcmp_vendor_message_deleted', true);
        if (!empty($dismiss_notices_ids)) {
            $dismiss_notices_ids_array = explode(',', $dismiss_notices_ids);
        } else {
            $dismiss_notices_ids_array = array();
        }
        $readed_notices_ids = get_user_meta($vendor_id, '_wcmp_vendor_message_readed', true);
        if (!empty($readed_notices_ids)) {
            $readed_notices_ids_array = explode(',', $readed_notices_ids);
        } else {
            $readed_notices_ids_array = array();
        }
        if ($posts_array) {
            foreach ($posts_array as $post) {
                // deleted by vendor
                if (!in_array($post->ID, $dismiss_notices_ids_array)) {
                    $announcements['all'][$post->ID] = $post;
                    // readed by vendor
                    if (in_array($post->ID, $readed_notices_ids_array)) {
                        $post->is_read = true;
                        $announcements['read'][$post->ID] = $post;
                    } else {
                        $post->is_read = false;
                        $announcements['unread'][$post->ID] = $post;
                    }
                } else {
                    $post->is_read = false;
                    $announcements['deleted'][$post->ID] = $post;
                }
            }
        }
        return $announcements;
    }

    /**
     * Clear vendor all transients.
     *
     * @param int $id (default: current vendor)
     * @return void
     */
    public function clear_all_transients($id = '') {
        $vendor_id = $this->id;
        $response = false;
        if ($id) {
            $vendor_id = $id;
        }
        $transients_to_clear = array();
        // Transient names that include a vendor ID
        $vendor_transient_names = apply_filters('wcmp_clear_all_transients_included_vendor_id', array(
            'wcmp_dashboard_reviews_for_vendor_',
            'wcmp_customer_qna_for_vendor_',
            'wcmp_visitor_stats_data_',
            'wcmp_stats_report_data_',
        ));
        if ($vendor_id > 0) {
            foreach ($vendor_transient_names as $transient) {
                $transients_to_clear[] = $transient . $vendor_id;
            }
        }
        $transients_to_clear = apply_filters('wcmp_vendor_before_transients_to_clear', $transients_to_clear, $vendor_id);
        // Delete transients
        foreach ($transients_to_clear as $transient) {
            $response = delete_transient($transient);
        }
        do_action('wcmp_vendor_clear_all_transients', $vendor_id);
        return $response;
    }

    /**
     * Get vendor address.
     *
     * @param  array $args Arguments to show in address.
     * @return string
     */
    public function get_formatted_address($args = array(), $sep = ', ') {
        $formatted_address = array(
            'address_1' => $this->__get('address_1'),
            'address_2' => $this->__get('address_2'),
            'city' => $this->__get('city'),
            'state' => $this->__get('state'),
            'country' => $this->__get('country'),
            'postcode' => $this->__get('postcode'),
        );

        if($args) :
            foreach ($formatted_address as $key => $value) {
                if(in_array($key, $args)) continue;
                unset($formatted_address[$key]);
            }
        endif;
        // check empty data
        $addresses = array();
        foreach ($formatted_address as $key => $value) {
            if(!empty($value)){
                $addresses[$key] = $value;
            }
        }
        $addresses = apply_filters( 'wcmp_vendor_before_get_formatted_address', $addresses );

        $formatted_address = implode($sep, $addresses);

        return $formatted_address;
    }
    
    /**
     * Get order totals for display on pages and in emails.
     *
     * since WCMp 3.2.3
     * @param integer $order_id Order id.
     * @param string $split_tax Tax to display.
     * @param string $html_price Price to display.
     * @return array
    */
    public function get_vendor_order_item_totals($order_id, $split_tax = false, $html_price = true) {
        if($order_id){
            $order = wc_get_order(absint($order_id));
            $vendor_order = wcmp_get_order($order_id);
            if(!$vendor_order) return false;
            $order_total_arr = array();
            $vendor_items = $order->get_items( 'line_item' );
            //$vendor_items = get_wcmp_vendor_orders(array('order_id' => $order_id, 'vendor_id' => $this->id));
            $vendor_shipping_method = get_wcmp_vendor_order_shipping_method($order_id, $this->id);
            $total_rows  = array();
            // items subtotals
            if($vendor_items){
                $subtotal = 0;
                foreach ($vendor_items as $item) {
                    $subtotal += $item->get_subtotal();
                }
                $order_total_arr[] = $subtotal;
                $total_rows['order_subtotal'] = array(
                    'label' => __( 'Subtotal:', 'woocommerce' ),
                    'value' => ($html_price) ? wc_price($subtotal) : $subtotal,
                );
            }
            // shipping methods
            if ( $this->is_shipping_enable() && $vendor_shipping_method ) {
                $total_rows['shipping'] = array(
                    'label' => __( 'Shipping:', 'woocommerce' ),
                    'value' => $vendor_shipping_method->get_name(),
                );
            }
            // shipping cost
            $shipping_amount = $order->get_shipping_total();
            if ( $this->is_shipping_enable() && $shipping_amount ) {
                $order_total_arr[] = $shipping_amount;
                $total_rows['shipping_cost'] = array(
                    'label' => __( 'Shipping cost:', 'dc-woocommerce-multi-vendor' ),
                    'value' => ($html_price) ? wc_price($shipping_amount) : $shipping_amount,
                );
            }
            // tax
            $shipping_tax_amount = $order->get_shipping_tax();
            $tax_amount = $order->get_total_tax();
            if(!apply_filters('wcmp_get_vendor_order_item_totals_split_taxes', $split_tax, $order_id, array(), $this->id)){
                $order_total_arr[] = $tax_amount + $shipping_tax_amount;
                $total_rows['tax'] = array(
                    'label' => WC()->countries->tax_or_vat() . ':',
                    'value' => ($html_price) ? wc_price($tax_amount + $shipping_tax_amount) : $tax_amount + $shipping_tax_amount,
                );
            }else{
                $order_total_arr[] = $shipping_tax_amount;
                $total_rows['shipping_tax'] = array(
                    'label' => __( 'Shipping:', 'woocommerce' ).' '.WC()->countries->tax_or_vat() . ':',
                    'value' => ($html_price) ? wc_price($shipping_tax_amount) : $shipping_tax_amount,
                );
                $order_total_arr[] = $tax_amount;
                $total_rows['tax'] = array(
                    'label' => WC()->countries->tax_or_vat() . ':',
                    'value' => ($html_price) ? wc_price($tax_amount) : $tax_amount,
                );
            }
            // payment methods
            $total_rows['payment_method'] = array(
                'label' => __( 'Payment method:', 'woocommerce' ),
                'value' => $order->get_payment_method_title(),
            );
            // Order totals
            $total_rows['order_total'] = array(
                'label' => __( 'Total:', 'woocommerce' ),
                'value' => ($html_price) ? wc_price(array_sum($order_total_arr)) : array_sum($order_total_arr),
            );
            
            return apply_filters( 'wcmp_get_vendor_order_item_totals', $total_rows, $order_id, $this->id );
        }
        return false;
    }

}
