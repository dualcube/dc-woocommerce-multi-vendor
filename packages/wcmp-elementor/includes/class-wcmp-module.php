<?php

use Elementor\Controls_Manager;

class WCMp_Elementor_Module extends WCMp_Elementor_ModuleBase {

    public function __construct() {
        parent::init();

        add_action( 'elementor/documents/register', [ $this, 'register_documents' ] );
        add_action( 'elementor/dynamic_tags/register_tags', [ $this, 'register_tags' ] );
        add_action( 'elementor/controls/controls_registered', [ $this, 'register_controls' ] );
        add_action( 'elementor/editor/footer', [ $this, 'add_editor_templates' ], 9 );
        add_action( 'elementor/theme/register_conditions', [ $this, 'register_conditions' ] );
        add_filter( 'wcmp_store_locate_template', [ $this, 'locate_template_for_store_page' ], 999, 4 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
    }

    /**
     * Name of the elementor module
     *
     * @return string
     */
    public function get_name() {
			return 'wcmp';
    }

    /**
     * Module widgets
     *
     * @return array
     */
    public function get_widgets() {
        $widgets = [
            'StoreBanner',
            'StoreName',
            'StoreLogo',
            'StoreInfo',
            'StoreRating',
            'StoreTabs',
            'StoreTabContents',
            'StoreSocial',
        ];

        return $widgets;
    }

    /**
     * Register module documents
     *
     * @param Elementor\Core\Documents_Manager $documents_manager
     *
     * @return void
     */
    public function register_documents( $documents_manager ) {
    	
		$this->docs_types = [
				'wcmp-store' => StorePage::get_class_full_name(),
		];

		foreach ( $this->docs_types as $type => $class_name ) {
			$documents_manager->register_document_type( $type, $class_name );
		}
    }

    /**
     * Register module tags
     *
     * @return void
     */
    public function register_tags() {
    	global $WCMp, $wcmp_elementor;
    	
			$tags = [
					'StoreBanner',
					'StoreName',
					'StoreLogo',
					'StoreInfo',
					'StoreRating',
					'StoreTabs',
					'StoreDummyProducts',
					'StoreSocial',
			];

			$module = $wcmp_elementor->wcmp_elementor()->dynamic_tags;

			$module->register_group( 'wcmp', [
					'title' => __( 'WCMp', 'dc-woocommerce-multi-vendor' ),
			] );

			foreach ( $tags as $tag ) {
				require_once ( $WCMp->plugin_path . 'packages/wcmp-elementor/includes/Tags/' . esc_attr($tag) . '.php');
				$module->register_tag( "{$tag}" );
			}
    }

    /**
     * Register controls
     *
     * @return void
     */
    public function register_controls() {
    	global $wcmp_elementor;
		$controls = [
				'WCMp_Elementor_SortableList',
				'WCMp_Elementor_DynamicHidden',
		];

		$controls_manager = $wcmp_elementor->wcmp_elementor()->controls_manager;

		foreach ( $controls as $control ) {
			$control_class = "{$control}";
			$controls_manager->register_control( $control_class::CONTROL_TYPE, new $control_class() );
		}
    }

    /**
     * Add editor templates
     *
     * @return void
     */
    public function add_editor_templates() {
    	global $WCMp, $wcmp_elementor;
    	
			$template_names = [
					'sortable-list-row',
			];

			foreach ( $template_names as $template_name ) {
				$wcmp_elementor->wcmp_elementor()->common->add_template( $WCMp->plugin_path . "packages/wcmp-elementor/views/editor-templates/$template_name.php" );
			}
    }

    /**
     * Register condition for the module
     *
     * @param \ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Manager $conditions_manager
     *
     * @return void
     */
    public function register_conditions( $conditions_manager ) {
		$condition = new StoreCondition();
		$conditions_manager->get_condition( 'general' )->register_sub_condition( $condition );
    }

    /**
     * Filter to show the elementor built store template
     *
     * @return string
     */
    public static function locate_template_for_store_page( $template, $template_name, $template_path, $default_path ) {
    	global $WCMp, $wcmp_elementor;

		if ( wcmp_is_store_page() ) {
			$documents = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_documents_for_location( 'single' );

			if ( empty( $documents ) ) {
				return $template;
			}

			$page_templates_module = $wcmp_elementor->wcmp_elementor()->modules_manager->get_modules( 'page-templates' );

			$page_templates_module->set_print_callback( function() {
					\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_locations_manager()->do_location( 'single' );
			} );

			$template_path = $page_templates_module->get_template_path( $page_templates_module::TEMPLATE_HEADER_FOOTER );

			return $template_path;
		}

		return $template;
    }

    /**
     * Enqueue scripts in editing or preview mode
     *
     * @return void
     */
    public function enqueue_editor_scripts() {
    	global $WCMp, $wcmp_elementor;
			if ( $wcmp_elementor->is_edit_or_preview_mode() ) {
				$scheme  = is_ssl() ? 'https' : 'http';
				$api_key = '';
				if ( $api_key ) {
					wp_enqueue_script( 'wcmp-store-google-maps', $scheme . '://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places' );
				}
			}
    }
}
