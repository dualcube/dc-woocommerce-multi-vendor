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
        add_action('init', array($this, 'wcmp_register_store_sidebar'));
        add_action('widgets_init', array($this, 'product_vendor_register_widgets'));
        add_action('wp_dashboard_setup', array($this, 'wcmp_rm_meta_boxes'));
    }

    /**
     * Register Store Sidebar
     */
    public function wcmp_register_store_sidebar() {
        register_sidebar(
            apply_filters( 'wcmp_store_sidebar_args', array(
                        'name'          => __( 'Vendor Store Sidebar', 'dc-woocommerce-multi-vendor' ),
                        'id'            => 'sidebar-wcmp-store',
                        'before_widget' => '<aside id="%1$s" class="widget sidebar-box clr %2$s">',
                        'after_widget'  => '</aside>',
                        'before_title'  => '<div class="sidebar_heading"><h4 class="widget-title">',
                        'after_title'   => '</h4></div>',
                )
            )
        );
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
        require_once ('widgets/class-wcmp-widget-vendor-review.php');
        require_once ('widgets/class-wcmp-widget-vendor-product-search.php');
        require_once ('widgets/class-wcmp-widget-vendor-policies.php');
        require_once ('widgets/class-wcmp-widget-vendor-coupons.php');
        require_once ('widgets/class-wcmp-widget-vendor-on-sale-products.php');
        require_once ('widgets/class-wcmp-widget-vendor-recent-products.php');

        register_widget('DC_Widget_Vendor_Info');
        register_widget('DC_Widget_Vendor_List');
        register_widget('DC_Widget_Quick_Info_Widget');
        register_widget('DC_Woocommerce_Store_Location_Widget');
        register_widget('WCMp_Widget_Vendor_Product_Categories');
        register_widget('WCMp_Widget_Vendor_Top_Rated_Products');
        register_widget('WCMp_Widget_Vendor_Review_Widget');
        register_widget('WCMp_Widget_Vendor_Product_Search');
        register_widget('WCMp_Widget_Vendor_Policies');
        register_widget('WCMp_Widget_Vendor_Coupons');
        register_widget('WCMp_Widget_Vendor_On_Sale_Products');
        register_widget('WCMp_Widget_Vendor_Recent_Products');
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
