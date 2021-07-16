<?php

/**
 * WCMp Frontend Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Frontend {
    public $custom_store_url = '';

    public function __construct() {

        $permalinks = get_option('dc_vendors_permalinks');
        $this->custom_store_url = empty($permalinks['vendor_shop_base']) ? _x('vendor', 'slug', 'dc-woocommerce-multi-vendor') : $permalinks['vendor_shop_base'];
        //enqueue scripts
        add_action('wp_enqueue_scripts', array(&$this, 'frontend_scripts'));
        //enqueue styles
        add_action('wp_enqueue_scripts', array(&$this, 'frontend_styles'), 999);
        if ( apply_filters( 'wcmp_load_default_vendor_store', false ) ) {
            add_action('woocommerce_archive_description', array(&$this, 'product_archive_vendor_info'), 10);
        } else {
            add_action('wcmp_archive_description', array(&$this, 'product_archive_vendor_info'), 10);
        }
        add_filter('body_class', array(&$this, 'set_product_archive_class'));
        add_action('template_redirect', array(&$this, 'template_redirect'));

        add_filter('template_include', array(&$this, 'wcmp_vendor_dashboard_template'), 99);
        //add_filter('page_template', array(&$this, 'wcmp_vendor_dashboard_template'));

        add_action('woocommerce_order_details_after_order_table', array($this, 'display_vendor_msg_in_thank_you_page'), 100);
        add_action('wcmp_vendor_register_form', array(&$this, 'wcmp_vendor_register_form_callback'));
        add_action('woocommerce_register_post', array(&$this, 'wcmp_validate_extra_register_fields'), 10, 3);
        add_action('woocommerce_created_customer', array(&$this, 'wcmp_save_extra_register_fields'), 10, 3);
        // split woocommerce shipping packages
        add_filter('woocommerce_cart_shipping_packages', array(&$this, 'wcmp_split_shipping_packages'), 0);
        // Rename woocommerce shipping packages
        add_filter('woocommerce_shipping_package_name', array(&$this, 'woocommerce_shipping_package_name'), 10, 3);
        if (is_wcmp_version_less_3_4_0()) {
            // Add extra vendor_id to shipping packages
            add_action('woocommerce_checkout_create_order_shipping_item', array(&$this, 'add_meta_date_in_shipping_package'), 10, 4);
            // processed woocomerce checkout order data
            add_action('woocommerce_checkout_order_processed', array(&$this, 'wcmp_checkout_order_processed'), 30, 3);
        }
        // store visitors stats
        if(!apply_filters('wcmp_is_disable_store_visitors_stats', false))
            add_action('template_redirect', array(&$this, 'wcmp_store_visitors_stats'), 99);

        add_filter('woocommerce_get_zone_criteria', array(&$this, 'wcmp_shipping_zone_same_region_criteria'), 10, 3);
        // WPML work
        if ( defined( 'ICL_SITEPRESS_VERSION' ) && ! ICL_PLUGIN_INACTIVE && class_exists( 'SitePress' ) ) {
            add_filter( 'icl_ls_languages', array( &$this, 'wcmp_store_page_wpml_language_switcher' ), 999 );
            add_action( 'wcmp_product_manager_right_panel_after', array( &$this, 'wpml_wcmp_product_manager_translations' ), 200 );
            add_action( 'wcmp_dashboard_header_right_vendor_dropdown', array( &$this, 'wpml_language_switcher_option_on_dropdown' ) );
        }
        // WCMp store page callback
        add_action( 'wcmp_store_tab_widget_contents', array(&$this, 'wcmp_store_tab_widget_contents' ));
        add_action( 'wcmp_store_widget_contents', array(&$this, 'wcmp_store_widget_contents' ));
        add_action( 'wcmp_after_main_content', array(&$this, 'wcmp_after_main_content' ));
        add_action( 'wcmp_sidebar', array(&$this, 'wcmp_sidebar') );

        add_action( 'init', array(&$this, 'wcmp_sidebar_init'), 99 );
        add_filter( 'query_vars', array( $this, 'prefix_register_query_var' ), 1);
        add_action( 'pre_get_posts', [ $this, 'store_query_filter' ], 99 );

        // Review tab section
        add_action( 'wcmp_vendor_shop_page_reviews_endpoint', array(&$this, 'wcmp_vendor_shop_page_reviews_endpoint' ), 10, 2 );
        // Pllicies tab section
        if (get_wcmp_vendor_settings('is_policy_on', 'general') && get_wcmp_vendor_settings('is_policy_on', 'general') == 'Enable') {
            add_action( 'wcmp_vendor_shop_page_policies_endpoint', array(&$this, 'wcmp_vendor_shop_page_policies_endpoint' ), 10, 2 );
        }
        flush_rewrite_rules();

        // Customer follows vendor list on my account page
        if ( get_wcmp_vendor_settings('store_follow_enabled', 'general') && get_wcmp_vendor_settings('store_follow_enabled', 'general') == 'Enable' ) {
            add_filter( 'woocommerce_account_menu_items',array($this, 'wcmp_customer_followers_vendor'), 99 );
            add_action( 'woocommerce_account_followers_endpoint', array($this, 'wcmp_customer_followers_vendor_callback' ));
        }
        if (get_wcmp_vendor_settings( 'is_checkout_delivery_location_on', 'general' ) && 'Enable' === get_wcmp_vendor_settings( 'is_checkout_delivery_location_on', 'general' )) {
            add_filter( 'woocommerce_checkout_fields', array( &$this, 'wcmp_checkout_user_location_fields' ), 50 );
            add_action( 'woocommerce_after_checkout_billing_form', array( &$this, 'wcmp_checkout_user_location_map' ), 50 );
            add_action( 'woocommerce_checkout_update_order_review', array( &$this, 'wcmp_checkout_user_location_session_set' ), 50 );
            add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'wcmp_checkout_user_location_save' ), 50 );
        }
    }

    /**
     * Save the extra register fields.
     *
     * @param  int  $customer_id Current customer ID.
     *
     * @return void
     */
    function wcmp_save_extra_register_fields($customer_id) {
        global $WCMp;
        if (isset($_POST['wcmp_vendor_fields']) && isset($_POST['pending_vendor'])) {

            if (isset($_FILES['wcmp_vendor_fields'])) {
                $attacment_files = array_filter($_FILES['wcmp_vendor_fields']);
                $files = array();
                $count = 0;
                if (!empty($attacment_files) && is_array($attacment_files)) {
                    foreach ($attacment_files['name'] as $key => $attacment) {
                        foreach ($attacment as $key_attacment => $value_attacment) {
                            $files[$count]['name'] = $value_attacment;
                            $files[$count]['type'] = $attacment_files['type'][$key][$key_attacment];
                            $files[$count]['tmp_name'] = $attacment_files['tmp_name'][$key][$key_attacment];
                            $files[$count]['error'] = $attacment_files['error'][$key][$key_attacment];
                            $files[$count]['size'] = $attacment_files['size'][$key][$key_attacment];
                            $files[$count]['field_key'] = $key;
                            $count++;
                        }
                    }
                }
                $upload_dir = wp_upload_dir();
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                if (!function_exists('wp_handle_upload')) {
                    require_once( ABSPATH . 'wp-admin/includes/file.php' );
                }
                foreach ($files as $file) {
                    $uploadedfile = $file;
                    $upload_overrides = array('test_form' => false);
                    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                    if ($movefile && !isset($movefile['error'])) {
                        $filename = $movefile['file'];
                        $filetype = wp_check_filetype($filename, null);
                        $attachment = array(
                            'post_mime_type' => $filetype['type'],
                            'post_title' => $file['name'],
                            'post_content' => '',
                            'post_status' => 'inherit',
                            'guid' => $movefile['url']
                        );
                        $attach_id = wp_insert_attachment($attachment, $movefile['file']);
                        $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        $_POST['wcmp_vendor_fields'][$file['field_key']]['value'][] = $attach_id;
                    }
                }
            }
            $wcmp_vendor_fields = isset( $_POST['wcmp_vendor_fields'] ) ? array_filter( array_map( 'wc_clean', (array) $_POST['wcmp_vendor_fields'] ) ) : '';

            $wcmp_vendor_fields = apply_filters('wcmp_save_registration_fields', $wcmp_vendor_fields, $customer_id);
            update_user_meta($customer_id, 'wcmp_vendor_fields', $wcmp_vendor_fields);

            // WCMp Vendor Registration form data mapping
            $data_type_map = apply_filters('wcmp_vendor_formdata_mapped_types', array('vendor_address_1', 'vendor_address_2', 'vendor_phone', 'vendor_country', 'vendor_state', 'vendor_city', 'vendor_postcode', 'vendor_paypal_email', 'vendor_description'));
            if ($wcmp_vendor_fields && is_array($wcmp_vendor_fields)) {
                $data = array();
                foreach ($wcmp_vendor_fields as $key => $value) {
                    if (in_array($value['type'], $data_type_map)) {
                        $has_value = get_user_meta($customer_id, '_' . $value['type'], true);
                        if (!$has_value && wc_clean($value['value'])) {
                            $data[$value['type']] = wc_clean($value['value']);
                        }
                    }
                }
                $WCMp->vendor_dashboard->save_store_settings($customer_id, $data);
            }
            $WCMp->user->wcmp_woocommerce_created_customer_notification();
        }
    }

    /**
     * Validate the extra register fields.
     *
     * @param  string $username          Current username.
     * @param  string $email             Current email.
     * @param  object $validation_errors WP_Error object.
     *
     * @return void
     */
    function wcmp_validate_extra_register_fields($username, $email, $validation_errors) {
        $wcmp_vendor_registration_form_data = wcmp_get_option('wcmp_vendor_registration_form_data');
        if(isset($_POST['g-recaptchatype']) && $_POST['g-recaptchatype'] == 'v2'){
            if (isset($_POST['g-recaptcha-response']) && empty($_POST['g-recaptcha-response'])) {
                $validation_errors->add('recaptcha is not validate', __('Please Verify  Recaptcha', 'dc-woocommerce-multi-vendor'));
            }
        }elseif(isset($_POST['g-recaptchatype']) && $_POST['g-recaptchatype'] == 'v3') {
            $recaptcha_secret = isset($_POST['recaptchav3_secretkey']) ? wc_clean( $_POST['recaptchav3_secretkey'] ) : '';
            $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
            $recaptcha_response = isset($_POST['recaptchav3Response']) ? wc_clean( $_POST['recaptchav3Response'] ) : '';

            $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
            $recaptcha = json_decode($recaptcha);

            if ( !$recaptcha->success || $recaptcha->score < 0.5 ) {
                $validation_errors->add('recaptcha is not validate', __('Please Verify  Recaptcha', 'dc-woocommerce-multi-vendor'));
            }
        }
        
        if (isset($_FILES['wcmp_vendor_fields'])) {
            $attacment_files = array_filter($_FILES['wcmp_vendor_fields']);
            if (!empty($attacment_files) && is_array($attacment_files)) {
                foreach ($attacment_files['name'] as $key => $value) {
                    $file_type = array();
                    foreach ($wcmp_vendor_registration_form_data[$key]['fileType'] as $key1 => $value1) {
                        if ($value1['selected']) {
                            array_push($file_type, $value1['value']);
                        }
                    }
                    foreach ($attacment_files['type'][$key] as $file_key => $file_value) {
                        if (!empty($attacment_files['name'][$key][$file_key])) {
                            if ($wcmp_vendor_registration_form_data[$key]['required'] && !in_array($file_value, $file_type)) {
                                $validation_errors->add('file type error', __('Please Upload valid file', 'dc-woocommerce-multi-vendor'));
                            }
                        }
                    }
                    foreach ($attacment_files['size'][$key] as $file_size_key => $file_size_value) {
                        if (!empty($wcmp_vendor_registration_form_data[$key]['fileSize'])) {
                            if ($wcmp_vendor_registration_form_data[$key]['required'] && $file_size_value > $wcmp_vendor_registration_form_data[$key]['fileSize']) {
                                $validation_errors->add('file size error', __('File upload limit exceeded', 'dc-woocommerce-multi-vendor'));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Populate vendor registration form
     * @global object $WCMp
     */
    function wcmp_vendor_register_form_callback() {
        global $WCMp;
        $wcmp_vendor_registration_form_data = wcmp_get_option('wcmp_vendor_registration_form_data');
        $WCMp->template->get_template('vendor_registration_form.php', array('wcmp_vendor_registration_form_data' => $wcmp_vendor_registration_form_data));
    }

    /**
     * Display custom message in woocommerce thank you page
     * @global object $wpdb
     * @global object $WCMp
     * @param int $order_id
     */
    public function display_vendor_msg_in_thank_you_page($order_id) {
        global $WCMp;
        $order = wc_get_order($order_id);
        $items = $order->get_items('line_item');
        $vendor_array = array();
        $author_id = '';
        $customer_support_details_settings = get_option('wcmp_general_customer_support_details_settings_name');
        $is_csd_by_admin = '';
        foreach ($items as $item_id => $item) {
            $product_id = wc_get_order_item_meta($item_id, '_product_id', true);
            if ($product_id) {
                $author_id = wc_get_order_item_meta($item_id, '_vendor_id', true);
                if (empty($author_id)) {
                    $product_vendors = get_wcmp_product_vendors($product_id);
                    if (isset($product_vendors) && (!empty($product_vendors))) {
                        $author_id = $product_vendors->id;
                    } else {
                        $author_id = get_post_field('post_author', $product_id);
                    }
                }
                if (isset($vendor_array[$author_id])) {
                    $vendor_array[$author_id] = $vendor_array[$author_id] . ',' . $item['name'];
                } else {
                    $vendor_array[$author_id] = $item['name'];
                }
            }
        }
        if (!empty($vendor_array)) {
            echo '<div style="clear:both">';
            if (apply_filters('can_vendor_add_message_on_email_and_thankyou_page', true)) {
                $WCMp->template->get_template('vendor_message_to_buyer.php', array('vendor_array' => $vendor_array, 'capability_settings' => $customer_support_details_settings, 'customer_support_details_settings' => $customer_support_details_settings));
            } elseif (get_wcmp_vendor_settings('is_customer_support_details', 'general') == 'Enable') {
                $WCMp->template->get_template('customer_support_details_to_buyer.php', array('vendor_array' => $vendor_array, 'capability_settings' => $customer_support_details_settings, 'customer_support_details_settings' => $customer_support_details_settings));
            }
            echo "</div>";
        }
    }

    /**
     * split woocommerce shipping packages 
     * @since 2.6.6
     * @param array $packages
     * @return array
     */
    public function wcmp_split_shipping_packages($packages) {
        // Reset all packages
        $packages = array();
        $split_packages = array();
        foreach (WC()->cart->get_cart() as $item) {
            if ($item['data']->needs_shipping()) {
                $product_id = $item['product_id'];
                $vendor = get_wcmp_product_vendors($product_id);
                if ($vendor && $vendor->is_shipping_enable()) {
                    $split_packages[$vendor->id][] = $item;
                } else {
                    $split_packages[0][] = $item;
                }
            }
        }

        foreach ($split_packages as $vendor_id => $split_package) {
            $packages[$vendor_id] = array(
                'contents' => $split_package,
                'contents_cost' => array_sum(wp_list_pluck($split_package, 'line_total')),
                'applied_coupons' => WC()->cart->get_applied_coupons(),
                'user' => array(
                    'ID' => $vendor_id,
                ),
                'vendor_id' => $vendor_id,
                'destination' => array(
                    'country' => WC()->customer->get_shipping_country(),
                    'state' => WC()->customer->get_shipping_state(),
                    'postcode' => WC()->customer->get_shipping_postcode(),
                    'city' => WC()->customer->get_shipping_city(),
                    'address' => WC()->customer->get_shipping_address(),
                    'address_2' => WC()->customer->get_shipping_address_2()
                )
            );

            if( apply_filters( 'wcmp_is_allow_checkout_user_location', true ) ) {
                $wcmp_user_location     = WC()->session->get( '_wcmp_user_location' );
                $wcmp_user_location_lat = WC()->session->get( '_wcmp_user_location_lat' );
                $wcmp_user_location_lng = WC()->session->get( '_wcmp_user_location_lng' );
                if( $wcmp_user_location ) {
                    $packages[$vendor_id]['wcmp_user_location']     = $wcmp_user_location;
                    $packages[$vendor_id]['wcmp_user_location_lat'] = $wcmp_user_location_lat;
                    $packages[$vendor_id]['wcmp_user_location_lng'] = $wcmp_user_location_lng;
                }
            }
        }
        return apply_filters('wcmp_split_shipping_packages', $packages);
    }

    /**
     * 
     * @param object $item
     * @param sting $package_key as $vendor_id
     */
    public function add_meta_date_in_shipping_package($item, $package_key, $package, $order) {
        $vendor_id = ( isset( $package['vendor_id'] ) && $package['vendor_id'] ) ? $package['vendor_id'] : $package_key;
        $item->add_meta_data('vendor_id', $vendor_id, true);
        $package_qty = array_sum(wp_list_pluck($package['contents'], 'quantity'));
        $item->add_meta_data('package_qty', $package_qty, true);
        do_action('wcmp_add_shipping_package_meta_data');
    }

    /**
     * Rename shipping packages 
     * @since 2.6.6
     * @param string $package_name
     * @param string $vendor_id
     * @param array $package
     * @return string
     */
    public function woocommerce_shipping_package_name($package_name, $vendor_id, $package) {
        if ($vendor_id && $vendor_id != 0) {
            $vendor = get_wcmp_vendor($vendor_id);
            
            if ($vendor) {
                return $vendor->page_title . __(' Shipping', 'dc-woocommerce-multi-vendor');
            }
            return $package_name;
        }
        return $package_name;
    }

    /**
     * Process order after checkout for shipping, Tax calculation.
     *
     * @param int $order_id
     * @param array $order_posted
     * @param WC_Order $order WooCommerce order object
     * @return void
     */
    public function wcmp_checkout_order_processed($order_id, $order_posted, $order) {
        if (!get_post_meta($order_id, '_wcmp_order_processed', true)) {
            wcmp_process_order($order_id, $order);
        }
    }

    /**
     * Add frontend scripts
     * @return void
     */
    public function frontend_scripts() {
        global $WCMp, $wp_scripts;
        $frontend_script_path = $WCMp->plugin_url . 'assets/frontend/js/';
        $frontend_script_path = str_replace(array('http:', 'https:'), '', $frontend_script_path);
        $suffix = defined('WCMP_SCRIPT_DEBUG') && WCMP_SCRIPT_DEBUG ? '' : '.min';

        wp_register_script('wcmp_frontend_vdashboard_js', $frontend_script_path . 'wcmp_vendor_dashboard' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('frontend_js', $frontend_script_path . 'frontend' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('vendor_order_by_product_js', $frontend_script_path . 'vendor_order_by_product' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        //wp_register_script('simplepopup_js', $frontend_script_path . 'simplepopup' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_new_vandor_dashboard_js', $frontend_script_path . 'vendor_dashboard' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_seller_review_rating_js', $frontend_script_path . 'vendor_review_rating' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_single_product_multiple_vendors', $frontend_script_path . 'single-product-multiple-vendors' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_customer_qna_js', $frontend_script_path . 'wcmp-customer-qna' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_custom_scroller_js', $frontend_script_path . 'jquery.mCustomScrollbar.concat.min.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_country_state_js', $frontend_script_path . 'wcmp-country-state.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp-vendor-shipping', $frontend_script_path . 'vendor-shipping.js', array( 'jquery' ), $WCMp->version, true );
        wp_register_script('wcmp-meta-boxes', $frontend_script_path . 'meta-boxes.js', array( 'jquery' ), $WCMp->version, true );
     
        /** localize script data * */
        $WCMp->localize_script('frontend_js');
        $WCMp->localize_script('wcmp_frontend_vdashboard_js');
        $WCMp->localize_script('wcmp_single_product_multiple_vendors');
        $WCMp->localize_script('wcmp_seller_review_rating_js');
        $WCMp->localize_script('wcmp_customer_qna_js');
        $WCMp->localize_script('wcmp-vendor-shipping');
        
        if (is_vendor_dashboard()) {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-blockui');
            wp_enqueue_script( 'wc-country-select' );

            $WCMp->library->load_bootstrap_script_lib();
            $WCMp->library->load_qtip_lib();
            wp_enqueue_script('wcmp_frontend_vdashboard_js');
            wp_enqueue_script('wcmp_new_vandor_dashboard_js');
            wp_enqueue_script('vendor_order_by_product_js');
            wp_enqueue_script('wcmp_seller_review_rating_js');
            wp_enqueue_script('wcmp_customer_qna_js');
            wp_enqueue_script('wcmp_custom_scroller_js');
            wp_enqueue_script('wcmp_country_state_js');
        }
        if (is_woocommerce()) {
            wp_enqueue_script('frontend_js');
            //wp_enqueue_script('simplepopup_js');
            wp_enqueue_script('wcmp_single_product_multiple_vendors');
            wp_enqueue_script('wcmp_customer_qna_js');
        }
        if (wcmp_is_store_page()) {
            wp_enqueue_script('wcmp_seller_review_rating_js');
            wp_enqueue_script('frontend_js');
        }
        if( is_checkout() && apply_filters( 'wcmp_is_allow_checkout_user_location', true ) ) {
            if (wcmp_mapbox_api_enabled()) {
                $WCMp->library->load_mapbox_api();
            } else {
                $WCMp->library->load_gmap_api();                
            }
            wp_enqueue_script( 'wcmp_checkout_location_js', $frontend_script_path . 'checkout/wcmp-script-checkout-location' . $suffix . '.js', array('jquery' ), $WCMp->version, true );
            wp_localize_script( 'wcmp_checkout_location_js', 'wcmp_checkout_map_options', array( 'search_location' => __( 'Insert your address ..', 'dc-woocommerce-multi-vendor' ), 'mapbox_emable' => wcmp_mapbox_api_enabled(), 'default_lat' => -79.4512, 'default_lng' => 43.6568, 'default_zoom' => 2, 'store_icon' => $WCMp->plugin_url . 'assets/images/store-marker.png', 'icon_width' => apply_filters( 'wcmp_map_icon_width', 40 ), 'icon_height' => apply_filters( 'wcmp_map_icon_height', 57 ) ) );
        }
    }

    /**
     * Add frontend styles
     * @return void
     */
    public function frontend_styles() {
        global $WCMp;
        $frontend_style_path = $WCMp->plugin_url . 'assets/frontend/css/';
        $frontend_style_path = str_replace(array('http:', 'https:'), '', $frontend_style_path);
        $suffix = defined('WCMP_SCRIPT_DEBUG') && WCMP_SCRIPT_DEBUG ? '' : '.min';
        $is_vendor_dashboard = is_vendor_dashboard() && is_user_logged_in() && (is_user_wcmp_vendor(get_current_user_id()) || is_user_wcmp_pending_vendor(get_current_user_id()) || is_user_wcmp_rejected_vendor(get_current_user_id())) && apply_filters('wcmp_vendor_dashboard_dequeue_global_styles', true);
        if ($is_vendor_dashboard) {
            $this->wcmp_dequeue_global_style();
        }

        wp_register_style('frontend_css', $frontend_style_path . 'frontend' . $suffix . '.css', array(), $WCMp->version);
        wp_register_style('product_css', $frontend_style_path . 'product' . $suffix . '.css', array(), $WCMp->version);
        wp_register_style('vandor-dashboard-style', $frontend_style_path . 'vendor_dashboard' . $suffix . '.css', array(), $WCMp->version);
        wp_register_style('multiple_vendor', $frontend_style_path . 'multiple-vendor' . $suffix . '.css', array(), $WCMp->version);
        wp_register_style('wcmp_custom_scroller', $frontend_style_path . 'lib/jquery.mCustomScrollbar.css', array(), $WCMp->version);
        wp_register_style( 'advance-product-manager', $frontend_style_path . 'advance-product-manager.css', array(), $WCMp->version );
        // register vendor shoppage css
        wp_register_style( 'wcmp_seller_shop_page_css', $frontend_style_path . 'wcmp-shop-page' . $suffix . '.css', array(), $WCMp->version );
        // Add RTL support
        wp_style_add_data('vandor-dashboard-style', 'rtl', 'replace');
        wp_style_add_data('frontend_css', 'rtl', 'replace');
        wp_style_add_data('product_css', 'rtl', 'replace');
        wp_style_add_data('advance-product-manager', 'rtl', 'replace');

        if (is_vendor_dashboard() && is_user_logged_in() && (is_user_wcmp_vendor(get_current_user_id()) || is_user_wcmp_pending_vendor(get_current_user_id()) || is_user_wcmp_rejected_vendor(get_current_user_id()))) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style('jquery-ui-style');
            $WCMp->library->load_bootstrap_style_lib();
            wp_enqueue_style('vandor-dashboard-style');
            wp_add_inline_style('vandor-dashboard-style', get_wcmp_vendor_settings('wcmp_vendor_dashboard_custom_css', 'vendor', 'dashboard'));
            wp_enqueue_style('wcmp_custom_scroller');
        }
        if (is_woocommerce()) {
            wp_enqueue_style('product_css');
            wp_enqueue_style('multiple_vendor');
            // add styly to product page
            $pstyle = '.wcmp-product-policies .description { margin: 0 0 1.41575em;}';
            wp_add_inline_style('woocommerce-inline', $pstyle);
        }
        if (wcmp_is_store_page()) {
            wp_enqueue_style('frontend_css');
            // Enqueue shop page css
            wp_enqueue_style('wcmp_seller_shop_page_css');
        }
        do_action('wcmp_frontend_enqueue_scripts', $is_vendor_dashboard);
    }

    public function wcmp_dequeue_global_style() {
        global $wp_styles;
        $styles_to_keep = apply_filters('wcmp_styles_to_keep', array('admin-bar', 'select2', 'dashicons', 'qtip_css'));
        // loop over all of the registered scripts
        foreach ($wp_styles->registered as $handle => $data) {
            // remove all style
            if (!in_array($handle, $styles_to_keep)) {
                wp_dequeue_style($handle);
            }
        }
    }

    /**
     * Add html for vendor taxnomy page
     * @return void
     */
    public function product_archive_vendor_info() {
        global $WCMp;
        if (wcmp_is_store_page()) {
            $store_id = wcmp_find_shop_page_vendor();
            $vendor = get_wcmp_vendor($store_id);
            if( $vendor ){
                $image = $vendor->get_image() ? $vendor->get_image() : $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
                $description = $vendor->description;

                $address = $vendor->get_formatted_address();

                $WCMp->template->get_template('archive_vendor_info.php', array('vendor_id' => $vendor->id, 'banner' => $vendor->get_image('banner'), 'profile' => $image, 'description' => apply_filters('the_content', $description), 'mobile' => $vendor->phone, 'location' => $address, 'email' => $vendor->user_data->user_email));
            }
        }
    }

    /**
     * Add 'woocommerce' class to body tag for vendor pages
     *
     * @param  arr $classes Existing classes
     * @return arr          Modified classes
     */
    public function set_product_archive_class($classes) {
        global $WCMp;
        if (wcmp_is_store_page()) {

            // Add generic classes
            $classes[] = 'woocommerce';
            $classes[] = 'product-vendor';

            // Get vendor ID
            $vendor_id = wcmp_find_shop_page_vendor();

            // Get vendor info
            $vendor = get_wcmp_vendor($vendor_id);

            // Add vendor slug as class
            if ('' != $vendor->slug) {
                $classes[] = $vendor->slug;
            }
        }
        return $classes;
    }

    /**
     * template redirect function
     * @return void
     */
    public function template_redirect() {
        //redirect to my account or vendor dashbord page if user loggedin
        if (is_user_logged_in() && is_page_vendor_registration()) {
            if (current_user_can('administrator') || current_user_can('shop_manager')) {
                wp_safe_redirect(get_permalink(wc_get_page_id('myaccount')));
            } else {
                wp_safe_redirect(get_permalink(wcmp_vendor_dashboard_page_id()));
            }
            exit();
        }elseif(is_product()){
            // restrict block/suspended vendor product view
            global $post;
            if($post){
                $vendor = get_wcmp_product_vendors($post->ID);
                if($vendor){
                    $is_block = get_user_meta($vendor->id, '_vendor_turn_off', true);
                    if($is_block) wp_safe_redirect(apply_filters('wcmp_suspended_vendor_product_view_return_url', wc_get_page_permalink( 'shop' )));
                }
            }
        }
    }

    /**
     * Return vendor dashboard template
     * @global object $WCMp
     * @param string $page_template
     * @return string
     * @since 3.0.0
     */
    public function wcmp_vendor_dashboard_template($page_template) {
        global $WCMp;
        if (is_vendor_dashboard() && is_user_logged_in() && (is_user_wcmp_vendor(get_current_user_id()) || is_user_wcmp_pending_vendor(get_current_user_id()) || is_user_wcmp_rejected_vendor(get_current_user_id())) && apply_filters('wcmp_vendor_dashboard_exclude_header_footer', true)) {
            return $WCMp->template->locate_template('template-vendor-dashboard.php');
        }
        return $page_template;
    }

    /**
     * Store visitors Stats
     * @global object $WCMp
     * @return void
     * @since 3.0.0
     */
    public function wcmp_store_visitors_stats() {
        global $WCMp;
        $product_vendor = false;
        if (is_product()) {
            global $post;
            $product_vendor = get_wcmp_product_vendors($post->ID);
        } elseif (wcmp_is_store_page()) {
            $vendor_id = wcmp_find_shop_page_vendor();
            $product_vendor = get_wcmp_vendor($vendor_id);;
        }
        if ($product_vendor && isset($_COOKIE["_wcmp_user_cookie_" . get_current_user_id()])) {
            $ip_data = get_visitor_ip_data();
            if( !empty($ip_data) && $ip_data->status == 'success' ) {
                $ip_data->user_id = get_current_user_id();
                $ip_data->user_cookie = $_COOKIE["_wcmp_user_cookie_" . get_current_user_id()];
                $ip_data->session_id = session_id();
                wcmp_save_visitor_stats($product_vendor->id, $ip_data);
            }
        }
    }

    public function wcmp_shipping_zone_same_region_criteria( $criteria, $package, $postcode_locations ) {
        global $wpdb;
        $postcode  = wc_normalize_postcode( wc_clean( $package['destination']['postcode'] ) );
        if( !$postcode ) return $criteria;
        $search_results = $wpdb->get_results($wpdb->prepare(
            "SELECT vendor_id,zone_id
            FROM {$wpdb->prefix}wcmp_shipping_zone_locations where location_code = %s", $postcode
            ));
        $match_rates = array();
        if ( !empty( $search_results ) ) {
            foreach ($search_results as $key => $value) {
                if( $value->vendor_id == $package['vendor_id'] ) {
                    $match_rates[] = $value->zone_id;
                }
            }
            if( !empty( $match_rates ) ) {
                $criteria[] = 'AND zones.zone_id IN (' . implode( ',', $match_rates ) . ')';
            }
        }
        return $criteria;
    }

    function wpml_wcmp_product_manager_translations($product_id) {
        global $sitepress;
        if( !$product_id ) return;
        $active_languages = $this->get_filtered_active_lanugages();
        if ( count( $active_languages ) <= 1 ) {
            return;
        }
        $current_language = $sitepress->get_current_language();
        unset( $active_languages[ $current_language ] );

        if ( count( $active_languages ) > 0 ) {
            $translation_html = '';
            ?>
            <div id="product_images_container" class="custom-panel">
                <div style="max-width: 214px; margin: 0 auto;">
                    <h3><p class="product_translations"><strong><?php esc_html_e( 'Translations', 'dc-woocommerce-multi-vendor' ); ?></strong></p></h3>
                    <label class="screen-reader-text" for="product_translations"><?php esc_html_e( 'Translations', 'dc-woocommerce-multi-vendor' ); ?></label>
                    
                    <table style="margin-top:0px;">
                        <tbody id="wcmp_product_translations" data-product_id="<?php echo esc_attr($product_id); ?>">
                            <?php echo $translation_html; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Get list of active languages.
     *
     * @return array
     */
    public function get_filtered_active_lanugages() {
        global $sitepress; 
        $active_languages = $sitepress->get_active_languages();
        return apply_filters( 'wpml_active_languages_access', $active_languages, array( 'action' => 'edit' ) );
    }

    /**
     * Store Page WPML Language Switcher Compatibility
     */
    function wcmp_store_page_wpml_language_switcher( $languages ) {
        global $WCMp, $sitepress;
        $default_lang = $sitepress->get_default_language();
        if ( wcmp_is_store_page() ) {
            if ( defined( 'ICL_SITEPRESS_VERSION' ) && ! ICL_PLUGIN_INACTIVE && class_exists( 'SitePress' ) ) {
                $vendor_id = wcmp_find_shop_page_vendor();
                $vendor = get_wcmp_vendor($vendor_id);
                $formated_languages = array();
                $default_lang = $sitepress->get_default_language();
                $permalinks = get_option('dc_vendors_permalinks');
                $vendor_permalink = is_array($permalinks) && isset($permalinks['vendor_shop_base']) && !empty($permalinks['vendor_shop_base']) ? $permalinks['vendor_shop_base'] : 'vendor';
                if( !empty( $languages ) ) {
                    $is_wpml_configured = apply_filters( 'wpml_setting', 0, 'language_negotiation_type' );
                    foreach( $languages as $lang => $language ) {
                        if( $default_lang  && ( $default_lang  == $language['language_code'] ) ) {
                            $language['url'] = site_url() .'/'. $vendor_permalink .'/'. $vendor->page_title;
                        } else {
                            if ($is_wpml_configured == 3) {
                                $language['url'] = site_url() .'/'. $vendor_permalink .'/'. $vendor->page_title . '/' . '?lang='. $language['language_code'];
                            } else {
                                $language['url'] = site_url() .'/'. $language['language_code'] .'/'. $vendor_permalink .'/'. $vendor->page_title;
                            }
                        }
                        $formated_languages[$lang] = $language;
                    }
                    $languages = $formated_languages;
                }
            }
        }
        return $languages;
    }

    public function wpml_language_switcher_option_on_dropdown() {
        global $WCMp;
        if ( $WCMp->endpoints->get_current_endpoint() != 'edit-product' ) {
            ?><div style="height: 100px; overflow: scroll;">
                <?php do_action( 'wpml_footer_language_selector'); ?>
            </div><?php 
        }
    }

    public function wcmp_vendor_shop_page_reviews_endpoint( $store_id, $query_vars_name ) {
        global $WCMp;
        $WCMp->review_rating->wcmp_seller_review_rating_form();
    }

    public function wcmp_vendor_shop_page_policies_endpoint( $store_id, $query_vars_name ) {
        $_vendor_shipping_policy = get_user_meta( $store_id, '_vendor_shipping_policy', true ) ? get_user_meta( $store_id, '_vendor_shipping_policy', true ) : __( 'No policy found', 'dc-woocommerce-multi-vendor' );

        $_vendor_refund_policy = get_user_meta( $store_id, '_vendor_refund_policy', true ) ? get_user_meta( $store_id, '_vendor_refund_policy', true ) : __( 'No policy found', 'dc-woocommerce-multi-vendor' );

        $_vendor_cancellation_policy = get_user_meta( $store_id, '_vendor_cancellation_policy', true ) ? get_user_meta( $store_id, '_vendor_cancellation_policy', true ) : __( 'No policy found', 'dc-woocommerce-multi-vendor' );

        ?>
        <div class="wcmp-policie-sec">
            <div class="wcmp-policies-header wcmp-tabcontent-header">
                <div class='wcmp-heading'><?php esc_html_e( 'Shop policies', 'dc-woocommerce-multi-vendor' ); ?></div>
            </div>
            <!-- Shipping policy -->
            <div>
                <div class="wcmp-sub-heading">
                    <span class="dashicons dashicons-cart"></span>
                    <p><?php esc_html_e( 'Shipping Policy', 'dc-woocommerce-multi-vendor' ); ?></p>
                </div>
                <div class='wcmp-policie-sub-area'>
                    <p><?php echo wp_kses_post( $_vendor_shipping_policy ); ?></p>
                </div>
            </div>
            <!-- Refund policy -->
            <div>
                <div class="wcmp-sub-heading">
                    <span class="dashicons dashicons-cart"></span>
                    <p><?php esc_html_e( 'Refund Policy', 'dc-woocommerce-multi-vendor' ); ?></p>
                </div>
                <div class='wcmp-policie-sub-area'>
                    <p><?php echo wp_kses_post( $_vendor_refund_policy ); ?></p>
                </div>
            </div>
            <!-- Cancellation policy -->
            <div>
                <div class="wcmp-sub-heading">
                    <span class="dashicons dashicons-cart"></span>
                    <p><?php esc_html_e( 'Cancellation/Return/Exchange Policy', 'dc-woocommerce-multi-vendor' ); ?></p>
                </div>
                <div class='wcmp-policie-sub-area'>
                    <p><?php echo wp_kses_post( $_vendor_cancellation_policy ); ?></p>
                </div>
            </div>
            <?php do_action( 'wcmp_vendor_shop_page_policies', $store_id, $query_vars_name  ); ?>
        </div>
        <?php
    }

    public function store_query_filter( $query ) {
        global $wp_query, $WCMp;
        $author = get_query_var( $this->custom_store_url );
        if ( ! is_admin() && $query->is_main_query() && ! empty( $author ) ) {
            $seller_info = '';
            $term_details = get_term_by('slug', $author, $WCMp->taxonomy->taxonomy_name);
            $term_id = $term_details && $term_details->term_id ? $term_details->term_id : 0;
            if ($term_id) {
               $seller_info = get_wcmp_vendor_by_term($term_id);
            }
            if ( ! $seller_info ) {
                return get_404_template();
            }

            $query->set( 'post_type', 'product' );
            $query->set( 'author_name', $seller_info->user_data->data->user_nicename );
        }
    }

    function prefix_register_query_var( $vars ) {
        $vars[] = $this->custom_store_url;
        return $vars;
    }

    function wcmp_vendor_page_query_vars() {
        $query_vars = apply_filters(
            'wcmp_query_var_filter', [
                'reviews',
                'policies'
            ]
        );
        return $query_vars;
    }

    function wcmp_sidebar_init() {
        $query_vars = $this->wcmp_vendor_page_query_vars();
        foreach ( $query_vars as $var ) {
            add_rewrite_endpoint( $var, EP_PAGES );
        }
        add_rewrite_rule( $this->custom_store_url.'/([^/]+)/reviews?$', 'index.php?'.$this->custom_store_url.'=$matches[1]&reviews=true', 'top' );
        add_rewrite_rule( $this->custom_store_url.'/([^/]+)/policies?$', 'index.php?'.$this->custom_store_url.'=$matches[1]&policies=true', 'top' );
    }

    public function wcmp_sidebar() {
        wc_get_template( 'global/sidebar.php' );
    }

    public function wcmp_after_main_content() {
        wc_get_template( 'global/wrapper-start.php' );
    }

    public function wcmp_store_tab_widget_contents() {
        global $WCMp;
        $store_id = wcmp_find_shop_page_vendor();
        $shop_query_exist = false;
        $query_vars_name = 'products';
        $wcmp_shop_page_query_vars = $this->wcmp_vendor_page_query_vars();
        foreach ($wcmp_shop_page_query_vars as $key_query) {
            if (get_query_var($key_query)) {
                $shop_query_exist = true;
                $query_vars_name = $key_query;
            }
        }
        $store_tabs = $this->wcmp_get_store_tabs( $store_id );
        echo '<main id="main" class="site-main">';
        if ( ! empty( $store_tabs ) ) : ?>

        <div class='wcmp-main-section'>
            <?php if (get_wcmp_vendor_settings('store_sidebar_position', 'general') == 'left') do_action( 'wcmp_store_widget_contents' ); ?>
            <div class="column-class wcmp-middle-sec ">
                <div class="wcmp-tab-header">
                    <?php foreach( $store_tabs as $key => $tab ) { 
                        ?>
                        <?php if ( $tab['url'] ): ?>
                            <a href="<?php echo esc_url( $tab['url'] ); ?>">
                                <div class="wcmp-tablink <?php if( $tab['id'] == $query_vars_name ) echo 'active'; ?>">
                                    <?php echo esc_html( $tab['title'] ); ?>
                                </div>
                            </a>                         
                        <?php endif; ?>
                    <?php } ?>
                </div>
                <div>
                <?php

                if ($shop_query_exist) {
                    do_action( 'wcmp_vendor_shop_page_'. $query_vars_name .'_endpoint', $store_id, $query_vars_name );
                } else {
                    $this->wcmp_shop_product_callback(); 
                }

                ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (get_wcmp_vendor_settings('store_sidebar_position', 'general') == 'right') do_action( 'wcmp_store_widget_contents' ); ?>
        </div>
        <?php
    }

    public function wcmp_get_store_tabs( $store_id ) {
        $store_id = wcmp_find_shop_page_vendor();
        $vendor = get_wcmp_vendor($store_id);
        $userstore = $vendor->permalink;
        $tabs = array(
            'products' => array(
                'id' => 'products',
                'title' => __( 'Products', 'dc-woocommerce-multi-vendor' ),
                'url'   => $userstore,
                'priority' => 1
            )
        );
        if (get_wcmp_vendor_settings('is_policy_on', 'general') && get_wcmp_vendor_settings('is_policy_on', 'general') == 'Enable') {
            $tabs['policies'] = array(
                'id' => 'policies',
                'title' => __( 'Policies', 'dc-woocommerce-multi-vendor' ),
                'url'   => $this->wcmp_get_policies_url( $store_id ),
                'priority' => 3
            );
        }

        $is_enable = wcmp_seller_review_enable(absint($vendor->term_id));
        if (isset($is_enable) && $is_enable) {
            $tabs['reviews'] = array(
                'id' => 'reviews',
                'title' => __( 'Reviews', 'dc-woocommerce-multi-vendor' ),
                'url'   => $this->wcmp_get_review_url( $store_id ),
                'priority' => 2
            );
        }
        // reorder as per pririty
        usort($tabs, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        return apply_filters( 'wcmp_store_tabs', $tabs, $store_id );
    }

    function wcmp_get_policies_url( $user_id ) {
        if ( !$user_id ) {
            return '';
        }
        $vendor = get_wcmp_vendor($user_id);
        $userstore = $vendor->permalink;
        return apply_filters( 'wcmp_get_seller_policies_url', $userstore . 'policies' );
    }

    function wcmp_get_review_url( $user_id ) {
        if ( !$user_id ) {
            return '';
        }
        $vendor = get_wcmp_vendor($user_id);
        $userstore = $vendor->permalink;
        return apply_filters( 'wcmp_get_seller_review_url', $userstore . 'reviews' );
    }

    // Product loop callback
    public function wcmp_shop_product_callback() {

        if ( woocommerce_product_loop() ) {

            /**
            * Hook: woocommerce_before_shop_loop.
            *
            * @hooked woocommerce_output_all_notices - 10
            * @hooked woocommerce_result_count - 20
            * @hooked woocommerce_catalog_ordering - 30
            */
            do_action( 'woocommerce_before_shop_loop' );

            woocommerce_product_loop_start();

            if ( wc_get_loop_prop( 'total' ) ) {
                while ( have_posts() ) {
                    the_post();

                    /**
                    * Hook: woocommerce_shop_loop.
                    */
                    do_action( 'woocommerce_shop_loop' );

                    wc_get_template_part( 'content', 'product' );
                }
            }   

            woocommerce_product_loop_end();

            /**
            * Hook: woocommerce_after_shop_loop.
            *
            * @hooked woocommerce_pagination - 10
            */
            do_action( 'woocommerce_after_shop_loop' );
            } else {

            /**
            * Hook: woocommerce_no_products_found.
            *
            * @hooked wc_no_products_found - 10
            */
            do_action( 'woocommerce_no_products_found' );
        }
    }

    // Sideber as per admin choice
    public function wcmp_store_widget_contents() {

        if (get_wcmp_vendor_settings('store_sidebar_position', 'general') == 'left') { 
            $widget_class = 'wcmp-leftwidget-sec';
        } elseif (get_wcmp_vendor_settings('store_sidebar_position', 'general') == 'right') {
            $widget_class = 'wcmp-rightwidget-sec';
        } else {
            $widget_class = '';
        }
        if ($widget_class != '' && is_active_sidebar('sidebar-wcmp-store') && get_wcmp_vendor_settings('is_enable_store_sidebar', 'general') == 'Enable') {
            ?>
            <div class="column-class <?php //echo $widget_class ?>" >
                <?php dynamic_sidebar( 'sidebar-wcmp-store' ); ?>
            </div> 
            <?php
        }
    }

    public function wcmp_customer_followers_vendor($items) {
        if (is_user_wcmp_vendor(get_current_user_id())) {
            return $items;
        }
        unset( $items['customer-logout'] );
        $items[ 'followers' ] = __( 'Following', 'dc-woocommerce-multi-vendor' );
        $items[ 'customer-logout' ] = __( 'Log out', 'dc-woocommerce-multi-vendor' );
        return $items;
    }
    public function wcmp_customer_followers_vendor_callback() {
        $wcmp_customer_follow_vendor = get_user_meta( get_current_user_id(), 'wcmp_customer_follow_vendor', true ) ? get_user_meta( get_current_user_id(), 'wcmp_customer_follow_vendor', true ) : array();
        ?>
        <table>
            <tbody>
                <?php 
                if ($wcmp_customer_follow_vendor) {
                    foreach ($wcmp_customer_follow_vendor as $key_follow_vendor => $value_follow_vendor) {
                        $vendor = get_wcmp_vendor($value_follow_vendor['user_id']);
                        if (!$vendor) continue;
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($vendor->permalink); ?>"> <?php echo esc_html($vendor->page_title); ?> </a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    esc_html_e('You do not follow any customer till now', 'dc-woocommerce-multi-vendor');
                }
                ?>
            </tbody>
        </table>
        <?php
    }

        /**
     * Checkout User Location Field Save
     */
    public function wcmp_checkout_user_location_save( $order_id ) {
        if( apply_filters( 'wcmp_is_allow_checkout_user_location', true ) ) {
            if ( ! empty( $_POST['wcmp_user_location'] ) ) {
                update_post_meta( $order_id, '_wcmp_user_location', sanitize_text_field( $_POST['wcmp_user_location'] ) );
            }
            if ( ! empty( $_POST['wcmp_user_location_lat'] ) ) {
                update_post_meta( $order_id, '_wcmp_user_location_lat', sanitize_text_field( $_POST['wcmp_user_location_lat'] ) );
            }
            if ( ! empty( $_POST['wcmp_user_location_lng'] ) ) {
                update_post_meta( $order_id, '_wcmp_user_location_lng', sanitize_text_field( $_POST['wcmp_user_location_lng'] ) );
            }
        }
    }

    /**
     * Checkout User Location Field Save in Session
     */
    public function wcmp_checkout_user_location_session_set( $post_data_raw ) {
        if( apply_filters( 'wcmp_is_allow_checkout_user_location', true ) ) {
            parse_str( $post_data_raw, $post_data );
            if ( ! empty( $post_data['wcmp_user_location'] ) ) {
                WC()->customer->set_props( array( 'wcmp_user_location' => sanitize_text_field( $post_data['wcmp_user_location'] ) ) );
                WC()->session->set( '_wcmp_user_location', sanitize_text_field( $post_data['wcmp_user_location'] ) );
            }
            if ( ! empty( $post_data['wcmp_user_location_lat'] ) ) {
                WC()->session->set( '_wcmp_user_location_lat', sanitize_text_field( $post_data['wcmp_user_location_lat'] ) );
            }
            if ( ! empty( $post_data['wcmp_user_location_lng'] ) ) {
                WC()->session->set( '_wcmp_user_location_lng', sanitize_text_field( $post_data['wcmp_user_location_lng'] ) );
            }
        }
    }

    /**
     * Checkout User Location Field
     */
    public function wcmp_checkout_user_location_fields( $fields ) {
        ?>
        <style>
            .input-hidden{
                display: none;
            }
        </style>
        <?php
        if( ! WC()->is_rest_api_request() ) {
            if( ( true === WC()->cart->needs_shipping() ) && apply_filters( 'wcmp_is_allow_checkout_user_location', true ) ) {
                $user_location_filed = wcmp_mapbox_api_enabled() ? array('input-hidden') : array('form-row-wide');
                $fields['billing']['wcmp_user_location'] = array(
                        'label'     => __( 'Delivery Location', 'dc-woocommerce-multi-vendor' ),
                        'placeholder'   => _x( 'Insert your address ..', 'placeholder', 'dc-woocommerce-multi-vendor' ),
                        'required'  => true,
                        'class'     => $user_location_filed,
                        'clear'     => true,
                        'priority'  => 999,
                        'value'     => WC()->session->get( '_wcmp_user_location' )
                 );
                $fields['billing']['wcmp_user_location_lat'] = array(
                        'required'  => false,
                        'class'     => array('input-hidden'),
                        'value'     => WC()->session->get( '_wcmp_user_location_lat' )
                 );
                $fields['billing']['wcmp_user_location_lng'] = array(
                        'required'  => false,
                        'class'     => array('input-hidden'),
                        'value'     => WC()->session->get( '_wcmp_user_location_lng' )
                 );
            }
        }

     return $fields;
    }

    /**
     * Checkout User Location Map
     */
    public function wcmp_checkout_user_location_map( $checkout ) {
        if( ( true === WC()->cart->needs_shipping() ) && apply_filters( 'wcmp_is_allow_checkout_user_location', true ) ) {
            ?>
            <div class="woocommerce-billing-fields__field-wrapper">
                <div class="wcmp-user-locaton-map" id="wcmp-user-locaton-map" style="width: 100%; height: 300px;"></div>
            </div>
            <?php
        }
    }

}
