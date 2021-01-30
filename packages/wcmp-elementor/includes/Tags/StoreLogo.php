<?php

use Elementor\Controls_Manager;

class StoreLogo extends WCMp_Elementor_DataTagBase {

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
        return 'wcmp-store-logo';
    }

    /**
     * Tag title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Logo', 'dc-woocommerce-multi-vendor' );
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
        $picture = $wcmp_elementor->get_wcmp_store_data( 'logo' );

        if ( empty( $picture['id'] ) ) {
            $settings = $this->get_settings();

            if ( ! empty( $settings['fallback']['id'] ) ) {
                $picture = $settings['fallback'];
            }
        }

        return $picture;
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
                    'id'  => 0,
                    'url' => $WCMp->plugin_url . 'packages/wcmp-elementor/assets/images/default-logo.png',
                ]
            ]
        );
    }
}
