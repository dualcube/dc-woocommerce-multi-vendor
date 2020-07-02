<?php

/**
 * WCMp Widget Init Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Widget_Init {

    public function __construct() {
        add_action('widgets_init', array($this, 'product_vendor_register_widgets'));
        add_action('wp_dashboard_setup', array($this, 'wcmp_rm_meta_boxes'));
    }

    /**
     * Add vendor widgets
     */
    public function product_vendor_register_widgets() {
        include_once ('widgets/class-wcmp-widget-vendor-info.php');
        require_once ('widgets/class-wcmp-widget-vendor-list.php');
        require_once ('widgets/class-wcmp-widget-vendor-quick-info.php');
        require_once ('widgets/class-wcmp-widget-vendor-location.php');
        require_once ('widgets/class-wcmp-widget-vendor-product-categories.php');
        require_once ('widgets/class-wcmp-widget-vendor-top-rated-products.php');
         require_once ('widgets/class-wcmp-widget-vendor-product-search.php');

        register_widget('DC_Widget_Vendor_Info');
        register_widget('DC_Widget_Vendor_List');
        register_widget('DC_Widget_Quick_Info_Widget');
        register_widget('DC_Woocommerce_Store_Location_Widget');
        register_widget('WCMp_Widget_Vendor_Product_Categories');
        register_widget('WCMp_Widget_Vendor_Top_Rated_Products');
        register_widget('DC_Widget_Vendor_Product_search');
    }

    /**
     * Removing woocommerce widget from vendor dashboard
     */
    public function wcmp_rm_meta_boxes() {
        if (is_user_wcmp_vendor(get_current_vendor_id())) {
            remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        }
    }

}
