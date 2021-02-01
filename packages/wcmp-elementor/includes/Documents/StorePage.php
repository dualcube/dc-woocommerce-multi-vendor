<?php

use ElementorPro\Modules\ThemeBuilder\Documents\Single;

class StorePage extends Single {

	/**
	 * Class constructor
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function __construct( $data = [] ) {
			parent::__construct( $data );

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 11 );
	}

	/**
	 * Enqueue document related scripts
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		global $WCMp;
		
		wp_enqueue_style(
				'wcmp-doc-store',
				$WCMp->plugin_url . 'packages/wcmp-elementor/assets/css/wcmp-elementor-document-store.css',
				[],
				$WCMp->version
		);
	}

	/**
	 * Document properties
	 *
	 * @return array
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['location']       = 'single';
		$properties['condition_type'] = 'general';

		return $properties;
	}

	/**
	 * Document name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcmp-store';
	}

	/**
	 * Document title
	 *
	 * @return string
	 */
	public static function get_title() {
		return __( 'WCMp Store Page', 'dc-woocommerce-multi-vendor' );
	}

	/**
	 * Elementor builder panel categories
	 *
	 * @return array
	 */
	protected static function get_editor_panel_categories() {
		$categories = [
				'wcmp-store-elements-single' => [
						'title'  => __( 'WC Marketplace', 'dc-woocommerce-multi-vendor' ),
						'active' => true,
				],
		];

		$categories += parent::get_editor_panel_categories();

		return $categories;
	}
	
	/**
	 * Remote library config
	 *
	 * From elementor pro v2.4.0 `get_remote_library_config` is used
	 * instead of `get_remote_library_type`
	 *
	 * @since 2.9.13
	 *
	 * @return array
	 */
	public function get_remote_library_config() {
			$config = parent::get_remote_library_config();

			$config['category'] = 'single store';

			return $config;
	}
}
