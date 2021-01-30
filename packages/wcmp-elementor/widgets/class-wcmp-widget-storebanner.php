<?php

use Elementor\Controls_Manager;
use Elementor\Widget_Image;

class WCMp_Elementor_StoreBanner extends Widget_Image {

	use PositionControls;

	/**
	 * Widget name
	 *
	 * @return string
	 */
	public function get_name() {
			return 'wcmp-store-banner';
	}

	/**
	 * Widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Store Banner', '' );
	}

	/**
	 * Widget icon class
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-box';
	}

	/**
	 * Widget categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'wcmp-store-elements-single' ];
	}

	/**
	 * Widget keywords
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'wcmp', 'store', 'vendor', 'banner', 'picture', 'image', 'avatar' ];
	}

	/**
	 * Register widget controls
	 *
	 * @return void
	 */
	protected function _register_controls() {
		global $wcmp_elementor;
		parent::_register_controls();

		$this->update_control(
				'section_image',
				[
						'label' => __( 'Banner', 'dc-woocommerce-multi-vendor' ),
				]
		);

		$this->update_control(
				'image',
				[
						'dynamic' => [
								'default' => $wcmp_elementor->wcmp_elementor()->dynamic_tags->tag_data_to_tag_text( null, 'wcmp-store-banner' ),
						],
						'selectors' => [
								'{{WRAPPER}} > .elementor-widget-container > .elementor-image > img' => 'width: 100%;',
						]
				],
				[
						'recursive' => true,
				]
		);
		
		$this->remove_control( 'caption_source' );
		$this->remove_control( 'caption' );

		$this->add_position_controls();
	}

	/**
	 * Html wrapper class
	 *
	 * @return string
	 */
	protected function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' elementor-widget-' . parent::get_name();
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
		if ( ! wcmp_is_store_page() ) {
			return;
		}
		
		$settings = $this->get_settings_for_display();
			
		if( isset( $settings['image'] ) && isset( $settings['image']['banner_type'] ) && ( $settings['image']['banner_type'] == 'slider' ) && isset( $settings['image']['banner_slider'] ) && !empty( $settings['image']['banner_slider'] ) && is_array( $settings['image']['banner_slider'] ) ) {
			$banner_sliders = $settings['image']['banner_slider'];
			?>
			<div class="wcmp_slider_area">
				<div class="wcmp_slideshow_container">
					<?php foreach( $banner_sliders as $banner_slider_key => $banner_slider ) { ?>
						<?php if( !empty( $banner_slider['image'] ) ) { ?>
							<div class="wcmpSlides wcmp_slide_fade">
								<a href="<?php echo $banner_slider['link'] ? $banner_slider['link'] : wcmp_get_attachment_url($banner_slider['image']); ?>" target="_blank">
									<img src="<?php echo wcmp_get_attachment_url($banner_slider['image']); ?>" style="width:100%">
								</a>
							</div>
						<?php } ?>
					<?php } ?>
				</div>
			</div>
			<?php
		} elseif( isset( $settings['image'] ) && isset( $settings['image']['banner_type'] ) && ( $settings['image']['banner_type'] == 'video' ) && isset( $settings['image']['banner_video'] ) && !empty( $settings['image']['banner_video'] ) ) {
			$banner_video = $settings['image']['banner_video'];
			?>
			<section class="banner_area">
			  <?php if( apply_filters( 'wcmp_is_allow_full_width_video', true ) ) { ?>
					<style>
					.banner_area {
						position: relative;
						height: <?php echo (350+75); ?>px;
						overflow:hidden;
					}
					.banner_video {
						position: relative;
						padding-bottom: 56.25%; /* 16:9 */
						height: 0;
					}
					.banner_video iframe {
						position: absolute;
						top: -75px;
						left: 0;
						width: 100%;
						height: 100%;
					}
					@media screen and (max-width: 640px) {
					  .banner_area {
					  	height: <?php echo (250-50); ?>px;
					  }
					  .banner_video iframe {
					  	top: 0px;
					  }
					}
					</style>
				<?php } ?>
				<div class="banner_video">
					<?php echo preg_replace("/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i", "<iframe width=\"100%\" height=\"315\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media\" src=\"//www.youtube.com/embed/$2?iv_load_policy=3&enablejsapi=1&disablekb=1&autoplay=1&controls=0&showinfo=0&rel=0&loop=1&wmode=transparent&widgetid=1\" allowfullscreen=\"1\"></iframe>", $banner_video); ?>
				</div>
			</section>
			<?php
		} else {
			parent::render();
		}
	}
}
