<?php
/**
 * Load WCMp Elementor package class.
 *
 */

use Elementor\Controls_Manager;

class WCMp_Elementor {
    /**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	public static $instance = null;
        
    /**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance_wcmp() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init the plugin.
	 */
	public function init() {
		
		if ( ! $this->has_dependencies() ) {
			return;
		} 
		$this->on_plugins_loaded();
	}
        
	/**
	 * Check dependencies exist.
	 *
	 * @return boolean
	 */
	public function has_dependencies() {
		return class_exists( 'WCMp' );
	}
        
	/**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {

		$wcmp_elementor = new WCMp_Elementor( __FILE__ );
		$GLOBALS['wcmp_elementor'] = $wcmp_elementor;

		add_action( 'elementor_pro/init', array( $this, 'wcmp_elementor_init' ) );
	}

	public function load_class($class_name = '') {
		global $WCMp;
		if ('' != $class_name && '' != $WCMp->token) {
			require_once ($WCMp->plugin_path . 'packages/wcmp-elementor/includes/class-' . esc_attr($WCMp->token) . '-' . esc_attr($class_name) . '.php');
		} // End If Statement
	}

	public function wcmp_elementor_init() {
		global $WCMp;

		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Abstracts/ModuleBase.php';
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Abstracts/DataTagBase.php';
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Abstracts/TagBase.php';
		
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Conditions/Store.php';
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Documents/StorePage.php';
		
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Controls/DynamicHidden.php';
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Controls/SortableList.php';
		
		require_once $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Traits/wcmp-elementor-position-controls.php';

		add_action( 'elementor/elements/categories_registered', [ &$this, 'wcmp_categories' ] );

		// Templates
		$this->load_class( 'templates' );
		new WCMp_Elementor_Templates();

		// Module
		$this->load_class( 'module' );
		new WCMp_Elementor_Module();
	}

 
    public function wcmp_elementor() {
		return \Elementor\Plugin::instance();
	}

	public function is_edit_or_preview_mode() {
		global $wcmp_elementor;
		$is_edit_mode = $wcmp_elementor->wcmp_elementor()->editor->is_edit_mode();

		$is_preview_mode = $wcmp_elementor->wcmp_elementor()->preview->is_preview_mode();
		if ( empty( $is_edit_mode ) && empty( $is_preview_mode ) ) {
			if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['editor_post_id'] ) ) {
				$is_edit_mode = true;
			} else if ( ! empty( $_REQUEST['preview'] ) && $_REQUEST['preview'] && ! empty( $_REQUEST['theme_template_id'] ) ) {
				$is_preview_mode = true;
			}
		}

		if ( $is_edit_mode || $is_preview_mode ) {
			return true;
		}

		return false;
	}

	/**
	 * Default store data for widgets
	 *
	 * @param string $prop
	 *
	 * @return mixed
	 */
	public function get_wcmp_store_data( $prop = null ) {
		$this->load_class( 'store-data' );
	  	$default_store_data = new WCMp_Elementor_StoreData();

		return $default_store_data->get_data( $prop );
	}

	/**
	 * Social network name mapping to elementor icon names
	 *
	 * @return array
	 */
	public function get_social_networks_map() {
			$map = [
					'fb'        => 'fab fa-facebook',
					'gplus'     => 'fab fa-google-plus',
					'twitter'   => 'fab fa-twitter',
					'linkedin'  => 'fab fa-linkedin-in',
					'youtube'   => 'fab fa-youtube',
					'instagram' => 'fab fa-instagram',
			];

			return apply_filters( 'wcmp_elementor_social_network_map', $map );
	}
	
	/**
	 * Register Elementor "WCMp Marketplace" category
	 */
	function wcmp_categories( $elements_manager ) {
		$elements_manager->add_category(
			'wcmp-store-elements-single',
			[
				'title' => __( 'WC Marketplace', 'dc-woocommerce-multi-vendor' ),
				'icon' => 'fa fa-plug',
			]
		);
	}

}

WCMp_Elementor::instance_wcmp()->init();