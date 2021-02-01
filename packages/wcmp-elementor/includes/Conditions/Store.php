<?php

use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class StoreCondition extends Condition_Base {

	/**
	 * Type of condition
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'wcmp-store';
	}

	/**
	 * Condition name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcmp-store';
	}

	/**
	 * Condition label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'WCMp Store Page', 'dc-woocommerce-multi-vendor' );
	}

	/**
	 * Condition label for all items
	 *
	 * @return string
	 */
	public function get_all_label() {
		return __( 'All Stores', 'dc-woocommerce-multi-vendor' );
	}

	/**
	 * Check if proper conditions are met
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function check( $args ) {
		global $WCMp;
		return wcmp_is_store_page();
	}
}
