<?php
/**
 * WCMp Marketplace Vendor Store Setup Class
 * 
 * @since 3.6
 * @author WC Marketplace
 */
if (!defined('ABSPATH')) {
    exit;
}

class WCMp_Migrator {

	/** @var string Currenct Step */
	private $step = '';

	/** @var array Steps for the setup wizard */
	private $steps = array();

	public $is_marketplace;
	public $wcmp_migration_marketplace;

	public function __construct() {
		$this->is_marketplace = $this->wcmp_is_marketplace();
		if( $this->is_marketplace ) {
			if ( is_admin() ) {
				$current_page = filter_input( INPUT_GET, 'page' );
				if ( $current_page && $current_page == 'wcmp-migrator' ) {
					add_action( 'init', array( &$this, 'init_migration' ) );
					add_action( 'admin_menu', array( $this, 'wcmp_admin_menus' ) );
					add_action( 'admin_init', array( $this, 'wcmp_migration' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'wcmp_active_notice' ) );
				}
			}
			// set cron schedule for cron job
			add_filter('cron_schedules', array($this, 'add_wcmp_migrate_corn_schedule'));
			if (!wp_next_scheduled('migrate_multivendor_order_table') ) {
				wp_schedule_event(time(), 'inevery_1minute', 'migrate_multivendor_order_table');
			}
			add_action('migrate_multivendor_order_table', array($this, 'migrate_multivendor_order_table'));	
		}
	}

	public function wcmp_active_notice() {
		?>
		<div id="message" class="notice notice-warning">
		<p><?php printf( __( '%sWCMp Marketplace Migrator is active.%s Do you want to run migration, then %sclick here%s.', 'dc-woocommerce-multi-vendor' ), '<strong>', '</strong>', '<a href="'.admin_url( 'index.php?page=wcmp-migrator' ).'">', '</a>' ); ?></p>
		</div>
		<?php
	}

	public function init_migration() {
		if( $this->is_marketplace ) {
			$this->load_class( $this->is_marketplace );
			if( $this->is_marketplace == 'wcvendors' ) $this->wcmp_migration_marketplace = new WCMp_WCVendors();
			elseif( $this->is_marketplace == 'wcfmmarketplace' ) $this->wcmp_migration_marketplace = new WCMp_WCfmMarketplace();
			elseif( $this->is_marketplace == 'wcpvendors' ) $this->wcmp_migration_marketplace = new WCMp_WCPVendors();
			elseif( $this->is_marketplace == 'dokan' ) $this->wcmp_migration_marketplace = new WCMp_Dokan();
		}
	}

	public function load_class($class_name = '') {
		global $WCMp;
        if ('' != $class_name) {
            require_once ($WCMp->plugin_path . 'classes/migration/class-' . esc_attr($WCMp->token) . '-' . esc_attr($class_name) . '.php');
        } // End If Statement
	}

	function add_wcmp_migrate_corn_schedule($schedules) {
	    $schedules['inevery_1minute'] = array(
	            'interval' => 1*60, // in seconds
	            'display'  => __( 'Every 1 minute', 'dc-woocommerce-multi-vendor' )
	    );
	    
	    return $schedules;
	}

	function migrate_multivendor_order_table() {
		$this->init_migration();
		$this->wcmp_migration_marketplace->store_order_migrate();
	}

	/**
	 * Add admin menus/screens.
	 */
	public function wcmp_admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'wcmp-migrator', '' );
	}
	
	/**
	 * Show the setup wizard.
	 */
	public function wcmp_migration() {
		if ( filter_input(INPUT_GET, 'page') != 'wcmp-migrator') {
			return;
		}
		
		$default_steps = array(
			'introduction' => array(
				'name' => __('Introduction', 'dc-woocommerce-multi-vendor' ),
				'view' => array($this, 'wcmp_migration_introduction'),
				'handler' => '',
			),
			'store-stat' => array(
				'name' => __('Store Stat', 'dc-woocommerce-multi-vendor'),
				'view' => array($this, 'wcmp_migration_store_stat'),
				'handler' => ''
			),
			'store-process' => array(
				'name' => __('Processing', 'dc-woocommerce-multi-vendor'),
				'view' => array($this, 'wcmp_migration_store_process'),
				'handler' => array($this, 'wcmp_migration_store_process_save')
			),
			'next_steps' => array(
				'name' => __('Complete!', 'dc-woocommerce-multi-vendor'),
				'view' => array($this, 'wcmp_migration_complete'),
				'handler' => '',
			),
		);
		
		$this->steps = apply_filters('wcmp_migration_steps', $default_steps);
		$current_step = filter_input(INPUT_GET, 'step');
		$this->step = $current_step ? sanitize_key($current_step) : current(array_keys($this->steps));
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script('jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array('jquery'), '2.70', true);
		
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array('dashicons', 'install'), WC_VERSION);

		if (!empty($_POST['save_step']) && isset($this->steps[$this->step]['handler'])) {
				call_user_func($this->steps[$this->step]['handler'], $this);
		}

		ob_start();
		$this->wcmp_migration_header();
		$this->wcmp_migration_steps();
		$this->wcmp_migration_content();
		$this->wcmp_migration_footer();
		exit();
	}

	/**
	 * Get the URL for the next step's screen.
	 * @param string step   slug (default: current step)
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 * @since 1.0.0
	 */
	public function get_next_step_link($step = '') {
		if (!$step) {
			$step = $this->step;
		}

		$keys = array_keys($this->steps);
		if (end($keys) === $step) {
			return admin_url();
		}

		$step_index = array_search($step, $keys);
		if (false === $step_index) {
			return '';
		}

		return add_query_arg('step', $keys[$step_index + 1]);
	}

	/**
	 * Setup Wizard Header.
	 */
	public function wcmp_migration_header() {
		set_current_screen();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php esc_html_e('WCMp Marketplace Migration', 'dc-woocommerce-multi-vendor'); ?></title>
				<?php wp_print_scripts('jquery-blockui'); ?>
				<?php
				?>
				<?php do_action('admin_print_scripts'); ?>
				<?php do_action('admin_print_styles'); ?>
				<?php do_action('admin_head'); ?>
				<style type="text/css">
					.wc-setup-steps {
						justify-content: center;
					}
				</style>
			</head>
			<body class="wc-setup wp-core-ui">

			 <h1 id="wc-logo"><a target="_blank" href="<?php echo site_url(); ?>"><img src="<?php echo trailingslashit(plugins_url('dc-woocommerce-multi-vendor')); ?>assets/images/wc-marketplace.png" alt="<?php echo get_bloginfo('title'); ?>" /></a></h1>
			<?php
	}

	/**
	 * Output the steps.
	 */
	public function wcmp_migration_steps() {
		$ouput_steps = $this->steps;
		array_shift($ouput_steps);
		?>
		<ol class="wc-setup-steps">
			<?php foreach ($ouput_steps as $step_key => $step) : ?>
			  <li class="<?php
					if ($step_key === $this->step) {
							echo 'active';
					} elseif (array_search($this->step, array_keys($this->steps)) > array_search($step_key, array_keys($this->steps))) {
							echo 'done';
					}
					?>">
					<?php echo esc_html($step['name']); ?>
				</li>
		<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function wcmp_migration_content() {
		echo '<div class="wc-setup-content">';
		call_user_func($this->steps[$this->step]['view'], $this);
		echo '</div>';
	}

	/**
	 * Introduction step.
	 */
	public function wcmp_migration_introduction() {
		?>
		<h1><?php printf( __("Welcome to %s migration!", 'dc-woocommerce-multi-vendor'), 'WCMp Marketplace' ); ?></h1>
			<p><?php printf( __('Before we start please read this carefully -', 'dc-woocommerce-multi-vendor') ); ?></p>
			<ul>
				<li><?php esc_html_e("You have already kept backup of your site's Database.", 'dc-woocommerce-multi-vendor'); ?></li>
				<li><?php esc_html_e("Disabled caching and optimize plugins.", 'dc-woocommerce-multi-vendor'); ?></li>
				<li><?php esc_html_e("Your previous multi-vendor plugin installed and activated.", 'dc-woocommerce-multi-vendor'); ?></li>
				<li><?php esc_html_e("WC Marketplace installed and activated.", 'dc-woocommerce-multi-vendor'); ?></li>
				<li><?php esc_html_e("You are using a non-interrupted internet connection.", 'dc-woocommerce-multi-vendor'); ?></li>
			</ul>
			<p style="color: #ff9900;  font-weight: 400;">*<?php printf( __("This migration tool only for migrating vendors as well as product, marketplace orders will not migrated using this. Orders will migrated using cron schedule. Which will run in every one minute.", 'dc-woocommerce-multi-vendor') ); ?></p>
			<p style="color: #ff4500;  font-weight: 400;">**<?php printf( __("Vendor's shipping setting will not migrate as WC Marketplace vendor shipping totally different than your previous multi-vendor plugin!", 'dc-woocommerce-multi-vendor') ); ?></p>
			<p style="color: #ff4500;  font-weight: 400;">**<?php printf( __("Deleted orders or deleted product's orders will not be migrated!", 'dc-woocommerce-multi-vendor') ); ?></p>
			<p style="color: #bb0000;  font-weight: 600;">***<?php printf( __('Never close this browser tab when migration is running.', 'dc-woocommerce-multi-vendor') ); ?></p>
			<p><?php printf( __('We are ready, are you?', 'dc-woocommerce-multi-vendor') ); ?></p>
			<p class="wc-setup-actions step">
				<a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="button-primary button button-large button-next"><?php esc_html_e("Let's go!", 'dc-woocommerce-multi-vendor'); ?></a>
				<a href="<?php echo esc_url(admin_url('admin.php?page=vendors')); ?>" class="button button-large"><?php esc_html_e('Not right now', 'dc-woocommerce-multi-vendor'); ?></a>
			</p>
		<?php
		
	}

	/**
	 * Store stat content
	 */
	public function wcmp_migration_store_stat() {

		$marketplace_get_vendors = $this->wcmp_migration_marketplace->get_marketplace_vendor();

		update_option( '_wcmp_vendor_count', count($marketplace_get_vendors) );
		update_option( '_wcmp_migrated_vendor_count', 0 );

		?>
		<h1><?php printf( __("Multi-vendor Store Stat!", 'dc-woocommerce-multi-vendor') ); ?></h1>
		<ul>
			<li><?php printf( __("Multi-vendor Plugin - %s", 'dc-woocommerce-multi-vendor'), 'WCMp Marketplace' ); ?></li>
			<li><?php printf( __("Vendors - %s", 'dc-woocommerce-multi-vendor'), count($marketplace_get_vendors) ); ?></li>
		</ul>
		<?php if( count($marketplace_get_vendors) > 0 ) { ?>
			<p><?php printf( __('You are about to start migration!', 'dc-woocommerce-multi-vendor') ); ?></p>
			<p style="color: #bb0000;  font-weight: 600;">***<?php printf( __('Never close this browser tab when migration is running.', 'dc-woocommerce-multi-vendor') ); ?></p>
			<p class="wc-setup-actions step">
				<a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="button-primary button button-large button-next"><?php esc_html_e("Start Migration", 'dc-woocommerce-multi-vendor'); ?></a>
				<a href="<?php echo esc_url(admin_url('admin.php?page=vendors')); ?>" class="button button-large"><?php esc_html_e('Not right now', 'dc-woocommerce-multi-vendor'); ?></a>
			</p>
		<?php } else { ?>
			<p style="color: #ff4500;  font-weight: 400;"><?php printf( __('You have no vendor, no migration require!', 'dc-woocommerce-multi-vendor') ); ?></p>
			<p class="wc-setup-actions step">
				<a href="<?php echo esc_url(admin_url('admin.php?page=vendors')); ?>" class="button button-large"><?php esc_html_e('Get me out from this', 'dc-woocommerce-multi-vendor'); ?></a>
			</p>
		<?php } ?>
		<?php
	}
	
	/**
	 * Store Processing content
	 */
	public function wcmp_migration_store_process() {

		$processed_vendors = absint( get_option( '_wcmp_migrated_vendor_count', 0 ) );
		$migration_step = get_option( '_wcmp_migration_step', '' );

		$marketplace_get_vendors = $this->wcmp_migration_marketplace->get_marketplace_vendor();
		if( !empty( $marketplace_get_vendors ) ) {
			foreach( $marketplace_get_vendors as $vendor_id => $marketplace_vendor ) {
				?>
				<h1><?php printf( __("Migrating Store: #%s", 'dc-woocommerce-multi-vendor'), $vendor_id. ' ' . $marketplace_vendor->ID ); ?></h1>
				<script>
				  jQuery(document).ready(function($) {
				  		$('.wc-setup-content').block({
								message: null,
								overlayCSS: {
									background: '#fff',
									opacity: 0.6
								}
							});
				  });
				</script>
				<style>
				 .processing_box{padding: 0px 20px 20px 20px; border:1px solid #ccc; border-radius: 5px; margin-bottom: 20px;}
				 .processing_message{}
				 .processing_box_icon{font-size:15px;margin-right:10px;color:#111;}
				 .processing_box_status{font-size:25px;margin-left:10px;color:#00798b;}
				</style>
				<div class="processing_box processing_box_setting">
				  <?php if( !$migration_step ) { ?>
						<p class="setting_process processing_message" style=""><span class="fa fa-gear processing_box_icon"></span><?php printf( __('Store setting migrating ....', 'dc-woocommerce-multi-vendor') ); ?></p>
						<?php 
						$setting_complete = $this->store_setting_migrate(); 
						if( $setting_complete ) {
							$migration_step = 'setting';
							update_option( '_wcmp_migration_step', 'setting' );
						?>
							<style>
								.setting_process{display:none;}
							</style>
							<p class="setting_complete processing_message" style=""><span class="fa fa-gear processing_box_icon"></span><?php printf( __('Store setting migration complete', 'dc-woocommerce-multi-vendor') ); ?><span class="fa fa-check-square-o processing_box_status"></span></p>
						<?php } ?>
					<?php } else { ?>
						<p class="setting_complete processing_message" style=""><span class="fa fa-gear processing_box_icon"></span><?php printf( __('Store setting migration complete', 'dc-woocommerce-multi-vendor') ); ?><span class="fa fa-check-square-o processing_box_status"></span></p>
					<?php } ?>
				</div>
				
				<div class="processing_box processing_box_product">
				  <?php if( $migration_step == 'setting' ) { ?>
						<p class="product_process processing_message" style=""><span class="fa fa-cube processing_box_icon"></span><?php printf( __('Store product and vendor migrating ....', 'dc-woocommerce-multi-vendor') ); ?></p>
						<?php 
						$product_complete = $this->wcmp_migration_marketplace->store_product_vendor_migrate($marketplace_vendor->ID); 
						if( $product_complete ) {
							$migration_step = 'product';
							update_option( '_wcmp_migration_step', 'product' );
						?>
							<style>
								.product_process{display:none;}
							</style>
							<p class="product_complete processing_message" style=""><span class="fa fa-cube processing_box_icon"></span><?php printf( __('Store product migration complete', 'dc-woocommerce-multi-vendor') ); ?><span class="fa fa-check-square-o processing_box_status"></span></p>
						<?php } ?>
					<?php } else { ?>
						<p class="product_complete processing_message" style=""><span class="fa fa-cube processing_box_icon"></span><?php printf( __('Store product migration complete', 'dc-woocommerce-multi-vendor') ); ?><span class="fa fa-check-square-o processing_box_status"></span></p>
					<?php } ?>
				</div>
				
				<?php
				$processed_vendors++;
				update_option( '_wcmp_migrated_vendor_count', $processed_vendors );
				update_option( '_wcmp_migrated_last_vendor', $vendor_id );
				update_option( '_wcmp_migration_step', '' );
				?>
				<script>
				setTimeout(function() {
				  window.location = window.location.href;
				}, 1000);
				</script>
				<?php
			}
		} else {
			wp_safe_redirect($this->get_next_step_link());
		}
	}
	
	public function store_setting_migrate() {
		return true;
	}

	/**
	 * Ready to go content
	 */
	public function wcmp_migration_complete() {
		?>
		<h1><?php esc_html_e('We are done!', 'dc-woocommerce-multi-vendor'); ?></h1>
		<p><?php esc_html_e("Your store is successfully migrated ( Except orders. Whenever your all vendor and product migrated then it will automaticly generated vendor orders. Please wait for while ). It's time to experience the things more Easily and Peacefully.", 'dc-woocommerce-multi-vendor'); ?></p>
		</div>
		<div class="wc-setup-next-steps">
		  <p class="wc-setup-actions step">
			  <a class="button button-primary button-large" href="<?php echo esc_url(admin_url('admin.php?page=vendors')); ?>"><?php esc_html_e( "Let's go to Dashboard", 'dc-woocommerce-multi-vendor' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function wcmp_migration_footer() {
			?></body>
			<?php do_action('admin_footer'); ?>
		</html>
		<?php
	}


	function wcmp_get_products_by_vendor( $vendor_id = 0, $post_status = 'any', $custom_args = array() ) {
		$vendor_product_list = array();

		if( !$vendor_id ) return $vendor_product_list;
		$vendor_id = absint( $vendor_id );

		if( $post_status == 'any' ) { $post_status = array('draft', 'pending', 'publish', 'private'); }

		$post_count = 9999;
		$post_loop_offset = 0;
		$products_arr = array(0);
		while( $post_loop_offset < $post_count ) {
			$args = array(
				'posts_per_page'   => apply_filters( 'wcmp_break_loop_offset', 100 ),
				'offset'           => $post_loop_offset,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'post_type'        => 'product',
				'post_status'      => $post_status,
				'suppress_filters' => 0,
				'fields'           => 'ids'
			);
			$args = array_merge( $args, $custom_args );
			$is_marketplace = $this->wcmp_is_marketplace();
			if( $is_marketplace ) {
				if( $is_marketplace == 'wcpvendors' ) {
					$args['tax_query'][] = array(
						'taxonomy' => WC_PRODUCT_VENDORS_TAXONOMY,
						'field' => 'term_id',
						'terms' => $vendor_id,
					);
				} elseif( $is_marketplace == 'wcvendors' ) {
					$args['author'] = $vendor_id;
				} elseif( $is_marketplace == 'dokan' ) {
					$args['author'] = $vendor_id;
				} elseif( $is_marketplace == 'wcfmmarketplace' ) {
					$args['author'] = $vendor_id;
				}
			}
			$args = apply_filters( 'wcmp_products_by_vendor_args', $args );

			$vendor_products = get_posts($args);
			if( !empty( $vendor_products ) ) {
				foreach( $vendor_products as $vendor_product ) {
					$vendor_product_list[$vendor_product] = get_post( $vendor_product );
				}
				$post_loop_offset += apply_filters( 'wcmp_break_loop_offset', 100 );
			} else {
				break;
			}
		}
		return $vendor_product_list;
	}

	public function wcmp_is_marketplace() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		$is_marketplace = false;

		// WCfM Multivendor Marketplace Check
		if( !$is_marketplace )
		$is_marketplace = ( in_array( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins ) || array_key_exists( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins ) || class_exists( 'WCFMmp' ) ) ? 'wcfmmarketplace' : false;

		// WC Vendors Check
		if( !$is_marketplace )
			$is_marketplace = ( in_array( 'wc-vendors/class-wc-vendors.php', $active_plugins ) || array_key_exists( 'wc-vendors/class-wc-vendors.php', $active_plugins ) || class_exists( 'WC_Vendors' ) ) ? 'wcvendors' : false;

		// WC Product Vendors Check
		if( !$is_marketplace )
			$is_marketplace = ( in_array( 'woocommerce-product-vendors/woocommerce-product-vendors.php', $active_plugins ) || array_key_exists( 'woocommerce-product-vendors/woocommerce-product-vendors.php', $active_plugins ) ) ? 'wcpvendors' : false;

		// Dokan Lite Check
		if( !$is_marketplace )
			$is_marketplace = ( in_array( 'dokan-lite/dokan.php', $active_plugins ) || array_key_exists( 'dokan-lite/dokan.php', $active_plugins ) || class_exists( 'WeDevs_Dokan' ) ) ? 'dokan' : false;

		return $is_marketplace;
	}

}

new WCMp_Migrator();
