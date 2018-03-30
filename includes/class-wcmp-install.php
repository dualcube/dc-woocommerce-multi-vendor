<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Demo plugin Install
 *
 * Plugin install script which adds default pages, taxonomies, and database tables to WordPress. Runs on activation and upgrade.
 *
 * @author 		WC Marketplace
 * @package 	wcmp/Admin/Install
 * @version    0.0.1
 */
class WCMp_Install {

    public function __construct() {
        if (!get_option('dc_product_vendor_plugin_db_version')) {
            $this->save_default_plugin_settings();
        }
        $this->wcmp_plugin_tables_install();
        $this->remove_other_vendors_plugin_role();
        self::register_user_role();
        if (!get_option("dc_product_vendor_plugin_page_install")) {
            $this->wcmp_product_vendor_plugin_create_pages();
            update_option("dc_product_vendor_plugin_page_install", 1);
        }
        $this->do_wcmp_migrate();
        if(!get_option('dc_product_vendor_plugin_installed') && apply_filters('wcmp_enable_setup_wizard', true)){
            set_transient( '_wcmp_activation_redirect', 1, 30 );
        }
        $this->do_schedule_cron_events();
    }

    /**
     * Remove other vendor role created by other plugin
     *
     * @access public
     * @return void
     */
    function remove_other_vendors_plugin_role() {
        global $wp_roles;

        if (!class_exists('WP_Roles')) {
            return;
        }

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        $other_vendor_role = array('seller', 'yith_vendor', 'pending_vendor', 'vendor');
        foreach ($other_vendor_role as $element) {
            if ($wp_roles->is_role($element)) {
                remove_role($element);
            }
        }
    }

    /**
     * Create a page
     *
     * @access public
     * @param mixed $slug Slug for the new page
     * @param mixed $option Option name to store the page's ID
     * @param string $page_title (default: '') Title for the new page
     * @param string $page_content (default: '') Content for the new page
     * @param int $post_parent (default: 0) Parent for the new page
     * @return void
     */
    function wcmp_product_vendor_plugin_create_page($slug, $option, $page_title = '', $page_content = '', $post_parent = 0) {
        global $wpdb;
        $option_value = get_option($option);
        if ($option_value > 0 && get_post($option_value)) {
            return;
        }
        $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1;");
        if ($page_found) :
            if (!$option_value) {
                update_option($option, $page_found);
            }
            return;
        endif;
        $page_data = array(
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'post_name' => $slug,
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_parent' => $post_parent,
            'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);
        update_option($option, $page_id);
    }

    /**
     * Create pages that the plugin relies on, storing page id's in variables.
     *
     * @access public
     * @return void
     */
    function wcmp_product_vendor_plugin_create_pages() {

        // WCMp Plugin pages
        $is_trash = wp_trash_post(get_option('wcmp_product_vendor_vendor_dashboard_page_id'));
        if ($is_trash) {
            delete_option('wcmp_product_vendor_vendor_dashboard_page_id');
            delete_option('wcmp_product_vendor_vendor_page_id');
        }
        $this->wcmp_product_vendor_plugin_create_page(esc_sql(_x('dashboard', 'page_slug', 'dc-woocommerce-multi-vendor')), 'wcmp_product_vendor_vendor_page_id', __('Vendor Dashboard', 'dc-woocommerce-multi-vendor'), '[wcmp_vendor]');
        $this->wcmp_product_vendor_plugin_create_page(esc_sql(_x('vendor-registration', 'page_slug', 'dc-woocommerce-multi-vendor')), 'wcmp_product_vendor_registration_page_id', __('Vendor Registration', 'dc-woocommerce-multi-vendor'), '[vendor_registration]');
        $wcmp_product_vendor_vendor_page_id = get_option('wcmp_product_vendor_vendor_page_id');
        $wcmp_product_vendor_registration_page_id = get_option('wcmp_product_vendor_registration_page_id');
        update_wcmp_vendor_settings('wcmp_vendor', $wcmp_product_vendor_vendor_page_id, 'vendor', 'general');
        update_wcmp_vendor_settings('vendor_registration', $wcmp_product_vendor_registration_page_id, 'vendor', 'general');
    }

    /**
     * save default product vendor plugin settings
     *
     * @access public
     * @return void
     */
    function save_default_plugin_settings() {

        $general_settings = get_option('wcmp_general_settings_name');
        if (empty($general_settings)) {
            $general_settings = array(
                'approve_vendor_manually' => 'Enable',
                'is_policy_on' => 'Enable'
            );
            update_option('wcmp_general_settings_name', $general_settings);
        }

        if (!get_wcmp_vendor_settings('is_upload_files', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('is_upload_files', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('is_submit_product', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('is_submit_product', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('simple', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('simple', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('variable', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('variable', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('grouped', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('grouped', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('virtual', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('virtual', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('external', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('external', 'Enable', 'capabilities', 'product');
        }
        if (!get_wcmp_vendor_settings('downloadable', 'capabilities', 'product')) {
            update_wcmp_vendor_settings('downloadable', 'Enable', 'capabilities', 'product');
        }
        $payment_settings = get_option('wcmp_payment_settings_name');
        if (empty($payment_settings)) {
            $payment_settings = array(
                'commission_include_coupon' => 'Enable',
                'give_tax' => 'Enable',
                'give_shipping' => 'Enable',
                'commission_type' => 'percent',
            );
            update_option('wcmp_payment_settings_name', $payment_settings);
        }
        
        if (!get_wcmp_vendor_settings('is_singleproductmultiseller', 'general')) {
            update_wcmp_vendor_settings('is_singleproductmultiseller', 'Enable', 'general');
        }
    }

    /**
     * Create WCMp dependency tables
     * @global object $wpdb
     */
    function wcmp_plugin_tables_install() {
        global $wpdb;
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        $create_tables_query = array();
        $create_tables_query[] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wcmp_vendor_orders` (
		`ID` bigint(20) NOT NULL AUTO_INCREMENT,
		`order_id` bigint(20) NOT NULL,
		`commission_id` bigint(20) NOT NULL,
                `commission_status` varchar(100) NOT NULL DEFAULT 'unpaid',
                `commission_paid_date` timestamp NULL,
		`vendor_id` bigint(20) NOT NULL,
		`shipping_status` varchar(255) NOT NULL,
		`order_item_id` bigint(20) NOT NULL,
                `line_item_type` longtext NULL,
		`product_id` bigint(20) NOT NULL,
                `variation_id` bigint(20) NOT NULL DEFAULT 0,
                `quantity` bigint(20) NOT NULL DEFAULT 1,
		`commission_amount` varchar(255) NOT NULL,
		`shipping` varchar(255) NOT NULL,
		`tax` varchar(255) NOT NULL,
                `shipping_tax_amount` varchar(255) NOT NULL DEFAULT 0,
		`is_trashed` varchar(10) NOT NULL,				
		`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,				
		PRIMARY KEY (`ID`),
		CONSTRAINT vendor_orders UNIQUE (order_id, vendor_id, commission_id, order_item_id)
		) $collate;";

        $create_tables_query[] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wcmp_products_map` (
		`ID` bigint(20) NOT NULL AUTO_INCREMENT,
		`product_title` varchar(255) NOT NULL,
		`product_ids` text NOT NULL,						
		`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,				
		PRIMARY KEY (`ID`)
		) $collate;";
        
        $create_tables_query[] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wcmp_visitors_stats` (
		`ID` bigint(20) NOT NULL AUTO_INCREMENT,
                `vendor_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `user_cookie` varchar(255) NOT NULL,
                `session_id` varchar(191) NOT NULL,
                `ip` varchar(60) NOT NULL,
                `lat` varchar(60) NOT NULL,
                `lon` varchar(60) NOT NULL,
                `city` text NOT NULL,
                `zip` varchar(20) NOT NULL,
                `regionCode` text NOT NULL,
                `region` text NOT NULL,
                `countryCode` text NOT NULL,
                `country` text NOT NULL,
                `isp` text NOT NULL,
                `timezone` varchar(255) NOT NULL,
                `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,				
                PRIMARY KEY (`ID`),
                CONSTRAINT visitor UNIQUE (vendor_id, session_id),
                KEY vendor_id (vendor_id),
                KEY user_id (user_id),
                KEY user_cookie (user_cookie(191)),
                KEY session_id (session_id),
                KEY ip (ip)
		) $collate;";
        
        $create_tables_query[] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wcmp_cust_questions` (
		`ques_ID` bigint(20) NOT NULL AUTO_INCREMENT,
		`product_ID` BIGINT UNSIGNED NOT NULL DEFAULT '0',
                `ques_details` text NOT NULL,
		`ques_by` BIGINT UNSIGNED NOT NULL DEFAULT '0',
		`ques_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ques_vote` longtext NULL,
		PRIMARY KEY (`ques_ID`)
		) $collate;";
        
        $create_tables_query[] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "wcmp_cust_answers` (
		`ans_ID` bigint(20) NOT NULL AUTO_INCREMENT,
		`ques_ID` BIGINT UNSIGNED NOT NULL DEFAULT '0',
                `ans_details` text NOT NULL,
		`ans_by` BIGINT UNSIGNED NOT NULL DEFAULT '0',
		`ans_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ans_vote` longtext NULL,
		PRIMARY KEY (`ans_ID`),
                CONSTRAINT ques_id UNIQUE (ques_ID)
		) $collate;";

        foreach ($create_tables_query as $create_table_query) {
            $wpdb->query($create_table_query);
        }
        update_option('wcmp_table_created', true);
    }

    /**
     * Migrate old data
     * @global type $WCMp
     * @global object $wpdb
     */
    function do_wcmp_migrate() {
        global $wpdb;
        #region map existing product in product map table
        if (!get_option('is_wcmp_product_sync_with_multivendor')) {
            $args_multi_vendor = array(
                'posts_per_page' => -1,
                'post_type' => 'product',
                'post_status' => 'publish',
                'suppress_filters' => true
            );
            $post_array = get_posts($args_multi_vendor);
            foreach ($post_array as $product_post) {
                $results = $wpdb->get_results("select * from {$wpdb->prefix}wcmp_products_map where product_title = '{$product_post->post_title}' ");
                if (is_array($results) && (count($results) > 0)) {
                    $id_of_similar = $results[0]->ID;
                    $product_ids = $results[0]->product_ids;
                    $product_ids_arr = explode(',', $product_ids);
                    if (is_array($product_ids_arr) && !in_array($product_post->ID, $product_ids_arr)) {
                        $product_ids = $product_ids . ',' . $product_post->ID;
                        $wpdb->query("update {$wpdb->prefix}wcmp_products_map set product_ids = '{$product_ids}' where ID = {$id_of_similar}");
                    }
                } else {
                    $wpdb->query("insert into {$wpdb->prefix}wcmp_products_map set product_title='{$product_post->post_title}', product_ids = '{$product_post->ID}' ");
                }
            }
            update_option('is_wcmp_product_sync_with_multivendor', 1);
        }
        #endregion
    }

    /**
     * Register vendor user role
     *
     * @access public
     * @return void
     */
    public static function register_user_role() {

        add_role('dc_pending_vendor', apply_filters('dc_pending_vendor_role', __('Pending Vendor', 'dc-woocommerce-multi-vendor')), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));

        add_role('dc_rejected_vendor', apply_filters('dc_rejected_vendor_role', __('Rejected Vendor', 'dc-woocommerce-multi-vendor')), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));

        add_role('dc_vendor', apply_filters('dc_vendor_role', __('Vendor', 'dc-woocommerce-multi-vendor')), array(
            'read' => true,
            'manage_product' => true,
            'edit_posts' => true,
            'delete_posts' => false,
            'view_woocommerce_reports' => true,
            'assign_product_terms' => true,
            'upload_files' => true,
            'read_product' => true,
            'read_shop_coupon' => true,
            'edit_product' => true,
            'delete_product' => true,
            'edit_products' => true,
            'delete_products' => true
        ));
    }
    
    /**
     * WCMp schedule cron events
     *
     * @access public
     * @return void
     */
    function do_schedule_cron_events(){
        if (apply_filters('wcmp_do_schedule_cron_vendor_weekly_order_stats', true) && !wp_next_scheduled('vendor_weekly_order_stats')) {
            wp_schedule_event(time(), 'weekly', 'vendor_weekly_order_stats');
        }
        if (apply_filters('wcmp_do_schedule_cron_vendor_weekly_order_stats', true) && !wp_next_scheduled('vendor_monthly_order_stats')) {
            wp_schedule_event(time(), 'monthly', 'vendor_monthly_order_stats');
        }
    }
}
