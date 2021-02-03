<?php

/**
 *
 * @author 		WC Marketplace
 * @package 	wcmp/core
 * @version   1.0.0
 */

class WCMp_WCPVendors {
	
	public function __construct() {}
	
	// Get all dokan vendor
	public function get_marketplace_vendor() {
		$seller_query = new WP_User_Query( array(
			'role'	  => 'wc_product_vendors_admin_vendor',
		) );
		$marketplace_get_vendors = $seller_query->get_results();
		return $marketplace_get_vendors;
	}

	// store migrate	
	public function store_product_vendor_migrate( $vendor_id ) {
		global $WCMp;
		$user = new WP_User(absint($vendor_id));
		if( !$vendor_id ) return false;
		if(!in_array('dc_vendor', $user->roles)) {
			$user->set_role('dc_vendor');
			$user->remove_cap( 'wc_product_vendors_admin_vendor');
			$vendor = get_wcmp_vendor($vendor_id);
			if (!$vendor) return false;
			$term_id = get_user_meta( $vendor_id, '_vendor_term_id', true);
			$shipping_class_id = get_user_meta( $vendor_id, 'shipping_class_id', true );
			wp_update_term( absint($term_id), 'dc_vendor_shop' );
			wp_update_term( absint($shipping_class_id), 'product_shipping_class' );

			// store vendor update
			$this->store_vendor_data_migrate($vendor_id);
			
			// commission update
			$commission_fixed   = ! empty( $wcpv_vendor_data['commission'] ) ? $wcpv_vendor_data['commission'] : get_option( 'wcpv_vendor_settings_default_commission', '0' );
			$commission_percent = ! empty( $wcpv_vendor_data['commission'] ) ? $wcpv_vendor_data['commission'] : get_option( 'wcpv_vendor_settings_default_commission', '0' );

			update_user_meta( $vendor_id, '_vendor_commission', $commission_fixed );
			update_user_meta( $vendor_id, '_vendor_commission', $commission_percent );

			// store product update 
			$this->store_product_migrate( $vendor_id, $term_id );
		}
		return true;
	}

	public function store_vendor_data_migrate( $vendor_id ) {
		$wcpv_vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );
		// social
		if (get_user_meta($vendor_id, '_twitter_profile', true )) {
			update_user_meta($vendor_id, '_vendor_twitter_profile', get_user_meta($vendor_id, '_twitter_profile', true ));
		}
		if (get_user_meta($vendor_id, '_fb_profile', true )) {
			update_user_meta($vendor_id, '_vendor_fb_profile', get_user_meta($vendor_id, '_fb_profile', true ));
		}
		if (get_user_meta($vendor_id, '_linkdin_profile', true )) {
			update_user_meta($vendor_id, '_vendor_linkdin_profile', get_user_meta($vendor_id, '_linkdin_profile', true ));
		}
		if (get_user_meta($vendor_id, '_youtube', true )) {
			update_user_meta($vendor_id, '_vendor_youtube', get_user_meta($vendor_id, '_youtube', true ));
		}
		if (get_user_meta($vendor_id, '_instagram', true )) {
			update_user_meta($vendor_id, '_vendor_instagram', get_user_meta($vendor_id, '_instagram', true ));
		}

		// address
		if (get_user_meta( $vendor_id, 'billing_address_1', true )) {
			update_user_meta($vendor_id, '_vendor_address_1', get_user_meta( $vendor_id, 'billing_address_1', true ));
		}
		if (get_user_meta( $vendor_id, 'billing_address_2', true )) {
			update_user_meta($vendor_id, '_vendor_address_2', get_user_meta( $vendor_id, 'billing_address_2', true ));
		}
		if (get_user_meta( $vendor_id, 'billing_country', true )) {
			update_user_meta($vendor_id, '_vendor_country', get_user_meta( $vendor_id, 'billing_country', true ));
		}
		if (get_user_meta( $vendor_id, 'billing_city', true )) {
			update_user_meta($vendor_id, '_vendor_city', get_user_meta( $vendor_id, 'billing_city', true ));
		}
		if (get_user_meta( $vendor_id, 'billing_state', true )) {
			update_user_meta($vendor_id, '_vendor_state', get_user_meta( $vendor_id, 'billing_state', true ));
		}
		if (get_user_meta( $vendor_id, 'billing_postcode', true )) {
			update_user_meta($vendor_id, '_vendor_postcode', get_user_meta( $vendor_id, 'billing_postcode', true ));
		}
		if (get_user_meta( $vendor_id, 'billing_phone', true )) {
			update_user_meta($vendor_id, '_vendor_phone', get_user_meta( $vendor_id, 'billing_phone', true ));
		}

		// store description	
		$seller_info = ! empty( $wcpv_vendor_data['profile'] ) ? $wcpv_vendor_data['profile'] : '';
			update_user_meta($vendor_id, '_vendor_description', stripslashes( html_entity_decode( $seller_info, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
	}

	public function store_product_migrate($vendor_id, $term_id) {
		global $WCMp;
		include_once ($WCMp->plugin_path . "/classes/migration/class-wcmp-migration.php" );
		$get_product_vendor = new WCMp_Migrator();
		$vendor_products = $get_product_vendor->wcmp_get_products_by_vendor( $vendor_id );
		if($vendor_products) {
			foreach($vendor_products as $product ) {
				wp_delete_object_term_relationships($product->ID, $WCMp->taxonomy->taxonomy_name);
				wp_set_object_terms($product->ID, (int) $term_id, $WCMp->taxonomy->taxonomy_name, true);					
			}
		}
	}

	public function store_order_migrate() {
		global $WCMp;
		$wcpvendors_get_vendors = $this->get_marketplace_vendor();
		if( empty( $wcpvendors_get_vendors ) ) {

			$woocommerce_orders = get_posts( array(
				'numberposts' => -1,
				'post_type'   => wc_get_order_types(),
				'post_status' => array_keys( wc_get_order_statuses() ),
				'post_parent'    => 0
			) );
			if (!empty($woocommerce_orders)) {
				foreach ($woocommerce_orders as $woocommerce_order) {
					$order_id = $woocommerce_order->ID;
					$order = wc_get_order($order_id);
					if(!$order) continue;

					$order = wc_get_order($order_id);
					$_wcmp_vendor_specific_order_migrated = get_post_meta($order_id, '_wcmp_vendor_specific_order_migrated', true) ? get_post_meta($order_id, '_wcmp_vendor_specific_order_migrated', true) : array();
					$set_order_id_migration = array();
					if ( !in_array($order_id, $_wcmp_vendor_specific_order_migrated) ) {

						$set_order_id_migration[] = $order_id;

						// Remove previous added items
						$line_items = $order->get_items();
						$shipping_items = $order->get_items('shipping');

						foreach ($line_items as $key_items => $value_items) {
							wc_delete_order_item_meta( $key_items, '_vendor_id' );
						}

						foreach ($shipping_items as $key_shipping => $value_shipping) {
							wc_delete_order_item_meta( $key_shipping, 'method_slug' ); 
						}

						$suborder_create = $WCMp->order->wcmp_create_orders_from_backend($order_id, '');
						update_post_meta($order_id, '_wcmp_vendor_specific_order_migrated', $set_order_id_migration);
					}
				}
			}
			update_option('wcmp_migration_orders_table_migrated', true);
			wp_clear_scheduled_hook('migrate_multivendor_order_table');
		}
	}

}