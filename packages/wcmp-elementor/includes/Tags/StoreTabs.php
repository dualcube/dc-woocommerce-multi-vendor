<?php

use Elementor\Controls_Manager;

class StoreTabs extends WCMp_Elementor_TagBase {

    /**
     * Tag name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-tabs';
    }

    /**
     * Tag title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Tabs', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Render Tag
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function get_value() {
        global $WCMp;
        $store_id = 0;

        if ( wcmp_is_store_page() ) {
            $store = wcmp_find_shop_page_vendor();

            if ( $store ) {
                $store_id = $store;
            }

           $store_tab_items = $WCMp->frontend->wcmp_get_store_tabs($store_id);
        } else {
            $store_tab_items = $this->get_store_tab_items();
        }
        
        $tab_items = [];

        foreach( $store_tab_items as $item_key => $item ) {
            $url = $item['url'];

            if ( empty( $url ) && ! $store_id ) {
                $url = '#';
            }

            $tab_items[] = [
                'key'         => $item_key,
                'title'       => $item['title'],
                'text'        => $item['title'],
                'url'         => $url,
                'icon'        => '',
                'show'        => true,
                '__dynamic__' => [
                    'text' => $item['title'],
                    'url'  => $url,
                ]
            ];
        }

        /**
         * Filter to modify tag values
         *
         * @since 1.0.0
         *
         * @param array $tab_items
         */
        return apply_filters( 'wcmp_elementor_tags_store_tab_items_value', $tab_items );
    }

    /**
     * Store tab items for Elementor Builder
     *
     * @since 2.9.14
     *
     * @return array
     */
    protected function get_store_tab_items() {
        return [
            'products' => [
                'title' => __( 'Products', 'dc-woocommerce-multi-vendor' ),
                'url'   => '#',
            ],
            'policies' => [
                'title' => __( 'Policies', 'dc-woocommerce-multi-vendor' ),
                'url'   => '#',
            ],
            'reviews' => [
                'title' => __( 'Reviews', 'dc-woocommerce-multi-vendor' ),
                'url'   => '#'
            ],
        ];
    }

    protected function render() {
        echo json_encode( $this->get_value() );
    }
}
