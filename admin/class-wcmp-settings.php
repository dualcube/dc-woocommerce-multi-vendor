<?php

class WCMp_Settings {

    private $tabs = array();
    private $options = array();
    private $tabsection_general = array();
    private $tabsection_payment = array();
    private $tabsection_vendor = array();
    private $tabsection_capabilities = array();
    /*********************** refund tab ***************************/
    private $tabsection_refund = array();
    /*********************** refund tab ***************************/
    private $vendor_class_obj;

    /**
     * Start up
     */
    public function __construct() {
        // Admin menu
        add_action( 'admin_menu', array( $this, 'add_settings_page' ), 100 );
        add_action( 'admin_init', array( $this, 'settings_page_init' ) );

        add_action( 'in_admin_header', array( &$this, 'wcmp_settings_admin_header' ), 100 );

        // Settings tabs general
        add_action( 'settings_page_general_tab_init', array( &$this, 'general_tab_init' ), 10, 1 );
        add_action( 'settings_page_general_policies_tab_init', array( &$this, 'general_policies_tab_init' ), 10, 2 );
        add_action( 'settings_page_general_customer_support_details_tab_init', array( &$this, 'general_customer_support_details_tab_init' ), 10, 2 );
        add_action( 'settings_page_general_tools_tab_init', array( &$this, 'general_tools_tab_init' ), 10, 2 );
        // Settings tabs vendor
        add_action( 'settings_page_vendor_general_tab_init', array( &$this, 'vendor_general_tab_init' ), 10, 2 );
        add_action( 'settings_page_vendor_registration_tab_init', array( &$this, 'vendor_registration_tab_init' ), 10, 2 );
        add_action( 'settings_page_vendor_dashboard_tab_init', array( &$this, 'vendor_dashboard_tab_init' ), 10, 2 );
        // Settings tabs frontend
//        add_action('settings_page_frontend_tab_init', array(&$this, 'frontend_tab_init'), 10, 1);
        // Settings tabs payment
        add_action( 'settings_page_payment_tab_init', array( &$this, 'payment_tab_init' ), 10, 1 );
        add_action( 'settings_page_payment_paypal_masspay_tab_init', array( &$this, 'payment_paypal_masspay_init' ), 10, 2 );
        add_action( 'settings_page_payment_paypal_payout_tab_init', array( &$this, 'payment_paypal_payout_init' ), 10, 2 );
        add_action( 'settings_page_payment_stripe_gateway_tab_init', array(&$this, 'payment_stripe_gateway_tab_init'), 10, 2);
        // Settings tabs capability
        add_action( 'settings_page_capabilities_product_tab_init', array( &$this, 'capabilites_product_tab_init' ), 10, 2 );

        /********************************   Refund tab  start  ******************************************/
        add_action( 'settings_page_RMA_refund_tab_init', array( &$this, 'RMA_refund_tab_init' ), 10, 2 );
        /********************************   Refund tab  end  ******************************************/

//        add_action('settings_page_capabilities_order_tab_init', array(&$this, 'capabilites_order_tab_init'), 10, 2);
//        add_action('settings_page_capabilities_miscellaneous_tab_init', array(&$this, 'capabilites_miscellaneous_tab_init'), 10, 2);
        // Settings tabs others
        add_action( 'settings_page_wcmp-addons_tab_init', array( &$this, 'wcmp_addons_tab_init' ), 10, 2 );
        add_action( 'settings_page_to_do_list_tab_init', array( &$this, 'to_do_list_tab_init' ), 10, 1 );
        add_action( 'settings_page_notices_tab_init', array( &$this, 'notices_tab_init' ), 10, 1 );
        add_action( 'settings_page_vendors_tab_init', array( &$this, 'vendors_tab_init' ), 10, 1 );

        add_action( 'update_option_wcmp_vendor_general_settings_name', array( &$this, 'wcmp_update_option_wcmp_vendor_general_settings_name' ) );
        
        // Save screen options
        add_filter('set-screen-option', array( &$this, 'vendors_set_option'), 10, 3);
    }
    
    public function wcmp_settings_admin_header() {
        $screen = get_current_screen();
        if ( empty( $screen->id ) || strpos( $screen->id, 'wcmp_page_wcmp-setting-admin' ) === false ) {
            return;
        }
        echo '<div class="wcmp-settings-admin-header"></div>';
    }

    /**
     * flush rewrite rules after endpoints change
     */
    public function wcmp_update_option_wcmp_vendor_general_settings_name() {
        global $WCMp;
        $WCMp->endpoints->init_wcmp_query_vars();
        $WCMp->endpoints->add_wcmp_endpoints();
        flush_rewrite_rules();
    }

    /**
     * Add options page   
     */
    public function add_settings_page() {
        global $WCMp, $submenu;

        add_menu_page(
            __( 'WCMp', 'dc-woocommerce-multi-vendor' )
            , __( 'WCMp', 'dc-woocommerce-multi-vendor' )
            , 'manage_woocommerce'
            , 'wcmp'
            , null
            , $WCMp->plugin_url . 'assets/images/dualcube.png'
            , 45
        );
        add_submenu_page( 'wcmp', __( 'Reports', 'dc-woocommerce-multi-vendor' ), __( 'Reports', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'wc-reports&tab=wcmp_vendors', '__return_false' );
        $wcmp_vendors_page = add_submenu_page( 'wcmp', __( 'Vendors', 'dc-woocommerce-multi-vendor' ), __( 'Vendors', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'vendors', array( $this, 'wcmp_vendors' ) );
        $wcmp_settings_page = add_submenu_page( 'wcmp', __( 'Settings', 'dc-woocommerce-multi-vendor' ), __( 'Settings', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'wcmp-setting-admin', array( $this, 'create_wcmp_settings' ) );

        $wcmp_todo_list = add_submenu_page( 'wcmp', __( 'To-do List', 'dc-woocommerce-multi-vendor' ), __( 'To-do List', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'wcmp-to-do', array( $this, 'wcmp_to_do' ) );
        $wcmp_extension_page = add_submenu_page( 'wcmp', __( 'Extensions', 'dc-woocommerce-multi-vendor' ), __( 'Extensions', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'wcmp-extensions', array( $this, 'wcmp_extensions' ) );
        // transaction details page
        add_submenu_page( null, __( 'Transaction Details', 'dc-woocommerce-multi-vendor' ), __( 'Transaction Details', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'wcmp-transaction-details', array( $this, 'wcmp_transaction_details' ) );
        // Report a bugs
        //$wcmp_extension_page = add_submenu_page( 'wcmp', __( 'Report a bugs', 'dc-woocommerce-multi-vendor' ), __( 'Report a bugs', 'dc-woocommerce-multi-vendor' ), 'manage_woocommerce', 'wcmp-report-bugs', array( $this, 'wcmp_report_bugs' ) );

        // Assign priority incrmented by 1
        $wcmp_submenu_priority = array(
        	'wc-reports&tab=wcmp_vendors' => 5,
        	'edit.php?post_type=dc_commission' => 0,
        	'edit.php?post_type=wcmp_vendor_notice' => 2,
        	'edit.php?post_type=wcmp_university' => 3,
        	'vendors' => 4,
        	'wcmp-setting-admin' => 6,
        	'wcmp-to-do' => 1,
        	'wcmp-extensions' => 7,
		);
        
		$this->tabs = $this->get_wcmp_settings_tabs();
        $this->tabsection_general = $this->get_wcmp_settings_tabsections_general();
        $this->tabsection_payment = $this->get_wcmp_settings_tabsections_payment();
        $this->tabsection_vendor = $this->get_wcmp_settings_tabsections_vendor();
        $this->tabsection_capabilities = $this->get_wcmp_settings_tabsections_capabilities();
        /******************** refund tab *******************************/
        $this->tabsection_refund = $this->get_wcmp_settings_tabsections_refund();
        /***************************** refund tab end ***********************************/
        // Add WCMp Help Tab
        add_action( 'load-' . $wcmp_settings_page, array( &$this, 'wcmp_settings_add_help_tab' ) );
        add_action( 'load-' . $wcmp_extension_page, array( &$this, 'wcmp_settings_add_help_tab' ) );
        add_action( 'load-' . $wcmp_todo_list, array( &$this, 'wcmp_settings_add_help_tab' ) );
        add_action( 'load-' . $wcmp_vendors_page, array( &$this, 'wcmp_vendors_add_help_tab' ) );
        
        /* sort wcmp submenu */
        if ( isset( $submenu['wcmp'] ) ) {
        	$wcmp_submenu_priority = apply_filters( 'wcmp_submenu_items', $wcmp_submenu_priority, $submenu['wcmp'] );
        	$submenu_wcmp_sort = array();
        	$submenu_wcmp_sort_duplicates = array();
        	foreach($submenu['wcmp'] as $menu_items) {
        		if(isset($wcmp_submenu_priority[$menu_items[2]]) && ($wcmp_submenu_priority[$menu_items[2]] >= 0) && !isset($submenu_wcmp_sort[$wcmp_submenu_priority[$menu_items[2]]])) $submenu_wcmp_sort[$wcmp_submenu_priority[$menu_items[2]]] = $menu_items;
				else $submenu_wcmp_sort_duplicates[] = $menu_items;
        	}
        	
        	ksort($submenu_wcmp_sort);
        	
        	$submenu_wcmp_sort = array_merge($submenu_wcmp_sort, $submenu_wcmp_sort_duplicates);
        	
        	$submenu['wcmp'] = $submenu_wcmp_sort;
        }
    }

    public function wcmp_transaction_details() {
        global $WCMp;
        ?>
        <div class="wrap blank-wrap"><h3><?php _e( 'Transaction Details', 'dc-woocommerce-multi-vendor' ); ?></h3></div>
        <div class="wrap wcmp-settings-wrap panel-body">
            <?php
            $_is_trans_details_page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
            $trans_id = isset( $_REQUEST['trans_id'] ) ? absint( $_REQUEST['trans_id'] ) : 0;
            if ( $_is_trans_details_page == 'wcmp-transaction-details' && $trans_id != 0 ) {
                $transaction = get_post( $trans_id );
                if ( isset( $transaction->post_type ) && $transaction->post_type == 'wcmp_transaction' ) {
                    $vendor = get_wcmp_vendor_by_term( $transaction->post_author ) ? get_wcmp_vendor_by_term( $transaction->post_author ) : get_wcmp_vendor( $transaction->post_author );
                    $commission_details = $WCMp->transaction->get_transaction_item_details( $trans_id );
                    ?>
                    <table class="widefat fixed striped">
                        <?php
                        if ( ! empty( $commission_details['header'] ) ) {
                            echo '<thead><tr>';
                            foreach ( $commission_details['header'] as $header_val ) {
                                echo '<th>' . $header_val . '</th>';
                            }
                            echo '</tr></thead>';
                        }
                        echo '<tbody>';
                        if ( ! empty( $commission_details['body'] ) ) {

                            foreach ( $commission_details['body'] as $commission_detail ) {
                                echo '<tr>';
                                foreach ( $commission_detail as $details ) {
                                    foreach ( $details as $detail_key => $detail ) {
                                        echo '<td>' . $detail . '</td>';
                                    }
                                }
                                echo '</tr>';
                            }
                        }
                        if ( $totals = $WCMp->transaction->get_transaction_item_totals( $trans_id, $vendor ) ) {
                            foreach ( $totals as $total ) {
                                echo '<tr><td colspan="3" >' . $total['label'] . '</td><td>' . $total['value'] . '</td></tr>';
                            }
                        }
                        echo '</tbody>';
                        ?>
                    </table>
                <?php } else { ?>
                    <p class="wcmp_headding3"><?php echo __( 'Unfortunately transaction details are not found. You may try again later.', 'dc-woocommerce-multi-vendor' ); ?></p>
                <?php }
            } else {
                ?>
                <p class="wcmp_headding3"><?php echo __( 'Unfortunately transaction details are not found. You may try again later.', 'dc-woocommerce-multi-vendor' ); ?></p> 
            <?php } ?>
        </div>
        <?php
    }
    
    public function wcmp_vendors_add_help_tab() {
        global $WCMp;
        $tab = 'vendors';
        
        $screen = get_current_screen();
        
        $option = 'per_page';
		$args   = [
			'label'   => __('Number of vendors per page:', 'dc-woocommerce-multi-vendor'),
			'default' => 5,
			'option'  => 'vendors_per_page'
		];
	
		add_screen_option( $option, $args );
		
		$WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
		$this->vendor_class_obj = new WCMp_Settings_WCMp_Vendors( $tab );
    }
    
    function vendors_set_option($status, $option, $value) {
		if ( 'vendors_per_page' == $option ) return $value;
		return $status;
	}
    
    public function wcmp_settings_add_help_tab() {
        global $WCMp;
        $screen = get_current_screen();

        $screen->add_help_tab( array(
            'id'      => 'wcmp_intro',
            'title'   => __( 'WC Marketplace', 'dc-woocommerce-multi-vendor' ),
            'content' => '<h2>WC Marketplace ' . WCMp_PLUGIN_VERSION . '</h2>' . '<iframe src="https://player.vimeo.com/video/203286653?title=0&byline=0&portrait=0" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
        ) );
        $screen->add_help_tab( array(
            'id'      => 'wcmp_help',
            'title'   => __( 'Help &amp; Support', 'dc-woocommerce-multi-vendor' ),
            'content' => '<h2>Help &amp; Support</h2>' .
            '<p>Our enrich documentation is suffice to answer all of your queries on WC Marketplace. We have covered all of your questions with snippets, graphics and a complete set-up guide.</p>'
            . '<p>For further assistance in WC Marketplace please contact to our <a target="_blank" href="https://wc-marketplace.com/support-forum/">support forum</a> .</p>',
        ) );
        $screen->add_help_tab( array(
            'id'      => 'wcmp_found_bug',
            'title'   => __( 'Found a bug?', 'dc-woocommerce-multi-vendor' ),
            'content' => '<h2>Found a bug?</h2>'
            . '<p>If you find a bug within WC Marketplace core you can submit your report by raising a ticket via <a target="_blank" href="https://github.com/dualcube/dc-woocommerce-multi-vendor/issues">Github issues</a>. Prior to submitting the report, please read the contribution guide.</p>'
        ) );
        $screen->add_help_tab( array(
            'id'      => 'wcmp_knowledgebase',
            'title'   => __( 'Knowledgebase', 'dc-woocommerce-multi-vendor' ),
            'content' => '<h2>Knowledgebase</h2>'
            . '<p>If you would like to learn more about using WC Marketplace, please follow our <a target="_blank" href="https://wc-marketplace.com/knowledgebase/">knowledgebase</a> section.</p>'
        ) );
        $screen->add_help_tab( array(
            'id'      => 'wcmp_onboard_tab',
            'title'   => __( 'Setup wizard', 'dc-woocommerce-multi-vendor' ),
            'content' =>
            '<h2>' . __( 'Setup wizard', 'dc-woocommerce-multi-vendor' ) . '</h2>' .
            '<p>' . __( 'If you need to access the setup wizard again, please click on the button below.', 'dc-woocommerce-multi-vendor' ) . '</p>' .
            '<p><a href="' . admin_url( 'index.php?page=wcmp-setup' ) . '" class="button button-primary">' . __( 'Setup wizard', 'dc-woocommerce-multi-vendor' ) . '</a></p>',
        ) );
        $screen->set_help_sidebar(
            '<p><strong>' . __( 'For more information:', 'dc-woocommerce-multi-vendor' ) . '</strong></p>' .
            '<p><a href="' . 'https://wordpress.org/plugins/dc-woocommerce-multi-vendor/' . '" target="_blank">' . __( 'WordPress.org Project', 'dc-woocommerce-multi-vendor' ) . '</a></p>' .
            '<p><a href="' . 'https://github.com/dualcube/dc-woocommerce-multi-vendor' . '" target="_blank">' . __( 'Github Project', 'dc-woocommerce-multi-vendor' ) . '</a></p>' .
            '<p><a href="' . 'http://wcmpdemos.com/addon/WCMp/' . '" target="_blank">' . __( 'View Demo', 'dc-woocommerce-multi-vendor' ) . '</a></p>' .
            '<p><a href="' . 'https://wc-marketplace.com/third-party-themes/' . '" target="_blank">' . __( 'Supported Themes', 'dc-woocommerce-multi-vendor' ) . '</a></p>' .
            '<p><a href="' . 'https://wc-marketplace.com/addons/' . '" target="_blank">' . __( 'Official Extensions', 'dc-woocommerce-multi-vendor' ) . '</a></p>'
        );
    }

    public function get_wcmp_settings_tabs() {
        $tabs = apply_filters( 'wcmp_tabs', array(
            'general'      => __( 'General', 'dc-woocommerce-multi-vendor' ),
            'vendor'       => __( 'Vendor', 'dc-woocommerce-multi-vendor' ),
//            'frontend' => __('Frontend', 'dc-woocommerce-multi-vendor'),
            'payment'      => __( 'Payment', 'dc-woocommerce-multi-vendor' ),
            'capabilities' => __( 'Capabilities', 'dc-woocommerce-multi-vendor' ),
            'RMA'      => __( 'RMA', 'dc-woocommerce-multi-vendor' ),
        ) );
        return $tabs;
    }

    public function get_wcmp_settings_tabsections_general() {
        $tabsection_general = apply_filters( 'wcmp_tabsection_general', array(
            'general' => array( 'title' => __( 'General', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-admin-site' ),
        ) );
        if ( 'Enable' === get_wcmp_vendor_settings( 'is_policy_on', 'general', '' ) ) {
            $tabsection_general['policies'] = array( 'title' => __( 'Policies', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-lock' );
        }
        if ( 'Enable' === get_wcmp_vendor_settings( 'is_customer_support_details', 'general', '' ) ) {
            $tabsection_general['customer_support_details'] = array( 'title' => __( 'Customer Support', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-universal-access' );
        }
        $tabsection_general['tools'] = array( 'title' => __( 'Tools', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-hammer' );

        return $tabsection_general;
    }

    public function get_wcmp_settings_tabsections_payment() {
        $tabsection_payment = apply_filters( 'wcmp_tabsection_payment', array(
            'payment' => array( 'title' => __( 'Payment Settings', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-share-alt' )
        ) );
        if ( 'Enable' === get_wcmp_vendor_settings( 'payment_method_paypal_masspay', 'payment' ) ) {
            $tabsection_payment['paypal_masspay'] = array( 'title' => __( 'Paypal Masspay', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-tickets-alt' );
        }
        if ( 'Enable' === get_wcmp_vendor_settings( 'payment_method_paypal_payout', 'payment' ) ) {
            $tabsection_payment['paypal_payout'] = array( 'title' => __( 'Paypal Payout', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-randomize' );
        }
        if ( 'Enable' === get_wcmp_vendor_settings( 'payment_method_stripe_masspay', 'payment' ) ) {
            $tabsection_payment['stripe_gateway'] = array( 'title' => __( 'Stripe Gateway', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-tickets-alt' );
        }
        return $tabsection_payment;
    }

    public function get_wcmp_settings_tabsections_vendor() {
        $tabsection_vendor = apply_filters( 'wcmp_tabsection_vendor', array(
            'registration' => array( 'title' => __( 'Vendor Registration', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-media-document' ),
            'general'      => array( 'title' => __( 'Vendor Pages', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-admin-page' ),
            'dashboard'    => array( 'title' => __( 'Vendor Frontend', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-admin-appearance' )
        ) );
        return $tabsection_vendor;
    }

    public function get_wcmp_settings_tabsections_capabilities() {
        $tabsection_capabilities = apply_filters( 'wcmp_tabsection_capabilities', array(
            'product' => array( 'title' => __( 'Product', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-cart' ),
//            'order' => __('Order', 'dc-woocommerce-multi-vendor'),
//            'miscellaneous' => __('Miscellaneous', 'dc-woocommerce-multi-vendor')
        ) );
        return $tabsection_capabilities;
    }

    /************************* refund tab section *****************************************/
    public function get_wcmp_settings_tabsections_refund() {
        $tabsection_refund = apply_filters( 'wcmp_tabsection_refund', array(
            'refund' => array( 'title' => __( 'Refund', 'dc-woocommerce-multi-vendor' ), 'icon' => 'dashicons-cart' ),
        ) );
        return $tabsection_refund;
    }
    /************************* refund tab section *****************************************/

    public function get_settings_tab_desc() {
        $tab_desc = apply_filters( 'wcmp_tabs_desc', array(
            'product'  => __( 'Configure the "Product Add" page for vendors. Choose the features you want to show to your vendors.', 'dc-woocommerce-multi-vendor' ),
            'frontend' => __( 'Configure which vendor details you want to reveal to your users', 'dc-woocommerce-multi-vendor' ),
        ) );
        return $tab_desc;
    }

    public function wcmp_settings_tabs() {
        $current = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
        $sublinks = array();
        foreach ( $this->tabs as $tab_id => $tab ) {
            if ( $current != $tab_id || ! $this->is_wcmp_tab_has_subtab( $tab_id ) ) {
                continue;
            }
            $current_section = isset( $_GET['tab_section'] ) && ! empty( $_GET['tab_section'] ) ? $_GET['tab_section'] : current( array_keys( $this->get_wcmp_subtabs( $tab_id ) ) );

            foreach ( $this->get_wcmp_subtabs( $tab_id ) as $subtab_id => $subtab ) {
                $sublink = '';
                if ( is_array( $subtab ) ) {
                    $icon = isset( $subtab['icon'] ) && ! empty( $subtab['icon'] ) ? '<span class="dashicons ' . $subtab['icon'] . '"></span> ' : '';
                    $sublink = $icon . '<label>' . $subtab['title'] . '</label>';
                } else {
                    $sublink = '<label>' . $subtab . '</label>';
                }

                if ( $subtab_id === $current_section ) {
                    $sublinks[] = "<li><a class='current wcmp_sub_sction' href='?page=wcmp-setting-admin&tab=$tab_id&tab_section=$subtab_id'>$sublink</a></li>";
                } else {
                    $sublinks[] = "<li><a class='wcmp_sub_sction' href='?page=wcmp-setting-admin&tab=$tab_id&tab_section=$subtab_id'>$sublink</a></li>";
                }
            }
        }

        $links = array();
        foreach ( $this->tabs as $tab => $name ) :
            if ( $tab == $current ) :
                $links[] = "<a class='nav-tab nav-tab-active' href='?page=wcmp-setting-admin&tab=$tab'>$name</a>";
            else :
                $links[] = "<a class='nav-tab' href='?page=wcmp-setting-admin&tab=$tab'>$name</a>";
            endif;
        endforeach;


        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $links as $link ) {
            echo $link;
        }
        echo '</h2>';

        $display_sublink = apply_filters( 'display_wcmp_sublink', $this->is_wcmp_tab_has_subtab( $current ), $current );
        $sublinks = apply_filters( 'wcmp_subtab', $sublinks, $current );
        if ( $display_sublink ) {
            echo '<div class="wcmp_subtab_container">';
            echo '<ul class="subsubsub wcmpsubtabadmin">';
            foreach ( $sublinks as $sublink ) {
                echo $sublink;
            }
            echo '</ul>';
        }
    }

    /**
     * Options page callback
     */
    public function create_wcmp_settings() {
        global $WCMp;
        ?>
        <div class="wrap blank-wrap"><h2></h2></div>
        <div class="wrap wcmp-settings-wrap">
            <?php $this->wcmp_settings_tabs(); ?>
            <?php
            $tab = isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : current( array_keys( $this->tabs ) );

            foreach ( $this->tabs as $tab_id => $_tab ) {
                if ( $tab_id != $tab ) {
                    continue;
                }
                $tab_section = isset( $_GET['tab_section'] ) && ! empty( $_GET['tab_section'] ) ? $_GET['tab_section'] : current( array_keys( $this->get_wcmp_subtabs( $tab_id ) ) );
            }

            foreach ( $this->tabs as $tab_id => $tab_name ) {
                $this->options = array_merge( $this->options, (array) get_option( "wcmp_{$tab_id}_settings_name", array() ) );
                if ( $this->is_wcmp_tab_has_subtab( $tab_id ) ) {
                    foreach ( $this->get_wcmp_subtabs( $tab_id ) as $subtab_id => $subtab_name ) {
                        $this->options = array_merge( $this->options, get_option( "wcmp_{$tab_id}_{$subtab_id}_settings_name", array() ) );
                    }
                }
            }
            /**
             *  patch for duplicate field name override from different settings areas
             */
            $tab_sub_tab = '';
            if( $tab ) $tab_sub_tab = $tab;
            if( $tab_section ) $tab_sub_tab = $tab_sub_tab."_".$tab_section;
            $tab_settings_options = ( get_option( "wcmp_{$tab_sub_tab}_settings_name", array() ) ) ? get_option( "wcmp_{$tab_sub_tab}_settings_name", array() ) : get_option( "wcmp_{$tab}_settings_name", array() );
            $this->options = ( $tab_settings_options ) ? $tab_settings_options : $this->options;
            
            foreach ( $this->tabs as $tab_id => $tab_name ) {
                settings_errors( "wcmp_{$tab_id}_settings_name" );
                if ( $this->is_wcmp_tab_has_subtab( $tab_id ) ) {
                    foreach ( $this->get_wcmp_subtabs( $tab_id ) as $subtab_id => $subtab_name ) {
                        settings_errors( "wcmp_{$tab_id}_{$subtab_id}_settings_name" );
                    }
                }
            }
            ?>
            <form class='wcmp_vendors_settings <?php echo $this->is_wcmp_tab_has_subtab( $tab ) ? 'wcmp_subtab_content' : 'wcmp_tab_content'; ?> wcmp_<?php echo $tab; ?>_<?php echo $tab_section; ?>_settings_group' method="post" action="options.php">
                <?php
                $tab_desc = $this->get_settings_tab_desc();
                if ( ! empty( $tab_desc[$tab] ) ) {
                    echo '<h4 class="wcmp-tab-description">' . $tab_desc[$tab] . '</h4>';
                }
                ?>
                <?php
                // This prints out all hidden setting fields
                if ( $tab == 'general' && isset( $_GET['tab_section'] ) && $_GET['tab_section'] != 'general' ) {
                    settings_fields( "wcmp_{$tab}_{$tab_section}_settings_group" );
                    do_action( "wcmp_{$tab}_{$tab_section}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-{$tab_section}-settings-admin" );
                    if ( $tab_section == 'tools' ) {
                        do_action( "settings_page_{$tab}_{$tab_section}_tab_init", $tab, $tab_section );
                    }else{
                        submit_button();
                    }
                } else if ( $tab == 'payment' && isset( $_GET['tab_section'] ) && $_GET['tab_section'] != 'payment' ) {
                    settings_fields( "wcmp_{$tab}_{$tab_section}_settings_group" );
                    do_action( "wcmp_{$tab}_{$tab_section}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-{$tab_section}-settings-admin" );
                    submit_button();
                } else if ( $tab == 'vendor' ) {
                    settings_fields( "wcmp_{$tab}_{$tab_section}_settings_group" );
                    do_action( "wcmp_{$tab}_{$tab_section}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-{$tab_section}-settings-admin" );
                    if ( $tab_section == 'registration' ) {
                        do_action( "settings_page_{$tab}_{$tab_section}_tab_init", $tab, $tab_section );
                        wp_enqueue_script( 'wcmp_angular', $WCMp->plugin_url . 'assets/admin/js/angular.min.js', array(), $WCMp->version );
                        wp_enqueue_script( 'wcmp_angular-ui', $WCMp->plugin_url . 'assets/admin/js/sortable.js', array( 'wcmp_angular' ), $WCMp->version );
                        wp_enqueue_script( 'wcmp_vendor_registration', $WCMp->plugin_url . 'assets/admin/js/vendor_registration_app.js', array( 'wcmp_angular', 'wcmp_angular-ui' ), $WCMp->version );
                        $wcmp_vendor_registration_form_data = get_option( 'wcmp_vendor_registration_form_data' );
                        wp_localize_script( 'wcmp_vendor_registration', 'vendor_registration_param', array( 'partials' => $WCMp->plugin_url . 'assets/admin/partials/', 'ajax_url' => admin_url( 'admin-ajax.php' ), 'lang' => array('need_country_dependancy' => __('Please add country field first.', 'dc-woocommerce-multi-vendor')), 'form_data' => $wcmp_vendor_registration_form_data ) );
                    } else {
                        submit_button();
                    }
                } else if ( $tab == 'capabilities' ) {
                    if ( isset( $_GET['tab_section'] ) ) {
                        $tab_section = $_GET['tab_section'];
                    } else {
                        $tab_section = 'product';
                    }
                    settings_fields( "wcmp_{$tab}_{$tab_section}_settings_group" );
                    do_action( "wcmp_{$tab}_{$tab_section}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-{$tab_section}-settings-admin" );
                    submit_button();
                } else if ( $tab == 'RMA' ) {
                    if ( isset( $_GET['tab_section'] ) ) {
                        $tab_section = $_GET['tab_section'];
                    } else {
                        $tab_section = 'refund';
                    }
                    settings_fields( "wcmp_{$tab}_{$tab_section}_settings_group" );
                    do_action( "wcmp_{$tab}_{$tab_section}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-{$tab_section}-settings-admin" );
                    submit_button();
                } else if ( $tab == 'wcmp-addons' ) {
                    do_action( "settings_page_{$tab}_tab_init", $tab );
                } else if ( isset( $_GET['tab_section'] ) && $_GET['tab_section'] && $_GET['tab_section'] != 'general' && $tab != 'general' && $tab != 'payment' ) {
                    $tab_section = $_GET['tab_section'];
                    settings_fields( "wcmp_{$tab}_{$tab_section}_settings_group" );
                    do_action( "wcmp_{$tab}_{$tab_section}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-{$tab_section}-settings-admin" );
                    submit_button();
                } else {
                    settings_fields( "wcmp_{$tab}_settings_group" );
                    do_action( "wcmp_{$tab}_settings_before_submit" );
                    do_settings_sections( "wcmp-{$tab}-settings-admin" );
                    submit_button();
                }
                ?>
            </form>
            <?php echo $this->is_wcmp_tab_has_subtab( $tab ) ? '</div>' : ''; ?>
        </div>
        <?php
        do_action( 'dualcube_admin_footer' );
    }

    public function wcmp_extensions() {
        ?>  
        <div class="wrap">
            <h1><?php _e( 'WCMp Extensions', 'dc-woocommerce-multi-vendor' ) ?></h1>
            <?php do_action( "settings_page_wcmp-addons_tab_init", 'wcmp-addons' ); ?>
            <?php do_action( 'dualcube_admin_footer' ); ?>
        </div>
        <?php
    }

    public function wcmp_to_do() {
        ?>  
        <div class="wrap wcmp_vendors_settings">
            <h1><?php _e( 'To-do', 'dc-woocommerce-multi-vendor' ) ?></h1>
            <?php do_action( "settings_page_to_do_list_tab_init", 'to_do_list' ); ?>
            <?php do_action( 'dualcube_admin_footer' ); ?>
        </div>
        <?php
    }
    
    public function wcmp_vendors() {
        ?>  
        <div class="wrap">
        	<?php do_action( "settings_page_vendors_tab_init", 'vendors' ); ?>
            <?php do_action( 'dualcube_admin_footer' ); ?>
        </div>
        <?php
    }
    
    public function wcmp_report_bugs(){
        global $WCMp;
        
        if ( ! empty( $_POST ) && check_admin_referer( 'wcmp_split_report_bugs_nonce_action', 'wcmp_split_report_nonce' ) ) {
            if(empty($_POST['report_title'])) echo '<div id="message" class="error"><p>' . __( 'Please, add a report title.', 'dc-woocommerce-multi-vendor' ) . '</p></div>';
            $to = 'plugins@dualcube.com';
            $subject = __( 'WCMp Split (v3.4) report bug - ', 'dc-woocommerce-multi-vendor' ) . sanitize_text_field( $_POST['report_title'] );
            $message = get_option( 'blogname' ) . __( " has reported a bugs regarding WCMp (v3.4). Details are as follows -\n", 'dc-woocommerce-multi-vendor' );
            $message .= sanitize_textarea_field( $_POST['report_comment'] );
            $message .= __("\n\n From : ", 'dc-woocommerce-multi-vendor') . site_url();
            $attachments = array();
            $attachments[] = get_attached_file( absint($_POST['report_attach']) );
            
            $send = wp_mail($to, $subject, $message, $headers = '', $attachments);
            if( $send ) {
                echo '<div class="notice notice-success"><p>' . __( 'Thanks for reporting this bugs.', 'dc-woocommerce-multi-vendor' ) . '</p></div>';
            } else {
                echo '<div id="message" class="error"><p>' . __( 'Please try after sometime.', 'dc-woocommerce-multi-vendor' ) . '</p></div>';
            }
        }
        
        ?>  
        <div class="wrap">
            <h1><?php _e( 'Report a bugs', 'dc-woocommerce-multi-vendor' ) ?></h1>
            <form method="post">
                <table class="form-table wc-shipping-zone-settings" style="width: 70%;border: 1px solid #ddd;margin: 0 auto;margin-bottom: 40px;;">
                    <tbody>
                        <tr class="" valign="top">
                            <th scope="row" class="titledesc" style="padding-left:24px;">
                                <label for="report_title"><?php _e( 'Title', 'dc-woocommerce-multi-vendor' ) ?></label>
                            </th>
                            <td class="forminp">
                                <input type="text" name="report_title" id="report_title" value="" placeholder="Title" style="width:100%;">
                            </td>
			</tr>
                        <tr class="" valign="top">
                            <th scope="row" class="titledesc" style="padding-left:24px;">
                                <label for="report_comment"><?php _e( 'Comment', 'dc-woocommerce-multi-vendor' ) ?></label>
                            </th>
                            <td class="forminp">
                                <textarea name="report_comment" id="report_comment" style="width:100%;"></textarea>
                            </td>
			</tr>
                        <tr class="" valign="top">
                            <th scope="row" class="titledesc" style="padding-left:24px;">
                                <label for="report_attach"><?php _e( 'Attachments', 'dc-woocommerce-multi-vendor' ) ?></label>
                            </th>
                            <td class="forminp">
                                <?php
                                $reportoptions =  array(
                                    "report_attach" => array('label' => __('', 'dc-woocommerce-multi-vendor'), 'type' => 'upload', 'id' => 'report_attach', 'label_for' => 'report_attach', 'name' => 'report_attach', 'in_table' => 'true'),
                                );
                                $WCMp->wcmp_wp_fields->dc_generate_form_field($reportoptions);
                                ?>
                            </td>
			</tr>
                        <tr class="" valign="top">
                            <td colspan="2">
                                <?php wp_nonce_field( 'wcmp_split_report_bugs_nonce_action', 'wcmp_split_report_nonce' ); ?>
                                <input type="submit" class="button button-primary" name="report_bug_submit" value="<?php _e( 'Submit issue', 'dc-woocommerce-multi-vendor' ) ?>"/>
                            </td>
			</tr>
                    </tbody>
                </table>
                
            </form>
            <?php do_action( 'dualcube_admin_footer' ); ?>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
        do_action( 'befor_settings_page_init' );
        foreach ( $this->tabs as $tab_id => $tab ) {
            do_action( "settings_page_{$tab_id}_tab_init", $tab_id );
            $exclude_list = apply_filters( 'wcmp_subtab_init_exclude_list', array( 'tools', 'payment', 'registration' ), $tab_id );
            if ( $this->is_wcmp_tab_has_subtab( $tab_id ) ) {
                foreach ( $this->get_wcmp_subtabs( $tab_id ) as $subtab_id => $subtab ) {
                    if ( ! in_array( $subtab_id, $exclude_list ) ) {
                        do_action( "settings_page_{$tab_id}_{$subtab_id}_tab_init", $tab_id, $subtab_id );
                    }
                }
            }
        }
        do_action( 'after_settings_page_init' );
    }

    /**
     * Register and add settings fields
     */
    public function settings_field_init( $tab_options ) {
        if ( ! empty( $tab_options ) && isset( $tab_options['tab'] ) && isset( $tab_options['ref'] ) && isset( $tab_options['sections'] ) ) {
            // Register tab options
            register_setting(
                "wcmp_{$tab_options['tab']}_settings_group", // Option group
                "wcmp_{$tab_options['tab']}_settings_name", // Option name
                array( $tab_options['ref'], "wcmp_{$tab_options['tab']}_settings_sanitize" ) // Sanitize
            );

            foreach ( $tab_options['sections'] as $sectionID => $section ) {
                // Register section
                if ( method_exists( $tab_options['ref'], "{$sectionID}_info" ) ) {
                    add_settings_section(
                        $sectionID, // ID
                        $section['title'], // Title
                        array( $tab_options['ref'], "{$sectionID}_info" ), // Callback
                        "wcmp-{$tab_options['tab']}-settings-admin" // Page
                    );
                } else {
                    $callback = isset( $section['ref'] ) && method_exists( $section['ref'], "{$sectionID}_info" ) ? array( $section['ref'], "{$sectionID}_info" ) : __return_false();
                    add_settings_section(
                        $sectionID, // ID
                        $section['title'], // Title
                        $callback, // Callback
                        "wcmp-{$tab_options['tab']}-settings-admin" // Page
                    );
                }

                // Register fields
                if ( isset( $section['fields'] ) ) {
                    foreach ( $section['fields'] as $fieldID => $field ) {
                        if ( isset( $field['type'] ) ) {
                            $field['title'] = isset( $field['title'] ) ? $field['title'] : '';
                            $field['tab'] = $tab_options['tab'];
                            $callbak = $this->get_field_callback_type( $field['type'] );
                            if ( ! empty( $callbak ) ) {
                                add_settings_field(
                                    $fieldID, $field['title'], array( $this, $callbak ), "wcmp-{$tab_options['tab']}-settings-admin", $sectionID, $this->process_fields_args( $field, $fieldID )
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Register and add settings fields
     */
    public function settings_field_withsubtab_init( $tab_options ) {
        if ( ! empty( $tab_options ) && isset( $tab_options['tab'] ) && isset( $tab_options['ref'] ) && isset( $tab_options['sections'] ) && isset( $tab_options['subsection'] ) ) {
            // Register tab options
            register_setting(
                "wcmp_{$tab_options['tab']}_{$tab_options['subsection']}_settings_group", // Option group
                "wcmp_{$tab_options['tab']}_{$tab_options['subsection']}_settings_name", // Option name
                array( $tab_options['ref'], "wcmp_{$tab_options['tab']}_{$tab_options['subsection']}_settings_sanitize" ) // Sanitize
            );

            foreach ( $tab_options['sections'] as $sectionID => $section ) {
                // Register section
                if ( apply_filters( "{$tab_options['tab']}_{$sectionID}_info_display", method_exists( $tab_options['ref'], "{$sectionID}_info" ) ) ) {
                    add_settings_section(
                        $sectionID, // ID
                        $section['title'], // Title
                        array( $tab_options['ref'], "{$sectionID}_info" ), // Callback
                        "wcmp-{$tab_options['tab']}-{$tab_options['subsection']}-settings-admin" // Page
                    );
                } else {
                    $callback = isset( $section['ref'] ) && method_exists( $section['ref'], "{$sectionID}_info" ) ? array( $section['ref'], "{$sectionID}_info" ) : __return_false();
                    add_settings_section(
                        $sectionID, // ID
                        $section['title'], // Title
                        $callback, // Callback
                        "wcmp-{$tab_options['tab']}-{$tab_options['subsection']}-settings-admin" // Page
                    );
                }

                // Register fields
                if ( isset( $section['fields'] ) ) {
                    foreach ( $section['fields'] as $fieldID => $field ) {
                        if ( isset( $field['type'] ) ) {
                            $field['tab'] = $tab_options['tab'] . '_' . $tab_options['subsection'];
                            $callbak = $this->get_field_callback_type( $field['type'] );
                            if ( ! empty( $callbak ) ) {
                                add_settings_field(
                                    $fieldID, $field['title'], array( $this, $callbak ), "wcmp-{$tab_options['tab']}-{$tab_options['subsection']}-settings-admin", $sectionID, $this->process_fields_args( $field, $fieldID )
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * function process_fields_args
     * @param $fields
     * @param $fieldId
     * @return Array
     */
    public function process_fields_args( $field, $fieldID ) {
        if ( ! isset( $field['id'] ) ) {
            $field['id'] = $fieldID;
        }
        if ( ! isset( $field['label_for'] ) ) {
            $field['label_for'] = $fieldID;
        }
        if ( ! isset( $field['name'] ) ) {
            $field['name'] = $fieldID;
        }
        return $field;
    }

    public function general_tab_init( $tab ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_General( $tab );
    }

    public function general_policies_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_General_Policies( $tab, $subsection );
    }

    public function general_customer_support_details_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_General_Customer_support_Details( $tab, $subsection );
    }
    
    public function general_tools_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_General_Tools( $tab, $subsection );
    }

    public function capabilites_product_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Capabilities_Product( $tab, $subsection );
    }

    /************************ refund tab init ***************************************/
    public function RMA_refund_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        //print_r( $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token ) );die;
        new WCMp_Settings_RMA_refund( $tab, $subsection );
    }
    /************************ refund tab init  end ***************************************/


//    public function capabilites_order_tab_init($tab, $subsection) {
//        global $WCMp;
//        $WCMp->admin->load_class("settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token);
//        new WCMp_Settings_Capabilities_Order($tab, $subsection);
//    }
//
//    public function capabilites_miscellaneous_tab_init($tab, $subsection) {
//        global $WCMp;
//        $WCMp->admin->load_class("settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token);
//        new WCMp_Settings_Capabilities_Miscellaneous($tab, $subsection);
//    }

    public function notices_tab_init( $tab ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Notices( $tab );
    }

    public function payment_tab_init( $tab ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Payment( $tab );
    }

    public function payment_paypal_masspay_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Payment_Paypal_Masspay( $tab, $subsection );
    }

    public function payment_paypal_payout_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Payment_Paypal_Payout( $tab, $subsection );
    }
    
    public function payment_stripe_gateway_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Payment_Stripe_Connect( $tab, $subsection );
    }

//    public function frontend_tab_init($tab) {
//        global $WCMp;
//        $WCMp->admin->load_class("settings-{$tab}", $WCMp->plugin_path, $WCMp->token);
//        new WCMp_Settings_Frontend($tab);
//    }

    public function to_do_list_tab_init( $tab ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_To_Do_List( $tab );
    }

    public function vendor_registration_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Vendor_Registration( $tab, $subsection );
    }

    public function vendor_dashboard_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Vendor_Dashboard( $tab, $subsection );
    }

    public function vendor_general_tab_init( $tab, $subsection ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}-{$subsection}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_Vendor_General( $tab, $subsection );
    }

    public function wcmp_addons_tab_init( $tab ) {
        global $WCMp;
        $WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
        new WCMp_Settings_WCMp_Addons( $tab );
    }
    
    public function vendors_tab_init( $tab ) {
        //global $WCMp;
        //$WCMp->admin->load_class( "settings-{$tab}", $WCMp->plugin_path, $WCMp->token );
        //new WCMp_Settings_WCMp_Vendors( $tab );
        $this->vendor_class_obj->settings_page_init();
    }


    public function is_wcmp_tab_has_subtab( $tab = 'general' ) {
        return in_array( $tab, apply_filters( 'is_wcmp_tab_has_subtab', array( 'general', 'payment', 'vendor', 'capabilities','RMA' ), $tab ) );
    }

    public function get_wcmp_subtabs( $tab = 'general' ) {
        $subtabs = array();
        switch ( $tab ) {
            case 'payment':
                $subtabs = $this->tabsection_payment;
                break;
            case 'vendor':
                $subtabs = $this->tabsection_vendor;
                break;
            case 'capabilities':
                $subtabs = $this->tabsection_capabilities;
                break;
            case 'RMA':
                $subtabs = $this->tabsection_refund;
                break;
            default :
                $subtabs = $this->tabsection_general;
                break;
        }
        return apply_filters( 'wcmp_get_subtabs', $subtabs, $tab );
    }

    public function get_field_callback_type( $fieldType ) {
        $callBack = '';
        switch ( $fieldType ) {
            case 'input':
            case 'number':
            case 'text':
            case 'email':
            case 'password':
            case 'url':
                $callBack = 'text_field_callback';
                break;

            case 'hidden':
                $callBack = 'hidden_field_callback';
                break;

            case 'textarea':
                $callBack = 'textarea_field_callback';
                break;

            case 'wpeditor':
                $callBack = 'wpeditor_field_callback';
                break;

            case 'checkbox':
                $callBack = 'checkbox_field_callback';
                break;

            case 'radio':
                $callBack = 'radio_field_callback';
                break;
            case 'radio_select':
                $callBack = 'radio_select_field_callback';
                break;
            case 'color_scheme_picker':
                $callBack = 'color_scheme_picker_callback';
                break;

            case 'select':
                $callBack = 'select_field_callback';
                break;

            case 'upload':
                $callBack = 'upload_field_callback';
                break;

            case 'colorpicker':
                $callBack = 'colorpicker_field_callback';
                break;

            case 'datepicker':
                $callBack = 'datepicker_field_callback';
                break;

            case 'multiinput':
                $callBack = 'multiinput_callback';
                break;

            case 'label':
                $callBack = 'label_callback';
                break;

            default:
                $callBack = '';
                break;
        }

        return $callBack;
    }

    /**
     * Get the hidden field display
     */
    public function hidden_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->hidden_input( $field );
    }

    /**
     * Get the text field display
     */
    public function text_field_callback( $field ) {
        global $WCMp;
        //print_r($this->options);die;
        $field['dfvalue'] = isset( $field['dfvalue'] ) ? esc_attr( $field['dfvalue'] ) : '';
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : $field['dfvalue'];
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->text_input( $field );
    }

    /**
     * Get the label field display
     */
    public function label_callback( $field ) {
        global $WCMp;
        $field['dfvalue'] = isset( $field['dfvalue'] ) ? esc_attr( $field['dfvalue'] ) : '';
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : $field['dfvalue'];
        $WCMp->wcmp_wp_fields->label_input( $field );
    }

    /**
     * Get the text area display
     */
    public function textarea_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_textarea( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_textarea( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->textarea_input( $field );
    }

    /**
     * Get the wpeditor display
     */
    public function wpeditor_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? ( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? ( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->wpeditor_input( $field );
    }

    /**
     * Get the checkbox field display
     */
    public function checkbox_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['dfvalue'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : '';
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->checkbox_input( $field );
    }

    /**
     * Get the checkbox field display
     */
    public function radio_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->radio_input( $field );
    }

    /**
     * Get the checkbox field display
     */
    public function radio_select_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->radio_select_input( $field );
    }

    public function color_scheme_picker_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->color_scheme_picker_input( $field );
    }

    /**
     * Get the select field display
     */
    public function select_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_textarea( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_textarea( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->select_input( $field );
    }

    /**
     * Get the upload field display
     */
    public function upload_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->upload_input( $field );
    }

    /**
     * Get the multiinput field display
     */
    public function multiinput_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? $field['value'] : array();
        $field['value'] = isset( $this->options[$field['name']] ) ? $this->options[$field['name']] : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->multi_input( $field );
    }

    /**
     * Get the colorpicker field display
     */
    public function colorpicker_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->colorpicker_input( $field );
    }

    /**
     * Get the datepicker field display
     */
    public function datepicker_field_callback( $field ) {
        global $WCMp;
        $field['value'] = isset( $field['value'] ) ? esc_attr( $field['value'] ) : '';
        $field['value'] = isset( $this->options[$field['name']] ) ? esc_attr( $this->options[$field['name']] ) : $field['value'];
        $field['name'] = "wcmp_{$field['tab']}_settings_name[{$field['name']}]";
        $WCMp->wcmp_wp_fields->datepicker_input( $field );
    }

}
