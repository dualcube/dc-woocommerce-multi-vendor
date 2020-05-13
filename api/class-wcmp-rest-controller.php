<?php
/**
 * WC Marketplace API
 *
 * Handles WCMp-API endpoint requests.
 *
 * @author   WC Marketplace
 * @category API
 * @package  WCMp/API
 * @since    3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class.
 */
class WCMp_REST_API {
	/**
	 * Setup class.
	 *
	 * @since 3.1
	 */
	public function __construct() {

		// Add query vars.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Register API endpoints.
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		// Handle wc-api endpoint requests.
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );
		
		// WP REST API.
		$this->rest_api_init();
	}
	

	/**
	 * Add new query vars.
	 *
	 * @since 3.1
	 * @param array $vars Query vars.
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wcmp-api';
		return $vars;
	}

	/**
	 * WCMp API for payment gateway IPNs, etc.
	 *
	 * @since 3.1
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'wcmp-api', EP_ALL );
	}

	/**
	 * API request - Trigger any API requests.
	 *
	 * @since   3.1
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['wcmp-api'] ) ) { // WPCS: input var okay, CSRF ok.
			$wp->query_vars['wcmp-api'] = sanitize_key( wp_unslash( $_GET['wcmp-api'] ) ); // WPCS: input var okay, CSRF ok.
		}

		// wcmp-api endpoint requests.
		if ( ! empty( $wp->query_vars['wcmp-api'] ) ) {

			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			wc_nocache_headers();

			// Clean the API request.
			$api_request = strtolower( wc_clean( $wp->query_vars['wcmp-api'] ) );

			// Trigger generic action before request hook.
			do_action( 'wcmp_rest_api_request', $api_request );

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
			status_header( has_action( 'wcmp_rest_api_' . $api_request ) ? 200 : 400 );

			// Trigger an action which plugins can hook into to fulfill the request.
			do_action( 'wcmp_rest_api_' . $api_request );

			// Done, clear buffer and exit.
			ob_end_clean();
			die( '-1' );
		}
	}

	/**
	 * Init WP REST API.
	 *
	 * @since 3.1
	 */
	private function rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Include REST API classes.
	 *
	 * @since 3.1
	 */
	private function rest_api_includes() {
		// REST API v1 controllers.
		$this->load_controller_class('vendors');
		$this->load_controller_class('products');
		$this->load_controller_class('coupons');
		$this->load_controller_class('orders');
		$this->load_controller_class('vendor-reviews');
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 3.1
	 */
	public function register_rest_routes() {
		// Register settings to the REST API.
		$this->register_wp_admin_settings();

		$this->rest_api_includes();

		$controllers = array(
			// v1 controllers.
			'WCMp_REST_API_Vendors_Controller',
			'WCMp_REST_API_Vendor_Reviews_Controller'
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Register WC settings from WP-API to the REST API.
	 *
	 * @since  3.0.0
	 */
	public function register_wp_admin_settings() {
		$pages = WC_Admin_Settings::get_settings_pages();
		foreach ( $pages as $page ) {
			new WC_Register_WP_Admin_Settings( $page, 'page' );
		}

		$emails = WC_Emails::instance();
		foreach ( $emails->get_emails() as $email ) {
			new WC_Register_WP_Admin_Settings( $email, 'email' );
		}
	}
	
	/**
	 * Load class located under api folder
	 *
	 * @since  3.1
	 */
	function load_controller_class($class_name = '') {
        global $WCMp;
        if ('' != $class_name) {
            require_once($WCMp->plugin_path . 'api/class-' . esc_attr($WCMp->token) . '-rest-' . esc_attr($class_name) . '-controller.php');
        } // End If Statement
    }

}
