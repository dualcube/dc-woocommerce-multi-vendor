<?php

use Elementor\Control_Hidden;

class WCMp_Elementor_DynamicHidden extends Control_Hidden {

	/**
	 * Control type
	 *
	 * @var string
	 */
	const CONTROL_TYPE = 'dynamic_hidden';

	/**
	 * Get repeater control type.
	 *
	 * @return string
	 */
	public function get_type() {
			return self::CONTROL_TYPE;
	}

	/**
	 * Get default settings for the control
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		$default_settings = parent::get_default_settings();

		$default_settings['dynamic'] = [];

		return $default_settings;
	}
}