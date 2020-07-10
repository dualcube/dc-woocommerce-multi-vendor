<?php

/**
 * WCMp Library Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Library {

    public $lib_path;
    public $lib_url;
    public $php_lib_path;
    public $php_lib_url;
    public $jquery_lib_path;
    public $jquery_lib_url;
    public $bootstrap_lib_url;
    public $jqvmap;
    public $dataTable_lib_url;

    public function __construct() {

        global $WCMp;

        $this->lib_path = $WCMp->plugin_path . 'lib/';

        $this->lib_url = $WCMp->plugin_url . 'lib/';

        $this->php_lib_path = $this->lib_path . 'php/';

        $this->php_lib_url = $this->lib_url . 'php/';

        $this->jquery_lib_path = $this->lib_path . 'jquery/';

        $this->jquery_lib_url = $this->lib_url . 'jquery/';

        $this->css_lib_path = $this->lib_path . 'css/';

        $this->css_lib_url = $this->lib_url . 'css/';

        $this->bootstrap_lib_url = $this->lib_url . 'bootstrap/';

        $this->jqvmap = $this->lib_url . 'jqvmap/';

        $this->dataTable_lib_url = $this->lib_url . 'dataTable/';
    }

    /**
     * PHP WP fields Library
     */
    public function load_wp_fields() {
        require_once ($this->php_lib_path . 'class-dc-wp-fields.php');
        $DC_WP_Fields = new WCMp_WP_Fields();
        return $DC_WP_Fields;
    }

    public function load_wcmp_frontend_fields() {
        require_once ($this->php_lib_path . 'class-wcmp-frontend-wp-fields.php');
        return new WCMp_Frontend_WP_Fields();
    }

    /**
     * Jquery qTip library
     */
    public function load_qtip_lib() {
        global $WCMp;
        wp_enqueue_script('qtip_js', $this->jquery_lib_url . 'qtip/qtip.js', array('jquery'), $WCMp->version, true);
        wp_enqueue_style('qtip_css', $this->jquery_lib_url . 'qtip/qtip.css', array(), $WCMp->version);
    }

    /**
     * WP Media library
     */
    public function load_upload_lib() {
        global $WCMp;
        wp_enqueue_media();
        wp_enqueue_script('upload_js', $this->jquery_lib_url . 'upload/media-upload.js', array('jquery'), $WCMp->version, true);
        wp_enqueue_style('upload_css', $this->jquery_lib_url . 'upload/media-upload.css', array(), $WCMp->version);
    }

    /**
     * WP Media library
     */
    public function load_frontend_upload_lib() {
        global $WCMp;
        wp_enqueue_media();
        wp_enqueue_script('frontend_upload_js', $this->lib_url . 'upload/media-upload.js', array('jquery'), $WCMp->version, true);
        wp_localize_script('frontend_upload_js', 'media_upload_params', array('media_title' => __('Choose Media', 'dc-woocommerce-multi-vendor')));
        wp_enqueue_style('upload_css', $this->lib_url . 'upload/media-upload.css', array(), $WCMp->version);
    }

    /**
     * WP Media library for dashboard
     */
    public function load_dashboard_upload_lib() {
        global $WCMp;
        wp_enqueue_media();
        wp_enqueue_style( 'imgareaselect' );
        wp_enqueue_script('frontend_dash_upload_js', $this->jquery_lib_url . 'upload/frontend-media-upload.js', array('jquery', 'imgareaselect'), $WCMp->version, true);
        $enableCrop = false;
        if(wp_image_editor_supports()) $enableCrop = true;
        $image_script_params = array('enableCrop' => $enableCrop, 'default_logo_ratio' => array(100, 100), 'cover_ratio' => array(1200, 390), 'canSkipCrop' => false);
        wp_localize_script( 'frontend_dash_upload_js', 'frontend_dash_upload_script_params', apply_filters( 'wcmp_frontend_dash_upload_script_params', $image_script_params) );
    }

    /**
     * Jquery Accordian library
     */
    public function load_accordian_lib() {
        global $WCMp;
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_style('accordian_css', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), $WCMp->version);
    }

    /**
     * Select2 library
     */
    public function load_select2_lib() {
        global $WCMp;
        wp_enqueue_script('select2_js', $this->lib_url . 'select2/select2.js', array('jquery'), $WCMp->version, true);
        wp_enqueue_style('select2_css', $this->lib_url . 'select2/select2.css', array(), $WCMp->version);
    }

    /**
     * Jquery TinyMCE library
     */
    public function load_tinymce_lib() {
        global $WCMp;
        wp_enqueue_script('tinymce_js', '//cdnjs.cloudflare.com/ajax/libs/tinymce/4.5.6/tinymce.min.js', array('jquery'), $WCMp->version, true);
        wp_enqueue_script('jquery_tinymce_js', '//cdnjs.cloudflare.com/ajax/libs/tinymce/4.5.6/jquery.tinymce.min.js', array('jquery'), $WCMp->version, true);
    }

    /**
     * WP ColorPicker library
     */
    public function load_colorpicker_lib() {
        global $WCMp;
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('colorpicker_init', $this->jquery_lib_url . 'colorpicker/colorpicker.js', array('jquery', 'wp-color-picker'), $WCMp->version, true);
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * WP DatePicker library
     */
    public function load_datepicker_lib() {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-style');
    }

    /**
     * Jquery style library
     */
    public function load_jquery_style_lib() {
        global $wp_scripts;
        if (!wp_style_is('jquery-ui-style', 'registered')) {
            $jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';
            wp_register_style('jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css', array(), $jquery_version);
        }
    }

    public function load_bootstrap_style_lib() {
        wp_register_style('wcmp-bootstrap-style', $this->bootstrap_lib_url . 'css/bootstrap.min.css', array(), '4.5.0');
        wp_enqueue_style('wcmp-bootstrap-style');
    }

    public function load_bootstrap_script_lib() {
        wp_register_script('wcmp-bootstrap-script', $this->bootstrap_lib_url . 'js/bootstrap.min.js', array('jquery'), '4.5.0');
        if (!defined('WCMP_UNLOAD_BOOTSTRAP_LIB')) {
            wp_enqueue_script('wcmp-bootstrap-script');
        }
    }

    /**
     * Google Map API
     */
    public function load_gmap_api() {
        $api_key = get_wcmp_vendor_settings('google_api_key');
        $protocol = is_ssl() ? 'https' : 'http';
        if ($api_key) {
            $wcmp_gmaps_url = apply_filters('wcmp_google_maps_api_url', array(
                            'protocol' => $protocol,
                            'url_base' => '://maps.googleapis.com/maps/api/js?',
                            'url_data' => http_build_query(apply_filters('wcmp_google_maps_api_args', array(
                                                    'libraries' => 'places',
                                                    'key'       => $api_key,
                                                )
                                            ), '', '&amp;'
					),
				), $api_key
			);
            wp_register_script('wcmp-gmaps-api', implode( '', $wcmp_gmaps_url ), array('jquery'));
            wp_enqueue_script('wcmp-gmaps-api');
        }
    }

    /**
     * dataTable library
     */
    public function load_dataTable_lib() {
        global $WCMp;
        wp_register_style('wcmp-datatable-bs-style', $this->dataTable_lib_url . 'dataTables.bootstrap.min.css');
        wp_register_style('wcmp-datatable-fhb-style', $this->dataTable_lib_url . 'fixedHeader.bootstrap.min.css');
        wp_register_style('wcmp-datatable-rb-style', $this->dataTable_lib_url . 'responsive.bootstrap.min.css');
        wp_register_script('wcmp-datatable-bs-script', $this->dataTable_lib_url . 'dataTables.bootstrap.min.js', array('jquery'));
        wp_register_script('wcmp-datatable-fh-script', $this->dataTable_lib_url . 'dataTables.fixedHeader.min.js', array('jquery'));
        wp_register_script('wcmp-datatable-resp-script', $this->dataTable_lib_url . 'dataTables.responsive.min.js', array('jquery'));
        wp_register_script('wcmp-datatable-rb-script', $this->dataTable_lib_url . 'responsive.bootstrap.min.js', array('jquery'));
        wp_register_script('wcmp-datatable-script', $this->dataTable_lib_url . 'jquery.dataTables.min.js', array('jquery'));
        wp_enqueue_style('wcmp-datatable-bs-style');
        wp_enqueue_style('wcmp-datatable-fhb-style');
        wp_enqueue_style('wcmp-datatable-rb-style');
        wp_enqueue_script('wcmp-datatable-script');
        wp_enqueue_script('wcmp-datatable-bs-script');
        wp_enqueue_script('wcmp-datatable-fh-script');
        wp_enqueue_script('wcmp-datatable-resp-script');
        wp_enqueue_script('wcmp-datatable-rb-script');
        wp_add_inline_script('wcmp-datatable-script', 'jQuery(document).ready(function($){
          $.fn.dataTable.ext.errMode = "none";
        });');
    }

    /**
     * jqvmap library
     */
    public function load_jqvmap_script_lib() {
        wp_register_style('wcmp-jqvmap-style', $this->jqvmap . 'jqvmap.min.css', array(), '1.5.1');
        wp_register_script('wcmp-vmap-script', $this->jqvmap . 'jquery.vmap.min.js', true, '1.5.1');
        wp_register_script('wcmp-vmap-world-script', $this->jqvmap . 'maps/jquery.vmap.world.min.js', true, '1.5.1');
        wp_enqueue_style('wcmp-jqvmap-style');
        wp_enqueue_script('wcmp-vmap-script');
        wp_enqueue_script('wcmp-vmap-world-script');
        do_action('wcmp_jqvmap_enqueue_scripts');
    }
    
    /**
     * Stripe Library
     */
    public function stripe_library() {
        if(!class_exists("Stripe\Stripe")) {
            require_once( $this->lib_path . 'Stripe/init.php' );
        }
    }
    
    /**
     * jQuery serializejson Library
     */
    public function load_jquery_serializejson_library() {
        $suffix = defined( 'WCMP_SCRIPT_DEBUG' ) && WCMP_SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script('wcmp-serializejson', $this->lib_url . 'jquery-serializejson/jquery.serializejson' . $suffix . '.js', array('jquery'), '2.8.1');
    }
    
    /**
     * Load tabs Library
     */
    public function load_tabs_library() {
        wp_enqueue_script( 'wcmp-tabs', $this->lib_url . 'tabs/tabs.js', array( 'jquery' ) );
    }

}
