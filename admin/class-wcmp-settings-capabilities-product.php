<?php

class WCMp_Settings_Capabilities_Product {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;
    private $subsection;

    /**
     * Start up
     */
    public function __construct( $tab, $subsection ) {
        $this->tab = $tab;
        $this->subsection = $subsection;
        $this->options = get_option( "wcmp_{$this->tab}_{$this->subsection}_settings_name" );
        $this->settings_page_init();
        $this->get_product_type_selector();
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
        global $WCMp;
        $settings_tab_options = array( "tab"        => "{$this->tab}",
            "ref"        => &$this,
            "subsection" => "{$this->subsection}",
            "sections"   => array(
                "products_capability"                  => array(
                    "title"  => __( 'Products Capability', 'dc-woocommerce-multi-vendor' ),
                    "fields" => array(
                        "is_submit_product"                => array( 'title' => __( 'Submit Products', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_submit_product', 'label_for' => 'is_submit_product', 'text' => __( 'Allow vendors to submit products for approval/publishing.', 'dc-woocommerce-multi-vendor' ), 'name' => 'is_submit_product', 'value' => 'Enable' ), // Checkbox
                        "is_published_product"             => array( 'title' => __( 'Publish Products', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_published_product', 'label_for' => 'is_published_product', 'name' => 'is_published_product', 'text' => __( 'If checked, products uploaded by vendors will be directly published without admin approval.', 'dc-woocommerce-multi-vendor' ), 'value' => 'Enable' ), // Checkbox
                        "is_edit_delete_published_product" => array( 'title' => __( 'Edit Published Products', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_edit_delete_published_product', 'label_for' => 'is_edit_delete_published_product', 'name' => 'is_edit_delete_published_product', 'text' => __( 'Allow vendors to edit published products.', 'dc-woocommerce-multi-vendor' ), 'value' => 'Enable' ), // Checkbox
                        "is_submit_coupon"                 => array( 'title' => __( 'Submit Coupons', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_submit_coupon', 'label_for' => 'is_submit_coupon', 'name' => 'is_submit_coupon', 'text' => __( 'Allow vendors to create coupons.', 'dc-woocommerce-multi-vendor' ), 'value' => 'Enable' ), // Checkbox
                        "is_published_coupon"              => array( 'title' => __( 'Publish Coupons', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_published_coupon', 'label_for' => 'is_published_coupon', 'name' => 'is_published_coupon', 'text' => __( 'If checked, coupons added by vendors will be directly published without admin approval.', 'dc-woocommerce-multi-vendor' ), 'value' => 'Enable' ), // Checkbox
                        "is_edit_delete_published_coupon"  => array( 'title' => __( 'Edit Published Coupons', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_edit_delete_published_coupon', 'label_for' => 'is_edit_delete_published_coupon', 'name' => 'is_edit_delete_published_coupon', 'text' => __( 'Allow vendor to edit/delete published shop coupons.', 'dc-woocommerce-multi-vendor' ), 'value' => 'Enable' ), // Checkbox
                        "is_upload_files"                  => array( 'title' => __( 'Upload Media Files', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'is_upload_files', 'label_for' => 'is_upload_files', 'name' => 'is_upload_files', 'text' => __( 'Allow vendors to upload media files.', 'dc-woocommerce-multi-vendor' ), 'value' => 'Enable' ), // Checkbox
                    )
                ),
                "default_settings_section_types"       => array( "title"  => __( 'Product Types ', 'dc-woocommerce-multi-vendor' ), // Section one
                    "fields" => apply_filters( "wcmp_vendor_product_types", array(
                        "simple"   => array( 'title' => __( 'Simple', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'simple', 'label_for' => 'simple', 'name' => 'simple', 'value' => 'Enable', 'text' => __( 'Both frontend and back-end', 'dc-woocommerce-multi-vendor' ) ), // Checkbox
                        "variable" => array( 'title' => __( 'Variable', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'variable', 'label_for' => 'variable', 'name' => 'variable', 'value' => 'Enable', 'text' => __( 'Back-end only', 'dc-woocommerce-multi-vendor' ) ), // Checkbox
                        "grouped"  => array( 'title' => __( 'Grouped', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'grouped', 'label_for' => 'grouped', 'name' => 'grouped', 'value' => 'Enable', 'text' => __( 'Back-end only', 'dc-woocommerce-multi-vendor' ) ), // Checkbox
                        "external" => array( 'title' => __( 'External / Affiliate', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'external', 'label_for' => 'external', 'name' => 'external', 'value' => 'Enable', 'text' => __( 'Back-end only', 'dc-woocommerce-multi-vendor' ) ), // Checkbox
                        )
                    )
                ),
                "default_settings_section_type_option" => array( "title"  => __( 'Type Options ', 'dc-woocommerce-multi-vendor' ), // Section one
                    "fields" => apply_filters( "wcmp_vendor_product_type_options", array(
                        "virtual"      => array( 'title' => __( 'Virtual', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'virtual', 'label_for' => 'virtual', 'name' => 'virtual', 'value' => 'Enable' ), // Checkbox
                        "downloadable" => array( 'title' => __( 'Downloadable', 'dc-woocommerce-multi-vendor' ), 'type' => 'checkbox', 'id' => 'downloadable', 'label_for' => 'downloadable', 'name' => 'downloadable', 'value' => 'Enable' ), // Checkbox
                        )
                    )
                ),
                // Buddypress sync option
                "default_buddypress_settings_section_type_option" => array( "title"  => __( 'BuddyPress Sync Options ', 'dc-woocommerce-multi-vendor' ), // Section one
                    "fields" => apply_filters( "wcmp_vendor_buddypress_options", array(
                        "profile_sync" => array('title' => __('Vendor Capability Sync', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'profile_sync', 'label_for' => 'profile_sync', 'name' => 'profile_sync', 'value' => 'Enable' ,'desc' => __('Ignore if BuddyPress is not active' , 'dc-woocommerce-multi-vendor' ) ), // Select
                        )
                    )
                )
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

        if ( isset( $input['is_upload_files'] ) ) {
            $new_input['is_upload_files'] = sanitize_text_field( $input['is_upload_files'] );
        }

        if ( isset( $input['is_published_product'] ) ) {
            $new_input['is_published_product'] = sanitize_text_field( $input['is_published_product'] );
        }

        if ( isset( $input['is_edit_delete_published_product'] ) ) {
            $new_input['is_edit_delete_published_product'] = $input['is_edit_delete_published_product'];
        }

        if ( isset( $input['is_submit_product'] ) ) {
            $new_input['is_submit_product'] = sanitize_text_field( $input['is_submit_product'] );
        }

        if ( isset( $input['is_published_coupon'] ) ) {
            $new_input['is_published_coupon'] = sanitize_text_field( $input['is_published_coupon'] );
        }

        if ( isset( $input['is_submit_coupon'] ) ) {
            $new_input['is_submit_coupon'] = sanitize_text_field( $input['is_submit_coupon'] );
        }

        if ( isset( $input['is_edit_delete_published_coupon'] ) ) {
            $new_input['is_edit_delete_published_coupon'] = $input['is_edit_delete_published_coupon'];
        }
        if ( isset( $input['simple'] ) ) {
            $new_input['simple'] = sanitize_text_field( $input['simple'] );
        }
        if ( isset( $input['variable'] ) ) {
            $new_input['variable'] = sanitize_text_field( $input['variable'] );
        }
        if ( isset( $input['grouped'] ) ) {
            $new_input['grouped'] = sanitize_text_field( $input['grouped'] );
        }
        if ( isset( $input['external'] ) ) {
            $new_input['external'] = sanitize_text_field( $input['external'] );
        }
        if ( isset( $input['virtual'] ) ) {
            $new_input['virtual'] = sanitize_text_field( $input['virtual'] );
        }
        if ( isset( $input['downloadable'] ) ) {
            $new_input['downloadable'] = sanitize_text_field( $input['downloadable'] );
        }
        // BuddyPress option
        if ( isset( $input['profile_sync'] ) ) {
            $new_input['profile_sync'] = sanitize_text_field( $input['profile_sync'] );
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

    public function get_product_type_selector() {
        wc_get_product_types();
        $product_types = array();
        foreach ( wc_get_product_types() as $type => $name ) {
            $product_types[$type] = array( 'title' => $name, 'type' => 'checkbox', 'id' => $type, 'label_for' => $type, 'name' => $type, 'value' => 'Enable' );
        }
        return apply_filters( 'wcmp_vendor_product_types', $product_types );
    }

    public function default_settings_section_types_info() {
        if ( ! class_exists( 'WCMp_Frontend_Product_Manager' ) || ! class_exists( 'WCMp_AFM' ) ) {
            echo '<div class="frontend_manager_promo">';
            _e( 'WCMp 3.0 lets vendors\' add Simple Products from frontend. Grab our best-selling <a href="//wc-marketplace.com/product/wcmp-frontend-manager/">Advanced Frontend Manager</a> and allow all product types to be uploaded from vendor dashboard itself.', 'dc-woocommerce-multi-vendor' );
            echo '</div>';
            ?>
            <style type="text/css">
                .frontend_manager_promo {
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
                    .frontend_manager_promo {
                        position: relative;
                        right: auto;
                    }
                }
            </style>
            <?php

        }
    }

}
