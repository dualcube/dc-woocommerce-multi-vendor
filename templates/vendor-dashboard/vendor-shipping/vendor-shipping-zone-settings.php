<?php global $WCMp; ?>
<div class="zone-component panel-content-padding">
	<div class="shipping-zone-list">
		<a href="javascript:void(0);" ><i class="wcmp-font ico-back-arrow"></i> <?php  _e('Shipping Zones', 'dc-woocommerce-multi-vendor'); ?></a>
		<hr>
	</div>
	<form action="" method="post">
		<div class="form-group"> 
			<label class="control-label col-sm-3 col-md-3"><?php  _e('Zone Name', 'dc-woocommerce-multi-vendor'); ?></label>
			<div class="col-md-6 col-sm-9"><?php _e($zones['data']['zone_name'], 'dc-woocommerce-multi-vendor'); ?></div>
		</div>
		<div class="form-group"> 
			<label class="control-label col-sm-3 col-md-3"><?php  _e('Zone region', 'dc-woocommerce-multi-vendor'); ?></label>
			<div class="col-md-6 col-sm-9"><?php _e($zones['formatted_zone_location'], 'dc-woocommerce-multi-vendor'); ?></div>
		</div>		
		<div class="form-group">
		   	<div class="col-md-6 col-sm-9">
		    	<input id="zone_id" class="form-control" type="hidden" name="<?php echo 'wcmp_shipping_zone['. $zone_id .'][_zone_id]'; ?>" value="<?php echo $zone_id; ?>">
		   	</div>
		</div>
		<?php if( $show_limit_location_link && $zone_id !== 0 ) { ?>
			<div class="form-group">
			   	<label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Limit Zone Location', 'dc-woocommerce-multi-vendor' ); ?></label>
			   	<div class="col-md-6 col-sm-9">
			    	<input id="limit_zone_location" class="form-control" type="checkbox" name="<?php echo 'wcmp_shipping_zone['. $zone_id .'][_limit_zone_location]'; ?>" value="1" <?php checked( $want_to_limit_location, 1 ); ?>>
			   	</div>
			</div>
		<?php } ?>
		<?php if( $show_state_list ) { ?>
			<div class="form-group hide_if_zone_not_limited">
			   	<label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Select specific states', 'dc-woocommerce-multi-vendor' ); ?></label>
			   	<div class="col-md-6 col-sm-9">
			    	<select id="select_zone_states" class="form-control" name="<?php echo 'wcmp_shipping_zone['. $zone_id .'][_select_zone_states][]'; ?>" multiple>
			    		<?php foreach( $state_key_by_country as $key => $value ) { ?>
			    			<option value="<?php echo $key; ?>" <?php selected( in_array( $key, $states ), true ); ?>><?php echo $value; ?></option>
			    		<?php } ?>
			    	</select>
			   	</div>
			</div>
		<?php } ?>
		<?php if( $show_post_code_list ) { ?>
			<div class="form-group hide_if_zone_not_limited">
			   	<label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Set your postcode', 'dc-woocommerce-multi-vendor' ); ?></label>
			   	<div class="col-md-6 col-sm-9">
			    	<input id="select_zone_postcodes" class="form-control" type="text" name="<?php echo 'wcmp_shipping_zone['. $zone_id .'][_select_zone_postcodes]'; ?>" value="<?php echo $postcodes; ?>" placeholder="<?php esc_attr_e( 'Postcodes need to be comma separated', 'dc-woocommerce-multi-vendor' ); ?>">
			   	</div>
			</div>
		<?php } ?>
	</form>
	<div class="wcmp-zone-method-wrapper form-group mt-10">
		<label class="control-label col-sm-3 col-md-3 wcmp-zone-method-heading" for="_sku">
			<?php esc_html_e( 'Shipping methods', 'dc-woocommerce-multi-vendor' ); ?>
            <div class="form-text mt-10 small"><?php esc_html_e('Add your shipping method for appropiate zone', 'dc-woocommerce-multi-vendor'); ?></div>
        </label> 
		<div class="wcmp-zone-method-content col-md-9 col-sm-9">
			<table class="table wcmp-table zone-method-table table-bordered">
				<thead>
					<tr>
						<th class="title"><?php esc_html_e('Title', 'dc-woocommerce-multi-vendor'); ?></th>
						<th class="enabled"><?php esc_html_e('Enabled', 'dc-woocommerce-multi-vendor'); ?></th> 
						<th class="description"><?php esc_html_e('Description', 'dc-woocommerce-multi-vendor'); ?></th>
						<th class="action"><?php esc_html_e('Action', 'dc-woocommerce-multi-vendor'); ?></th>
					</tr>
				</thead> 
				<tbody>
					<?php 
					if(empty($vendor_shipping_methods)) { ?> 
						<tr>
							<td colspan="4"><?php esc_html_e( 'You can add multiple shipping methods within this zone. Only customers within the zone will see them.', 'dc-woocommerce-multi-vendor' ); ?></td>
						</tr>
						<?php 
					} else { 
						foreach ( $vendor_shipping_methods as $vendor_shipping_method ) { ?>
							<tr>
								<td><?php echo esc_html($vendor_shipping_method['title']); ?>
									<div data-instance_id="<?php echo $vendor_shipping_method['instance_id']; ?>" data-method_id="<?php echo $vendor_shipping_method['id']; ?>" data-method-settings='<?php echo json_encode($vendor_shipping_method); ?>' class="row-actions edit_del_actions">
									</div>
								</td>
								<td> 
									<input id="method_status" class="form-control method-status" type="checkbox" name="<?php echo 'method_status'; ?>" value="<?php echo $vendor_shipping_method['instance_id']; ?>" <?php checked( ( $vendor_shipping_method['enabled'] == "yes" ), true ); ?>>
								</td>
								<td><?php _e($vendor_shipping_method['settings']['description'], 'dc-woocommerce-multi-vendor' ); ?></td>
								<td>
									<div class="col-actions edit_del_actions" data-zone_id="<?php echo $zone_id; ?>" data-instance_id="<?php echo $vendor_shipping_method['instance_id']; ?>" data-method_id="<?php echo $vendor_shipping_method['id']; ?>" data-method-settings='<?php echo json_encode($vendor_shipping_method); ?>'>
										<span class="edit"><a href="javascript:void(0);" class="edit-shipping-method" title="<?php _e( 'Edit', 'dc-woocommerce-multi-vendor' ) ?>"><i class="wcmp-font ico-edit-pencil-icon"></i></a>
										</span>|
										<span class="delete"><a class="delete-shipping-method" href="javascript:void(0);" title="<?php _e( 'Delete', 'dc-woocommerce-multi-vendor' ) ?>"><i class="wcmp-font ico-delete-icon"></i></a></span>
									</div>
								</td>
							</tr>
						<?php 
					}
				}
				?>
				</tbody>
			</table>
		</div>
		<div class="wcmp-zone-method-footer col-md-9 col-sm-9 col-md-offset-3 mt-15">
			<a href="javascript:void(0);" class="btn btn-default wcmp-zone-method-add-btn show-shipping-methods"><i class="fa fa-plus"></i><?php _e( 'Add Shipping Method', 'dc-woocommerce-multi-vendor' ) ?></a>
		</div>
		<?php 

		$WCMp->template->get_template( 'vendor-dashboard/vendor-shipping/vendor-edit-shipping-method.php' );
		$WCMp->template->get_template( 'vendor-dashboard/vendor-shipping/vendor-add-shipping-method.php' );
		?>
	</div>
</div>