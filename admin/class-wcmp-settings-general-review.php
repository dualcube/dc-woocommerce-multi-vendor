<?php

class WCMp_Settings_General_Review {

    public function __construct( $tab, $subsection ) {
      $this->settings_page_init();
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
      global $WCMp;
      $wcmp_review_options  = get_option( 'wcmp_review_settings_option', array() );
      $review_auto_approve  = get_wcmp_vendor_settings('is_sellerreview', 'general') && get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable' ? 'yes' : '';
      $review_only_store_user = get_wcmp_vendor_settings('is_sellerreview_varified', 'general') && get_wcmp_vendor_settings('is_sellerreview_varified', 'general') == 'Enable' ? 'yes' : '';
      $product_review_sync    = get_wcmp_vendor_settings('product_review_sync', 'general') && get_wcmp_vendor_settings('product_review_sync', 'general') == 'Enable' ? 'yes' : '';
      $wcmp_review_categories = isset( $wcmp_review_options['review_categories'] ) ? $wcmp_review_options['review_categories'] : array();

      $WCMp->wcmp_wp_fields->dc_generate_form_field( apply_filters( 'wcmp_marketplace_settings_fields_review', array(
        "is_sellerreview" => array('label' => __('Enable Vendor Review', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'name' => 'wcmp_review_options[is_sellerreview]', 'class' => 'wcmp-checkbox wcmp_ele', 'label_class' => 'wcmp_title checkbox_title', 'value' => 'yes', 'dfvalue' => $review_auto_approve, 'desc_class' => 'wcmp_page_options_desc', 'desc' =>__('Buyers can rate and review vendor.', 'dc-woocommerce-multi-vendor') ),
        "is_sellerreview_varified" => array('label' => __('Review only store users?', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'name' => 'wcmp_review_options[is_sellerreview_varified]', 'class' => 'wcmp-checkbox wcmp_ele', 'label_class' => 'wcmp_title checkbox_title', 'value' => 'yes', 'dfvalue' => $review_only_store_user, 'desc_class' => 'wcmp_page_options_desc', 'desc' =>  __('Only buyers, purchased from the vendor can rate.', 'dc-woocommerce-multi-vendor') ),
        "product_review_sync"    => array('label' => __('Product review sync?', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'name' => 'wcmp_review_options[product_review_sync]', 'class' => 'wcmp-checkbox wcmp_ele', 'label_class' => 'wcmp_title checkbox_title', 'value' => 'yes', 'dfvalue' => $product_review_sync, 'desc_class' => 'wcmp_page_options_desc', 'desc' => __( 'Enable this to allow vendor\'s products review consider as store review.', 'dc-woocommerce-multi-vendor' ) ),
        "wcmp_review_categories" => array('label' => __('Review Categories', 'dc-woocommerce-multi-vendor'), 'type' => 'multiinput', 'name' => 'wcmp_review_options[review_categories]', 'class' => 'wcmp-text wcmp_ele', 'label_class' => 'wcmp_title wcmp_ele wcmp_full_title', 'value' => $wcmp_review_categories, 'options' => array( 
          "category" => array('label' => __('Category', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'class' => 'wcmp-text wcmp_ele', 'label_class' => 'wcmp_title wcmp_ele' ),
        ) ),
        "wcmp_vendor_review_setting"  => array('type' => 'button', 'name' => 'wcmp_review_options[wcmp_vendor_review_setting]', 'class' => 'button button-primary vendor-update-btn wcmp-primary-btn', 'value' => __('Update', 'dc-woocommerce-multi-vendor') ),
      ) ) );
    }
}