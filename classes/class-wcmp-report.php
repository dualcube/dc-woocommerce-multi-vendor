<?php

/**
 * WCMp Report Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Report {

    public function __construct() {

        add_action('woocommerce_admin_reports', array($this, 'wcmp_report_tabs'));
        if (is_user_wcmp_vendor(get_current_vendor_id())) {
            add_filter('woocommerce_reports_charts', array($this, 'filter_tabs'), 99);
            add_filter('wcmp_filter_orders_report_overview', array($this, 'filter_orders_report_overview'), 99);
        }
        // filter admin woocommerce reports by excluding sub-orders
        add_filter('woocommerce_reports_get_order_report_data_args', array($this, 'woocommerce_reports_get_order_report_data_args'), 99);
    }
    
    public function woocommerce_reports_get_order_report_data_args( $args ) {
        if( is_user_wcmp_vendor( get_current_user_id() ) ) return $args;
        if( isset( $args['where'] ) ) {
            $args['where'][] = array(
                'key'      => 'posts.post_parent',
                'value'    => 0,
                'operator' => '=',
            );
        }else{
            $args['where'] = array( array(
                'key'      => 'posts.post_parent',
                'value'    => 0,
                'operator' => '=',
            ) );
        }
        return $args;
    }
    
    /**
	 * Get report totals such as order totals and discount amounts.
	 *
	 * Data example:
	 *
	 * '_order_total' => array(
	 *     'type'     => 'meta',
	 *     'function' => 'SUM',
	 *     'name'     => 'total_sales'
	 * )
	 *
	 * @param  array $args
	 * @return mixed depending on query_type
	 */
	public function get_order_report_data( $args = array(), $report_data) {
		global $wpdb;

		$default_args = array(
			'data'                => array(),
			'where'               => array(),
			'where_meta'          => array(),
			'query_type'          => 'get_row',
			'group_by'            => '',
			'order_by'            => '',
			'limit'               => '',
			'filter_range'        => false,
			'nocache'             => false,
			'debug'               => false,
			'order_status'        => array( 'completed', 'processing', 'on-hold' ),
		);
		$args         = apply_filters( 'woocommerce_reports_get_order_report_data_args', $args );
		$args         = wp_parse_args( $args, $default_args );

		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$order_status = apply_filters( 'woocommerce_reports_order_statuses', $order_status );

		$query  = array();
		$select = array();
                
		foreach ( $data as $raw_key => $value ) {
			$key      = sanitize_key( $raw_key );
			$distinct = $get_key = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}
                        
			switch ( $value['type'] ) {
				case 'meta':
					$get_key = "meta_{$key}.meta_value";
					break;
                                case 'parent_meta':
                                    $get_key = "parent_meta_{$key}.meta_value";
                                    break;
				case 'post_data':
					$get_key = "posts.{$key}";
					break;
				case 'order_item_meta':
					$get_key = "order_item_meta_{$key}.meta_value";
					break;
				case 'order_item':
					$get_key = "order_items.{$key}";
					break;
				default:
					break;
			}

			if ( $value['function'] ) {
				$get = "{$value['function']}({$distinct} {$get_key})";
			} else {
				$get = "{$distinct} {$get_key}";
			}

			$select[] = "{$get} as {$value['name']}";
		}

		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = "FROM {$wpdb->posts} AS posts";

		// Joins
		$joins = array();

		foreach ( ( $data + $where ) as $raw_key => $value ) {
			$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
			$type      = isset( $value['type'] ) ? $value['type'] : false;
			$key       = sanitize_key( $raw_key );

			switch ( $type ) {
				case 'meta':
					$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON ( posts.ID = meta_{$key}.post_id AND meta_{$key}.meta_key = '{$raw_key}' )";
					break;
                                case 'parent_meta':
                                    $joins[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS parent_meta_{$key} ON (posts.post_parent = parent_meta_{$key}.post_id) AND (parent_meta_{$key}.meta_key = '{$raw_key}')";
                                    break;
                                    
				case 'order_item_meta':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON (posts.ID = order_items.order_id)";

					if ( ! empty( $value['order_item_type'] ) ) {
						$joins['order_items'] .= " AND (order_items.order_item_type = '{$value['order_item_type']}')";
					}

					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON " .
														"(order_items.order_item_id = order_item_meta_{$key}.order_item_id) " .
														" AND (order_item_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
					break;
			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
				$type      = isset( $value['type'] ) ? $value['type'] : false;
				$key       = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( 'order_item_meta' === $type ) {

					$joins['order_items']              = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";

				} else {
					// If we have a where clause for meta, join the postmeta table
					$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";
				}
			}
		}
                
                if ( ! empty( $parent_order_status ) ) {
			$joins['parent'] = "LEFT JOIN {$wpdb->posts} AS parent ON posts.post_parent = parent.ID";
		}

		$query['join'] = implode( ' ', $joins );
                $current_user = get_current_user_id();
		$query['where']  = "
                    WHERE   posts.post_type     = 'shop_order'
                    AND     posts.post_status   != 'trash'
                    AND     posts.post_author = {$current_user}
                    ";

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	posts.post_date >= '" . date( 'Y-m-d H:i:s', $report_data->start_date ) . "'
				AND 	posts.post_date < '" . date( 'Y-m-d H:i:s', strtotime( '+1 DAY', $report_data->end_date ) ) . "'
			";
		}

		if ( ! empty( $where_meta ) ) {

			$relation = isset( $where_meta['relation'] ) ? $where_meta['relation'] : 'AND';

			$query['where'] .= ' AND (';

			foreach ( $where_meta as $index => $value ) {

				if ( ! is_array( $value ) ) {
					continue;
				}

				$key = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( strtolower( $value['operator'] ) == 'in' || strtolower( $value['operator'] ) == 'not in' ) {

					if ( is_array( $value['meta_value'] ) ) {
						$value['meta_value'] = implode( "','", $value['meta_value'] );
					}

					if ( ! empty( $value['meta_value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['meta_value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['meta_value']}'";
				}

				if ( ! empty( $where_value ) ) {
					if ( $index > 0 ) {
						$query['where'] .= ' ' . $relation;
					}

					if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND order_item_meta_{$key}.meta_value {$where_value} )";
					} else {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND meta_{$key}.meta_value {$where_value} )";
					}
				}
			}

			$query['where'] .= ')';
		}

		if ( ! empty( $where ) ) {

			foreach ( $where as $value ) {

				if ( strtolower( $value['operator'] ) == 'in' || strtolower( $value['operator'] ) == 'not in' ) {

					if ( is_array( $value['value'] ) ) {
						$value['value'] = implode( "','", $value['value'] );
					}

					if ( ! empty( $value['value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['value']}'";
				}

				if ( ! empty( $where_value ) ) {
					$query['where'] .= " AND {$value['key']} {$where_value}";
				}
			}
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		$query          = apply_filters( 'woocommerce_reports_get_order_report_query', $query );
		$query          = implode( ' ', $query );
		$query_hash     = md5( $query_type . $query );
		$cached_results = get_transient( strtolower( get_class( $report_data ) ) );

		if ( $debug ) {
			echo '<pre>';
			wc_print_r( $query );
			echo '</pre>';
		}

		if ( $debug || $nocache || false === $cached_results || ! isset( $cached_results[ $query_hash ] ) ) {
			static $big_selects = false;
			// Enable big selects for reports, just once for this session
			if ( ! $big_selects ) {
				$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
				$big_selects = true;
			}
			
			$cached_results[ $query_hash ] = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
			set_transient( strtolower( get_class( $report_data ) ), $cached_results, DAY_IN_SECONDS );
		}

		$result = $cached_results[ $query_hash ];

		return $result;
	}

    /**
     * Filter orders report for vendor
     *
     * @param object $orders
     */
    public function filter_orders_report_overview($orders) {
        foreach ($orders as $order_key => $order) {
            $vendor_item = false;
            $order_obj = new WC_Order($order->ID);
            $items = $order_obj->get_items('line_item');
            foreach ($items as $item_id => $item) {
                $product_id = wc_get_order_item_meta($item_id, '_product_id', true);
                $vendor_id = wc_get_order_item_meta($item_id, '_vendor_id', true);
                $current_user = get_current_vendor_id();
                if ($vendor_id) {
                    if ($vendor_id == $current_user) {
                        $existsids[] = $product_id;
                        $vendor_item = true;
                    }
                } else {
                    //for vendor logged in only
                    if (is_user_wcmp_vendor($current_user)) {
                        $vendor = get_wcmp_vendor($current_user);
                        $vendor_products = $vendor->get_products_ids();
                        $existsids = array();
                        foreach ($vendor_products as $vendor_product) {
                            $existsids[] = ( $vendor_product->ID );
                        }
                        if (in_array($product_id, $existsids)) {
                            $vendor_item = true;
                        }
                    }
                }
            }
            if (!$vendor_item)
                unset($orders[$order_key]);
        }
        return $orders;
    }

    /**
     * Show only reports that are useful to a vendor
     *
     * @param array $tabs
     *
     * @return array
     */
    public function filter_tabs($tabs) {
        global $woocommerce;
        unset($tabs['wcmp_vendors']['reports']['vendor']);
        $return = array(
            'wcmp_vendors' => $tabs['wcmp_vendors'],
        );
        return $return;
    }

    /**
     * WCMp reports tab options
     */
    function wcmp_report_tabs($reports) {
        global $WCMp;
        $reports['wcmp_vendors'] = apply_filters( 'wcmp_backend_sales_report_tabs', array(
            'title' => __('WCMp', 'dc-woocommerce-multi-vendor'),
            'reports' => array(
                "overview" => array(
                    'title' => __('Overview', 'dc-woocommerce-multi-vendor'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array(__CLASS__, 'wcmp_get_report')
                ),
                "vendor" => array(
                    'title' => __('Vendor', 'dc-woocommerce-multi-vendor'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array(__CLASS__, 'wcmp_get_report')
                ),
                "product" => array(
                    'title' => __('Product', 'dc-woocommerce-multi-vendor'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array(__CLASS__, 'wcmp_get_report')
                )
            )
        ) );
        if( !is_user_wcmp_vendor(get_current_user_id() ) ) {
            if( isset( $reports['wcmp_vendors']['reports']['overview'] ) ){
                unset( $reports['wcmp_vendors']['reports']['overview'] );
            }
        }
        return $reports;
    }

    /**
     * Get a report from our reports subfolder
     */
    public static function wcmp_get_report($name) {
        $name = sanitize_title(str_replace('_', '-', $name));
        $class = 'WCMp_Report_' . ucfirst(str_replace('-', '_', $name));
        include_once( apply_filters('wcmp_admin_reports_path', 'reports/class-wcmp-report-' . $name . '.php', $name, $class) );
        if (!class_exists($class))
            return;
        $report = new $class();
        $report->output_report();
    }

    /**
     * get vendor commission by date
     *
     * @access public
     * @param mixed $vars
     * @return array
     */
    public function vendor_sales_stat_overview($vendor, $start_date = false, $end_date = false) {
        global $WCMp;
        $total_sales = 0;
        $total_vendor_earnings = 0;
        $total_order_count = 0;
        $total_purchased_products = 0;
        $total_coupon_used = 0;
        $total_coupon_discount_value = 0;
        $total_earnings = 0;
        $total_customers = array();
        $vendor = get_wcmp_vendor(get_current_vendor_id());
        $vendor = apply_filters('wcmp_dashboard_sale_stats_vendor', $vendor);
        for ($date = strtotime($start_date); $date <= strtotime('+1 day', strtotime($end_date)); $date = strtotime('+1 day', $date)) {

            $year = date('Y', $date);
            $month = date('n', $date);
            $day = date('j', $date);

            $line_total = $sales = $comm_amount = $vendor_earnings = $earnings = 0;

            $args = array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'post_status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed'),
                'meta_query' => array(
                    array(
                        'key' => '_commissions_processed',
                        'value' => 'yes',
                        'compare' => '='
                    ),
                    array(
                        'key' => '_vendor_id',
                        'value' => get_current_user_id(),
                        'compare' => '='
                    )
                ),
                'date_query' => array(
                    array(
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                    ),
                )
            );

            $qry = new WP_Query($args);

            $orders = apply_filters('wcmp_filter_orders_report_overview', $qry->get_posts(), $vendor->id);
            if (!empty($orders)) {
                foreach ($orders as $order_obj) {

                    $order = new WC_Order($order_obj->ID);
                    if ($order) :
                        $vendor_order = wcmp_get_order($order->get_id());
                        $total_sales += $order->get_total();
                        $total_coupon_discount_value += $order->get_total_discount();
                        $total_earnings += $vendor_order->get_commission_total('edit');
                        $total_vendor_earnings += $vendor_order->get_commission('edit');
                        $total_purchased_products += count($order->get_items('line_item'));
                    endif;

                    //coupons count
                    $coupon_used = array();
                    $coupons = $order->get_items('coupon');
                    foreach ($coupons as $coupon_item_id => $item) {
                        $coupon = new WC_Coupon(trim($item['name']));
                        $coupon_post = get_post($coupon->get_id());
                        $author_id = $coupon_post->post_author;
                        if ($vendor->id == $author_id) {
                            $total_coupon_used++;
                            //$total_coupon_discount_value += (float) wc_get_order_item_meta($coupon_item_id, 'discount_amount', true);
                        }
                    }
                    ++$total_order_count;

                    //user count
                    if ($order->get_customer_id() != 0 && $order->get_customer_id() != 1)
                        array_push($total_customers, $order->get_customer_id());
                }
            }
        }

        return apply_filters('wcmp_vendor_dashboard_report_data', array('total_order_count' => $total_order_count, 'total_vendor_sales' => $total_sales, 'total_vendor_earning' => $total_vendor_earnings, 'total_coupon_discount_value' => $total_coupon_discount_value, 'total_coupon_used' => $total_coupon_used, 'total_customers' => array_unique($total_customers), 'total_purchased_products' => $total_purchased_products), $vendor);
    }

}

?>