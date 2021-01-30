<?php

use Elementor\Controls_Manager;

class StoreBanner extends WCMp_Elementor_DataTagBase {

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @param array $data
     */
    public function __construct( $data = [] ) {
        parent::__construct( $data );
    }

    /**
     * Tag name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-banner';
    }

    /**
     * Tag title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Banner', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Tag categories
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
    }

    /**
     * Store profile picture
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function get_value( array $options = [] ) {
    	global $wcmp_elementor;
        $banner = $wcmp_elementor->get_wcmp_store_data( 'banner' );
        
        if ( empty( $banner['id'] ) ) {
            $settings = $this->get_settings();

            if ( ! empty( $settings['fallback']['id'] ) ) {
                $banner = $settings['fallback'];
            }
        }

        return $banner;
    }

    /**
     * Register tag controls
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function _register_controls() {
    	global $WCMp;
    	  
        $this->add_control(
            'fallback',
            [
                'label' => __( 'Fallback', 'dc-woocommerce-multi-vendor' ),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => $WCMp->plugin_url . 'packages/wcmp-elementor/assets/images/default-banner.jpg',
                ]
            ]
        );
    }
}
