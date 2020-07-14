<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/widget/quick-info.php
 *
 * @author      WC Marketplace
 * @package     dc-product-vendor/Templates
 * @version   0.0.1
 */

global $WCMp;
$submit_label = isset( $instance['submit_label'] ) ? $instance['submit_label'] : __( 'Submit', 'dc-woocommerce-multi-vendor' );
$script = isset( $instance['recaptcha_v2_script'] ) ? $instance['recaptcha_v2_script'] : '';
$enable_recaptcha = isset( $instance['enable_recaptcha_v2'] ) ? $instance['enable_recaptcha_v2']: false ;

extract( $instance );

?>
<div class="wcmp-quick-info-wrapper">
    <?php
    if( isset( $_GET['message'] ) ) {
                    $message = sanitize_text_field( $_GET['message'] );
                    echo "<div class='woocommerce-{$widget->response[ $message ]['class']}'>" . $widget->response[ $message ]['message'] . "</div>";
    }

    else {
                    echo '<p>' . $description . '</p>';
    }?>

    <form action="" method="post" id="respond" style=" padding: 0;">
                    <input type="text" class="input-text " name="quick_info[name]" value="<?php echo $current_user->display_name ?>" placeholder="<?php _e( 'Name', 'dc-woocommerce-multi-vendor' ) ?>" required/>
                    <input type="text" class="input-text " name="quick_info[subject]" value="" placeholder="<?php _e( 'Subject', 'dc-woocommerce-multi-vendor' ) ?>" required/>
                    <input type="email" class="input-text " name="quick_info[email]" value="<?php echo $current_user->user_email  ?>" placeholder="<?php _e( 'Email', 'dc-woocommerce-multi-vendor' ) ?>" required/>
                    <textarea name="quick_info[message]" rows="5" placeholder="<?php _e( 'Message', 'dc-woocommerce-multi-vendor' ) ?>" required></textarea>
                    <?php
                    if( $enable_recaptcha != false && $script && !empty( $script) ){?>
                        <script src="https://www.google.com/recaptcha/api.js"></script>
                        <?php echo $script; ?>
                        <input type="hidden" name="enable_recaptcha" value="<?php $enable_recaptcha ?>" />
                     <?php } ?>
                    <input type="submit" class="submit" id="submit" name="quick_info[submit]" value="<?php echo $submit_label ?>" />
                    <input type="hidden" name="quick_info[spam]" value="" />
                    <input type="hidden" name="quick_info[vendor_id]" value="<?php echo $vendor->id ?>" />
                    <?php wp_nonce_field( 'dc_vendor_quick_info_submitted', 'dc_vendor_quick_info_submitted' ); ?>
    </form>
</div>