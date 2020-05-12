<?php

class WCMp_Settings_Vendor_Dashboard {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;
    private $subsection;

    /**
     * Start up
     */
    public function __construct($tab, $subsection) {
        $this->tab = $tab;
        $this->subsection = $subsection;
        $this->options = get_option("wcmp_{$this->tab}_{$this->subsection}_settings_name");
        $this->settings_page_init();
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
        global $WCMp;
//        $pages = get_pages();
//        $woocommerce_pages = array(wc_get_page_id('shop'), wc_get_page_id('cart'), wc_get_page_id('checkout'), wc_get_page_id('myaccount'));
//        foreach ($pages as $page) {
//            if (!in_array($page->ID, $woocommerce_pages)) {
//                $pages_array[$page->ID] = $page->post_title;
//            }
//        }
        $template_options = apply_filters('wcmp_vendor_shop_template_options', array('template1' => $WCMp->plugin_url.'assets/images/template1.png', 'template2' => $WCMp->plugin_url.'assets/images/template2.png', 'template3' => $WCMp->plugin_url.'assets/images/template3.png'));
        $settings_tab_options = array("tab" => "{$this->tab}",
            "ref" => &$this,
            "subsection" => "{$this->subsection}",
            "sections" => array(
                "wcmp_vendor_dashboard_settings" => array("title" => __('Dashboard Settings', 'dc-woocommerce-multi-vendor'), // Section one
                    "fields" => array(
                        "wcmp_dashboard_site_logo" => array('title' => __('Site Logo', 'dc-woocommerce-multi-vendor'), 'type' => 'upload', 'id' => 'wcmp_dashboard_site_logo', 'label_for' => 'wcmp_dashboard_site_logo', 'name' => 'wcmp_dashboard_site_logo', 'hints' => __('Used as site logo on vendor dashboard pages', 'dc-woocommerce-multi-vendor')),
                        "google_api_key" => array('title' => __('Google Map API key', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'google_api_key', 'label_for' => 'google_api_key', 'name' => 'google_api_key', 'hints' => __('Used for vendor store maps','dc-woocommerce-multi-vendor'), 'desc' => __('<a href="https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key" target="_blank">Click here to generate key</a>','dc-woocommerce-multi-vendor')),
                        "vendor_list_template" => array('title' => __('Apply the new look (Introduced on version 3.5)
                        ', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'vendor_list_template', 'label_for' => 'vendor_list_template', 'text' => __('Enable it to get new vendor list template.', 'dc-woocommerce-multi-vendor'), 'name' => 'vendor_list_template', 'value' => 'Enable'), // Checkbox
                        "vendor_color_scheme_picker" => array('title' => __('Dashboard Color Scheme', 'dc-woocommerce-multi-vendor'), 'class' => 'vendor_color_scheme_picker', 'type' => 'color_scheme_picker', 'id' => 'vendor_color_scheme_picker', 'label_for' => 'vendor_color_scheme_picker', 'name' => 'vendor_color_scheme_picker', 'dfvalue' => 'outer_space_blue', 'options' => array('outer_space_blue' => array('label' => __('Outer Space', 'dc-woocommerce-multi-vendor'), 'color' => array('#202528', '#333b3d','#3f85b9', '#316fa8')), 'green_lagoon' => array('label' => __('Green Lagoon', 'dc-woocommerce-multi-vendor'), 'color' => array('#171717', '#212121', '#009788','#00796a')), 'old_west' => array('label' => __('Old West', 'dc-woocommerce-multi-vendor'), 'color' => array('#46403c', '#59524c', '#c7a589', '#ad8162')), 'wild_watermelon' => array('label' => __('Wild Watermelon', 'dc-woocommerce-multi-vendor'), 'color' => array('#181617', '#353130', '#fd5668', '#fb3f4e'))))
                    ),
                ),
                "wcmp_vendor_setup_wizard_settings" => array("title" => __('Store Setup Wizard', 'dc-woocommerce-multi-vendor'), // Section one
                    "fields" => array(
                        "setup_wizard_introduction" => array('title' => __('Introduction step', 'dc-woocommerce-multi-vendor'), 'type' => 'wpeditor', 'id' => 'setup_wizard_introduction', 'label_for' => 'setup_wizard_introduction', 'name' => 'setup_wizard_introduction', 'cols' => 50, 'rows' => 6, 'hints' => __('Add some introduction or welcome speech to your vendor. This section display in vendor store setup wizard introduction step.', 'dc-woocommerce-multi-vendor')), // Textarea
                    ),
                ),
                'wcmp_vendor_shop_template' => array(
                    'title' => __('Vendor Shop', 'dc-woocommerce-multi-vendor'),
                    'fields' => array(
                        "wcmp_vendor_shop_template" => array('title' => __('Vendor Shop Template', 'dc-woocommerce-multi-vendor'), 'type' => 'radio_select', 'id' => 'wcmp_vendor_shop_template', 'label_for' => 'wcmp_vendor_shop_template', 'name' => 'wcmp_vendor_shop_template', 'dfvalue' => 'vendor', 'options' => $template_options, 'value' => 'template1', 'desc' => ''), // Radio
                        'wcmp_vendor_dashboard_custom_css' => array('title' => __('Custom CSS', 'dc-woocommerce-multi-vendor'), 'type' => 'textarea', 'name' => 'wcmp_vendor_dashboard_custom_css', 'id' => 'wcmp_vendor_dashboard_custom_css', 'label_for' => 'wcmp_vendor_dashboard_custom_css', 'rows' => 4, 'cols' => 40, 'raw_value' => true, 'hints' => __('Will be applicable on vendor frontend', 'dc-woocommerce-multi-vendor'))
                    )
                )
            ),
        );

        $WCMp->admin->settings->settings_field_withsubtab_init(apply_filters("settings_{$this->tab}_{$this->subsection}_tab_options", $settings_tab_options));
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function wcmp_vendor_dashboard_settings_sanitize($input) {
        $new_input = array();
        $hasError = false;
        
        if (isset($input['wcmp_dashboard_site_logo'])) {
            $new_input['wcmp_dashboard_site_logo'] = $input['wcmp_dashboard_site_logo'];
        }
        if(isset($input['google_api_key'])){
            $new_input['google_api_key'] = $input['google_api_key'];
        }
        if(isset($input['vendor_list_template'])){
            $new_input['vendor_list_template'] = $input['vendor_list_template'];
        }
        if(isset($input['vendor_color_scheme_picker'])){
            $new_input['vendor_color_scheme_picker'] = sanitize_text_field($input['vendor_color_scheme_picker']);
        }
        if(isset($input['wcmp_vendor_shop_template'])){
            $new_input['wcmp_vendor_shop_template'] = sanitize_text_field($input['wcmp_vendor_shop_template']);
        }
        if(isset($input['can_vendor_edit_shop_template'])){
            $new_input['can_vendor_edit_shop_template'] = sanitize_text_field($input['can_vendor_edit_shop_template']);
        }
        
        if(isset($input['wcmp_vendor_dashboard_custom_css'])){
            $new_input['wcmp_vendor_dashboard_custom_css'] = wp_unslash($input['wcmp_vendor_dashboard_custom_css']);
        }
        
        if (isset($input['setup_wizard_introduction'])) {
            $new_input['setup_wizard_introduction'] = $input['setup_wizard_introduction'];
        }
        
        if (!$hasError) {
            add_settings_error(
                    "wcmp_{$this->tab}_{$this->subsection}_settings_name", esc_attr("wcmp_{$this->tab}_{$this->subsection}_settings_admin_updated"), __('Vendor Settings Updated', 'dc-woocommerce-multi-vendor'), 'updated'
            );
        }
        return apply_filters("settings_{$this->tab}_{$this->subsection}_tab_new_input", $new_input, $input);
    }

}
