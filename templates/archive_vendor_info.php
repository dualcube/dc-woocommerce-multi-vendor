<?php
/**
 * The template for displaying archive vendor info
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/archive_vendor_info.php
 *
 * @author      WC Marketplace
 * @package     WCMp/Templates
 * @version   2.2.0
 */
global $WCMp;
$vendor = get_wcmp_vendor($vendor_id);
$vendor_hide_address = apply_filters('wcmp_vendor_store_header_hide_store_address', get_user_meta($vendor_id, '_vendor_hide_address', true), $vendor->id);
$vendor_hide_phone = apply_filters('wcmp_vendor_store_header_hide_store_phone', get_user_meta($vendor_id, '_vendor_hide_phone', true), $vendor->id);
$vendor_hide_email = apply_filters('wcmp_vendor_store_header_hide_store_email', get_user_meta($vendor_id, '_vendor_hide_email', true), $vendor->id);
$template_class = get_wcmp_vendor_settings('wcmp_vendor_shop_template', 'vendor', 'dashboard', 'template1');
$template_class = apply_filters('can_vendor_edit_shop_template', false) && get_user_meta($vendor_id, '_shop_template', true) ? get_user_meta($vendor_id, '_shop_template', true) : $template_class;
?>
<div class="vendor_description_background wcmp_vendor_banner_template <?php echo $template_class; ?>">
    <div class="wcmp_vendor_banner">
        <?php
            if($banner != ''){
        ?>
            <img src="<?php echo $banner; ?>" alt="<?php echo $vendor->page_title ?>">
        <?php
            } else{
        ?>
            <img src="<?php echo $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>" alt="<?php echo $vendor->page_title ?>">
        <?php        
            }
        ?>
        
        
        <?php if(apply_filters('wcmp_vendor_store_header_show_social_links', true, $vendor->id)) :?>
        <div class="wcmp_social_profile">
            <?php
            $vendor_fb_profile = get_user_meta($vendor_id, '_vendor_fb_profile', true);
            $vendor_twitter_profile = get_user_meta($vendor_id, '_vendor_twitter_profile', true);
            $vendor_linkdin_profile = get_user_meta($vendor_id, '_vendor_linkdin_profile', true);
            $vendor_google_plus_profile = get_user_meta($vendor_id, '_vendor_google_plus_profile', true);
            $vendor_youtube = get_user_meta($vendor_id, '_vendor_youtube', true);
            $vendor_instagram = get_user_meta($vendor_id, '_vendor_instagram', true);
            $vendor_buddypress = get_user_meta( $vendor_id, '_vendor_buddypress', true );
            ?>
            <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="wcmp-font ico-facebook-icon"></i></a><?php } ?>
            <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="wcmp-font ico-twitter-icon"></i></a><?php } ?>
            <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="wcmp-font ico-linkedin-icon"></i></a><?php } ?>
            <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="wcmp-font ico-google-plus-icon"></i></a><?php } ?>
            <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="wcmp-font ico-youtube-icon"></i></a><?php } ?>
            <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="wcmp-font ico-instagram-icon"></i></a><?php } ?>
            <?php if ($vendor_buddypress) { ?> <a target="_blank" href="<?php echo esc_url($vendor_buddypress); ?>"><i class="wcmp-font ico-instagram-icon"></i></a><?php } ?>
        </div>
        <?php endif; ?>

        <?php
            if($template_class == 'template1'){
        ?>
        <div class="vendor_description">
            <div class="vendor_img_add">
                <div class="img_div"><img src=<?php echo $profile; ?> alt="<?php echo $vendor->page_title ?>"/></div>
                <div class="vendor_address">
                    <p class="wcmp_vendor_name"><?php echo $vendor->page_title ?></p>
                    <?php do_action('before_wcmp_vendor_information',$vendor_id);?>
                    <div class="wcmp_vendor_rating">
                        <?php
                        if (get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable') {
                            $queried_object = get_queried_object();
                            if (isset($queried_object->term_id) && !empty($queried_object)) {
                                $rating_val_array = wcmp_get_vendor_review_info($queried_object->term_id);
                                $WCMp->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                            }
                        }
                        ?>      
                    </div>  
                    <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="wcmp_vendor_detail"><i class="wcmp-font ico-location-icon"></i><label><?php echo $location; ?></label></p><?php } ?>
                    <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="wcmp_vendor_detail"><i class="wcmp-font ico-call-icon"></i><label><?php echo apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); ?></label></p><?php } ?>
                    <?php if (!empty($email) && $vendor_hide_email != 'Enable') { ?><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="wcmp_vendor_detail"><i class="wcmp-font ico-mail-icon"></i><?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?></a><?php } ?>
                    <?php
                    if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                        $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                        $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                        if (empty($external_store_label))
                            $external_store_label = __('External Store URL', 'dc-woocommerce-multi-vendor');
                        if (isset($external_store_url) && !empty($external_store_url)) {
                            ?><p class="external_store_url"><label><a target="_blank" href="<?php echo apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id); ?>"><?php echo $external_store_label; ?></a></label></p><?php
                            }
                        }
                        ?>
                    <?php do_action('after_wcmp_vendor_information',$vendor_id);?>          
                    <?php
                        $vendor_hide_description = apply_filters('wcmp_vendor_store_header_hide_description', get_user_meta($vendor_id, '_vendor_hide_description', true), $vendor->id);
                        if (!$vendor_hide_description && !empty($description) && $template_class != 'template1') {
                    ?>
                    <div class="description_data"> 
                        <?php echo htmlspecialchars_decode( wpautop( $description ), ENT_QUOTES ); ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
            }
        ?>
    </div>

    <?php
        if($template_class != 'template1'){
    ?>
    <div class="vendor_description">
        <div class="vendor_img_add">
            <div class="img_div"><img src=<?php echo $profile; ?> alt="<?php echo $vendor->page_title ?>"/></div>
            <div class="vendor_address">
                <p class="wcmp_vendor_name"><?php echo $vendor->page_title ?></p>
                <?php do_action('before_wcmp_vendor_information',$vendor_id);?>
                <div class="wcmp_vendor_rating">
                    <?php
                    if (get_wcmp_vendor_settings('is_sellerreview', 'general') == 'Enable') {
                        $queried_object = get_queried_object();
                        if (isset($queried_object->term_id) && !empty($queried_object)) {
                            $rating_val_array = wcmp_get_vendor_review_info($queried_object->term_id);
                            $WCMp->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                        }
                    }
                    ?>      
                </div>  
                <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="wcmp_vendor_detail"><i class="wcmp-font ico-location-icon"></i><label><?php echo $location; ?></label></p><br /><?php } ?>
                <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="wcmp_vendor_detail"><i class="wcmp-font ico-call-icon"></i><label><?php echo apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); ?></label></p><?php } ?>
                <?php if (!empty($email) && $vendor_hide_email != 'Enable') { ?><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="wcmp_vendor_detail"><i class="wcmp-font ico-mail-icon"></i><?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?></a><?php } ?>
                <?php
                if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                    $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                    $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                    if (empty($external_store_label))
                        $external_store_label = __('External Store URL', 'dc-woocommerce-multi-vendor');
                    if (isset($external_store_url) && !empty($external_store_url)) {
                        ?><p class="external_store_url"><label><a target="_blank" href="<?php echo apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id); ?>"><?php echo $external_store_label; ?></a></label></p><?php
                        }
                    }
                    ?>
                <?php do_action('after_wcmp_vendor_information',$vendor_id);?>          
                <?php
                    $vendor_hide_description = apply_filters('wcmp_vendor_store_header_hide_description', get_user_meta($vendor_id, '_vendor_hide_description', true), $vendor->id);
                    if (!$vendor_hide_description && !empty($description) && $template_class != 'template1') {
                ?>
                <div class="description_data"> 
                    <?php echo htmlspecialchars_decode( wpautop( $description ), ENT_QUOTES ); ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
        }
    ?>

    <?php
        $vendor_hide_description = apply_filters('wcmp_vendor_store_header_hide_description', get_user_meta($vendor_id, '_vendor_hide_description', true), $vendor->id);
        if (!$vendor_hide_description && !empty($description) && $template_class == 'template1') {
    ?>
    <div class="description_data"> 
        <?php echo htmlspecialchars_decode( wpautop( $description ), ENT_QUOTES ); ?>
    </div>
    <?php } ?>
</div>  