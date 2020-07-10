<?php
/**
 * The template for displaying vendor profile management 
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/profile.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   3.1.0
 */
global $WCMp;

$vendor_profile_image = get_user_meta($user->ID, '_vendor_profile_image', true);
?>
<div class="col-md-12">
    <form method="post" name="profile_settings_form" class="wcmp_profile_form form-horizontal">
        <?php do_action('wcmp_before_vendor_dashboard_profile'); ?>
		<div class="panel panel-default pannel-outer-heading">
			<div class="panel-heading d-flex">
				<h3><?php _e('Personal Information', 'dc-woocommerce-multi-vendor'); ?></h3>
			</div>
			<div class="panel-body panel-content-padding">
				<div class="wcmp_form1">
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('First Name', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="text" name="vendor_profile_data[first_name]" value="<?php echo isset($user->first_name)? $user->first_name : ''; ?>"  placeholder="<?php _e('Enter your First Name here', 'dc-woocommerce-multi-vendor'); ?>">
                        </div>  
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Last Name', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="text" name="vendor_profile_data[last_name]" value="<?php echo isset($user->last_name)? $user->last_name : ''; ?>"  placeholder="<?php _e('Enter your Last Name here', 'dc-woocommerce-multi-vendor'); ?>">
                        </div>  
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Email', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="text" name="vendor_profile_data[user_email]" required value="<?php echo isset($user->user_email) ? $user->user_email : ''; ?>"  placeholder="<?php _e('Enter your Email here', 'dc-woocommerce-multi-vendor'); ?>">
                            <div class="wcmp-do-change-pass">
                                <button type="button" class="btn btn-secondary" id="wcmp-change-pass"><?php _e('Change Password', 'dc-woocommerce-multi-vendor'); ?></button>
                            </div>
                        </div>  
                    </div>
                    
                    <div class="form-group vendor-edit-pass-field" style="display:none;">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Current password', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="password" name="vendor_profile_data[password_current]" autocomplete="off" >
                            <div class="hints"><?php _e('Keep it blank for not to update.', 'dc-woocommerce-multi-vendor'); ?></div>
                        </div>  
                    </div>
                    <div class="form-group vendor-edit-pass-field" style="display:none;">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('New password', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="password" name="vendor_profile_data[password_1]" autocomplete="off" >
                            <div class="hints"><?php _e('Keep it blank for not to update.', 'dc-woocommerce-multi-vendor'); ?></div>
                        </div>  
                    </div>
                    <div class="form-group vendor-edit-pass-field" style="display:none;">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Confirm new password', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="password" name="vendor_profile_data[password_2]" autocomplete="off" >
                        </div>  
                    </div>             
                    
                    <?php if ($WCMp->vendor_caps->vendor_can('is_upload_files')) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Profile Image', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                        	<img id="vendor-profile-img" src="<?php echo (isset($vendor_profile_image) && $vendor_profile_image > 0) ? wp_get_attachment_url($vendor_profile_image) :  get_avatar_url($user->ID, array('size' => 120)); ?>" width="120" alt="dp">
							<div class="wcmp-media profile-pic-btn">
								<button type="button" class="btn btn-secondary wcmp_upload_btn" data-target="vendor-profile"><i class="wcmp-font ico-edit-pencil-icon"></i> <?php _e('Upload image', 'dc-woocommerce-multi-vendor'); ?></button>
							</div>
							<input type="hidden" name="vendor_profile_data[vendor_profile_image]" id="vendor-profile-img-id" class="user-profile-fields" value="<?php echo isset($vendor_profile_image) ? $vendor_profile_image : ''; ?>"  />
						</div>  
                    </div>
                    <?php } ?>
                </div>
			</div>
		</div>
        <?php do_action('wcmp_after_vendor_dashboard_profile'); ?>
        <?php do_action('other_exta_field_dcmv'); ?>
        <div class="wcmp-action-container">
            <button class="btn btn-default" name="store_save_profile"><?php _e('Save Options', 'dc-woocommerce-multi-vendor'); ?></button>
            <div class="clear"></div>
        </div>
    </form>
</div>