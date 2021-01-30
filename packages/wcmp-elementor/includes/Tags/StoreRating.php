<?php

class StoreRating extends WCMp_Elementor_TagBase {

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
        return 'wcmp-store-rating-tag';
    }

    /**
     * Tag title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Rating', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Render tag
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function get_value() {
        global $wcmp_elementor;
        return $wcmp_elementor->get_wcmp_store_data( 'rating' );
    }
}
