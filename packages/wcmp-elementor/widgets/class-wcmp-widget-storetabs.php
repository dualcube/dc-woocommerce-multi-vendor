<?php

class WCMp_Elementor_StoreTabs extends WCMp_Elementor_StoreInfo {

    /**
     * Widget name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-tabs';
    }

    /**
     * Widget title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Tabs', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Widget icon class
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-editor-list-ul';
    }

    /**
     * Widget keywords
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_keywords() {
        return [ 'wcmp', 'store', 'vendor', 'tab', 'menu', 'items' ];
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

        $this->remove_control( 'store_info' );

        $this->update_control(
            'icon_list',
            [
                'default' => json_decode(
                    $wcmp_elementor->wcmp_elementor()->dynamic_tags->get_tag_data_content( null, 'wcmp-store-tabs' ),
                    true
                ),
            ]
        );

        $this->add_control(
            'tab_items',
            [
                'type'    => WCMp_Elementor_DynamicHidden::CONTROL_TYPE,
                'dynamic' => [
                    'active'  => true,
                    'default' => $wcmp_elementor->wcmp_elementor()->dynamic_tags->tag_data_to_tag_text( null, 'wcmp-store-tabs' )
                ]
            ],
            [
                'position' => [ 'of' => 'icon_list' ]
            ]
        );
    }

    /**
     * Set wrapper classes
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function get_html_wrapper_class() {
        return parent::get_html_wrapper_class() . ' wcmp-store-tabs elementor-widget-' . parent::get_name();
    }

    /**
     * Render icon list widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function render() {
        global $WCMp;
        $settings = $this->get_settings_for_display();

        $this->add_render_attribute( 'icon_list', 'class', 'elementor-icon-list-items' );
        $this->add_render_attribute( 'list_item', 'class', 'elementor-icon-list-item' );

        if ( 'inline' === $settings['view'] ) {
            $this->add_render_attribute( 'icon_list', 'class', 'elementor-inline-items' );
            $this->add_render_attribute( 'list_item', 'class', 'elementor-inline-item' );
        }

        $shop_query_exist = false;
        $query_vars_name = 'products';
        if (wcmp_is_store_page()) {
            $wcmp_shop_page_query_vars = $WCMp->frontend->wcmp_vendor_page_query_vars();
            foreach ($wcmp_shop_page_query_vars as $key_query) {
                if (get_query_var($key_query)) {
                    $shop_query_exist = true;
                    $query_vars_name = $key_query;
                }
            }
        }
        if ( ! empty( $settings['icon_list'] ) && ! empty( $settings['tab_items'] ) ): ?>
            <?php $tab_items = json_decode( $settings['tab_items'], true ); 

            ?>
            <?php if ( is_array( $tab_items ) ): ?>
                <ul <?php echo $this->get_render_attribute_string( 'icon_list' ); ?>>
                    <?php
                    foreach ( $settings['icon_list'] as $index => $item ) :

                        $repeater_setting_key = $this->get_repeater_setting_key( 'text', 'icon_list', $index );

                        $this->add_render_attribute( $repeater_setting_key, 'class', 'elementor-icon-list-text' );

                        $this->add_inline_editing_attributes( $repeater_setting_key );

                        if ( $item['show'] ):
                            $tab_item = array_filter( $tab_items, function ( $list_item ) use ( $item ) {
                                return $list_item['key'] === $item['key'];
                            } );

                            if ( empty( $tab_item ) ) {
                                continue;
                            }

                            $tab_item = array_pop( $tab_item );

                            $text = $tab_item['text'];
                            $url  = $tab_item['url'];
                            if ( ! $text || ! $url ) {
                                continue;
                            }

                            $link_key = 'link_' . $index;

                            $this->add_render_attribute( $link_key, 'href', $url );
                        ?>
                            <li class="elementor-icon-list-item" >
                                <a <?php echo $this->get_render_attribute_string( $link_key ); ?>>
                                    <?php

                                    if ( ! empty( $item['icon'] ) ) :

                                        ?>
                                        <span class="elementor-icon-list-icon">
                                            <i class="<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></i>
                                        </span>
                                    <?php endif; ?>
                                    <span <?php echo $this->get_render_attribute_string( $repeater_setting_key ); ?>>
                                        <div class="wcmp-tablink <?php if( $item['key'] == $query_vars_name ) echo 'active'; ?>">
                                            <?php echo esc_html( $text ); ?>
                                        </div>
                                    </span>
                                </a>
                            </li>
                        <?php
                        endif;
                    endforeach;
                    ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
        <?php
    }

    /**
     * Render icon list widget output in the editor.
     *
     * Written as a Backbone JavaScript template and used to generate the live preview.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function _content_template() {
        ?>
        <#
            view.addRenderAttribute( 'icon_list', 'class', 'elementor-icon-list-items' );
            view.addRenderAttribute( 'list_item', 'class', 'elementor-icon-list-item' );

            if ( 'inline' == settings.view ) {
                view.addRenderAttribute( 'icon_list', 'class', 'elementor-inline-items' );
                view.addRenderAttribute( 'list_item', 'class', 'elementor-inline-item' );
            }
        #>
        <# if ( settings.icon_list && settings.tab_items ) { #>
            <# var tab_items = JSON.parse( settings.tab_items ); #>
            <ul {{{ view.getRenderAttributeString( 'icon_list' ) }}}>
            <# _.each( settings.icon_list, function( item, index ) {
                    var iconTextKey = view.getRepeaterSettingKey( 'text', 'icon_list', index );

                    view.addRenderAttribute( iconTextKey, 'class', 'elementor-icon-list-text' );

                    view.addInlineEditingAttributes( iconTextKey ); #>

                    <# if ( item.show ) { #>
                        <#
                            var tab_item = _.findWhere( tab_items, { key: item.key } );
                        #>
                        <li {{{ view.getRenderAttributeString( 'list_item' ) }}}>
                            <a href="{{ tab_item.url || '#' }}">
                                <# if ( item.icon ) { #>
                                <span class="elementor-icon-list-icon">
                                    <i class="{{ item.icon }}" aria-hidden="true"></i>
                                </span>
                                <# } #>
                                <span {{{ view.getRenderAttributeString( iconTextKey ) }}}>{{{ tab_item.text }}}</span>
                            </a>
                        </li>

                    <# } #>
                <#
                } ); #>
            </ul>
        <#  } #>
        <?php
    }
}
