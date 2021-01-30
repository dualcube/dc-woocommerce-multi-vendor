<?php

abstract class WCMp_Elementor_ModuleBase {

	/**
	 * Runs after first instance
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
	}

	/**
	 * Module name
	 *
	 * @return void
	 */
	abstract public function get_name();

	/**
	 * Module widgets
	 *
	 * @return array
	 */
	public function get_widgets() {
		return [];
	}

	/**
	 * Register module widgets
	 *
	 * @return void
	 */
	public function init_widgets() {
		global $wcmp_elementor;
		$widget_manager = $wcmp_elementor->wcmp_elementor()->widgets_manager;

		foreach ( $this->get_widgets() as $widget ) {
			$this->load_class( $widget );
			
			$class_name = "WCMp_Elementor_{$widget}";

			if ( class_exists( $class_name ) ) {
				$widget_manager->register_widget_type( new $class_name() );
			}
		}
	}
	
	public function load_class($class_name = '') {
		global $WCMp;
		if ('' != $class_name && '' != $WCMp->token) {
			require_once ( $WCMp->plugin_path .  'packages/wcmp-elementor/widgets/class-' . esc_attr($WCMp->token) . '-widget-' . strtolower(esc_attr($class_name)) . '.php');
		} // End If Statement
	}
}
