<?php
/**
 * The template for displaying archive vendor info
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/archive_vendor_info.php
 *
 * @author      WC Marketplace
 * @package     WCMp/Templates
 * @version     3.7
 */
global $WCMp;
$vendor = get_wcmp_vendor($vendor_id);
$vendor_hide_address = apply_filters('wcmp_vendor_store_header_hide_store_address', get_user_meta($vendor_id, '_vendor_hide_address', true), $vendor->id);
$vendor_hide_phone = apply_filters('wcmp_vendor_store_header_hide_store_phone', get_user_meta($vendor_id, '_vendor_hide_phone', true), $vendor->id);
$vendor_hide_email = apply_filters('wcmp_vendor_store_header_hide_store_email', get_user_meta($vendor_id, '_vendor_hide_email', true), $vendor->id);
$template_class = get_wcmp_vendor_settings('wcmp_vendor_shop_template', 'vendor', 'dashboard', 'template1');
$template_class = apply_filters('can_vendor_edit_shop_template', false) && get_user_meta($vendor_id, '_shop_template', true) ? get_user_meta($vendor_id, '_shop_template', true) : $template_class;

$vendor_fb_profile = get_user_meta($vendor_id, '_vendor_fb_profile', true);
$vendor_twitter_profile = get_user_meta($vendor_id, '_vendor_twitter_profile', true);
$vendor_linkdin_profile = get_user_meta($vendor_id, '_vendor_linkdin_profile', true);
$vendor_google_plus_profile = get_user_meta($vendor_id, '_vendor_google_plus_profile', true);
$vendor_youtube = get_user_meta($vendor_id, '_vendor_youtube', true);
$vendor_instagram = get_user_meta($vendor_id, '_vendor_instagram', true);
// Follow code
$wcmp_customer_follow_vendor = get_user_meta( get_current_user_id(), 'wcmp_customer_follow_vendor', true ) ? get_user_meta( get_current_user_id(), 'wcmp_customer_follow_vendor', true ) : array();
$vendor_lists = !empty($wcmp_customer_follow_vendor) ? wp_list_pluck( $wcmp_customer_follow_vendor, 'user_id' ) : array();
$follow_status = in_array($vendor_id, $vendor_lists) ? __( 'Unfollow', 'dc-woocommerce-multi-vendor' ) : __( 'Follow', 'dc-woocommerce-multi-vendor' );

if ( $template_class == 'template3') { ?>
<div class='wcmp_bannersec_start wcmp-theme01'>
    <div class="wcmp-banner-wrap">
        <?php if($banner != '') { ?>
            <img src="<?php echo esc_url($banner); ?>" class="wcmp-imgcls"/>
        <?php } else { ?>
            <img src="<?php echo $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>" class="wcmp-imgcls"/>
        <?php } ?>

        <div class='wcmp-banner-area'>
            <div class='wcmp-bannerright'>
                <div class="socialicn-area">

                    <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="wcmp-font ico-facebook-icon"></i></a><?php } ?>
                    <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="wcmp-font ico-twitter-icon"></i></a><?php } ?>
                    <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="wcmp-font ico-linkedin-icon"></i></a><?php } ?>
                    <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="wcmp-font ico-google-plus-icon"></i></a><?php } ?>
                    <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="wcmp-font ico-youtube-icon"></i></a><?php } ?>
                    <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="wcmp-font ico-instagram-icon"></i></a><?php } ?>
                    <?php do_action( 'wcmp_vendor_store_header_social_link', $vendor_id ); ?>

                </div>
                <div class='wcmp-butn-area'>
                    <?php do_action( 'wcmp_additional_button_at_banner' ); ?>
                </div>
            </div>
        </div>

        <div class='wcmp-banner-below'>
            <div class='wcmp-profile-area'>
                <img src='<?php echo esc_attr($profile); ?>' class='wcmp-profile-imgcls' />
            </div>
            <div>
                <div class="wcmp-banner-middle">
                    <div class="wcmp-heading"><?php echo esc_html($vendor->page_title) ?></div>
                    <!-- Follow button will be added here -->
                    <button type="button" class='wcmp-butn wcmp-stroke-butn' data-vendor_id=<?php echo esc_attr($vendor_id); ?> data-status=<?php echo esc_attr($follow_status); ?> ><span class="dashicons dashicons-heart"></span><?php echo esc_html($follow_status) ?></button>
                </div>
                <div class="wcmp-contact-deatil">
                    
                    
                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="wcmp-address"><span><i class="wcmp-font ico-location-icon"></i></span><?php echo esc_html($location); ?></p><?php } ?>

                    
                    <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="wcmp-address"><span><i class="wcmp-font ico-call-icon"></i></span><?php echo apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); ?></p><?php } ?>

                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?>
                    <p class="wcmp-address"><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="wcmp_vendor_detail"><i class="wcmp-font ico-mail-icon"></i><?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?></a></p><?php } ?>

                    <?php
                    if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                        $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                        $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                        if (empty($external_store_label))
                            $external_store_label = __('External Store URL', 'dc-woocommerce-multi-vendor');
                        if (isset($external_store_url) && !empty($external_store_url)) {
                            ?><p class="external_store_url"><label><a target="_blank" href="<?php echo apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id); ?>"><?php echo esc_html($external_store_label); ?></a></label></p><?php
                            }
                        }
                        ?>
                    <?php do_action('after_wcmp_vendor_information',$vendor_id);?>   
                    
                </div>
            </div>
            <div class="wcmp_vendor_rating">
                <?php
                if (get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable') {
                    if (wcmp_is_store_page()) {
                        $vendor_term_id = get_user_meta( wcmp_find_shop_page_vendor(), '_vendor_term_id', true );
                        $rating_val_array = wcmp_get_vendor_review_info($vendor_term_id);
                        $WCMp->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                    }
                }
                ?>      
            </div>  
        </div>
    </div>
</div>
<?php } elseif ( $template_class == 'template1' ) {
    ?>
    <div class='wcmp_bannersec_start wcmp-theme02'>
        
        <div class="wcmp-banner-wrap">
        <?php if($banner != '') { ?>
            <img src="<?php echo esc_url($banner); ?>" class="wcmp-imgcls"/>
        <?php } else { ?>
            <img src="<?php echo $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>" class="wcmp-imgcls"/>
        <?php } ?>
        <div class='wcmp-banner-area'>
            <div class='wcmp-bannerleft'>
                <div class='wcmp-profile-area'>
                    <img src='<?php echo esc_attr($profile); ?>' class='wcmp-profile-imgcls' />
                </div>
                <div class="wcmp-heading"><?php echo esc_html($vendor->page_title); ?></div>
                
                <div class="wcmp_vendor_rating">
                    <?php
                    if (get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable') {
                        if (wcmp_is_store_page()) {
                            $vendor_term_id = get_user_meta( wcmp_find_shop_page_vendor(), '_vendor_term_id', true );
                            $rating_val_array = wcmp_get_vendor_review_info($vendor_term_id);
                            $WCMp->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                        }
                    }
                    ?>      
                </div>
                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="wcmp-address"><span><i class="wcmp-font ico-location-icon"></i></span><?php echo esc_html($location); ?></p><?php } ?>

                <div class="wcmp-contact-deatil">
                    
                    <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="wcmp-address"><span><i class="wcmp-font ico-call-icon"></i></span><?php echo esc_html(apply_filters('vendor_shop_page_contact', $mobile, $vendor_id)); ?></p><?php } ?>

                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?>
                    <p class="wcmp-address"><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="wcmp_vendor_detail"><i class="wcmp-font ico-mail-icon"></i><?php echo esc_html(apply_filters('vendor_shop_page_email', $email, $vendor_id)); ?></a></p><?php } ?>
                    <?php
                    if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                        $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                        $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                        if (empty($external_store_label))
                            $external_store_label = __('External Store URL', 'dc-woocommerce-multi-vendor');
                        if (isset($external_store_url) && !empty($external_store_url)) {
                            ?><p class="external_store_url"><label><a target="_blank" href="<?php echo esc_attr(apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id)); ?>"><?php echo esc_html($external_store_label); ?></a></label></p><?php
                            }
                        }
                        ?>
                    <?php do_action('after_wcmp_vendor_information',$vendor_id);?>   
                </div>
            </div>
            <div class='wcmp-bannerright'>
                <div class="socialicn-area">
                    <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="wcmp-font ico-facebook-icon"></i></a><?php } ?>
                    <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="wcmp-font ico-twitter-icon"></i></a><?php } ?>
                    <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="wcmp-font ico-linkedin-icon"></i></a><?php } ?>
                    <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="wcmp-font ico-google-plus-icon"></i></a><?php } ?>
                    <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="wcmp-font ico-youtube-icon"></i></a><?php } ?>
                    <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="wcmp-font ico-instagram-icon"></i></a><?php } ?>
                    <?php do_action( 'wcmp_vendor_store_header_social_link', $vendor_id ); ?>
                </div>
                <div class='wcmp-butn-area'>
                    <!-- Follow button will be added here -->
                    <button type="button" class='wcmp-butn wcmp-stroke-butn' data-vendor_id=<?php echo esc_attr($vendor_id); ?> data-status=<?php echo esc_attr($follow_status); ?> ><span class="dashicons dashicons-heart"></span><?php echo esc_html($follow_status) ?></button>
                    <?php do_action( 'wcmp_additional_button_at_banner' ); ?>
                </div>
            </div>
        </div>
        </div>
    </div>
<?php } elseif ( $template_class == 'template2' ) {
    ?>
    <div class='wcmp_bannersec_start wcmp-theme03'>
        <div class="wcmp-banner-wrap">
            <?php if($banner != '') { ?>
                <img src="<?php echo esc_url($banner); ?>" class="wcmp-imgcls"/>
            <?php } else { ?>
                <img src="<?php echo $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>" class="wcmp-imgcls"/>
            <?php } ?>
            <div class='wcmp-banner-area'>
                <div class='wcmp-bannerright'>
                    <div class="socialicn-area">
                        <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="wcmp-font ico-facebook-icon"></i></a><?php } ?>
                        <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="wcmp-font ico-twitter-icon"></i></a><?php } ?>
                        <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="wcmp-font ico-linkedin-icon"></i></a><?php } ?>
                        <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="wcmp-font ico-google-plus-icon"></i></a><?php } ?>
                        <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="wcmp-font ico-youtube-icon"></i></a><?php } ?>
                        <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="wcmp-font ico-instagram-icon"></i></a><?php } ?>
                        <?php do_action( 'wcmp_vendor_store_header_social_link', $vendor_id ); ?>
                    </div>
                </div>
            </div>
            <div class='wcmp-banner-below'>
                <div class='wcmp-profile-area'>
                    <img src='<?php echo esc_attr($profile); ?>' class='wcmp-profile-imgcls' />
                </div>
                <div class="wcmp-heading"><?php echo esc_html($vendor->page_title) ?></div>
                
                <div class="wcmp_vendor_rating">
                    <?php
                    if (get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable') {
                        if (wcmp_is_store_page()) {
                            $vendor_term_id = get_user_meta( wcmp_find_shop_page_vendor(), '_vendor_term_id', true );
                            $rating_val_array = wcmp_get_vendor_review_info($vendor_term_id);
                            $WCMp->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                        }
                    }
                    ?>      
                </div>  

                <div class="wcmp-contact-deatil">
                    
                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="wcmp-address"><span><i class="wcmp-font ico-location-icon"></i></span><?php echo esc_html($location); ?></p><?php } ?>

                    <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="wcmp-address"><span><i class="wcmp-font ico-call-icon"></i></span><?php echo apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); ?></p><?php } ?>
                    
                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?>
                    <p class="wcmp-address"><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="wcmp_vendor_detail"><i class="wcmp-font ico-mail-icon"></i><?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?></a></p><?php } ?>
                    <?php
                    if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                        $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                        $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                        if (empty($external_store_label))
                            $external_store_label = __('External Store URL', 'dc-woocommerce-multi-vendor');
                        if (isset($external_store_url) && !empty($external_store_url)) {
                            ?><p class="external_store_url"><label><a target="_blank" href="<?php echo apply_filters('vendor_shop_page_external_store', esc_url_rafolw($external_store_url), $vendor_id); ?>"><?php echo esc_html($external_store_label); ?></a></label></p><?php
                            }
                        }
                        ?>
                    <?php do_action('after_wcmp_vendor_information',$vendor_id);?>   
                </div>
                <div class='wcmp-butn-area'>
                    <!-- Follow button will be added here -->
                    <button type="button" class='wcmp-butn wcmp-stroke-butn' data-vendor_id=<?php echo esc_attr($vendor_id); ?> data-status=<?php echo esc_attr($follow_status); ?> ><span class="dashicons dashicons-heart"></span><?php echo esc_html($follow_status) ?></button>
                    <?php do_action( 'wcmp_additional_button_at_banner' ); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
// Additional hook after archive description ended
do_action('after_wcmp_vendor_description', $vendor_id);