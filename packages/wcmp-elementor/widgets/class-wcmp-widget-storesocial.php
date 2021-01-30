<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;
use Elementor\Widget_Social_Icons;

class WCMp_Elementor_StoreSocial extends Widget_Social_Icons {

    use PositionControls;

    /**
     * Widget name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-social';
    }

    /**
     * Widget title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Social', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Widget icon class
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-social-icons';
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
        return [ 'wcmp', 'store', 'vendor', 'social', 'profile', 'icons' ];
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
        
        $this->add_control(
            'store_social_links',
            [
                'type'    => WCMp_Elementor_DynamicHidden::CONTROL_TYPE,
                'dynamic' => [
                    'default' => $wcmp_elementor->wcmp_elementor()->dynamic_tags->tag_data_to_tag_text( null, 'wcmp-store-social-tag' ),
                    'active'  => true,
                ]
            ],
            [
                'position' => [ 'of' => 'social_icon_list' ],
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
        return parent::get_html_wrapper_class() . ' wcmp-store-social elementor-widget-' . parent::get_name();
    }

    /**
     * Frontend render method
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function render() {
        global $WCMp;
        $settings = $this->get_settings_for_display();

        $store_social_links = json_decode( $settings['store_social_links'], true );
        
        if ( wcmp_is_store_page() && empty( $store_social_links ) ) {
            return;
        }

        $class_animation = '';

        if ( ! empty( $settings['hover_animation'] ) ) {
            $class_animation = ' elementor-animation-' . $settings['hover_animation'];
        }
        ?>
        <div class="elementor-social-icons-wrapper">
            <?php
            foreach ( $settings['social_icon_list'] as $index => $item ) {
                if ( wcmp_is_store_page() && empty( $store_social_links[ $item['social_icon']['value'] ] ) ) {
                    continue;
                }

                $social = str_replace( 'fab fa-', '', $item['social_icon']['value'] );

                $link_key = 'link_' . $index;

                $this->add_render_attribute( $link_key, 'href', $store_social_links[ $item['social_icon']['value'] ] );

                if ( $item['link']['is_external'] ) {
                    $this->add_render_attribute( $link_key, 'target', '_blank' );
                }

                if ( $item['link']['nofollow'] ) {
                    $this->add_render_attribute( $link_key, 'rel', 'nofollow' );
                }
                ?>
                <a class="elementor-icon elementor-social-icon elementor-social-icon-<?php echo $social . $class_animation; ?>" <?php echo $this->get_render_attribute_string( $link_key ); ?>>
                    <span class="elementor-screen-only"><?php echo ucwords( $social ); ?></span>
                    <i class="<?php echo $item['social_icon']['value']; ?>"></i>
                </a>
            <?php } ?>
        </div>
        <?php
    }

    /**
     * Elementor builder content template
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function _content_template() {
        parent::_content_template();
    }

    /**
     * Render widget plain content
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_plain_content() {}
}
