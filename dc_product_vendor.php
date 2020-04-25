<?php
/**
 * Plugin Name: WC Marketplace
 * Plugin URI: https://wc-marketplace.com/
 * Description: A Free Extension That Transforms Your WooCommerce Site into a Marketplace.
 * Author: WC Marketplace, The Grey Parrots
 * Version: 3.4.11
 * Author URI: https://wc-marketplace.com/
 * Requires at least: 4.4
 * Tested up to: 5.4
 * WC requires at least: 3.0
 * WC tested up to: 4.0.1
 *
 * Text Domain: dc-woocommerce-multi-vendor
 * Domain Path: /languages/
 */
if (!class_exists('WC_Dependencies_Product_Vendor')) {
    require_once 'includes/class-wcmp-dependencies.php';
}
require_once 'includes/wcmp-core-functions.php';
require_once 'wcmp_config.php';
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('WCMp_PLUGIN_TOKEN')) {
    exit;
}
if (!defined('WCMp_TEXT_DOMAIN')) {
    exit;
}

/* Check whether another multi vendor plugin exist */
register_activation_hook(__FILE__, 'wcmp_check_if_another_vendor_plugin_exits');
/* Plugin activation hook */
register_activation_hook(__FILE__, 'activate_wcmp_plugin');
/* Plugin deactivation hook */
register_deactivation_hook(__FILE__, 'deactivate_wcmp_plugin');
/* Remove rewrite rules and then recreate rewrite rules. */
register_activation_hook(__FILE__, 'flush_rewrite_rules');

add_action('init', 'wcmp_plugin_init');
add_action('admin_init', 'wcmp_delete_woocomerce_transient_redirect_to_wcmp_setup', 5);
/**
 * Load setup class 
 */
function wcmp_plugin_init() {
    $current_page = filter_input(INPUT_GET, 'page');
    if ($current_page && $current_page == 'wcmp-setup') {
        include_once(dirname( __FILE__ ) . '/admin/class-wcmp-admin-setup-wizard.php');
    }
}
/**
 * Delete WooCommerce activation redirect transient
 */
function wcmp_delete_woocomerce_transient_redirect_to_wcmp_setup(){
    if ( get_transient( '_wc_activation_redirect' ) ) {
        delete_transient( '_wc_activation_redirect' );
        return;
    }
    if ( get_transient( '_wcmp_activation_redirect' ) ) {
        delete_transient( '_wcmp_activation_redirect' );
        if ( ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'wcmp-setup' ) ) ) || is_network_admin() || isset( $_GET['activate-multi'] ) || apply_filters( 'wcmp_prevent_automatic_wizard_redirect', false ) ) {
                return;
        }
        wp_safe_redirect( admin_url( 'index.php?page=wcmp-setup' ) );
	exit;
    }
}

if (!class_exists('WCMp') && WC_Dependencies_Product_Vendor::is_woocommerce_active()) {
    global $WCMp;
    require_once( 'classes/class-wcmp.php' );
    /* recheck plugin install */
    add_action('plugins_loaded', 'activate_wcmp_plugin');
    /* Initiate plugin main class */
    $WCMp = new WCMp(__FILE__);
    $GLOBALS['WCMp'] = $WCMp;
    if (is_admin() && !defined('DOING_AJAX')) {
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WCMp_action_links');
    }
} else {
    add_action('admin_notices', 'wcmp_admin_notice');

    function wcmp_admin_notice() {
        ?>
        <div class="error">
            <p><?php _e('WCMp plugin requires <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> plugins to be active!', 'dc-woocommerce-multi-vendor'); ?></p>
        </div>
        <?php
    }

}

function wcmp_namespace_approve( $value ) {
	
	$rest_prefix = trailingslashit( rest_get_url_prefix() );
	
	// Allow third party plugins use our authentication methods.
	$wcmp_support = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wcmp' ) );
	
	if($value || $wcmp_support) $return = true;
	else $return = false;
	
	return $return;
}
