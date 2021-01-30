<?php

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Icon_List;

class WCMp_Elementor_StoreInfo extends Widget_Icon_List {

    use PositionControls;

    /**
     * Widget name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-info';
    }

    /**
     * Widget title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Info', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Widget icon class
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-bullet-list';
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
        return [ 'wcmp', 'store', 'vendor', 'info', 'address', 'location' ];
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
            'section_icon',
            [
                'label' => __( 'Store Info', 'dc-woocommerce-multi-vendor' ),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'title',
            [
                'label'   => __( 'Title', 'dc-woocommerce-multi-vendor' ),
                'type'    => Controls_Manager::HIDDEN,
                'default' => __( 'Title', 'dc-woocommerce-multi-vendor' ),
            ]
        );

        $repeater->add_control(
            'text',
            [
            	  'label'       => __( 'Content', 'dc-woocommerce-multi-vendor' ),
                'type' => Controls_Manager::HIDDEN,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'icon',
            [
                'label'       => __( 'Icon', 'dc-woocommerce-multi-vendor' ),
                'type'        => Controls_Manager::ICON,
                'label_block' => true,
                'default'     => 'fa fa-check',
            ]
        );

        $this->update_control(
            'icon_list',
            [
                'type'    => WCMp_Elementor_SortableList::CONTROL_TYPE,
                'fields'  => $repeater->get_controls(),
                'default' => json_decode(
                    $wcmp_elementor->wcmp_elementor()->dynamic_tags->get_tag_data_content( null, 'wcmp-store-info' ),
                    true
                ),
            ]
        );

        $this->add_control(
            'store_info',
            [
                'type'    => WCMp_Elementor_DynamicHidden::CONTROL_TYPE,
                'dynamic' => [
                    'active'  => true,
                    'default' => $wcmp_elementor->wcmp_elementor()->dynamic_tags->tag_data_to_tag_text( null, 'wcmp-store-info' )
                ]
            ],
            [
                'position' => [ 'of' => 'icon_list' ]
            ]
        );

        $this->add_position_controls();
    }

    /**
     * Set wrapper classes
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function get_html_wrapper_class() {
        return parent::get_html_wrapper_class() . ' wcmp-store-info elementor-widget-' . parent::get_name();
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
        $settings = $this->get_settings_for_display();

        $this->add_render_attribute( 'icon_list', 'class', 'elementor-icon-list-items' );
        $this->add_render_attribute( 'list_item', 'class', 'elementor-icon-list-item' );

        if ( 'inline' === $settings['view'] ) {
            $this->add_render_attribute( 'icon_list', 'class', 'elementor-inline-items' );
            $this->add_render_attribute( 'list_item', 'class', 'elementor-inline-item' );
        }
        ?>
        <?php if ( ! empty( $settings['icon_list'] ) && ! empty( $settings['store_info'] ) ): ?>
            <?php $store_info = json_decode( $settings['store_info'], true ); ?>
            <?php if ( is_array( $store_info ) ): ?>
                <ul <?php echo $this->get_render_attribute_string( 'icon_list' ); ?>>
                    <?php
                    foreach ( $settings['icon_list'] as $index => $item ) :
                        $repeater_setting_key = $this->get_repeater_setting_key( 'text', 'icon_list', $index );

                        $this->add_render_attribute( $repeater_setting_key, 'class', 'elementor-icon-list-text' );

                        $this->add_inline_editing_attributes( $repeater_setting_key );

                        if ( $item['show'] ):
                            $info_item = array_filter( $store_info, function ( $list_item ) use ( $item ) {
                                return $list_item['key'] === $item['key'];
                            } );

                            if ( empty( $info_item ) ) {
                                continue;
                            }

                            $info_item = array_pop( $info_item );

                            $text = $info_item['text'];

                            if ( ! $text ) {
                                continue;
                            }
                        ?>
                            <li class="elementor-icon-list-item" >
                                <?php
                                if ( ! empty( $item['icon'] ) ) :
                                    ?>
                                    <span class="elementor-icon-list-icon">
                                        <i class="<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></i>
                                    </span>
                                <?php endif; ?>
                                <span <?php echo $this->get_render_attribute_string( $repeater_setting_key ); ?>><?php echo $text; ?></span>
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
     * @since 3.5.6
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
        <# if ( settings.icon_list && settings.store_info ) { #>
            <# var store_info = JSON.parse( settings.store_info ); #>
            <ul {{{ view.getRenderAttributeString( 'icon_list' ) }}}>
            <# _.each( settings.icon_list, function( item, index ) {
                    var iconTextKey = view.getRepeaterSettingKey( 'text', 'icon_list', index );

                    view.addRenderAttribute( iconTextKey, 'class', 'elementor-icon-list-text' );

                    view.addInlineEditingAttributes( iconTextKey ); #>

                    <# if ( item.show ) { #>
                        <#
                            var text = _.findWhere( store_info, { key: item.key } ).text;
                        #>
                        <li {{{ view.getRenderAttributeString( 'list_item' ) }}}>
                            <# if ( item.icon ) { #>
                            <span class="elementor-icon-list-icon">
                                <i class="{{ item.icon }}" aria-hidden="true"></i>
                            </span>
                            <# } #>
                            <span {{{ view.getRenderAttributeString( iconTextKey ) }}}>{{{ text }}}</span>
                        </li>
                    <# } #>
                <#
                } ); #>
            </ul>
        <#  } #>
        <?php
    }

    /**
     * Render widget plain content
     *
     * @since 3.5.6
     *
     * @return void
     */
    public function render_plain_content() {}
}
