<?php

use Elementor\Widget_Heading;

class WCMp_Elementor_StoreName extends Widget_Heading {

    use PositionControls;

    /**
     * Widget name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_name() {
        return 'wcmp-store-name';
    }

    /**
     * Widget title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_title() {
        return __( 'Store Name', 'dc-woocommerce-multi-vendor' );
    }

    /**
     * Widget icon class
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-site-title';
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
        return [ 'wcmp', 'store', 'vendor', 'name', 'heading' ];
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
            'title',
            [
                'dynamic' => [
                    'default' => $wcmp_elementor->wcmp_elementor()->dynamic_tags->tag_data_to_tag_text( null, 'wcmp-store-name-tag' ),
                ],
            ],
            [
                'recursive' => true,
            ]
        );

        $this->update_control(
            'header_size',
            [
                'default' => 'h1',
            ]
        );

        $this->remove_control( 'link' );

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
        return parent::get_html_wrapper_class() . ' wcmp-store-name elementor-page-title elementor-widget-' . parent::get_name();
    }

    /**
     * Frontend render method
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function render() {
        $this->add_render_attribute(
            'title',
            'class',
            [ 'entry-title' ]
        );

        parent::render();
    }

    /**
     * Elementor builder content template
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function _content_template() {
        ?>
            <#
                settings.link = {url: ''};
                view.addRenderAttribute( '_wrapper', 'class', [ 'wcmp-store-name' ] );
                view.addRenderAttribute( 'title', 'class', [ 'entry-title' ] );
            #>
        <?php

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
