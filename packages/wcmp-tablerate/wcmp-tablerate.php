<?php
/**
 * Load WCMp Tablerate package class.
 *
 */

class WCMp_Tablerate {
    /**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	public static $instance = null;
        
    /**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance_wcmp() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init the plugin.
	 */
	public function init() {
		if ( !$this->has_dependencies() ) {
			return;
		}
		$this->on_plugins_loaded();
	}
        
	/**
	 * Check dependencies exist.
	 *
	 * @return boolean
	 */
	public function has_dependencies() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'woocommerce-table-rate-shipping/woocommerce-table-rate-shipping.php' ) && !is_plugin_active( 'wcmp-advance-shipping/wcmp-advance-shipping.php' ) ) {
            return true;
        } else {
            return false;
        }
	}

	/**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {
		add_action('init', array(&$this, 'tablerate_init'), 99);
        add_filter('is_wcmp_vendor_shipping_tab_enable', array(&$this, 'is_wcmp_table_rate_shipping_enable'), 10, 2);
	}

	public function tablerate_init() {
        global $WCMp;
		// Admin end
		if (is_admin()) {
			add_action('admin_init', array(&$this, 'save_wcmp_table_rate_shipping_admin'));
	        add_action('admin_enqueue_scripts', array($this, 'wcmp_table_rate_shipping_admin_enqueue_scripts'));
	    }
	    // Ajax work
	    if (defined('DOING_AJAX')) {
	        add_action('wp_ajax_delete_table_rate_shipping_row', array(&$this, 'delete_table_rate_shipping_row'));
	        add_action('wp_ajax_nopriv_delete_table_rate_shipping_row', array(&$this, 'delete_table_rate_shipping_row'));
	    }
	    // Frontend
	    if (!is_admin() || defined('DOING_AJAX')) {
	    	add_action('wcmp_before_update_shipping_method', array(&$this, 'save_wcmp_table_rate_shipping'));
	        add_action('wcmp_frontend_enqueue_scripts', array(&$this, 'frontend_styles'));
	        add_filter( 'woocommerce_package_rates', array(&$this, 'wcmp_hide_table_rate_when_disabled' ), 99, 2 );
	        remove_action('wp_ajax_wcmp-toggle-shipping-method', array($WCMp->ajax, 'wcmp_toggle_shipping_method'));
	        add_action('wp_ajax_wcmp-toggle-shipping-method', array(&$this, 'wcmp_table_rate_toggle_shipping_method'));
	    }
	    // Template
	    add_filter('wcmp_vendor_backend_shipping_methods_edit_form_fields', array(&$this, 'wcmps_advance_shipping_table_rate'), 10, 4);
        add_filter('wcmp_vendor_shipping_methods', array(&$this, 'add_fields_wcmp_vendor_shipping_methods'));
        add_action('wcmp_vendor_shipping_table_rate_configure_form_fields', array(&$this, 'output_wcmps_advance_shipping_table_rate'), 10, 2);
    }

    /**
	 * Admin styles + scripts
	 */
    public function wcmp_table_rate_shipping_admin_enqueue_scripts() {
        global $WCMp;
        $frontend_style_path = $WCMp->plugin_url . 'packages/wcmp-tablerate/assets/frontend/';
        $frontend_style_path = str_replace(array('http:', 'https:'), '', $frontend_style_path);
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script( 'wcmp_advanced_shipping_frontend', $frontend_style_path . 'js/frontend' . $suffix . '.js', array( 'jquery' ), $WCMp->version);
        $screen = get_current_screen();
        $wcmp_shipping_screen = apply_filters( 'wcmp_table_rate_js_inclide_pages', array('wcmp_page_vendors', 'toplevel_page_dc-vendor-shipping'));
        if (in_array($screen->id, $wcmp_shipping_screen)) {
            wp_enqueue_script( 'wcmp_advanced_shipping', $WCMp->plugin_url . 'packages/wcmp-tablerate/assets/global/js/advance-shipping.js', array( 'jquery' ), $WCMp->version);
            $WCMp->localize_script('wcmp_advanced_shipping');
        }
    }

    public function save_wcmp_table_rate_shipping_admin() {
        global $wpdb;
        if (isset($_POST['wcmp_table_rate']) && isset($_POST['shipping_class_id'])) {
            $table_rate_datas = isset($_POST['wcmp_table_rate']) ? $_POST['wcmp_table_rate'] : '';
            $shipping_class_id = isset($_POST['shipping_class_id']) ? absint($_POST['shipping_class_id']) : '';
            if (!empty($table_rate_datas) && is_array($table_rate_datas)) {
            	// Clear cache
				$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_ship_%')" );
				$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_shipping-transient-version')" );

                foreach ($table_rate_datas as $shipping_method_id => $table_rate_data) {
					foreach ($table_rate_data as $data) {
                        $rate_id = isset($data['rate_id']) ? $data['rate_id'] : 0;
                        $rate_class = $shipping_class_id;
                        $rate_condition = isset($data['rate_condition']) ? $data['rate_condition'] : '';
                        $rate_min = isset($data['rate_min']) ? $data['rate_min'] : '';
                        $rate_max = isset($data['rate_max']) ? $data['rate_max'] : '';
                        $rate_priority = isset($data['rate_priority']) ? $data['rate_priority'] : 0;
						$rate_abort = isset($data['rate_abort']) ? $data['rate_abort'] : 0;
						$rate_cost = isset($data['rate_cost']) ? rtrim(rtrim(number_format((double) $data['rate_cost'], 4, '.', ''), '0'), '.') : '';
                        $rate_cost_per_item = isset($data['rate_cost_per_item']) ? rtrim(rtrim(number_format((double) $data['rate_cost_per_item'], 4, '.', ''), '0'), '.') : '';
                        $rate_cost_per_weight_unit = isset($data['rate_cost_per_weight_unit']) ? rtrim(rtrim(number_format((double) $data['rate_cost_per_weight_unit'], 4, '.', ''), '0'), '.') : '';
                        $rate_cost_percent = isset($data['rate_cost_percent']) ? rtrim(rtrim(number_format((double) str_replace('%', '', $data['rate_cost_percent']), 2, '.', ''), '0'), '.') : '';
                        $rate_label = isset($data['rate_label']) ? $data['rate_label'] : '';
                        if ($rate_id > 0) {
                            $wpdb->update(
                                    $wpdb->prefix . 'woocommerce_shipping_table_rates', array(
                                'rate_condition' => sanitize_title($rate_condition),
                                'rate_min' => $rate_min,
                                'rate_max' => $rate_max,
                                'rate_cost' => $rate_cost,
                                'rate_cost_per_item' => $rate_cost_per_item,
                                'rate_cost_per_weight_unit' => $rate_cost_per_weight_unit,
                                'rate_cost_percent' => $rate_cost_percent,
                                'rate_label' => $rate_label,
                                'rate_priority' => $rate_priority,
								'rate_abort' => $rate_abort,
                                    ), array(
                                'rate_id' => $rate_id
                                    ), array(
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
								'%d',
								'%d'
                                    ), array(
								'%d'
                                    )
                            );
                            add_action('admin_notices', array(&$this, 'add_shipping_updated_notice'));
                        } else {
                            $wpdb->insert("{$wpdb->prefix}woocommerce_shipping_table_rates", array(
                                'rate_class' => $rate_class,
                                'rate_condition' => sanitize_title($rate_condition),
                                'rate_min' => $rate_min,
                                'rate_max' => $rate_max,
                                'rate_priority' => $rate_priority,
								'rate_abort' => $rate_abort,
                                'rate_cost' => $rate_cost,
                                'rate_cost_per_item' => $rate_cost_per_item,
                                'rate_cost_per_weight_unit' => $rate_cost_per_weight_unit,
                                'rate_cost_percent' => $rate_cost_percent,
                                'shipping_method_id' => $shipping_method_id,
                                'rate_label' => $rate_label
                                    ), array(
                                '%d',
                                '%s',
                                '%d',
                                '%d',
                                '%d',
                                '%d',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%d',
                                '%s'
                                    )
                            );
                            add_action('admin_notices', array(&$this, 'add_shipping_updated_notice'));
                        }
                    }
                }
            }
        }
    }

    public function add_shipping_updated_notice() {
        ?>
        <div id="message" class="updated settings-error notice is-dismissible">
            <p><strong><?php esc_html_e('Table rates Updated', 'dc-woocommerce-multi-vendor') ?></strong></p>
        </div>
        <?php
    }

    public function delete_table_rate_shipping_row() {
        global $wpdb;
        if (is_array($_POST['rate_id'])) {
            $rate_ids = array_map('intval', $_POST['rate_id']);
        } else {
            $rate_ids = array(intval($_POST['rate_id']));
        }

        if (!empty($rate_ids)) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE rate_id IN (" . implode(',', $rate_ids) . ")");
        }
        die;
    }

    public function save_wcmp_table_rate_shipping($postedData) {
        global $wpdb;
        if (isset($postedData['settings'])) {
            $wcmp_table_rate = array();
            $new_index = 0;
            $struc_arr = array();
            $shipping_class_id = $shipping_method_id = 0;
            foreach ($postedData['settings'] as $key => $value) {
                if (strpos($key, 'wcmp_table_rate') !== false) {
                    $key_arr = explode("[",$key);
                    if (count($key_arr) > 2) {
                        foreach ($key_arr as $index => $struc) {
                            $subkey = preg_replace('/[^a-zA-Z0-9_]/', '', $struc);
                            if ($index == 0) {
                                continue;
                            } elseif ($index == 1) {
                                $new_index = $subkey;
                            } else {
                                $struc_arr[$subkey] = $value;
                            }
                            $wcmp_table_rate[$new_index] = $struc_arr;
                        }
                    } else {
                        foreach ($value as $index => $struc) {
                            $subkey = preg_replace('/[^a-zA-Z0-9_]/', '', $index);
                            $struc_arr[$subkey] = $struc;
                            $wcmp_table_rate[$key_arr[1]] = $struc_arr;
                        }
                    }
                } elseif ( $key == 'shipping_method_id') {
                    $shipping_method_id = $value;
                } elseif ( $key == 'shipping_class_id') {
                    $shipping_class_id = $value;
                }
            }
            if ($wcmp_table_rate) {
                // Clear cache
                $wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_ship_%')" );
                $wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_shipping-transient-version')" );

                foreach ($wcmp_table_rate as $data) {
                    $rate_id = $data['rate_id'];
                    $rate_class = $shipping_class_id;
                    $rate_condition = isset($data['rate_condition']) ? $data['rate_condition'] : '';
                    $rate_min = isset($data['rate_min']) ? $data['rate_min'] : '';
                    $rate_max = isset($data['rate_max']) ? $data['rate_max'] : '';
                    $rate_priority = isset($data['rate_priority']) ? 1 : 0;
                    $rate_abort = isset($data['rate_abort']) ? 1 : 0;
                    $rate_cost = isset($data['rate_cost']) ? rtrim(rtrim(number_format((double) $data['rate_cost'], 4, '.', ''), '0'), '.') : '';
                    $rate_cost_per_item = isset($data['rate_cost_per_item']) ? rtrim(rtrim(number_format((double) $data['rate_cost_per_item'], 4, '.', ''), '0'), '.') : '';
                    $rate_cost_per_weight_unit = isset($data['rate_cost_per_weight_unit']) ? rtrim(rtrim(number_format((double) $data['rate_cost_per_weight_unit'], 4, '.', ''), '0'), '.') : '';
                    $rate_cost_percent = isset($data['rate_cost_percent']) ? rtrim(rtrim(number_format((double) str_replace('%', '', $data['rate_cost_percent']), 2, '.', ''), '0'), '.') : '';
                    $rate_label = isset($data['rate_label']) ? $data['rate_label'] : '';
                    if ($rate_id > 0) {
                        $wpdb->update(
                                $wpdb->prefix . 'woocommerce_shipping_table_rates', array(
                            'rate_condition' => sanitize_title($rate_condition),
                            'rate_min' => $rate_min,
                            'rate_max' => $rate_max,
                            'rate_cost' => $rate_cost,
                            'rate_cost_per_item' => $rate_cost_per_item,
                            'rate_cost_per_weight_unit' => $rate_cost_per_weight_unit,
                            'rate_cost_percent' => $rate_cost_percent,
                            'rate_label' => $rate_label,
                            'rate_priority' => $rate_priority,
                            'rate_abort' => $rate_abort,
                                ), array(
                            'rate_id' => $rate_id
                                ), array(
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%d'
                                ), array(
                            '%d'
                                )
                        );
                    } else {
                        $wpdb->insert("{$wpdb->prefix}woocommerce_shipping_table_rates", array(
                            'rate_class' => $rate_class,
                            'rate_condition' => sanitize_title($rate_condition),
                            'rate_min' => $rate_min,
                            'rate_max' => $rate_max,
                            'rate_priority' => $rate_priority,
                            'rate_abort' => $rate_abort,
                            'rate_cost' => $rate_cost,
                            'rate_cost_per_item' => $rate_cost_per_item,
                            'rate_cost_per_weight_unit' => $rate_cost_per_weight_unit,
                            'rate_cost_percent' => $rate_cost_percent,
                            'shipping_method_id' => $shipping_method_id,
                            'rate_label' => $rate_label
                                ), array(
                            '%d',
                            '%s',
                            '%d',
                            '%d',
                            '%d',
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%s'
                                )
                        );
                    }
                }
                wc_add_notice(__('Table rates Updated', 'dc-woocommerce-multi-vendor'), 'success');
            }
        }
    }
    
    public function frontend_styles($is_vendor_dashboard) {
        global $WCMp;
        $frontend_style_path = $WCMp->plugin_url . 'packages/wcmp-tablerate/assets/frontend/';
        $frontend_style_path = str_replace(array('http:', 'https:'), '', $frontend_style_path);
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script( 'wcmp_advanced_shipping_frontend', $frontend_style_path . 'js/frontend' . $suffix . '.js', array( 'jquery' ), $WCMp->version);
		wp_enqueue_script( 'wcmp_advanced_shipping', $WCMp->plugin_url . 'packages/wcmp-tablerate/assets/global/js/advance-shipping.js', array( 'jquery' ), $WCMp->version);
        $WCMp->localize_script('wcmp_advanced_shipping');
        wp_register_style('wcmp_as_frontend', $frontend_style_path . 'css/frontend' . $suffix . '.css', array(), $WCMp->version);
        wp_enqueue_style('wcmp_as_frontend');
    }

    // Hide table rate when no rates are found
    public function wcmp_hide_table_rate_when_disabled( $rates, $package ) {
        $table_rate = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( 'wcmp_vendor_shipping' === $rate->method_id  && strpos($rate->id, "table_rate") !== false ) {
                unset($rates);
            } else {
                $table_rate[ $rate_id ] = $rate;
            }
        }
        return !empty( $table_rate ) ? $table_rate : $rates;
    }

    public function wcmp_table_rate_toggle_shipping_method() {
        global $WCMp, $wpdb;
        $instance_id = isset($_POST['instance_id']) ? wc_clean($_POST['instance_id']) : 0;
        $zone_id = isset($_POST['zoneID']) ? wc_clean($_POST['zoneID']) : 0;
        $checked_data = isset($_POST['checked']) ? wc_clean($_POST['checked']) : '';
        $find_method_id_by_instance = $wpdb->get_results($wpdb->prepare("SELECT method_id FROM {$wpdb->prefix}wcmp_shipping_zone_methods WHERE `instance_id` = %d", $instance_id) );
        if ( !empty($find_method_id_by_instance) && $find_method_id_by_instance[0]->method_id == 'table_rate' ) {
            $shipping_class_id = get_user_meta(get_current_vendor_id(), 'shipping_class_id', true);
            $table_rates = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE `rate_class` = %d order by 'shipping_method_id' ", $shipping_class_id, OBJECT));
            if (!empty($table_rates)) {
                foreach ($table_rates as $key => $value) {
                    $checked = ( $_POST['checked'] == 'true' ) ? 0 : 1;
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_shipping_table_rates SET rate_abort = %d WHERE rate_class = %d", $checked, $value->rate_class ) );
                }
            }
        }
        $data = array(
            'instance_id' => $instance_id,
            'zone_id' => $zone_id,
            'checked' => ( $checked_data == 'true' ) ? 1 : 0
        );
        if ( !class_exists( 'WCMP_Shipping_Zone' ) ) {
            $WCMp->load_vendor_shipping();
        }
        $result = WCMP_Shipping_Zone::toggle_shipping_method($data);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        $message = $data['checked'] ? __('Shipping method enabled successfully', 'dc-woocommerce-multi-vendor') : __('Shipping method disabled successfully', 'dc-woocommerce-multi-vendor');
        wp_send_json_success($message);
    }

    public function add_fields_wcmp_vendor_shipping_methods($vendor_shippings) {
        $vendor_shippings['table_rate'] = __('Table Rates', 'dc-woocommerce-multi-vendor');
        return $vendor_shippings;
    }

    public function output_wcmps_advance_shipping_table_rate($shipping_method, $postdata) { 
        ?>
        <div id="wrapper-<?php echo esc_attr($shipping_method['id']) ?>">
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php esc_html_e( 'Method Title', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="method_title_lp" class="form-control" type="text" name="title" value="<?php echo isset($shipping_method['title']) ? esc_attr($shipping_method['title']) : ''; ?>" placholder="<?php esc_attr_e('Enter method title', 'dc-woocommerce-multi-vendor'); ?>">
                </div>
            </div>
             
          <input type="hidden" id="method_description_lp" name="description" value="<?php echo isset($shipping_method['settings']['description']) ? esc_attr($shipping_method['settings']['description']) : ''; ?>" />
         </div> 
         <?php
         $this->wcmp_advance_shipping_template_table_rate($shipping_method, $postdata);
     }

     public function wcmp_advance_shipping_template_table_rate($shipping_method, $postdata) {
        global $wpdb;
     	$vendor_user_id = get_current_user_id();
     	$shipping_class_id = get_user_meta($vendor_user_id, 'shipping_class_id', true) ? get_user_meta($vendor_user_id, 'shipping_class_id', true) : 0;
     	if (!$shipping_class_id) {
     		$shipping_term = get_term_by('slug', $vendor_data->user_data->user_login . '-' . $vendor_user_id, 'product_shipping_class', ARRAY_A);
     		if (!$shipping_term) {
     			$shipping_term = wp_insert_term($vendor_data->user_data->user_login . '-' . $vendor_user_id, 'product_shipping_class');
     		}
     		if (!is_wp_error($shipping_term)) {
     			$shipping_term_id = $shipping_term['term_id'];
     			update_user_meta($vendor_user_id, 'shipping_class_id', absint($shipping_term['term_id']));
     			add_woocommerce_term_meta($shipping_term['term_id'], 'vendor_id', $vendor_user_id);
     			add_woocommerce_term_meta($shipping_term['term_id'], 'vendor_shipping_origin', get_option('woocommerce_default_country'));
     		}
     	}
     	$shipping_class_id = $shipping_term_id = get_user_meta($vendor_user_id, 'shipping_class_id', true) ? get_user_meta($vendor_user_id, 'shipping_class_id', true) : 0;
     	$raw_zones = WC_Shipping_Zones::get_zones();
     	$raw_zones[] = array('id' => 0);
     	$zone_id = isset($postdata['zoneId']) ? absint($postdata['zoneId']) : 0;
     	$zone = new WC_Shipping_Zone($zone_id);
     	$raw_methods = $zone->get_shipping_methods();
     	foreach ($raw_methods as $raw_method) {
     		if ($raw_method->id == 'table_rate') {
     			$table_rates = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE `rate_class` = %d AND `shipping_method_id` = %d order by 'shipping_method_id' ", $shipping_class_id, $raw_method->instance_id, OBJECT));
     			?>
     			<input type="hidden" name="shipping_method_id" value="<?php echo esc_attr($raw_method->instance_id); ?>" />
     			<input type="hidden" name="shipping_class_id" value="<?php echo esc_attr($shipping_class_id); ?>" />
     			<div class="panel panel-default pannel-outer-heading">
     				<div class="panel-heading">
     					<h3 class="wcmp_black_headding"><?php esc_html_e('Table Rates: ', 'dc-woocommerce-multi-vendor'); echo $zone->get_zone_name(); ?></h3>
     				</div>
     				<div class="wcmp_table_holder panel-body">
     					<table class="table table-bordered responsive-table wcmp_table_rate_shipping widefat striped">
     						<thead>
     							<tr>
     								<th><?php esc_html_e('Select Shipping', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th width="126"><?php esc_html_e('Condition', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Min', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Max', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Break', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Abort', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Row cost', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Item cost', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Kg cost', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('% cost', 'dc-woocommerce-multi-vendor'); ?></th>
     								<th><?php esc_html_e('Label', 'dc-woocommerce-multi-vendor'); ?></th>
     							</tr>
     						</thead>
     						<tbody>
     							<?php
     							if (count($table_rates) > 0) {
     								foreach ($table_rates as $table_rate) {
     									$shipping_method_id = $raw_method->instance_id;
     									$option_table = $table_rate;
     									$this->wcmp_advance_shipping_template_table_rate_item($option_table, $shipping_method_id);
     								}
     							} else {
     								$option = new stdClass();
     								$option->rate_id = '';
     								$option->rate_class = $shipping_class_id;
     								$option->rate_condition = '';
     								$option->rate_min = '';
     								$option->rate_max = '';
     								$option->rate_priority = 0;
     								$option->rate_abort = 0;
     								$option->rate_cost = '';
     								$option->rate_cost_per_item = '';
     								$option->rate_cost_per_weight_unit = '';
     								$option->rate_cost_percent = '';
     								$option->rate_label = '';
     								$shipping_method_id = $raw_method->instance_id;
     								$option_table = $option;
     								$this->wcmp_advance_shipping_template_table_rate_item($option_table, $shipping_method_id);
     							}
     							?>
     						</tbody>
     						<tfoot>
     							<tr>
     								<td colspan="5">
     									<button type="button" class="wcmp_add_tablerate_item btn btn-default"><?php esc_html_e('Add Shipping Rate', 'dc-woocommerce-multi-vendor') ?></button>
     								</td>
     								<td colspan="6">
     									<button style="float: right;" type="button" name="wcmp_remove_table_rate_item" class="wcmp_remove_table_rate_item btn btn-default"><?php esc_html_e('Delete selected rows', 'dc-woocommerce-multi-vendor') ?></button>
     								</td>
     							</tr>
     						</tfoot>
     					</table>
     				</div>
     			</div>
     			<?php
     		}
		}
     }

     public function wcmps_advance_shipping_table_rate($settings_html, $user_id, $zone_id, $vendor_shipping_method) { 
        global $wpdb;
        $shipping_class_id = get_user_meta($user_id, 'shipping_class_id', true) ? get_user_meta($user_id, 'shipping_class_id', true) : 0;
        $zone = new WC_Shipping_Zone($zone_id);
        $raw_methods = $zone->get_shipping_methods();
        foreach ($raw_methods as $raw_method) {
            if ($raw_method->id == 'table_rate') {
                $settings_html = '<!-- Table Rates -->'
                    . '<div class="shipping_form" id="'.$vendor_shipping_method['id'].'">'  
                    .'<input type="hidden" id="method_description_lp" name="description" value="'.$vendor_shipping_method['settings']['description'].'" />'
                     . '<div class="form-group">'
                    . '<label for="" class="control-label col-sm-3 col-md-3">'.__( 'Method Title', 'dc-woocommerce-multi-vendor' ).'</label>'
                    . '<div class="col-md-9 col-sm-9">'
                    . '<input id="method_title_fs" class="form-control" type="text" name="title" value="'.$vendor_shipping_method['title'].'" placholder="'.__( 'Enter method title', 'dc-woocommerce-multi-vendor' ).'">'
                    . '</div></div>'
                    . '<!--div class="form-group">'
                    . '<label for="" class="control-label col-sm-3 col-md-3">'.__( 'Description', 'dc-woocommerce-multi-vendor' ).'</label>'
                    . '<div class="col-md-9 col-sm-9">'
                    . '<textarea id="method_description_lp" class="form-control" name="method_description">'.$vendor_shipping_method['settings']['description'].'</textarea>'
                    . '</div></div--></div>'
                     .'<input type="hidden" name="shipping_method_id" value="'.$raw_method->instance_id.'" />'
                   .' <input type="hidden" name="shipping_class_id" value="'.$shipping_class_id.'" />'
                    .'<div class="panel panel-default pannel-outer-heading">'
                    .' <div class="panel-heading">'
                    .'<h3 class="wcmp_black_headding">'. __('Table Rates: ', 'dc-woocommerce-multi-vendor') . $zone->get_zone_name() .'</h3>'
                    .'</div>'
                    . '<div class="wcmp_table_holder panel-body">'
                    . '<table class="table table-bordered responsive-table wcmp_table_rate_shipping widefat striped">'
                    .'<thead><tr>'.'<th>'
                    . __('Select Shipping', 'dc-woocommerce-multi-vendor') .'</th>'
                    .'<th width="126">'. __('Condition', 'dc-woocommerce-multi-vendor').'</th>'
                    .'<th>'. __('Min', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'. __('Max', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'. __('Break', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'. __('Abort', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'. __('Row cost', 'dc-woocommerce-multi-vendor').'</th>'
                        .'<th>'.__('Item cost', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'.__('Kg cost', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'. __('% cost', 'dc-woocommerce-multi-vendor') .'</th>'
                        .'<th>'. __('Label', 'dc-woocommerce-multi-vendor') .'</th>'
                    .'</tr></thead>'
                    .'<tbody>';
                $table_rates = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE `rate_class` = %d AND `shipping_method_id` = %d order by 'shipping_method_id' ", $shipping_class_id, $raw_method->instance_id, OBJECT) );
                if ( $table_rates ) {
                    foreach ($table_rates as $table_rate) {
                        ob_start();
                        $shipping_method_id = $raw_method->instance_id;
                        $option_table = $table_rate;
                        $this->wcmp_advance_shipping_template_table_rate_item($option_table, $shipping_method_id);
                        $item_row = ob_get_clean();
                        $settings_html .= $item_row;
                    }
                } else {
                    ob_start();
                    $option = new stdClass();
                    $option->rate_id = '';
                    $option->rate_class = $shipping_class_id;
                    $option->rate_condition = '';
                    $option->rate_min = '';
                    $option->rate_max = '';
                    $option->rate_priority = 0;
                    $option->rate_abort = 0;
                    $option->rate_cost = '';
                    $option->rate_cost_per_item = '';
                    $option->rate_cost_per_weight_unit = '';
                    $option->rate_cost_percent = '';
                    $option->rate_label = '';
                    $shipping_method_id = $raw_method->instance_id;
                    $option = $option;
                    $this->wcmp_advance_shipping_template_table_rate_item($option, $shipping_method_id);
                    $item_row = ob_get_clean();
                    $settings_html .= $item_row;
                }
                $settings_html .= '</tbody>' 
                        .'<tfoot><tr>'
                       .'<td colspan="5"> 
                                <button type="button" class="wcmp_add_tablerate_item btn btn-default">'. __('Add Shipping Rate', 'dc-woocommerce-multi-vendor') .'</button>
                            </td>'
                           .'<td colspan="6">
                                <button style="float: right;" type="button" name="wcmp_remove_table_rate_item" class="wcmp_remove_table_rate_item btn btn-default">'. __('Delete selected rows', 'dc-woocommerce-multi-vendor') .'</button>
                            </td>'
                       .' </tr> </tfoot>'         
                    .'</table>'
                    .'</div></div>';

                return $settings_html;
            }
        }
    }

    public function wcmp_advance_shipping_template_table_rate_item( $option = '', $shipping_method_id = 0 ) {
    	$conditions = array('' => __('None', 'dc-woocommerce-multi-vendor'), 'price' => __('Price', 'dc-woocommerce-multi-vendor'), 'weight' => __('Weight', 'dc-woocommerce-multi-vendor'), 'items' => __('Item count', 'dc-woocommerce-multi-vendor'));
    	$index = !empty( $option->rate_id ) ? absint($option->rate_id) : 0;
    	?>
    	<tr>
    		<td class="table-rate-item-select" style="vertical-align: middle; text-align: center;">
    			<input type="checkbox" name="wcmp_table_rate[<?php echo $index; ?>]['selected_rate_id']" data-name="selected_rate_id" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo $option->rate_id ?>" />
    			<input type="hidden" name="wcmp_table_rate[<?php echo $index; ?>]['rate_id']" data-name="rate_id" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo esc_attr($option->rate_id) ?>" />
    		</td>
    		<td>
    			<select name="wcmp_table_rate[<?php echo $index; ?>]['rate_condition']" onchange="toggleDisableRate(this)" data-name="rate_condition" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" class="wcmp_table_rate_condition form-control">
    				<?php
    				foreach ($conditions as $key => $condition) {
    					if ($key == $option->rate_condition) {
    						echo '<option value="' . $key . '" selected="">' . $condition . '</option>';
    					} else {
    						echo '<option value="' . $key . '">' . $condition . '</option>';
    					}
    				}
    				?>
    			</select>
    		</td>
    		<td>
    			<input type="text" class="form-control" data-name="rate_min" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" <?php if (empty($option->rate_condition)) echo 'disabled=""'; ?> value="<?php echo $option->rate_min; ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_min']" /> 
    		</td>
    		<td>
    			<input type="text" class="form-control" data-name="rate_max" data-instance_id="<?php echo $shipping_method_id; ?>" <?php if (empty($option->rate_condition)) echo 'disabled=""'; ?> value="<?php echo esc_attr($option->rate_max); ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_max']" />
    		</td>
    		<td style="vertical-align: middle; text-align: center;">
    			<input type="checkbox" name="wcmp_table_rate[<?php echo $index; ?>]['rate_priority']" data-name="rate_priority" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="1" <?php if ($option->rate_priority) echo "checked"; ?> />
    		</td>
    		<td style="vertical-align: middle; text-align: center;">
    			<input type="checkbox" name="wcmp_table_rate[<?php echo $index; ?>]['rate_abort']" data-name="rate_abort" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="1" <?php if ($option->rate_abort) echo "checked"; ?>/>
    		</td>
    		<td colspan="4" class="abort_reason" <?php if (!$option->rate_abort) echo 'style="display:none;"';?> >
    			<input type="text" class="form-control full-width" data-name="rate_abort_reason" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php if (isset($option->rate_abort_reason) && !empty($option->rate_abort_reason)) echo $option->rate_abort_reason ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_abort_reason']" />
    		</td>
    		<td class="cost" <?php if ($option->rate_abort) echo 'style="display:none;"';?> >
    			<input type="text" class="form-control" data-name="rate_cost" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo $option->rate_cost ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_cost']" />
    		</td>
    		<td class="cost" <?php if ($option->rate_abort) echo 'style="display:none;"';?> >
    			<input type="text" class="form-control" data-name="rate_cost_per_item" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo $option->rate_cost_per_item ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_cost_per_item']" />
    		</td>
    		<td class="cost" <?php if ($option->rate_abort) echo 'style="display:none;"';?> >
    			<input type="text" class="form-control" data-name="rate_cost_per_weight_unit" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo $option->rate_cost_per_weight_unit ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_cost_per_weight_unit']" />
    		</td>
    		<td class="cost" <?php if ($option->rate_abort) echo 'style="display:none;"';?> >
    			<input type="text" class="form-control" data-name="rate_cost_percent" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo $option->rate_cost_percent ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_cost_percent']" />
    		</td>
    		<td>
    			<input type="text" class="form-control" data-name="rate_label" data-instance_id="<?php echo esc_attr($shipping_method_id); ?>" value="<?php echo $option->rate_label ?>" name="wcmp_table_rate[<?php echo $index; ?>]['rate_label']" />
    		</td>
    	</tr>
    	<?php
    }
    
    public function is_wcmp_table_rate_shipping_enable($is_shipping_enable, $is_enable) {
        $is_enable_table_rate = false;
        $raw_zones = WC_Shipping_Zones::get_zones();
        $raw_zones[] = array('id' => 0);
        if ($raw_zones) {
            foreach ($raw_zones as $raw_zone) {
                $zone = new WC_Shipping_Zone($raw_zone['id']);
                $raw_methods = $zone->get_shipping_methods();
                foreach ($raw_methods as $raw_method) {
                    if ($raw_method->id == 'table_rate') {
                        $is_enable_table_rate = true;
                    }
                }
            }
        }
        if ($is_enable && $is_enable_table_rate) {
            $is_shipping_enable = true;
        }
        return $is_shipping_enable;
    }
}

WCMp_Tablerate::instance_wcmp()->init();