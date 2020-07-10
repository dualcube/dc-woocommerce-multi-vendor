<?php
/**
 * The template for displaying vendor dashboard
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/non-vendor/rejected-vendor-reapply.php
 *
 * @author 	WC Marketplace
 * @package 	WCMp/Templates
 * @version     3.1.0
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
global $WCMp;

if(!is_user_wcmp_rejected_vendor(get_current_vendor_id())) {
	if(is_user_wcmp_pending_vendor(get_current_vendor_id())) {
		?>
		<div class="row">
            <div class="col-md-12 text-center">
				<div class="panel wcmp-pending-vendor-notice">
					<?php echo apply_filters( 'wcmp_pending_vendor_dashboard_message', __('Congratulations! You have successfully applied as a Vendor. Please wait for further notifications from the admin.', 'dc-woocommerce-multi-vendor') ); ?>
				</div>
			</div>
        </div>
        <?php
        return;
	}
	return;
}

$wcmp_vendor_registration_form_data = get_option('wcmp_vendor_registration_form_data');
$form_data = array();
if(isset($wcmp_vendor_registration_form_data) && is_array($wcmp_vendor_registration_form_data)) {
	$vendor_application_data = get_user_meta(get_current_user_id(), 'wcmp_vendor_fields', true);
	foreach($wcmp_vendor_registration_form_data as $key => $value) {
		if( !empty( $vendor_application_data ) ) {
			foreach($vendor_application_data as $app_key => $app_value) {
				if($value['type'] == $app_value['type'] && $value['label'] == $app_value['label'] && isset($app_value['value'])) {
					$form_data[$key]['value'] = $app_value['value'];
				}
			}
		}
	}
}

?>
<div class="col-md-12">
	<form method="post" name="reapply_vendor_application_form" class="reapply_vendor_application_form form-horizontal" enctype="multipart/form-data">
		<?php do_action('wcmp_before_reapply_vendor_application_form'); ?>
			<div class="panel panel-default pannel-outer-heading">
				<div class="panel-heading d-flex">
					<h3><?php _e('Previously Submitted Details', 'dc-woocommerce-multi-vendor'); ?></h3>
				</div>
				<div class="panel-body panel-content-padding">
					<div class="wcmp_regi_form_box">
						<?php
							$WCMp->template->get_template('vendor_registration_form.php', array('wcmp_vendor_registration_form_data' => $wcmp_vendor_registration_form_data, 'form_data' => array('wcmp_vendor_fields' => $form_data)));
						?>
						<div class="clearboth"></div>
					</div>
				</div>
			</div>
		<?php do_action('wcmp_after_reapply_vendor_application_form'); ?>
		<div class="wcmp-action-container">
			<button class="btn btn-default" name="reapply_vendor_application"><?php _e('Apply Again!!', 'dc-woocommerce-multi-vendor'); ?></button>
			<div class="clear"></div>
		</div>
	</form>
</div>
