<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WCMp Admin Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Admin {

    public $settings;

    public function __construct() {
        // Admin script and style
        add_action('admin_enqueue_scripts', array(&$this, 'enqueue_admin_script'), 30);
        add_action('dualcube_admin_footer', array(&$this, 'dualcube_admin_footer_for_wcmp'));
        add_action('admin_bar_menu', array(&$this, 'add_toolbar_items'), 100);
        add_action('admin_head', array(&$this, 'admin_header'));
        add_action('current_screen', array($this, 'conditonal_includes'));
        if (get_wcmp_vendor_settings('is_singleproductmultiseller', 'general') == 'Enable') {
            add_action('admin_enqueue_scripts', array($this, 'wcmp_kill_auto_save'));
        }
        $this->load_class('settings');
        $this->settings = new WCMp_Settings();
        add_filter('woocommerce_hidden_order_itemmeta', array(&$this, 'add_hidden_order_items'));

        add_action('admin_menu', array(&$this, 'wcmp_admin_menu'));
        add_action('admin_head', array($this, 'menu_commission_count'));
        add_action('wp_dashboard_setup', array(&$this, 'wcmp_remove_wp_dashboard_widget'));
        add_filter('woocommerce_order_actions', array(&$this, 'woocommerce_order_actions'));
        add_action('woocommerce_order_action_regenerate_order_commissions', array(&$this, 'regenerate_order_commissions'));
        add_filter('woocommerce_screen_ids', array(&$this, 'add_wcmp_screen_ids'));
        // Admin notice for advance frontend modules (Temp)
        add_action('admin_notices', array(&$this, 'advance_frontend_manager_notice'));
        $this->actions_handler();
    }
    
    public function actions_handler(){
        // Export pending bank transfers request in admin end
        if ( ! empty( $_POST ) && isset( $_REQUEST[ 'wcmp_admin_bank_transfer_export_nonce' ] ) && wp_verify_nonce( $_REQUEST[ 'wcmp_admin_bank_transfer_export_nonce' ], 'wcmp_todo_pending_bank_transfer_export' ) ) {
            $transactions_ids = isset( $_POST['transactions_ids'] ) ? json_decode( $_POST['transactions_ids'] ) : array();
            if( !$transactions_ids ) return false;
            // Set filename
            $date = date('Y-m-d H:i:s');
            $filename = apply_filters( 'wcmp_admin_export_pending_bank_transfer_file_name', 'Admin-Pending-Bank-Transfer-' . $date, $_POST );
            $filename = $filename.'.csv';
            // Set page headers to force download of CSV
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-type: text/x-csv");
            header("Content-Disposition: File Transfar");
            //header("Content-Type: application/octet-stream");
            //header("Content-Type: application/download");
            header("Content-Disposition: attachment;filename={$filename}");
            header("Content-Transfer-Encoding: binary");
            // Set CSV headers
            $headers = apply_filters( 'wcmp_admin_export_pending_bank_transfer_csv_headers',array(
                'dor'               => __( 'Date of request', 'dc-woocommerce-multi-vendor' ),
                'trans_id'          => __( 'Transaction ID', 'dc-woocommerce-multi-vendor' ),
                'commission_ids'    => __( 'Commission IDs', 'dc-woocommerce-multi-vendor' ),
                'vendor'            => __( 'Vendor', 'dc-woocommerce-multi-vendor' ),
                'amount'            => __( 'Amount', 'dc-woocommerce-multi-vendor' ),
                'bank_details'      => __( 'Bank Details', 'dc-woocommerce-multi-vendor' ),
            ), $transactions_ids, $_POST );
            $exporter_data = array();
            foreach ( $transactions_ids as $trans_id ) {
                $commission_ids = (array)get_post_meta( $trans_id, 'commission_detail', true );
                $vendor = get_wcmp_vendor_by_term( get_post_field( 'post_author', $trans_id ) );
                $account_details = array();
                if ( $vendor ) :
                    $account_details = apply_filters( 'wcmp_admin_export_pending_bank_transfer_vendor_account_details', array(
                        'account_name'   => get_user_meta( $vendor->id, '_vendor_account_holder_name', true ),
                        'account_number' => get_user_meta( $vendor->id, '_vendor_bank_account_number', true ),
                        'account_type'   => get_user_meta( $vendor->id, '_vendor_bank_account_type', true ),
                        'bank_name'      => get_user_meta( $vendor->id, '_vendor_bank_name', true ),
                        'iban'           => get_user_meta( $vendor->id, '_vendor_iban', true ),
                        'routing_number' => get_user_meta( $vendor->id, '_vendor_aba_routing_number', true ),
                    ), $transactions_ids, $_POST, $vendor );
                endif;
                $bank_details = '';
                if( $account_details ) {
                    foreach ( $account_details as $key => $value ) {
                        if( $key == 'account_name' ) {
                            $bank_details .= __( 'Account Holder Name', 'dc-woocommerce-multi-vendor' ) . ': ' . $value . ' | ';
                        }elseif( $key == 'account_number' ){
                            $bank_details .= __( 'Account Number', 'dc-woocommerce-multi-vendor' ) . ': ' . $value . ' | ';;
                        }elseif( $key == 'account_type' ){
                            $bank_details .= __( 'Account Type', 'dc-woocommerce-multi-vendor' ) . ': ' . $value . ' | ';
                        }elseif( $key == 'bank_name' ){
                            $bank_details .= __( 'Bank Name', 'dc-woocommerce-multi-vendor' ) . ': ' . $value . ' | ';
                        }elseif( $key == 'iban' ){
                            $bank_details .= __( 'IBAN', 'dc-woocommerce-multi-vendor' ) . ': ' . $value . ' | ';
                        }elseif( $key == 'routing_number' ){
                            $bank_details .= __( 'Routing Number', 'dc-woocommerce-multi-vendor' ) . ': ' . $value;
                        }else{
                            $bank_details .= apply_filters( 'wcmp_admin_export_pending_bank_transfer_vendor_bank_details', $bank_details, $account_details, $_POST );
                        }
                    }
                }
                $amount = get_post_meta( $trans_id, 'amount', true );
                $transfer_charge = get_post_meta( $trans_id, 'transfer_charge', true );
                $gateway_charge = get_post_meta( $trans_id, 'gateway_charge', true );
                $transaction_amt = $amount - $transfer_charge - $gateway_charge;
                $exporter_data[] = apply_filters( 'wcmp_admin_export_pending_bank_transfer_csv_row_data', array(
                    'date'              => get_the_date( 'Y-m-d', $trans_id ),
                    'trans_id'          => '#' . $trans_id,
                    'commission_ids'    => '#' . implode(', #', $commission_ids),
                    'vendor'            => get_user_meta( $vendor->id, '_vendor_page_title', true ),
                    'amount'            => $transaction_amt,
                    'bank_details'      => $bank_details,
                ), $transactions_ids, $_POST, $vendor );
            }
            
            // Initiate output buffer and open file
            ob_start();
            $file = fopen("php://output", 'w');

            // Add headers to file
            fputcsv( $file, $headers );
            // Add data to file
            if ( $exporter_data ) {
                foreach ( $exporter_data as $edata ) {
                    fputcsv( $file, $edata );
                }
            } else {
                fputcsv( $file, array( __('Sorry, no pending bank transaction data is available.', 'dc-woocommerce-multi-vendor') ) );
            }

            // Close file and get data from output buffer
            fclose($file);
            $csv = ob_get_clean();
            // Send CSV to browser for download
            echo $csv;
            die();
        }
    }
    
    function add_hidden_order_items($order_items) {
        $order_items[] = '_give_tax_to_vendor';
        $order_items[] = '_give_shipping_to_vendor';
        // and so on...
        return $order_items;
    }

    function conditonal_includes() {
        $screen = get_current_screen();

        if (in_array($screen->id, array('options-permalink'))) {
            $this->permalink_settings_init();
            $this->permalink_settings_save();
        }
    }

    function permalink_settings_init() {
        // Add our settings
        add_settings_field(
                'dc_product_vendor_taxonomy_slug', // id
                __('Vendor Shop Base', 'dc-woocommerce-multi-vendor'), // setting title
                array(&$this, 'wcmp_taxonomy_slug_input'), // display callback
                'permalink', // settings page
                'optional'                                      // settings section
        );
    }

    function wcmp_taxonomy_slug_input() {
        $permalinks = get_option('dc_vendors_permalinks');
        ?>
        <input name="dc_product_vendor_taxonomy_slug" type="text" class="regular-text code" value="<?php if (isset($permalinks['vendor_shop_base'])) echo esc_attr($permalinks['vendor_shop_base']); ?>" placeholder="<?php echo _x('vendor', 'slug', 'dc-woocommerce-multi-vendor') ?>" />
        <?php
    }

    function permalink_settings_save() {
        if (!is_admin()) {
            return;
        }
        // We need to save the options ourselves; settings api does not trigger save for the permalinks page
        if (isset($_POST['permalink_structure']) || isset($_POST['dc_product_vendor_taxonomy_slug'])) {

            // Cat and tag bases
            $dc_product_vendor_taxonomy_slug = wc_clean($_POST['dc_product_vendor_taxonomy_slug']);
            $permalinks = get_option('dc_vendors_permalinks');

            if (!$permalinks) {
                $permalinks = array();
            }

            $permalinks['vendor_shop_base'] = untrailingslashit($dc_product_vendor_taxonomy_slug);
            update_option('dc_vendors_permalinks', $permalinks);
        }
    }

    /**
     * Add Toolbar for vendor user 
     *
     * @access public
     * @param admin bar
     * @return void
     */
    function add_toolbar_items($admin_bar) {
        $user = wp_get_current_user();
        if (is_user_wcmp_vendor($user)) {
            $admin_bar->add_menu(
                    array(
                        'id' => 'vendor_dashboard',
                        'title' => __('Frontend  Dashboard', 'dc-woocommerce-multi-vendor'),
                        'href' => get_permalink(wcmp_vendor_dashboard_page_id()),
                        'meta' => array(
                            'title' => __('Frontend Dashboard', 'dc-woocommerce-multi-vendor'),
                            'target' => '_blank',
                            'class' => 'shop-settings'
                        ),
                    )
            );
            $admin_bar->add_menu(
                    array(
                        'id' => 'shop_settings',
                        'title' => __('Storefront', 'dc-woocommerce-multi-vendor'),
                        'href' => wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_store_settings_endpoint', 'vendor', 'general', 'storefront')),
                        'meta' => array(
                            'title' => __('Storefront', 'dc-woocommerce-multi-vendor'),
                            'target' => '_blank',
                            'class' => 'shop-settings'
                        ),
                    )
            );
        }
    }

    function load_class($class_name = '') {
        global $WCMp;
        if ('' != $class_name) {
            require_once ($WCMp->plugin_path . 'admin/class-' . esc_attr($WCMp->token) . '-' . esc_attr($class_name) . '.php');
        } // End If Statement
    }

// End load_class()

    /**
     * Add dualcube footer text on plugin settings page
     *
     * @access public
     * @param admin bar
     * @return void
     */
    function dualcube_admin_footer_for_wcmp() {
        global $WCMp;
        ?>
        <div style="clear: both"></div>
        <div id="dc_admin_footer">
            <?php _e('Powered by', 'dc-woocommerce-multi-vendor'); ?> <a href="https://wc-marketplace.com/" target="_blank"><img src="<?php echo $WCMp->plugin_url . 'assets/images/dualcube.png'; ?>"></a><?php _e('WC Marketplace', 'dc-woocommerce-multi-vendor'); ?> &copy; <?php echo date('Y'); ?>
        </div>
        <?php
    }

    /**
     * Add css on admin header
     *
     * @access public
     * @return void
     */
    function admin_header() {
        $screen = get_current_screen();
        if (is_user_logged_in()) {
            if (isset($screen->id) && in_array($screen->id, array('edit-dc_commission', 'edit-wcmp_university', 'edit-wcmp_vendor_notice'))) {
                ?>
                <script>
                    jQuery(document).ready(function ($) {
                        var target_ele = $(".wrap .wp-header-end");
                        var targethtml = target_ele.html();
                        //targethtml = targethtml + '<a href="<?php echo trailingslashit(get_admin_url()) . 'admin.php?page=wcmp-setting-admin'; ?>" class="page-title-action">Back To WCMp Settings</a>';
                        //target_ele.html(targethtml);
                <?php if (in_array($screen->id, array('edit-wcmp_university'))) { ?>
                            target_ele.before('<p><b><?php echo __('"Knowledgebase" section is visible only to vendors through the vendor dashboard. You may use this section to onboard your vendors. Share tutorials, best practices, "how to" guides or whatever you feel is appropriate with your vendors.', 'dc-woocommerce-multi-vendor'); ?></b></p>');
                <?php } ?>
                <?php if (in_array($screen->id, array('edit-wcmp_vendor_notice'))) { ?>
                            target_ele.before('<p><b><?php echo __('Announcements are visible only to vendors through the vendor dashboard(message section). You may use this section to broadcast your announcements.', 'dc-woocommerce-multi-vendor'); ?></b></p>');
                <?php } ?>
                    });

                </script>
                <?php
            }
        }
    }

    public function wcmp_admin_menu() {
        if (is_user_wcmp_vendor(get_current_vendor_id())) {
            remove_menu_page('edit.php');
            remove_menu_page('edit-comments.php');
            remove_menu_page('tools.php');
        }
    }

    public function menu_commission_count() {
        global $submenu;
        if (isset($submenu['wcmp'])) {
            if (apply_filters('wcmp_include_unpaid_commission_count_in_menu', true) && current_user_can('manage_woocommerce') && ( $order_count = wcmp_count_commission()->unpaid )) {
                foreach ($submenu['wcmp'] as $key => $menu_item) {
                    if (0 === strpos($menu_item[0], _x('Commissions', 'Admin menu name', 'wcmp'))) {
                        $submenu['wcmp'][$key][0] .= ' <span class="awaiting-mod update-plugins count-' . $order_count . '"><span class="processing-count">' . number_format_i18n($order_count) . '</span></span>';
                        break;
                    }
                }
            }
        }
    }

    /**
     * Admin Scripts
     */
    public function enqueue_admin_script() {
        global $WCMp;
        $screen = get_current_screen();
        $suffix = defined('WCMP_SCRIPT_DEBUG') && WCMP_SCRIPT_DEBUG ? '' : '.min';
        
        $wcmp_admin_screens = apply_filters('wcmp_enable_admin_script_screen_ids', array(
            'wcmp_page_wcmp-setting-admin',
            'wcmp_page_wcmp-to-do',
            'edit-wcmp_vendorrequest',
            'dc_commission',
            'woocommerce_page_wc-reports',
            'toplevel_page_wc-reports',
            'product',
            'edit-product',
            'edit-shop_order',
            'user-edit',
            'profile',
            'users',
            'wcmp_page_wcmp-extensions',
            'wcmp_page_vendors',
            'toplevel_page_dc-vendor-shipping',
	));
        
        // Register scripts.
        wp_register_style('wcmp_admin_css', $WCMp->plugin_url . 'assets/admin/css/admin' . $suffix . '.css', array(), $WCMp->version);
        wp_register_script('wcmp_admin_js', $WCMp->plugin_url . 'assets/admin/js/admin' . $suffix . '.js', apply_filters('wcmp_admin_script_add_dependencies', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'wc-backbone-modal')), $WCMp->version, true);
        wp_register_script('dc_to_do_list_js', $WCMp->plugin_url . 'assets/admin/js/to_do_list' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('WCMp_chosen', $WCMp->plugin_url . 'assets/admin/js/chosen.jquery' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('WCMp_ajax-chosen', $WCMp->plugin_url . 'assets/admin/js/ajax-chosen.jquery' . $suffix . '.js', array('jquery', 'WCMp_chosen'), $WCMp->version, true);
        wp_register_script('wcmp-admin-commission-js', $WCMp->plugin_url . 'assets/admin/js/commission' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp-admin-product-js', $WCMp->plugin_url . 'assets/admin/js/product' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('edit_user_js', $WCMp->plugin_url . 'assets/admin/js/edit_user' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('dc_users_js', $WCMp->plugin_url . 'assets/admin/js/to_do_list' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_admin_product_auto_search_js', $WCMp->plugin_url . 'assets/admin/js/admin-product-auto-search' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_report_js', $WCMp->plugin_url . 'assets/admin/js/report' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        wp_register_script('wcmp_vendor_js', $WCMp->plugin_url . 'assets/admin/js/vendor' . $suffix . '.js', array('jquery', 'woocommerce_admin'), $WCMp->version, true);
        wp_register_script('wcmp_vendor_shipping',$WCMp->plugin_url . 'assets/admin/js/vendor-shipping' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone', 'jquery-ui-sortable', 'wc-backbone-modal' ), $WCMp->version );

        $WCMp->localize_script('wcmp_admin_js', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'vendors_nonce' => wp_create_nonce('wcmp-vendors'),
            'lang'  => array(
                'in_percentage' => __('In Percentage', 'dc-woocommerce-multi-vendor'),
                'in_fixed' => __('In Fixed', 'dc-woocommerce-multi-vendor'),
            )
        ));
        if (in_array($screen->id, $wcmp_admin_screens)) :
            wp_enqueue_style( 'wcmp_admin_css' );
            wp_enqueue_script( 'wcmp_admin_js' );
        endif;
        // hide media list view access for vendor
        $user = wp_get_current_user();
        if(in_array('dc_vendor', $user->roles)){
            $custom_css = "
            .view-switch .view-list{
                    display: none;
            }";
            wp_add_inline_style( 'media-views', $custom_css );
        }
        // WCMp library
        if (in_array($screen->id, array('wcmp_page_wcmp-setting-admin', 'wcmp_page_wcmp-to-do'))) :
            $WCMp->library->load_qtip_lib();
            $WCMp->library->load_upload_lib();
            $WCMp->library->load_colorpicker_lib();
            $WCMp->library->load_datepicker_lib();
            wp_enqueue_script('wcmp_admin_js', $WCMp->plugin_url . 'assets/admin/js/admin' . $suffix . '.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'), $WCMp->version, true);
            wp_enqueue_style('wcmp_admin_css', $WCMp->plugin_url . 'assets/admin/css/admin' . $suffix . '.css', array(), $WCMp->version);
        endif;
        if (in_array($screen->id, array('wcmp_page_wcmp-to-do', 'edit-wcmp_vendorrequest'))) {
            wp_enqueue_script( 'dc_to_do_list_js' );
        }
        if (in_array($screen->id, array('wcmp_page_vendors'))) :
        	$WCMp->library->load_upload_lib();
	        wp_enqueue_script('wcmp_admin_js');
                wp_register_script('wc-country-select', WC()->plugin_url() . '/assets/js/frontend/country-select' . $suffix . '.js', array('jquery'), WC_VERSION);
                $params = array(
                        'countries'                 => wp_json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
                        'i18n_select_state_text'    => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
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
                );
                wp_localize_script( 'wc-country-select', 'wc_country_select_params', $params );
                wp_enqueue_script( 'wc-country-select' );
                wp_register_script('wcmp_country_state_js', $WCMp->plugin_url . 'assets/frontend/js/wcmp-country-state.js', array('jquery', 'wc-country-select'), $WCMp->version, true);
                wp_enqueue_script( 'wcmp_country_state_js' );
            
        endif;

        if (in_array($screen->id, array('dc_commission', 'woocommerce_page_wc-reports', 'toplevel_page_wc-reports', 'product', 'edit-product'))) :
            $WCMp->library->load_qtip_lib();
            if (!wp_style_is('woocommerce_chosen_styles', 'queue')) {
                wp_enqueue_style('woocommerce_chosen_styles', $WCMp->plugin_url . '/assets/admin/css/chosen' . $suffix . '.css');
            }
            wp_enqueue_style( 'woocommerce_admin_styles' );
            wp_enqueue_script('WCMp_chosen');
            wp_enqueue_script('WCMp_ajax-chosen');
            wp_enqueue_script('wcmp-admin-commission-js');
            wp_localize_script('wcmp-admin-commission-js', 'dc_vendor_object', array('security' => wp_create_nonce("search-products")));
            wp_enqueue_script('wcmp-admin-product-js');
            wp_localize_script('wcmp-admin-product-js', 'dc_vendor_object', array('security' => wp_create_nonce("search-products")));
            if (get_wcmp_vendor_settings('is_singleproductmultiseller', 'general') == 'Enable' && in_array($screen->id, array('product'))) {
                wp_enqueue_script('wcmp_admin_product_auto_search_js');
                wp_localize_script('wcmp_admin_product_auto_search_js', 'wcmp_admin_product_auto_search_js_params', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'search_products_nonce' => wp_create_nonce('search-products'),
                ));
            }
        endif;

        if (in_array($screen->id, array('user-edit', 'profile'))) :
            $WCMp->library->load_qtip_lib();
            $WCMp->library->load_upload_lib();
            wp_enqueue_script('edit_user_js');
        endif;

        if (in_array($screen->id, array('users'))) :
            wp_enqueue_script('dc_users_js');
        endif;

        if (in_array($screen->id, array('woocommerce_page_wc-reports', 'toplevel_page_wc-reports'))) :
            wp_enqueue_script('WCMp_chosen');
            wp_enqueue_script('WCMp_ajax-chosen');
            wp_enqueue_script('wcmp-admin-product-js');
            wp_localize_script('wcmp-admin-product-js', 'dc_vendor_object', array('security' => wp_create_nonce("search-products")));
        endif;

        if (in_array($screen->id, array('woocommerce_page_wc-reports', 'toplevel_page_wc-reports'))) :
            wp_enqueue_script('wcmp_report_js');
        endif;

        if (is_user_wcmp_vendor(get_current_vendor_id())) {
            wp_enqueue_script('wcmp_vendor_js');
        }
        
        // hide coupon allow free shipping option for vendor
        if (is_user_wcmp_vendor(get_current_vendor_id())) {
            $custom_css = "
            #general_coupon_data .free_shipping_field{
                    display: none;
            }";
            wp_add_inline_style( 'woocommerce_admin_styles', $custom_css );
            wp_enqueue_script('wcmp_vendor_js');
        }
        
        // hide product cat from quick & bulk edit
        if(is_user_wcmp_vendor(get_current_vendor_id()) && in_array($screen->id, array('edit-product'))){
            $custom_css = "
            .inline-edit-product .inline-edit-categories, .bulk-edit-product .inline-edit-categories{
                display: none;
            }";
            wp_add_inline_style( 'woocommerce_admin_styles', $custom_css );
        }
        
        // report a bugs settings
        if($screen->id == 'wcmp_page_wcmp-report-bugs'){
            $WCMp->library->load_upload_lib();
            wp_enqueue_style('woocommerce_admin_styles');
        }
        
    }

    function wcmp_kill_auto_save() {
        if ('product' == get_post_type()) {
            wp_dequeue_script('autosave');
        }
    }

    /**
     * Remove wp dashboard widget for vendor
     * @global array $wp_meta_boxes
     */
    public function wcmp_remove_wp_dashboard_widget() {
        global $wp_meta_boxes;
        if (is_user_wcmp_vendor(get_current_vendor_id())) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
        }
    }

    public function woocommerce_order_actions($actions) {
        global $post;
        if( $post && wp_get_post_parent_id( $post->ID ) )
            $actions['regenerate_order_commissions'] = __('Regenerate order commissions', 'dc-woocommerce-multi-vendor');
        if(is_user_wcmp_vendor(get_current_user_id())){
            if(isset($actions['regenerate_order_commissions'])) unset($actions['regenerate_order_commissions']);
            if(isset($actions['send_order_details'])) unset( $actions['send_order_details'] );
            if(isset($actions['send_order_details_admin'])) unset( $actions['send_order_details_admin'] );
        }
        return $actions;
    }

    /**
     * Regenerate order commissions
     * @param Object $order
     * @since 3.0.2
     */
    public function regenerate_order_commissions($order) {
        global $wpdb, $WCMp;
        if ( !wp_get_post_parent_id( $order->get_id() ) ) {
            return;
        }
        if (!in_array($order->get_status(), apply_filters( 'wcmp_regenerate_order_commissions_statuses', array( 'on-hold', 'processing', 'completed' ), $order ))) {
            return;
        }
        
        delete_post_meta($order->get_id(), '_commissions_processed');
        $commission_id = get_post_meta($order->get_id(), '_commission_id', true) ? get_post_meta($order->get_id(), '_commission_id', true) : '';
        if ($commission_id) {
            wp_delete_post($commission_id, true);
        }
        delete_post_meta($order->get_id(), '_commission_id');
        // create vendor commission
        $commission_id = WCMp_Commission::create_commission($order->get_id());
        if ($commission_id) {
            // Add order note
            $order->add_order_note( __( 'Regenerated order commission.', 'dc-woocommerce-multi-vendor') );
            /**
             * Action filter to recalculate commission with modified settings.
             *
             * @since 3.5.0
             */
            $recalculate = apply_filters( 'wcmp_regenerate_order_commissions_by_new_commission_rate', false, $order );
            // Calculate commission
            WCMp_Commission::calculate_commission($commission_id, $order, $recalculate);
            update_post_meta($commission_id, '_paid_status', 'unpaid');

            // add commission id with associated vendor order
            update_post_meta($order->get_id(), '_commission_id', $commission_id);
            // Mark commissions as processed
            update_post_meta($order->get_id(), '_commissions_processed', 'yes');
        }
    }
    
    public function add_wcmp_screen_ids($screen_ids){
        $screen_ids[] = 'toplevel_page_dc-vendor-shipping';
        return $screen_ids;
    }
    
    public function advance_frontend_manager_notice(){
        if(!class_exists('WCMp_AFM') && WC_Dependencies_Product_Vendor::is_advance_frontend_manager_active()) :
        ?>
        <div id="message" class="error settings-error notice is-dismissible">
            <p><?php printf(__('%sAdvance Frontend Manager%s will not work with latest WCMp (v%s), so please update Advance Frontend Manager with latest one (v3.0.0).', 'dc-woocommerce-multi-vendor'
), '<strong>', '</strong>', WCMp_PLUGIN_VERSION); ?></p>
        </div>
        <?php 
        endif;
    }

}
