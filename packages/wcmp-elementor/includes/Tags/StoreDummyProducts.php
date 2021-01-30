<?php

class StoreDummyProducts extends WCMp_Elementor_TagBase {

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
        return 'wcmp-store-dummy-products';
    }

    /**
     * Tag title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Dummy Products', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Render tag
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render() {
        if ( wcmp_is_store_page() ) {
            return;
        }

        echo '<div class="site-main">';
        echo do_shortcode( '[products limit="12"]' );
        echo '</div>';
    }
}
