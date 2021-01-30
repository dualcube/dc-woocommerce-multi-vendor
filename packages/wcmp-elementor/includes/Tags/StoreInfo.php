<?php

use Elementor\Controls_Manager;

class StoreInfo extends WCMp_Elementor_TagBase {

    /**
     * Tag name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-info';
    }

    /**
     * Tag title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Info', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Render Tag
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function get_value() {
    	global $wcmp_elementor;
        $store_data = $wcmp_elementor->get_wcmp_store_data();

        $store_info = [
            [
                'key'         => 'address',
                'title'       => __( 'Address', 'dc-woocommerce-multi-vendor' ),
                'text'        => $store_data['address'],
                'icon'        => 'wcmp-font ico-location-icon',
                'show'        => true,
                '__dynamic__' => [
                    'text' => $store_data['address'],
                ]
            ],
            [
                'key'         => 'email',
                'title'       => __( 'Email', 'dc-woocommerce-multi-vendor' ),
                'text'        => $store_data['email'],
                'icon'        => 'wcmp-font ico-mail-icon',
                'show'        => true,
                '__dynamic__' => [
                    'text' => $store_data['email'],
                ]
            ],
            [
                'key'         => 'phone',
                'title'       => __( 'Phone No', 'dc-woocommerce-multi-vendor' ),
                'text'        => $store_data['phone'],
                'icon'        => 'wcmp-font ico-call-icon',
                'show'        => true,
                '__dynamic__' => [
                    'text' => $store_data['phone'],
                ]
            ],
        ];

        return apply_filters( 'wcmp_elementor_tags_store_info_value', $store_info );
    }

    protected function render() {
        echo json_encode( $this->get_value() );
    }
}
