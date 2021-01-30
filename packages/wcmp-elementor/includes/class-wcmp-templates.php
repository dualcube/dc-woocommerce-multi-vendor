<?php

class WCMp_Elementor_Templates {

	public function __construct() {
		add_filter( 'elementor/api/get_templates/body_args', [ self::class, 'add_http_request_filter' ] );
		add_filter( 'option_' . \Elementor\Api::LIBRARY_OPTION_KEY, [ self::class, 'add_template_library' ] );
		add_action( 'woocommerce_api_wcmp-template-preview-9000001', [ self::class, 'template_preview_9000001' ] );
	}

	/**
	 * Filter elementor https request
	 *
	 * @param array $body_args
	 */
	public static function add_http_request_filter( $body_args ) {
		add_filter( 'pre_http_request', [ self::class, 'pre_http_request' ], 1000, 3 );

		return $body_args;
	}

	/**
	 * Returns WCMp Marketplace Store templates for related request
	 *
	 * @param bool   $pre
	 * @param array  $r
	 * @param string $url
	 *
	 * @return bool|array
	 */
	public static function pre_http_request( $pre, $r, $url ) {
		global $WCMp;
		  
	  if ( preg_match( '/https\:\/\/my\.elementor\.com\/api\/connect\/v1\/library\/(get_template_content)/', $url, $matches ) ) {
	  	if( isset( $matches[1] ) && ( $matches[1] == 'get_template_content' ) ) {
				if( isset( $r['body'] ) && isset( $r['body']['id'] ) ) {
					$template_id = $r['body']['id'];
					if( in_array( $template_id, array( 9000001 ) ) ) {
						$json_file = $WCMp->plugin_path . 'packages/wcmp-elementor/templates/' . $template_id . '.json';
		
						if ( file_exists( $json_file ) ) {
							$content = json_decode( file_get_contents( $json_file ), true );
			
							return [
									'response' => [
											'code' => 200,
									],
									'body' => json_encode( $content )
							];
						}
					}
				}
			}
		}

		return $pre;
	}

	/**
	 * Add WCMp Marketplace Store templates as remote template source
	 *
	 * @param array $value
	 */
	public static function add_template_library( $value ) {
		global $WCMp;
		
		if ( 'string' === gettype($value['categories']) ) {
			$categories          = json_decode( $value['categories'], true );
			$categories[]        = 'single store';
			$value['categories'] = $categories;
		} else {
			$value['categories'][] = 'single store';
		}

		$store_templates = [
				[
						'id'                => "9000001",
						'source'            => "remote",
						'type'              => "block",
						'subtype'           => "single store",
						'title'             => "Store Page Layout",
						'thumbnail'         => $WCMp->plugin_url . 'packages/wcmp-elementor/assets/images/store-header-9000001.png',
						'tmpl_created'      => "1486569564",
						'author'            => "WC Marketplace",
						'tags'              => '',
						'is_pro'            => false,
						'popularity_index'  => 1,
						'trend_index'       => 1,
						'favorite'          => false,
						'has_page_settings' => false,
						'url'               => home_url( '/?wc-api=wcmp-template-preview-9000001' ),
				]
		];

		$value['templates'] = array_merge( $value['templates'], $store_templates );
		
		return $value;
	}

	/**
	 * Template preview
	 *
	 * @return void
	 */
	public static function template_preview_9000001() {
		global $WCMp;
		include $WCMp->plugin_path . 'packages/wcmp-elementor/views/template-preview-9000001.php';
	}

}