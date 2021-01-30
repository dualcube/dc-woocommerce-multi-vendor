<?php

use Elementor\Controls_Manager;
use Elementor\Widget_Image;

class WCMp_Elementor_StoreLogo extends Widget_Image {

    use PositionControls;

    /**
     * Widget name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-logo';
    }

    /**
     * Widget title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Logo', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Widget icon class
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-image';
    }

    /**
     * Widget categories
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_categories() {
        return [ 'wcmp-store-elements-single' ];
    }

    /**
     * Widget keywords
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_keywords() {
        return [ 'wcmp', 'store', 'vendor', 'profile', 'picture', 'image', 'avatar', 'logo' ];
    }

    /**
     * Register widget controls
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function _register_controls() {
    	global $wcmp_elementor;
        parent::_register_controls();

        $this->update_control(
            'section_image',
            [
                'label' => __( 'Store Logo', 'dc-woocommerce-multi-vendor' ),
            ]
        );

        $this->update_control(
            'image',
            [
                'dynamic' => [
                    'default' => $wcmp_elementor->wcmp_elementor()->dynamic_tags->tag_data_to_tag_text( null, 'wcmp-store-logo' ),
                ],
            ],
            [
                'recursive' => true,
            ]
        );
        
        $this->remove_control( 'caption_source' );
        $this->remove_control( 'caption' );

        $this->add_position_controls();
    }

    protected function get_html_wrapper_class() {
        return parent::get_html_wrapper_class() . ' elementor-widget-' . parent::get_name();
    }
}
