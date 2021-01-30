<?php

use Elementor\Control_Repeater;

class WCMp_Elementor_SortableList extends Control_Repeater {

	/**
	 * Control type
	 *
	 * @var string
	 */
	const CONTROL_TYPE = 'sortable_list';

	/**
	 * Get repeater control type.
	 *
	 * @return string
	 */
	public function get_type() {
		return self::CONTROL_TYPE;
	}

	/**
	 * Get repeater control default settings.
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return [
				'fields'        => [],
				'title_field'   => '',
				'prevent_empty' => true,
				'is_repeater'   => true,
				'item_actions'  => [
						'sort' => true,
				],
		];
	}

	/**
	 * Render repeater control output in the editor.
	 *
	 * @return void
	 */
	public function content_template() {
		?>
		<label>
				<span class="elementor-control-title">{{{ data.label }}}</span>
		</label>
		<div class="elementor-repeater-fields-wrapper"></div>
		<?php
	}

	/**
	 * Enqueue control scripts
	 *
	 * @return void
	 */
	public function enqueue() {
		global $WCMp;
		
		wp_enqueue_style(
				'wcmp-control-sortable-list',
				$WCMp->plugin_url . 'packages/wcmp-elementor/assets/css/wcmp-elementor-control-sortable-list.css',
				[],
				$WCMp->version
		);

		wp_enqueue_script(
				'wcmp-control-sortable-list',
				$WCMp->plugin_url . 'packages/wcmp-elementor/assets/js/wcmp-elementor-control-sortable-list.js',
				[ 'elementor-editor' ],
				$WCMp->version,
				true
		);
	}
}
