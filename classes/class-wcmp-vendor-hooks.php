<?php

/**
 * Description of WCMp_Vendor_Hooks
 *
 * @author WC Marketplace
 */
class WCMp_Vendor_Hooks {

    function __construct() {
        add_action( 'wcmp_vendor_dashboard_navigation', array( &$this, 'wcmp_create_vendor_dashboard_navigation' ) );
        add_action( 'wcmp_vendor_dashboard_content', array( &$this, 'wcmp_create_vendor_dashboard_content' ) );
        add_action( 'before_wcmp_vendor_dashboard', array( &$this, 'save_vendor_dashboard_data' ) );

        add_action( 'wcmp_vendor_dashboard_vendor-announcements_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_announcements_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-orders_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_orders_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_storefront_endpoint', array( &$this, 'wcmp_vendor_dashboard_storefront_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_profile_endpoint', array( &$this, 'wcmp_vendor_dashboard_profile_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-policies_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_policies_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-billing_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_billing_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-shipping_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_shipping_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-report_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_report_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_banking-overview_endpoint', array( &$this, 'wcmp_vendor_dashboard_banking_overview_endpoint' ) );

        add_action( 'wcmp_vendor_dashboard_add-product_endpoint', array( &$this, 'wcmp_vendor_dashboard_add_product_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_edit-product_endpoint', array( &$this, 'wcmp_vendor_dashboard_edit_product_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_products_endpoint', array( &$this, 'wcmp_vendor_dashboard_products_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_add-coupon_endpoint', array( &$this, 'wcmp_vendor_dashboard_add_coupon_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_coupons_endpoint', array( &$this, 'wcmp_vendor_dashboard_coupons_endpoint' ) );

        add_action( 'wcmp_vendor_dashboard_vendor-withdrawal_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_withdrawal_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_transaction-details_endpoint', array( &$this, 'wcmp_vendor_dashboard_transaction_details_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-knowledgebase_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_knowledgebase_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_vendor-tools_endpoint', array( &$this, 'wcmp_vendor_dashboard_vendor_tools_endpoint' ) );
        add_action( 'wcmp_vendor_dashboard_products-qna_endpoint', array( &$this, 'wcmp_vendor_dashboard_products_qna_endpoint' ) );

        add_filter( 'the_title', array( &$this, 'wcmp_vendor_dashboard_endpoint_title' ) );
        add_filter( 'wcmp_vendor_dashboard_menu_vendor_policies_capability', array( &$this, 'wcmp_vendor_dashboard_menu_vendor_policies_capability' ) );
        add_filter( 'wcmp_vendor_dashboard_menu_vendor_withdrawal_capability', array( &$this, 'wcmp_vendor_dashboard_menu_vendor_withdrawal_capability' ) );
        add_filter( 'wcmp_vendor_dashboard_menu_vendor_shipping_capability', array( &$this, 'wcmp_vendor_dashboard_menu_vendor_shipping_capability' ) );
        add_action( 'before_wcmp_vendor_dashboard_content', array( &$this, 'before_wcmp_vendor_dashboard_content' ) );
        add_action( 'wp', array( &$this, 'wcmp_add_theme_support' ), 15 );
        
        // Rejected vendor dashboard content
        add_action( 'wcmp_rejected_vendor_dashboard_content', array( &$this, 'rejected_vendor_dashboard_content' ) );
        add_action( 'before_wcmp_rejected_vendor_dashboard', array( &$this, 'save_rejected_vendor_reapply_data' ) );
    }

    /**
     * Create vendor dashboard menu
     * array $args
     */
    public function wcmp_create_vendor_dashboard_navigation( $args = array() ) {
        global $WCMp;
        $WCMp->template->get_template( 'vendor-dashboard/navigation.php', array( 'nav_items' => $this->wcmp_get_vendor_dashboard_navigation(), 'args' => $args ) );
    }

    public function wcmp_get_vendor_dashboard_navigation() {
        $vendor_nav = array(
            'dashboard'            => array(
                'label'       => __( 'Dashboard', 'dc-woocommerce-multi-vendor' )
                , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( 'dashboard' )
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_dashboard_capability', true )
                , 'position'    => 0
                , 'submenu'     => array()
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-dashboard-icon'
            ),
            'store-settings'       => array(
                'label'       => __( 'Store Settings', 'dc-woocommerce-multi-vendor' )
                , 'url'         => '#'
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_store_settings_capability', true )
                , 'position'    => 10
                , 'submenu'     => array(
                    'storefront'      => array(
                        'label'       => __( 'Storefront', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_store_settings_endpoint', 'vendor', 'general', 'storefront' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_shop_front_capability', true )
                        , 'position'    => 10
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-storefront-icon'
                    ),
                    'vendor-policies' => array(
                        'label'       => __( 'Policies', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_policies_endpoint', 'vendor', 'general', 'vendor-policies' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_policies_capability', false )
                        , 'position'    => 20
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-policies-icon'
                    ),
                    'vendor-billing'  => array(
                        'label'       => __( 'Billing', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_billing_endpoint', 'vendor', 'general', 'vendor-billing' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_billing_capability', true )
                        , 'position'    => 30
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-billing-icon'
                    ),
                    'vendor-shipping' => array(
                        'label'       => __( 'Shipping', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_shipping_endpoint', 'vendor', 'general', 'vendor-shipping' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_shipping_capability', wc_shipping_enabled() )
                        , 'position'    => 40
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-shippingnew-icon'
                    )
                )
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-store-settings-icon'
            ),
            'vendor-products'      => array(
                'label'       => __( 'Product Manager', 'dc-woocommerce-multi-vendor' )
                , 'url'         => '#'
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_products_capability', 'edit_products' )
                , 'position'    => 20
                , 'submenu'     => array(
                    'products'    => array(
                        'label'       => __( 'All Products', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => apply_filters( 'wcmp_vendor_products', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_products_endpoint', 'vendor', 'general', 'products' ) ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_products_capability', 'edit_products' )
                        , 'position'    => 10
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-product-manager-icon'
                    ),
                    'add-product' => array(
                        'label'       => __( 'Add Product', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => apply_filters( 'wcmp_vendor_dashboard_add_product_url', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_add_product_endpoint', 'vendor', 'general', 'add-product' ) ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_add_product_capability', 'edit_products' )
                        , 'position'    => 20
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-add-product-icon'
                    )
                )
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-product-manager-icon'
            ),
            'vendor-promte'        => array(
                'label'       => __( 'Coupons', 'dc-woocommerce-multi-vendor' )
                , 'url'         => '#'
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_promte_capability', 'edit_shop_coupons' )
                , 'position'    => 30
                , 'submenu'     => array(
                    'coupons'    => array(
                        'label'       => __( 'All Coupons', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => apply_filters( 'wcmp_vendor_coupons', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_coupons_endpoint', 'vendor', 'general', 'coupons' ) ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_coupons_capability', 'edit_shop_coupons' )
                        , 'position'    => 10
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-coupons-icon'
                    ),
                    'add-coupon' => array(
                        'label'       => __( 'Add Coupon', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => apply_filters( 'wcmp_vendor_submit_coupon', wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_add_coupon_endpoint', 'vendor', 'general', 'add-coupon' ) ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_add_coupon_capability', 'edit_shop_coupons' )
                        , 'position'    => 20
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-addcoupon-icon'
                    )
                )
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-coupons-icon'
            ),
            'vendor-report'        => array(
                'label'       => __( 'Stats / Reports', 'dc-woocommerce-multi-vendor' )
                , 'url'         => '#'
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_report_capability', true )
                , 'position'    => 40
                , 'submenu'     => array(
                    'vendor-report' => array(
                        'label'       => __( 'Overview', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_report_endpoint', 'vendor', 'general', 'vendor-report' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_report_capability', true )
                        , 'position'    => 10
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-reports-icon'
                    ),
                    'banking-overview' => array(
                        'label'       => __( 'Banking Overview', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_banking_overview_endpoint', 'vendor', 'general', 'banking-overview' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_banking_report_capability', true )
                        , 'position'    => 20
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-reports-icon'
                    )
                )
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-reports-icon'
            ),
            'vendor-orders'        => array(
                'label'       => __( 'Orders', 'dc-woocommerce-multi-vendor' )
                , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders' ) )
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_orders_capability', true )
                , 'position'    => 50
                , 'submenu'     => array()
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-orders-icon'
            ),
            'vendor-payments'      => array(
                'label'       => __( 'Payments', 'dc-woocommerce-multi-vendor' )
                , 'url'         => '#'
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_payments_capability', true )
                , 'position'    => 60
                , 'submenu'     => array(
                    'vendor-withdrawal'   => array(
                        'label'       => __( 'Withdrawal', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_withdrawal_endpoint', 'vendor', 'general', 'vendor-withdrawal' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_withdrawal_capability', false )
                        , 'position'    => 10
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-revenue-icon'
                    ),
                    'transaction-details' => array(
                        'label'       => __( 'History', 'dc-woocommerce-multi-vendor' )
                        , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_transaction_details_endpoint', 'vendor', 'general', 'transaction-details' ) )
                        , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_transaction_details_capability', true )
                        , 'position'    => 20
                        , 'link_target' => '_self'
                        , 'nav_icon'    => 'wcmp-font ico-history-icon'
                    )
                )
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-payments-icon'
            ),
            'vendor-knowledgebase' => array(
                'label'       => __( 'Knowledgebase', 'dc-woocommerce-multi-vendor' )
                , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_knowledgebase_endpoint', 'vendor', 'general', 'vendor-knowledgebase' ) )
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_knowledgebase_capability', true )
                , 'position'    => 70
                , 'submenu'     => array()
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-knowledgebase-icon'
            ),
            'vendor-tools'         => array(
                'label'       => __( 'Tools', 'dc-woocommerce-multi-vendor' )
                , 'url'         => wcmp_get_vendor_dashboard_endpoint_url( get_wcmp_vendor_settings( 'wcmp_vendor_tools_endpoint', 'vendor', 'general', 'vendor-tools' ) )
                , 'capability'  => apply_filters( 'wcmp_vendor_dashboard_menu_vendor_tools_capability', true )
                , 'position'    => 80
                , 'submenu'     => array()
                , 'link_target' => '_self'
                , 'nav_icon'    => 'wcmp-font ico-tools-icon'
            )
        );
        return apply_filters( 'wcmp_vendor_dashboard_nav', $vendor_nav );
    }

    /**
     * Display Vendor dashboard Content
     * @global object $wp
     * @global object $WCMp
     * @return null
     */
    public function wcmp_create_vendor_dashboard_content() {
        global $wp, $WCMp;
        foreach ( $wp->query_vars as $key => $value ) {
            // Ignore pagename and page param.
            if ( in_array( $key, array( 'page', 'pagename' ) ) ) {
                continue;
            }
            do_action( 'before_wcmp_vendor_dashboard_content', $key );
            if ( has_action( 'wcmp_vendor_dashboard_' . $key . '_endpoint' ) ) {
                if ( $this->current_vendor_can_view( $WCMp->endpoints->get_current_endpoint() ) ) {
                    do_action( 'wcmp_vendor_dashboard_' . $key . '_endpoint', $value );
                }
                return;
            }
            do_action( 'after_wcmp_vendor_dashboard_content' );
        }
        $WCMp->library->load_dataTable_lib();
        $WCMp->template->get_template( 'vendor-dashboard/dashboard.php' );
    }

    public function wcmp_create_vendor_dashboard_breadcrumbs( $current_endpoint, $nav = array(), $firstLevel = true ) {
        global $WCMp;
        $nav = ! empty( $nav ) ? $nav : $this->wcmp_get_vendor_dashboard_navigation();
        $resultArray = array();
        $current_endpoint = $current_endpoint ? $current_endpoint : 'dashboard';
        $breadcrumb = false;
        $curent_menu = array();
        if ( array_key_exists( $current_endpoint, $nav ) ) {
            $menu = $nav[$current_endpoint];
            $icon = isset($menu['nav_icon']) ? '<i class="' . $menu['nav_icon'] . '"></i>' : '';
            $breadcrumb = $icon . '<span> ' . $menu['label'] . '</span>';
            $curent_menu = $menu;
        } else {
            $submenus = wp_list_pluck( $nav, 'submenu' );
            foreach ( $submenus as $key => $submenu ) {
                if ( $submenu && array_key_exists( $current_endpoint, $submenu ) ) {
                    if ( ! $firstLevel ) {
                        $menu = $nav[$key];
                        $icon = isset($menu['nav_icon']) ? '<i class="' . $menu['nav_icon'] . '"></i>' : '';
                        $breadcrumb = $icon . '<span> ' . $menu['label'] . '</span>';
                        $subm = $submenu[$current_endpoint];
                        $subicon = isset($subm['nav_icon']) ? '<i class="' . $subm['nav_icon'] . '"></i>' : '';
                        $breadcrumb .= '&nbsp;<span class="bread-sepa"> ' . apply_filters( 'wcmp_vendor_dashboard_breadcrumbs_separator', '>' ) . ' </span>&nbsp;';
                        $breadcrumb .= $subicon . '<span> ' . $subm['label'] . '</span>';
                        $curent_menu = $subm;
                    } else {
                        $menu = $submenu[$current_endpoint];
                        $icon = isset($menu['nav_icon']) ? '<i class="' . $menu['nav_icon'] . '"></i>' : '';
                        $breadcrumb = $icon . '<span> ' . $menu['label'] . '</span>';
                        $curent_menu = $menu;
                    }
                    break;
                } else {
                    $current_endpoint_arr = isset($WCMp->endpoints->wcmp_query_vars[$current_endpoint]) ? $WCMp->endpoints->wcmp_query_vars[$current_endpoint] : array();
                    $icon = isset($current_endpoint_arr['icon']) ? '<i class="' . $current_endpoint_arr['icon'] . '"></i>' : '';
                    $breadcrumb = $icon . '<span> ' . $current_endpoint_arr['label'] . '</span>';
                    $curent_menu = $current_endpoint_arr;
                }
            }
        }
        return apply_filters( 'wcmp_create_vendor_dashboard_breadcrumbs', $breadcrumb, $curent_menu );
    }

    public function current_vendor_can_view( $current_endpoint = 'dashboard' ) {
        $nav = $this->wcmp_get_vendor_dashboard_navigation();
        foreach ( $nav as $endpoint => $menu ) {
            if ( $endpoint == $current_endpoint ) {
                return current_user_can( $menu['capability'] ) || true === $menu['capability'];
            } else if ( ! empty( $menu['submenu'] ) && array_key_exists( $current_endpoint, $menu['submenu'] ) && isset( $menu['submenu'][$current_endpoint]['capability'] ) ) {
                return current_user_can( $menu['submenu'][$current_endpoint]['capability'] ) || true === $menu['submenu'][$current_endpoint]['capability'];
            }
        }
        return true;
    }

    /**
     * Display Vendor Announcements content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_announcements_endpoint() {
        global $WCMp;
        $frontend_style_path = $WCMp->plugin_url . 'assets/frontend/css/';
        $frontend_style_path = str_replace( array( 'http:', 'https:' ), '', $frontend_style_path );
        $frontend_script_path = $WCMp->plugin_url . 'assets/frontend/js/';
        $frontend_script_path = str_replace( array( 'http:', 'https:' ), '', $frontend_script_path );
        $suffix = defined( 'WCMP_SCRIPT_DEBUG' ) && WCMP_SCRIPT_DEBUG ? '' : '.min';
        //wp_enqueue_style('font-vendor_announcements', '//fonts.googleapis.com/css?family=Lato:400,100,100italic,300,300italic,400italic,700,700italic,900,900italic', array(), $WCMp->version);
        //wp_enqueue_style('ui_vendor_announcements', '//code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css', array(), $WCMp->version);
        wp_enqueue_script( 'jquery-ui-accordion' );
        wp_enqueue_script( 'wcmp_new_vandor_announcements_js', $frontend_script_path . 'wcmp_vendor_announcements' . $suffix . '.js', array( 'jquery' ), $WCMp->version, true );
        $WCMp->localize_script( 'wcmp_new_vandor_announcements_js' );
        //wp_enqueue_script('jquery');
        //wp_enqueue_script('wcmp_new_vandor_announcements_js_lib_ui', '//code.jquery.com/ui/1.10.4/jquery-ui.js', array('jquery'), $WCMp->version, true);
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        $WCMp->template->get_template( 'vendor-dashboard/vendor-announcements.php', array( 'vendor_announcements' => $vendor->get_announcements() ) );
    }

    /**
     * Display vendor dashboard shop front content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_storefront_endpoint() {
        global $WCMp;
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        $user_array = $WCMp->user->get_vendor_fields( $vendor->id );
        $WCMp->library->load_dashboard_upload_lib();
        $WCMp->library->load_gmap_api();
        $WCMp->template->get_template( 'vendor-dashboard/shop-front.php', $user_array );
    }
    
    /**
     * Display vendor profile management content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_profile_endpoint() {
        global $WCMp;
        $user = wp_get_current_user();
        $WCMp->library->load_dashboard_upload_lib();
        $WCMp->template->get_template( 'vendor-dashboard/profile.php', array( 'user' => $user ) );
    }

    /**
     * display vendor policies content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_policies_endpoint() {
        global $WCMp;
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        $user_array = $WCMp->user->get_vendor_fields( $vendor->id );
        if ( ! wp_script_is( 'tiny_mce', 'enqueued' ) ) {
            wp_enqueue_editor();
        }
        $WCMp->template->get_template( 'vendor-dashboard/vendor-policy.php', $user_array );
    }

    /**
     * Display Vendor billing settings content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_billing_endpoint() {
        global $WCMp;
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        $user_array = $WCMp->user->get_vendor_fields( $vendor->id );
        $WCMp->template->get_template( 'vendor-dashboard/vendor-billing.php', $user_array );
    }

    /**
     * Display vendor shipping content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_shipping_endpoint() {
        global $WCMp;
        $wcmp_payment_settings_name = get_option( 'wcmp_payment_settings_name' );
        $_vendor_give_shipping = get_user_meta( get_current_vendor_id(), '_vendor_give_shipping', true );
        if ( isset( $wcmp_payment_settings_name['give_shipping'] ) && empty( $_vendor_give_shipping ) ) {
            if (wp_script_is('wcmp-vendor-shipping', 'registered') &&
                !wp_script_is('wcmp-vendor-shipping', 'enqueued')) {
                wp_enqueue_script('wcmp-vendor-shipping');
            }

            $WCMp->template->get_template('vendor-dashboard/vendor-shipping.php');
        } else {
            echo '<p class="wcmp_headding3">' . __( 'Sorry you are not authorized for this pages. Please contact with admin.', 'dc-woocommerce-multi-vendor' ) . '</p>';
        }
    }

    /**
     * Display vendor report content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_report_endpoint() {
        global $WCMp;
        if ( isset( $_POST['wcmp_stat_start_dt'] ) ) {
            $start_date = $_POST['wcmp_stat_start_dt'];
        } else {
            // hard-coded '01' for first day     
            $start_date = date( 'Y-m-01' );
        }

        if ( isset( $_POST['wcmp_stat_end_dt'] ) ) {
            $end_date = $_POST['wcmp_stat_end_dt'];
        } else {
            // hard-coded '01' for first day
            $end_date = date( 'Y-m-d' );
        }
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        $WCMp_Plugin_Post_Reports = new WCMp_Report();
        $array_report = $WCMp_Plugin_Post_Reports->vendor_sales_stat_overview( $vendor, $start_date, $end_date );
        $WCMp->template->get_template( 'vendor-dashboard/vendor-report.php', $array_report );
    }
    
    public function wcmp_vendor_dashboard_banking_overview_endpoint() {
        global $WCMp;
        $table_headers = apply_filters('wcmp_vendor_dashboard_banking_overview_table_headers', array(
            'status'        => array('label' => __( 'Status', 'dc-woocommerce-multi-vendor' ), 'class' => 'text-center'),
            'date'          => array('label' => __( 'Date', 'dc-woocommerce-multi-vendor' )),
            'ref_type'      => array('label' => __( 'Type', 'dc-woocommerce-multi-vendor' )),
            'ref_info'      => array('label' => __( 'Reference', 'dc-woocommerce-multi-vendor' )),
            'credit'        => array('label' => __( 'Credit', 'dc-woocommerce-multi-vendor' )),
            'debit'         => array('label' => __( 'Debit', 'dc-woocommerce-multi-vendor' )),
            'balance'       => array('label' => __( 'Balance', 'dc-woocommerce-multi-vendor' )),
        ), get_current_user_id());
        $WCMp->library->load_dataTable_lib();
        $WCMp->template->get_template( 'vendor-dashboard/vendor-reports/vendor-ledger.php', array( 'table_headers' => $table_headers ) );
    }

    public function wcmp_vendor_dashboard_add_product_endpoint() {
        global $WCMp, $wp;
        $WCMp->library->load_colorpicker_lib();
        $WCMp->library->load_datepicker_lib();
        $WCMp->library->load_frontend_upload_lib();
        $WCMp->library->load_accordian_lib();
        $WCMp->library->load_select2_lib();

        $suffix = defined( 'WCMP_SCRIPT_DEBUG' ) && WCMP_SCRIPT_DEBUG ? '' : '.min';

        if ( get_wcmp_vendor_settings( 'is_singleproductmultiseller', 'general' ) == 'Enable' ) {
            wp_enqueue_script( 'wcmp_admin_product_auto_search_js', $WCMp->plugin_url . 'assets/admin/js/admin-product-auto-search' . $suffix . '.js', array( 'jquery' ), $WCMp->version, true );
            wp_localize_script( 'wcmp_admin_product_auto_search_js', 'wcmp_admin_product_auto_search_js_params', array(
                'ajax_url'              => admin_url( 'admin-ajax.php' ),
                'search_products_nonce' => wp_create_nonce( 'search-products' ),
            ) );
        }

        if ( ! wp_script_is( 'tiny_mce', 'enqueued' ) ) {
            wp_enqueue_editor();
        }
        // Enqueue jQuery UI and autocomplete
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'wp-a11y' );
        wp_enqueue_script( 'suggest' );
        
        wp_register_script( 'wcmp_product_classify', $WCMp->plugin_url . 'assets/frontend/js/product-classify.js', array( 'jquery', 'jquery-blockui' ), $WCMp->version, true );
        $script_param = array(
            'ajax_url' => $WCMp->ajax_url(),
            'initial_graphic_url' => $WCMp->plugin_url.'assets/images/select-category-graphic.png',
            'i18n' => array(
                'select_cat_list' => __( 'Select a category from the list', 'dc-woocommerce-multi-vendor' )
            )
        );
        wp_enqueue_script( 'wcmp_product_classify' );
        $WCMp->localize_script( 'wcmp_product_classify', apply_filters( 'wcmp_product_classify_script_data_params', $script_param ) );

        $WCMp->template->get_template( 'vendor-dashboard/product-manager/add-product.php' );
    }
    
    public function wcmp_vendor_dashboard_edit_product_endpoint(){
        global $WCMp;
        // load scripts & styles
        $suffix = defined( 'WCMP_SCRIPT_DEBUG' ) && WCMP_SCRIPT_DEBUG ? '' : '.min';
        $WCMp->library->load_select2_lib();
        $WCMp->library->load_datepicker_lib();
        $WCMp->library->load_jquery_serializejson_library();
        $WCMp->library->load_tabs_library();
        wp_enqueue_media();
        wp_enqueue_script( 'selectWoo' );
        wp_enqueue_style('advance-product-manager');
        wp_register_script( 'wcmp-advance-product', $WCMp->plugin_url . 'assets/frontend/js/product.js', array( 'jquery', 'jquery-ui-sortable', 'select2_js', 'jquery-ui-datepicker', 'selectWoo', 'wcmp-serializejson', 'wcmp-tabs' ), $WCMp->version );
        wp_enqueue_script( 'wcmp-meta-boxes' );
        $WCMp->localize_script( 'wcmp-meta-boxes');
        // load classes
        $WCMp->load_class( 'edit-product', 'products' );
        $edit_product = new WCMp_Products_Edit_Product();
        $edit_product->output();
    }

    public function wcmp_vendor_dashboard_products_endpoint() {
        global $WCMp;
        if ( is_user_logged_in() && is_user_wcmp_vendor( get_current_vendor_id() ) ) {
            $WCMp->library->load_dataTable_lib();
            $products_table_headers = array(
                'select_product' => '',
                'image'      => '<i class="wcmp-font ico-image-icon"></i>',
                'name'       => __( 'Product', 'dc-woocommerce-multi-vendor' ),
                'price'      => __( 'Price', 'dc-woocommerce-multi-vendor' ),
                'stock'      => __( 'Stock', 'dc-woocommerce-multi-vendor' ),
                'categories' => __( 'Categories', 'dc-woocommerce-multi-vendor' ),
                'date'       => __( 'Date', 'dc-woocommerce-multi-vendor' ),
                'status'     => __( 'Status', 'dc-woocommerce-multi-vendor' ),
                'actions'     => __( 'Actions', 'dc-woocommerce-multi-vendor' ),
            );
            $products_table_headers = apply_filters( 'wcmp_vendor_dashboard_product_list_table_headers', $products_table_headers );
            $table_init = apply_filters( 'wcmp_vendor_dashboard_product_list_table_init', array(
                'ordering'    => 'true',
                'searching'   => 'false',
                'emptyTable'  => __( 'No products found!', 'dc-woocommerce-multi-vendor' ),
                'processing'  => __( 'Processing...', 'dc-woocommerce-multi-vendor' ),
                'info'        => __( 'Showing _START_ to _END_ of _TOTAL_ products', 'dc-woocommerce-multi-vendor' ),
                'infoEmpty'   => __( 'Showing 0 to 0 of 0 products', 'dc-woocommerce-multi-vendor' ),
                'lengthMenu'  => __( 'Number of rows _MENU_', 'dc-woocommerce-multi-vendor' ),
                'zeroRecords' => __( 'No matching products found', 'dc-woocommerce-multi-vendor' ),
                'search'      => __( 'Search:', 'dc-woocommerce-multi-vendor' ),
                'next'        => __( 'Next', 'dc-woocommerce-multi-vendor' ),
                'previous'    => __( 'Previous', 'dc-woocommerce-multi-vendor' ),
            ) );

            $WCMp->template->get_template( 'vendor-dashboard/product-manager/products.php', array( 'products_table_headers' => $products_table_headers, 'table_init' => $table_init ) );
        }
    }

    public function wcmp_vendor_dashboard_add_coupon_endpoint() {
        global $WCMp, $wp;
              
        $WCMp->library->load_select2_lib();
        $WCMp->library->load_datepicker_lib();
        wp_enqueue_script( 'selectWoo' );
        wp_register_script( 'wcmp-advance-coupon', $WCMp->plugin_url . 'assets/frontend/js/coupon.js', array( 'jquery', 'select2_js', 'jquery-ui-datepicker', 'selectWoo' ), $WCMp->version );
        wp_enqueue_script( 'wcmp-meta-boxes' );
        $WCMp->localize_script( 'wcmp-meta-boxes');
        // load classes
        $WCMp->load_class( 'add-coupon', 'coupons' );
        $add_coupon = new WCMp_Coupons_Add_Coupon();
        $add_coupon->output();
        
    }

    public function wcmp_vendor_dashboard_coupons_endpoint() {
        global $WCMp;
        if ( is_user_logged_in() && is_user_wcmp_vendor( get_current_vendor_id() ) ) {
            $WCMp->library->load_dataTable_lib();
            $WCMp->template->get_template( 'vendor-dashboard/coupon-manager/coupons.php' );
        }
    }

    /**
     * Dashboard order endpoint contect
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_orders_endpoint() {
        global $WCMp, $wp;
        $vendor = get_current_vendor();
        $suffix       = defined( 'WCMP_SCRIPT_DEBUG' ) && WCMP_SCRIPT_DEBUG ? '' : '.min';
        if ( isset( $_POST['wcmp-submit-mark-as-ship'] ) ) {
            $order_id = $_POST['order_id'];
            $tracking_id = $_POST['tracking_id'];
            $tracking_url = $_POST['tracking_url'];
            $vendor->set_order_shipped( $order_id, $tracking_id, $tracking_url );
        }

        /********************** Refund customer request ****************************************/
        if ( isset( $_POST['wcmp-submit-refund-customer-request'] ) ) {
            $order_id = $_POST['order_id'];
            update_post_meta( $order_id, '_customer_refund_order', $_POST[ 'refund_order_customer' ] );

            if( $_POST['refund_order_customer'] == 'status_refund' || $_POST['refund_order_customer'] == 'refund_request'  ) return;

            $_customer_user = get_post_meta( $order_id,'_customer_user', true );
            //admin email
            $user_by = get_user_by( 'email', get_option('admin_email') );
            $admin_id = $user_by->data->ID;

            $mail = WC()->mailer()->emails['WC_Email_Refund_Customer_order'];
            $result = $mail->trigger( $_customer_user , $order_id  );
            $result = $mail->trigger( $admin_id , $order_id  );

        }
        /********************** Refund customer request end ****************************************/
        $vendor_order = $wp->query_vars[get_wcmp_vendor_settings( 'wcmp_vendor_orders_endpoint', 'vendor', 'general', 'vendor-orders' )];
        if ( ! empty( $vendor_order ) ) {
            $order = wc_get_order( $vendor_order );
            $WCMp->library->load_select2_lib();
            wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
            wp_register_script( 'wc-clipboard', WC()->plugin_url() . '/assets/js/admin/wc-clipboard' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
            wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.4' );
            wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
            wp_localize_script(
                    'wc-enhanced-select',
                    'wc_enhanced_select_params',
                    array(
                            'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
                            'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
                            'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
                            'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
                            'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
                            'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
                            'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
                            'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
                            'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
                            'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
                            'ajax_url'                  => admin_url( 'admin-ajax.php' ),
                            'search_products_nonce'     => wp_create_nonce( 'search-products' ),
                            'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
                            'search_categories_nonce'   => wp_create_nonce( 'search-categories' ),
                    )
            );
            wp_enqueue_script('selectWoo');
            wp_enqueue_script('wc-clipboard');
            wp_enqueue_script('jquery-tiptip');
            wp_enqueue_script('wc-enhanced-select');
            wp_register_script('wcmp_order_details_js', $WCMp->plugin_url . 'assets/frontend/js/wcmp-order-details.js', array('jquery', 'accounting', 'jquery-tiptip', 'wc-enhanced-select', 'wc-clipboard'), $WCMp->version, true);
            wp_enqueue_script('wcmp_order_details_js');
            wp_localize_script(
                    'wcmp_order_details_js',
                    'wcmp_order_details_js_script_data',
                    array(
                        'i18n_do_refund'                => __( 'Are you sure you wish to process this refund? This action cannot be undone.', 'woocommerce' ),
                        'post_id'                       => isset( $vendor_order ) ? $vendor_order : '',
                        'order_item_nonce'              => wp_create_nonce( 'wcmp-order-item' ),
                        'grant_access_nonce'            => wp_create_nonce( 'grant-access' ),
                        'revoke_access_nonce'           => wp_create_nonce( 'revoke-access' ),
                        'mon_decimal_point'             => wc_get_price_decimal_separator(),
                        'ajax_url'                      => admin_url( 'admin-ajax.php' ),
                        'currency_format_num_decimals'  => wc_get_price_decimals(),
                        'currency_format_symbol'        => get_woocommerce_currency_symbol( $order->get_currency() ),
                        'currency_format_decimal_sep'   => esc_attr( wc_get_price_decimal_separator() ),
                        'currency_format_thousand_sep'  => esc_attr( wc_get_price_thousand_separator() ),
                        'currency_format'               => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting JS.
                        'rounding_precision'            => wc_get_rounding_precision(),
                        'i18n_download_permission_fail' => __( 'Could not grant access - the user may already have permission for this file or billing email is not set. Ensure the billing email is set, and the order has been saved.', 'woocommerce' ),
                        'i18n_permission_revoke'        => __( 'Are you sure you want to revoke access to this download?', 'woocommerce' ),
                        'i18n_do_cancel'                => __( 'Are you sure you want to cancel this order? This action cannot be undone.', 'dc-woocommerce-multi-vendor' ),
                    )
            );
            $WCMp->template->get_template( 'vendor-dashboard/vendor-orders/vendor-order-details.php', array( 'order_id' => $vendor_order ) );
        } else {
            $WCMp->library->load_dataTable_lib();

            if ( ! empty( $_POST['wcmp_start_date_order'] ) ) {
                $start_date = $_POST['wcmp_start_date_order'];
            } else {
                $start_date = date( 'Y-m-01' );
            }

            if ( ! empty( $_POST['wcmp_end_date_order'] ) ) {
                $end_date = $_POST['wcmp_end_date_order'];
            } else {
                $end_date = date( 'Y-m-d' );
            }
            
            /**
             * Action hook befor order list.
             *
             * @since 3.4.7
             */
            do_action('wcmp_befor_vendor_dashboard_order_list_actions', $_POST );
            
            // bulk actions
            $bulk_actions = apply_filters( 'wcmp_bulk_actions_vendor_order_list', array(
                'mark_processing'   => __( 'Change status to processing', 'woocommerce' ),
                'mark_on-hold'      => __( 'Change status to on-hold', 'woocommerce' ),
                'mark_completed'   => __( 'Change status to completed', 'woocommerce' ),
            ), $vendor );
                
            $WCMp->template->get_template( 'vendor-dashboard/vendor-orders.php', array( 
                'vendor' => $vendor, 
                'start_date'    => strtotime( $start_date ), 
                'end_date'      => strtotime( $end_date . ' +1 day' ),
                'bulk_actions'  => $bulk_actions,
            ) );
        }
    }

    /**
     * Display Vendor Withdrawal Content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_withdrawal_endpoint() {
        global $WCMp;
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        if ( $vendor ) {
            $WCMp->library->load_dataTable_lib();
            $meta_query['meta_query'] = array(
                array(
                    'key' => '_paid_status',
                    'value' => array('unpaid', 'partial_refunded'),
                    'compare' => 'IN'
                ),
                array(
                    'key'     => '_commission_vendor',
                    'value'   => absint( $vendor->term_id ),
                    'compare' => '='
                )
            );
            $vendor_unpaid_orders = $vendor->get_unpaid_orders( false, false, $meta_query );
            
            // withdrawal table init
            $table_init = apply_filters( 'wcmp_vendor_dashboard_payment_withdrawal_table_init', array(
                'ordering'    => 'false',
                'searching'   => 'false',
                'emptyTable'  => __( 'No orders found!', 'dc-woocommerce-multi-vendor' ),
                'processing'  => __( 'Processing...', 'dc-woocommerce-multi-vendor' ),
                'info'        => __( 'Showing _START_ to _END_ of _TOTAL_ orders', 'dc-woocommerce-multi-vendor' ),
                'infoEmpty'   => __( 'Showing 0 to 0 of 0 orders', 'dc-woocommerce-multi-vendor' ),
                'lengthMenu'  => __( 'Number of rows _MENU_', 'dc-woocommerce-multi-vendor' ),
                'zeroRecords' => __( 'No matching orders found', 'dc-woocommerce-multi-vendor' ),
                'search'      => __( 'Search:', 'dc-woocommerce-multi-vendor' ),
                'next'        => __( 'Next', 'dc-woocommerce-multi-vendor' ),
                'previous'    => __( 'Previous', 'dc-woocommerce-multi-vendor' ),
            ) );

            $WCMp->template->get_template( 'vendor-dashboard/vendor-withdrawal.php', array( 'vendor' => $vendor, 'vendor_unpaid_orders' => $vendor_unpaid_orders, 'table_init' => $table_init ) );
        }
    }

    /**
     * Display transaction details content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_transaction_details_endpoint() {
        global $WCMp, $wp;
        $user_id = get_current_vendor_id();
        if ( is_user_wcmp_vendor( $user_id ) ) {
            $transaction_id = $wp->query_vars[get_wcmp_vendor_settings( 'wcmp_transaction_details_endpoint', 'vendor', 'general', 'transaction-details' )];
            if ( ! empty( $transaction_id ) ) {
                $WCMp->template->get_template( 'vendor-dashboard/vendor-withdrawal/vendor-withdrawal-request.php', array( 'transaction_id' => $transaction_id ) );
            } else {
                $WCMp->library->load_dataTable_lib();
                $WCMp->template->get_template( 'vendor-dashboard/vendor-transactions.php' );
            }
        }
    }

    /**
     * Display Vendor university content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_knowledgebase_endpoint() {
        global $WCMp;
        wp_enqueue_style( 'jquery-ui-style' );
        wp_enqueue_script( 'jquery-ui-accordion' );
        $WCMp->template->get_template( 'vendor-dashboard/vendor-university.php' );
    }

    /**
     * Display Vendor Tools purging content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_vendor_tools_endpoint() {
        global $WCMp;
        $WCMp->template->get_template( 'vendor-dashboard/vendor-tools.php' );
    }

    /**
     * Display Vendor Products Q&As content
     * @global object $WCMp
     */
    public function wcmp_vendor_dashboard_products_qna_endpoint() {
        global $WCMp;
        if ( is_user_logged_in() && is_user_wcmp_vendor( get_current_vendor_id() ) ) {
            $WCMp->library->load_dataTable_lib();
            $WCMp->library->load_select2_lib();
            $WCMp->template->get_template( 'vendor-dashboard/vendor-products-qna.php' );
        }
    }

    public function save_vendor_dashboard_data() {
        global $WCMp;
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            switch ( $WCMp->endpoints->get_current_endpoint() ) {
                case 'storefront':
                case 'vendor-policies':
                case 'vendor-billing':
                    $error = $WCMp->vendor_dashboard->save_store_settings( $vendor->id, $_POST );
                    if ( empty( $error ) ) {
                        wc_add_notice( __( 'All Options Saved', 'dc-woocommerce-multi-vendor' ), 'success' );
                    } else {
                        wc_add_notice( $error, 'error' );
                    }
                    break;
                case 'vendor-shipping':
                    $WCMp->vendor_dashboard->save_vendor_shipping( $vendor->id, $_POST );
                    break;
                case 'profile':
                    $WCMp->vendor_dashboard->save_vendor_profile( $vendor->id, $_POST );
                    break;
                default :
                    break;
            }
        }
        // FPM add product messages
        if ( get_transient( 'wcmp_fpm_product_added_msg' ) ) {
            wc_add_notice( get_transient( 'wcmp_fpm_product_added_msg' ), 'success' );
            delete_transient( 'wcmp_fpm_product_added_msg' );
        }
    }

    /**
     * Change endpoint page title
     * @global object $wp_query
     * @global object $WCMp
     * @param string $title
     * @return string
     */
    public function wcmp_vendor_dashboard_endpoint_title( $title ) {
        global $wp_query, $WCMp;
        if ( ! is_null( $wp_query ) && ! is_admin() && is_main_query() && in_the_loop() && is_page() && is_wcmp_endpoint_url() ) {
            $endpoint = $WCMp->endpoints->get_current_endpoint();

            if ( isset( $WCMp->endpoints->wcmp_query_vars[$endpoint]['label'] ) && $endpoint_title = $WCMp->endpoints->wcmp_query_vars[$endpoint]['label'] ) {
                $title = $endpoint_title;
            }

            remove_filter( 'the_title', array( &$this, 'wcmp_vendor_dashboard_endpoint_title' ) );
        }

        return $title;
    }

    /**
     * set policies tab cap
     * @param Boolean $cap
     * @return Boolean
     */
    public function wcmp_vendor_dashboard_menu_vendor_policies_capability( $cap ) {
        if ( ('Enable' === get_wcmp_vendor_settings( 'is_policy_on', 'general' ) && apply_filters( 'wcmp_vendor_can_overwrite_policies', true )) || ('Enable' === get_wcmp_vendor_settings( 'is_customer_support_details', 'general' ) && apply_filters( 'wcmp_vendor_can_overwrite_customer_support', true )) ) {
            $cap = true;
        }
        return $cap;
    }

    public function wcmp_vendor_dashboard_menu_vendor_withdrawal_capability( $cap ) {
        if ( get_wcmp_vendor_settings( 'wcmp_disbursal_mode_vendor', 'payment' ) ) {
            $cap = true;
        }
        return $cap;
    }

    public function wcmp_vendor_dashboard_menu_vendor_shipping_capability( $cap ) {
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        if ( $vendor ) {
            return $vendor->is_shipping_enable();
        } else {
            return false;
        }
    }

    /**
     * Generate Vendor Progress
     * @global object $WCMp
     */
    public function before_wcmp_vendor_dashboard_content( $key ) {
        global $WCMp;
        if ( $key !== $WCMp->endpoints->get_current_endpoint() ) {
            return;
        }
        $vendor = get_wcmp_vendor( get_current_vendor_id() );
        if ( $vendor && apply_filters( 'wcmp_vendor_dashboard_show_progress_bar', true, $vendor ) ) {
            $vendor_progress = wcmp_get_vendor_profile_completion( $vendor->id );
            if ( $vendor_progress['progress'] < 100 ) {
                echo '<div class="col-md-12">';
                echo '<div class="panel">';
                if ( $vendor_progress['todo'] && is_array( $vendor_progress['todo'] ) ) {
                    $todo_link = isset( $vendor_progress['todo']['link'] ) ? esc_url( $vendor_progress['todo']['link'] ) : '';
                    $todo_label = isset( $vendor_progress['todo']['label'] ) ? $vendor_progress['todo']['label'] : '';
                    echo '<div style="margin:17px 20px 12px 20px;">' . __( 'To boost up your profile progress add', 'dc-woocommerce-multi-vendor' ) . ' <a href="' . $todo_link . '">' . $todo_label . '</a></div>';
                }
                echo '<div class="progress" style="margin:0 20px 20px;">';
                echo '<div class="progress-bar" role="progressbar" style="width: ' . $vendor_progress['progress'] . '%;" aria-valuenow="' . $vendor_progress['progress'] . '" aria-valuemin="0" aria-valuemax="100">' . $vendor_progress['progress'] . '%</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
    }

    /**
     * WCMp theme supported function
     */
    public function wcmp_add_theme_support() {
        if ( is_vendor_dashboard() && is_user_logged_in() && is_user_wcmp_vendor( get_current_user_id() ) ) {
            global $wp_filter;
            //Flatsome mobile menu support
            remove_action( 'wp_footer', 'flatsome_mobile_menu', 7 );
            // Remove demo store notice
            remove_action( 'wp_footer', 'woocommerce_demo_store' );
            // Remove custom css
            $wp_head_hooks = $wp_filter['wp_head']->callbacks;
            foreach ( $wp_head_hooks as $priority => $wp_head_hook ) {
                foreach ( array_keys( $wp_head_hook ) as $hook ) {
                    if ( strpos( $hook, 'custom_css' ) ) {
                        remove_action( 'wp_head', $hook, $priority );
                    }
                }
            }
        }
    }

    /**
     * WCMp rejected vendor dashboard function
     */
    public function rejected_vendor_dashboard_content() {
    	global $WCMp, $wp;
    	
    	if(isset($wp->query_vars['rejected-vendor-reapply'])) {
    		$WCMp->template->get_template('non-vendor/rejected-vendor-reapply.php');
    	} else {
    		$WCMp->template->get_template('non-vendor/rejected-vendor-dashboard.php');
		}
    }
    
    /**
     *  Update rejected vendor data and make the status pending
     */
    public function save_rejected_vendor_reapply_data() {
    	global $WCMp;
        $user = wp_get_current_user();
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' && is_user_wcmp_rejected_vendor($user->ID) && $WCMp->endpoints->get_current_endpoint() == 'rejected-vendor-reapply') {
        	if(isset($_POST['reapply_vendor_application']) && isset($_POST['wcmp_vendor_fields'])) {
        		if (isset($_FILES['wcmp_vendor_fields'])) {
					$attacment_files = $_FILES['wcmp_vendor_fields'];
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
                        /**
                         * Action hook to modify vendor re submit application before save.
                         *
                         * @since 3.4.5
                         */
                        do_action( 'wcmp_before_reapply_vendor_application_save', $_POST, get_current_vendor( $user->ID ) );
        		update_user_meta( $user->ID, 'wcmp_vendor_fields', $_POST['wcmp_vendor_fields']);
        		$user->remove_cap( 'dc_rejected_vendor' );
        		$user->add_cap( 'dc_pending_vendor' );
        		/**
                         * Action hook to modify vendor re submit application after save.
                         *
                         * @since 3.4.5
                         */
                        do_action( 'wcmp_after_reapply_vendor_application_save', $_POST, get_current_vendor( $user->ID ) );
        		$wcmp_vendor_rejection_notes = unserialize( get_user_meta( $user->ID, 'wcmp_vendor_rejection_notes', true ) );
				$wcmp_vendor_rejection_notes[time()] = array(
						'note_by' => $user->ID,
						'note' => __( 'Re applied to become a vendor', 'dc-woocommerce-multi-vendor' ));
				update_user_meta( $user->ID, 'wcmp_vendor_rejection_notes', serialize( $wcmp_vendor_rejection_notes ) );
                                /**
                         * Action hook to modify vendor re submit application after note save.
                         *
                         * @since 3.4.5
                         */
                        do_action( 'wcmp_after_reapply_vendor_application_saved_notes', $_POST, get_current_vendor( $user->ID ) );
        	}
    	}
    }
}
