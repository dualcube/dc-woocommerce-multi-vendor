<?php

class WCMp_Settings_General {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;

    /**
     * Start up
     */
    public function __construct($tab) {
        $this->tab = $tab;
        $this->options = get_option("wcmp_{$this->tab}_settings_name");
        $this->settings_page_init();
        //general tab migration option
        
        
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
        global $WCMp;
        $singleproductmultiseller_show_order_option = array();
        $spmv_terms = $WCMp->taxonomy->get_wcmp_spmv_terms(array('orderby' => 'id'));
        if( $spmv_terms ) :
            foreach ($spmv_terms as $key => $term){
                $singleproductmultiseller_show_order_option[$term->slug] = $term->name;
            }
        endif;
        $singleproductmultiseller_show_order_option = apply_filters('wcmp_spmv_setting_general_show_order_option', $singleproductmultiseller_show_order_option);
        $settings_tab_options = array("tab" => "{$this->tab}",
            "ref" => &$this,
            "sections" => array(
                "vendor_approval_settings_section" => array("title" => '', // Section one
                    "fields" => apply_filters('wcmp_general_tab_filds', array(
//                        "enable_registration" => array('title' => __('Vendor Registration', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'enable_registration', 'label_for' => 'enable_registration', 'text' => __('Anyone can register as vendor. Leave it unchecked if you want to keep your site an invite only marketpace.', 'dc-woocommerce-multi-vendor'), 'name' => 'enable_registration', 'value' => 'Enable'), // Checkbox
                        "approve_vendor_manually" => array('title' => __('Approve Vendors Manually', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'approve_vendor_manually', 'label_for' => 'approve_vendor_manually', 'text' => __('If left unchecked, every vendor applicant will be auto-approved, which is not a recommended setting.', 'dc-woocommerce-multi-vendor'), 'name' => 'approve_vendor_manually', 'value' => 'Enable'), // Checkbox
                        "is_backend_diabled" => apply_filters('is_wcmp_backend_disabled',array('title' => __('Disallow Vendors wp-admin Access', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_backend_diabled', 'custom_tags'=> array('disabled' => 'disabled'), 'label_for' => 'is_backend_diabled', 'text' => __('Get <a href="//wc-marketplace.com/product/wcmp-frontend-manager/">Advanced Frontend Manager</a> to offer a single dashboard for all vendor purpose and eliminate their backend access requirement.', 'dc-woocommerce-multi-vendor'), 'name' => 'is_backend_diabled', 'value' => 'Enable', 'hints' => __('If unchecked vendor will have access to backend', 'dc-woocommerce-multi-vendor'))) , // Checkbox
                        "sold_by_catalog" => array('title' => __('Enable "Sold by"', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'sold_by_catalogg', 'label_for' => 'sold_by_catalogg', 'text' => stripslashes(__('On shop, cart and checkout page.', 'dc-woocommerce-multi-vendor')), 'name' => 'sold_by_catalog', 'value' => 'Enable'), // Checkbox
//                        "is_university_on" => array('title' => __('Vendor Knowledgebase', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_university_on', 'label_for' => 'is_university_on', 'name' => 'is_university_on', 'value' => 'Enable', 'text' => __('Enable "Knowledgebase" section on vendor dashboard.', 'dc-woocommerce-multi-vendor')), // Checkbox
                        "is_singleproductmultiseller" => array('title' => __('Single Product Multiple Vendors (SPMV)', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_singleproductmultiseller', 'label_for' => 'is_singleproductmultiseller', 'name' => 'is_singleproductmultiseller', 'value' => 'Enable', 'text' => __('Allow multiple vendors to sell the same product. Buyers can choose their preferred vendor.','dc-woocommerce-multi-vendor')), // Checkbox
                        "singleproductmultiseller_show_order" => array('title' => __('Show SPMV products', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'singleproductmultiseller_show_order', 'name' => 'singleproductmultiseller_show_order', 'label_for' => 'singleproductmultiseller_show_order', 'desc' => stripslashes(__('Select option for shown products under SPMV concept.', 'dc-woocommerce-multi-vendor')), 'options' => $singleproductmultiseller_show_order_option), // select
//                        "google_api_key" => array('title' => __('Google Map API key', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'google_api_key', 'label_for' => 'google_api_key', 'name' => 'google_api_key', 'hints' => __('Used for vendor store maps','dc-woocommerce-multi-vendor'), 'desc' => __('<a href="https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key" target="_blank">Click here to generate key</a>','dc-woocommerce-multi-vendor')),
                        "is_disable_marketplace_plisting" => array('title' => __('Disable Advanced Marketplace Product Listing', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_disable_marketplace_plisting', 'label_for' => 'is_disable_marketplace_plisting', 'name' => 'is_disable_marketplace_plisting', 'value' => 'Enable', 'text' => __('Disable advanced marketplace product listing flows like popular ecommerce site.', 'dc-woocommerce-multi-vendor'), 'hints' => __('Advanced Marketplace Product Listing is a well known e-commerce product listing where you cannot change the options like Categoires, GTIN once chosen. By disabling you can override it.', 'dc-woocommerce-multi-vendor')), // Checkbox  
                        "is_gtin_enable" => array('title' => __('Enable Product GTIN', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_gtin_enable', 'label_for' => 'is_gtin_enable', 'name' => 'is_gtin_enable', 'value' => 'Enable', 'text' => __('Enable product GTIN features', 'dc-woocommerce-multi-vendor')), // Checkbox  
                        "is_sellerreview" => array('title' => __('Enable Vendor Review', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_sellerreview', 'label_for' => 'is_sellerreview', 'name' => 'is_sellerreview', 'value' => 'Enable', 'text' => __('Buyers can rate and review vendor.', 'dc-woocommerce-multi-vendor')), // Checkbox  
                        "is_sellerreview_varified" => array('title' => __('', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_sellerreview_varified', 'label_for' => 'is_sellerreview_varified', 'name' => 'is_sellerreview_varified', 'value' => 'Enable', 'text' => __('Only buyers, purchased from the vendor can rate.', 'dc-woocommerce-multi-vendor')), // Checkbox 
                        "is_policy_on" => array('title' => __('Enable Policies ', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_policy_on', 'label_for' => 'is_policy_on', 'name' => 'is_policy_on', 'value' => 'Enable', 'text' => __("Enable this to let vendor add policies and display at Vendor's shop page", 'dc-woocommerce-multi-vendor')), // Checkbox
                        "is_customer_support_details" => array('title' => __('Enable Customer Support', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_customer_support_details', 'label_for' => 'is_customer_support_details', 'name' => 'is_customer_support_details', 'value' => 'Enable', 'text' => __('Show support channel details in "Thank You" page and new order email.', 'dc-woocommerce-multi-vendor')), // Checkbox
                        "show_related_products" => array('title' => __('Related Product Settings', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'show_related_products', 'name' => 'show_related_products', 'label_for' => 'show_related_products', 'desc' => stripslashes(__('Select related products to show on the single product page.', 'dc-woocommerce-multi-vendor')), 'options' => array('all_related' => __('Related Products from Entire Store', 'dc-woocommerce-multi-vendor'), 'vendors_related' => __("Related Products from Vendor's Store", 'dc-woocommerce-multi-vendor'), 'disable' => __('Disable', 'dc-woocommerce-multi-vendor'))), // select
                        "custom_date_order_stat_report_mail" => array('title' => __('Set custom date for order stat report mail', 'dc-woocommerce-multi-vendor'), 'type' => 'number', 'id' => 'custom_date_order_stat_report_mail', 'label_for' => 'custom_date_order_stat_report_mail', 'name' => 'custom_date_order_stat_report_mail', 'desc' => __('Email will send as per select dates ( put is blank for disabled it ).', 'dc-woocommerce-multi-vendor'), 'placeholder' => __('in days', 'dc-woocommerce-multi-vendor')), // Text
                        )
                    ),
                ),
                "wcmp_shipping_section" => array("title" => __('Shipping setting', 'dc-woocommerce-multi-vendor'), // Section one
                    "fields" => array(
                        "is_vendor_shipping_on" => array('title' => __('Enable Vendor Shipping ', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_vendor_shipping_on', 'label_for' => 'is_vendor_shipping_on', 'name' => 'is_vendor_shipping_on', 'value' => 'Enable', 'text' => __('If enabled vendor can configure their shipping on dashboard.', 'dc-woocommerce-multi-vendor')), // Checkbox
                        "enabled_distance_by_shipping_for_vendor" => array('title' => __('Enable Distance By Shipping', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'enabled_distance_by_shipping_for_vendor', 'label_for' => 'enabled_distance_by_shipping_for_vendor', 'name' => 'enabled_distance_by_shipping_for_vendor', 'value' => 'Enable', 'text' => __('If enabled vendor can configure shipping by distance.', 'dc-woocommerce-multi-vendor')), // Checkbox
                        "is_checkout_delivery_location_on" => array('title' => __('Enable Checkout Delivery Location', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_checkout_delivery_location_on', 'label_for' => 'is_checkout_delivery_location_on', 'name' => 'is_checkout_delivery_location_on', 'value' => 'Enable', 'text' => __('If enabled customer can add delivery location to map.', 'dc-woocommerce-multi-vendor'), 'hints' => __('You must enable this option whenever you are using the shipping by distance feature.', 'dc-woocommerce-multi-vendor')), // Checkbox
                    ),
                ),
                "wcmp_store_section" => array("title" => __('Store setting', 'dc-woocommerce-multi-vendor'), // Section one
                    "fields" => array(
                        "is_enable_store_sidebar" => array('title' => __('Enable Store Sidebar', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'is_enable_store_sidebar', 'label_for' => 'is_enable_store_sidebar', 'name' => 'is_enable_store_sidebar', 'value' => 'Enable', 'text' => __('Uncheck this to disable vendor store sidebar.', 'dc-woocommerce-multi-vendor')), // Checkbox
                        "store_sidebar_position" => array('title' => __('Store Sidebar Position', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'store_sidebar_position', 'name' => 'store_sidebar_position', 'label_for' => 'store_sidebar_position', 'desc' => stripslashes(__('Set store sidebar position.', 'dc-woocommerce-multi-vendor')), 'options' => array('left' => __('At Left', 'dc-woocommerce-multi-vendor'), 'right' => __("At Right", 'dc-woocommerce-multi-vendor'))), // select
                        "store_follow_enabled" => array('title' => __('Enable Store Follow', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'store_follow_enabled', 'label_for' => 'store_follow_enabled', 'name' => 'store_follow_enabled', 'value' => 'Enable', 'text' => __('Checked this to enable store follow.', 'dc-woocommerce-multi-vendor')), // Checkbox                    
                    ),
                ),
            ),
        );

        $WCMp->admin->settings->settings_field_init(apply_filters("settings_{$this->tab}_tab_options", $settings_tab_options));
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function wcmp_general_settings_sanitize($input) {
        $new_input = array();
        $hasError = false;
//        if (isset($input['enable_registration'])) {
//            $new_input['enable_registration'] = sanitize_text_field($input['enable_registration']);
//        }

        if (isset($input['is_backend_diabled'])) {
            $new_input['is_backend_diabled'] = sanitize_text_field($input['is_backend_diabled']);
        }
        if (isset($input['approve_vendor_manually'])) {
            $new_input['approve_vendor_manually'] = sanitize_text_field($input['approve_vendor_manually']);
        }
        
        if (isset($input['sold_by_catalog'])) {
            $new_input['sold_by_catalog'] = sanitize_text_field($input['sold_by_catalog']);
        }

//        if (isset($input['is_university_on'])) {
//            $new_input['is_university_on'] = sanitize_text_field($input['is_university_on']);
//        }
        if(isset($input['is_singleproductmultiseller'])){
            $new_input['is_singleproductmultiseller'] = $input['is_singleproductmultiseller'];
        }
        if(isset($input['singleproductmultiseller_show_order'])){
            $new_input['singleproductmultiseller_show_order'] = $input['singleproductmultiseller_show_order'];
        }
        if(isset($input['is_sellerreview'])){
            $new_input['is_sellerreview'] = $input['is_sellerreview'];
        }
        if(isset($input['is_disable_marketplace_plisting'])){
            $new_input['is_disable_marketplace_plisting'] = $input['is_disable_marketplace_plisting'];
        }
        if(isset($input['is_gtin_enable'])){
            $new_input['is_gtin_enable'] = $input['is_gtin_enable'];
        }
        if(isset($input['is_sellerreview_varified'])){
            $new_input['is_sellerreview_varified'] = $input['is_sellerreview_varified'];
        }
        if(isset($input['is_policy_on'])){
            $new_input['is_policy_on'] = $input['is_policy_on'];
        }
        if(isset($input['is_vendor_shipping_on'])){
            $new_input['is_vendor_shipping_on'] = $input['is_vendor_shipping_on'];
        }
        if(isset($input['enabled_distance_by_shipping_for_vendor'])){
            $new_input['enabled_distance_by_shipping_for_vendor'] = $input['enabled_distance_by_shipping_for_vendor'];
        }
        if(isset($input['is_checkout_delivery_location_on'])){
            $new_input['is_checkout_delivery_location_on'] = $input['is_checkout_delivery_location_on'];
        }
        if(isset($input['is_customer_support_details'])){
            $new_input['is_customer_support_details'] = $input['is_customer_support_details'];
        }
        if (isset($input['show_related_products'])) {
            $new_input['show_related_products'] = sanitize_text_field($input['show_related_products']);
        }
        if (isset($input['is_enable_store_sidebar'])) {
            $new_input['is_enable_store_sidebar'] = sanitize_text_field($input['is_enable_store_sidebar']);
        }
        if (isset($input['store_sidebar_position'])) {
            $new_input['store_sidebar_position'] = sanitize_text_field($input['store_sidebar_position']);
        }
        if (isset($input['store_follow_enabled'])) {
            $new_input['store_follow_enabled'] = sanitize_text_field($input['store_follow_enabled']);
        }
        if (isset($input['custom_date_order_stat_report_mail'])) {
            $new_input['custom_date_order_stat_report_mail'] = sanitize_text_field($input['custom_date_order_stat_report_mail']);
        }
        if (!$hasError) {
            add_settings_error(
                    "wcmp_{$this->tab}_settings_name", esc_attr("wcmp_{$this->tab}_settings_admin_updated"), __('General Settings Updated', 'dc-woocommerce-multi-vendor'), 'updated'
            );
        }
        return apply_filters("settings_{$this->tab}_tab_new_input", $new_input, $input);
    }

    public function wcmp_store_section_info() {
        echo '<div class="wcmp_payment_help">';
        _e("If you are not sure where to add widget, just go to admin <a href=".admin_url('widgets.php')." terget='_blank'>widget</a> section and add your preferred widgets to <b>vendor store sidebar</b>.", 'dc-woocommerce-multi-vendor');
        echo '</div>';
        ?>
        <style type="text/css">
             .wcmp_payment_help {
                display: inline-block;
                padding: 10px;
                background: #ffffff;
                color: #333;
                font-style: italic;
                max-width: 300px;
                position: absolute;
                right: 20px;
                z-index: 9;
            }
            @media (max-width: 960px){
                .wcmp_payment_help {
                    position: relative;
                    right: auto;
                }
            }
        </style>
        <?php
    }

}
