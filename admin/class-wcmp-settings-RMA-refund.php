<?php

class WCMp_Settings_RMA_refund {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;
    private $subsection;
    private $customer_refund_status;

    /**
     * Start up
     */
    public function __construct( $tab, $subsection ) {
        $this->tab = $tab;
        $this->subsection = $subsection;
        $this->options = get_option( "wcmp_{$this->tab}_{$this->subsection}_settings_name" );
        $this->settings_page_init();

    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {


        $this->customer_refund_status = apply_filters('customer_refund_status', array('pending' => __('Pending', 'dc-woocommerce-multi-vendor'), 'on-hold' => __('On hold', 'dc-woocommerce-multi-vendor'), 'processing' => __('Processing', 'dc-woocommerce-multi-vendor'), 'completed' => __('Completed', 'dc-woocommerce-multi-vendor')));
        $automatic_method = array();
        $gateway_charge = array();
        $i = 0;
        foreach ($this->customer_refund_status as $key => $val) {
            if ($i == 0) {
                $automatic_method['refund_method_' . $key] = array('title' => __('Refund Order Status', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'refund_method_' . $key, 'class' => 'customer_refund_status', 'label_for' => 'refund_method_' . $key, 'text' => $val, 'name' => 'refund_method_' . $key, 'value' => $key, 'data-display-label' => $val);
            } else if ($key == 'direct_bank') {
                $automatic_method['refund_method_' . $key] = array('title' => __('', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'refund_method_' . $key, 'class' => 'customer_refund_status', 'label_for' => 'refund_method_' . $key, 'text' => $val, 'name' => 'refund_method_' . $key, 'value' => $key, 'data-display-label' => $val);
            } else {
                $automatic_method['refund_method_' . $key] = array('title' => __('', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'refund_method_' . $key, 'class' => 'customer_refund_status', 'label_for' => 'refund_method_' . $key, 'text' => $val, 'name' => 'refund_method_' . $key, 'value' => $key, 'data-display-label' => $val);
            }
           
            $i++;
        }

        global $WCMp;
        $settings_tab_options = array( "tab"        => "{$this->tab}",
            "ref"        => &$this,
            "subsection" => "{$this->subsection}",
            "sections"   => array(
                "products_capability"                  => array(
                    "title"  => __( 'Customer Capability', 'dc-woocommerce-multi-vendor' ),
                    "fields" => array_merge($automatic_method, array(
                        "refund_days"                => array( 'title' => __( 'Refund Days Limit', 'dc-woocommerce-multi-vendor' ), 'type' => 'number', 'id' => 'refund_days', 'label_for' => 'refund_days', 'text' => __( 'Number of Days for the refund period.', 'dc-woocommerce-multi-vendor' ), 'name' => 'refund_days' ), // Checkbox
                        
                        'refund_order_msg' => array('title' => __('Refund Massage', 'dc-woocommerce-multi-vendor'), 'type' => 'textarea', 'name' => 'refund_order_msg', 'id' => 'refund_order_msg', 'label_for' => 'refund_order_msg', 'rows' => 4, 'cols' => 40, 'raw_value' => true, 'hints' => __('Refund order massage', 'dc-woocommerce-multi-vendor'),'placeholder' => __('Enter every message with || ', 'dc-woocommerce-multi-vendor') )
                    )
                    ),
                ),
                
            )
        );

        $WCMp->admin->settings->settings_field_withsubtab_init( apply_filters( "settings_{$this->tab}_{$this->subsection}_tab_options", $settings_tab_options ) );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function wcmp_capabilities_product_settings_sanitize( $input ) {
        $new_input = array();

        $hasError = false;

        if ( isset( $input['refund_days'] ) ) {
            $new_input['refund_days'] = sanitize_text_field( $input['refund_days'] );
        }

        if ( isset( $input['refund_order_status'] ) ) {
            $new_input['refund_order_status'] = sanitize_text_field( $input['refund_order_status'] );
        }

        if ( isset( $input['refund_order_msg'] ) ) {
            $new_input['refund_order_msg'] = $input['refund_order_msg'];
        }

        if ( ! $hasError ) {
            add_settings_error(
                "wcmp_{$this->tab}_{$this->subsection}_settings_name", esc_attr( "wcmp_{$this->tab}_{$this->subsection}_settings_admin_updated" ), __( 'Vendor Settings Updated', 'dc-woocommerce-multi-vendor' ), 'updated'
            );
        }
        // before return settings values
        do_action( "wcmp_before_{$this->tab}_{$this->subsection}_settings_field_save", $new_input, $input );
        return apply_filters( "settings_{$this->tab}_{$this->subsection}_tab_new_input", $new_input, $input );
    }

    
}
