<?php

use Elementor\Controls_Manager;

trait PositionControls {

	/**
	 * Add css position controls
	 *
	 * @return void
	 */
	protected function add_position_controls() {
			$this->start_injection( [
					'type' => 'section',
					'at'   => 'start',
					'of'   => '_section_style',
			] );

			$this->start_controls_section(
					'section_position',
					[
							'label' => __( 'Position', 'dc-woocommerce-multi-vendor' ),
							'tab'   => Controls_Manager::TAB_ADVANCED,
					]
			);

			$this->add_responsive_control(
					'_wcmp_position',
					[
							'label'   => __( 'Position', 'dc-woocommerce-multi-vendor' ),
							'type'    => Controls_Manager::SELECT,
							'options' => [
									'static'   => __( 'Static', 'dc-woocommerce-multi-vendor' ),
									'relative' => __( 'Relative', 'dc-woocommerce-multi-vendor' ),
									'absolute' => __( 'Absolute', 'dc-woocommerce-multi-vendor' ),
									'sticky'   => __( 'Sticky', 'dc-woocommerce-multi-vendor' ),
									'fixed'    => __( 'Fixed', 'dc-woocommerce-multi-vendor' ),
							],
							'desktop_default' => 'relative',
							'tablet_default'  => 'relative',
							'mobile_default'  => 'relative',
							'selectors' => [
									'{{WRAPPER}}' => 'position: relative; min-height: 1px',
									'{{WRAPPER}} > .elementor-widget-container' => 'position: {{VALUE}};',
							],
					]
			);

			$this->add_responsive_control(
					'_wcmp_position_top',
					[
							'label'     => __( 'Top', 'dc-woocommerce-multi-vendor' ),
							'type'      => Controls_Manager::TEXT,
							'default'   => '',
							'selectors' => [
									'{{WRAPPER}} > .elementor-widget-container' => 'top: {{VALUE}};',
							],
					]
			);

			$this->add_responsive_control(
					'_wcmp_position_right',
					[
							'label'     => __( 'Right', 'dc-woocommerce-multi-vendor' ),
							'type'      => Controls_Manager::TEXT,
							'default'   => '',
							'selectors' => [
									'{{WRAPPER}} > .elementor-widget-container' => 'right: {{VALUE}};',
							],
					]
			);

			$this->add_responsive_control(
					'_wcmp_position_bottom',
					[
							'label'     => __( 'Bottom', 'dc-woocommerce-multi-vendor' ),
							'type'      => Controls_Manager::TEXT,
							'default'   => '',
							'selectors' => [
									'{{WRAPPER}} > .elementor-widget-container' => 'bottom: {{VALUE}};',
							],
					]
			);

			$this->add_responsive_control(
					'_wcmp_position_left',
					[
							'label'     => __( 'Left', 'dc-woocommerce-multi-vendor' ),
							'type'      => Controls_Manager::TEXT,
							'default'   => '',
							'selectors' => [
									'{{WRAPPER}} > .elementor-widget-container' => 'left: {{VALUE}};',
							],
					]
			);

			$this->end_controls_section();

			$this->end_injection();
	}
}
