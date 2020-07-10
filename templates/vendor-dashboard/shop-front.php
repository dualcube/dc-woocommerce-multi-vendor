<?php
/*
 * The template for displaying vendor dashboard
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/shop-front.php
 *
 * @author 	WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.4.5
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
global $WCMp;
$vendor = get_current_vendor();
if (!$vendor) {
    return;
}
$vendor_hide_description = get_user_meta($vendor->id, '_vendor_hide_description', true);
$vendor_hide_email = get_user_meta($vendor->id, '_vendor_hide_email', true);
$vendor_hide_address = get_user_meta($vendor->id, '_vendor_hide_address', true);
$vendor_hide_phone = get_user_meta($vendor->id, '_vendor_hide_phone', true);

$field_type = (apply_filters('wcmp_vendor_storefront_wpeditor_enabled', true, $vendor->id)) ? 'wpeditor' : 'textarea';
$_wp_editor_settings = array('tinymce' => true);
if (!$WCMp->vendor_caps->vendor_can('is_upload_files')) {
    $_wp_editor_settings['media_buttons'] = false;
}
$_wp_editor_settings = apply_filters('wcmp_vendor_storefront_wp_editor_settings', $_wp_editor_settings);
?>
<style>
    .store-map-address{
        margin-top: 10px;
        border: 1px solid transparent;
        border-radius: 2px 0 0 2px;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        height: 40px;
        outline: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }
    #searchStoreAddress {
        background-color: #fff;
        font-family: Roboto;
        font-size: 15px;
        margin-left: 12px;
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 44%;
    }
</style>
<div class="col-md-12">
    <!-- <div class="wcmp_headding2 card-header"><?php _e('General', 'dc-woocommerce-multi-vendor'); ?></div> -->
    <form method="post" name="shop_settings_form" class="wcmp_shop_settings_form form-horizontal">
        <?php do_action('wcmp_before_shop_front'); ?>

        <div class="panel panel-default pannel-outer-heading vendor-cover-panel">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="vendor-cover-wrap">
                            <img id="vendor-cover-img" src="<?php echo (isset($vendor_banner['url']) && (!empty($vendor_banner['url'])) ) ? $vendor_banner['url'] : $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>" alt="banner">

                            <div class="vendor-profile-pic-wraper pull-left">
                                <img id="vendor-profile-img" src="<?php echo (isset($vendor_image['url']) && (!empty($vendor_image['url']))) ? $vendor_image['url'] : $WCMp->plugin_url . 'assets/images/logo_placeholder.jpg'; ?>" alt="dp">
                                <div class="wcmp-media profile-pic-btn">
                                    <button type="button" class="wcmp_upload_btn" data-target="vendor-profile"><i class="wcmp-font ico-edit-pencil-icon"></i> <?php _e('Store Logo', 'dc-woocommerce-multi-vendor'); ?></button>
                                </div>
                                <input type="hidden" name="vendor_image" id="vendor-profile-img-id" class="user-profile-fields" value="<?php echo (isset($vendor_image['value']) && (!empty($vendor_image['value']))) ? $vendor_image['value'] : $WCMp->plugin_url . 'assets/images/WP-stdavatar.png'; ?>"  />
                            </div>
                            <div class="wcmp-media cover-pic-button pull-right">
                                <button type="button" class="wcmp_upload_btn" data-target="vendor-cover"><i class="wcmp-font ico-edit-pencil-icon"></i> <?php _e('Upload Cover Picture', 'dc-woocommerce-multi-vendor'); ?></button>
                            </div>
                            <input type="hidden" name="vendor_banner" id="vendor-cover-img-id" class="user-profile-fields" value="<?php echo (isset($vendor_banner['value']) && (!empty($vendor_banner['value'])) ) ? $vendor_banner['value'] : $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>"  />
                        </div>
                    </div>
                    <!-- 
                    <div class="col-md-3">
                        <div class="wcmp_media_block">
                            <span class="dc-wp-fields-uploader">
                                <img class="one_third_part" id="vendor_image_display" width="300" src="<?php echo (isset($vendor_image['value']) && (!empty($vendor_image['value']))) ? $vendor_image['value'] : $WCMp->plugin_url . 'assets/images/logo_placeholder.jpg'; ?>" class="placeHolder" />
                                <input type="text" name="vendor_image" id="vendor_image" style="display: none;" class="user-profile-fields" readonly value="<?php echo (isset($vendor_image['value']) && (!empty($vendor_image['value']))) ? $vendor_image['value'] : $WCMp->plugin_url . 'assets/images/logo_placeholder.jpg'; ?>"  />
                            </span>
                            <div class="button-group">                            
                                <button class="upload_button wcmp_black_btn moregap two_third_part btn btn-primary" name="vendor_image_button" id="vendor_image_button" value="<?php _e('Upload', 'dc-woocommerce-multi-vendor') ?>" style=" display: block; "><span class="dashicons dashicons-upload"></span> <?php _e('Upload', 'dc-woocommerce-multi-vendor') ?></button>
                                <button class="remove_button wcmp_black_btn moregap two_third_part btn btn-primary" name="vendor_image_remove_button" id="vendor_image_remove_button" value="<?php _e('Replace', 'dc-woocommerce-multi-vendor') ?>"><span class="dashicons dashicons-upload"></span> <?php _e('Replace', 'dc-woocommerce-multi-vendor') ?></button>
                            </div>
                            <div class="clear"></div>
                        </div>
                    </div>
                    <div class="col-md-7 col-md-offset-2">
                        <div class="wcmp_media_block">
                            <span class="dc-wp-fields-uploader">
                                <img class="one_third_part" id="vendor_banner_display" width="300" src="<?php echo (isset($vendor_banner['value']) && (!empty($vendor_banner['value'])) ) ? $vendor_banner['value'] : $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>" class="placeHolder" />
                                <input type="text" name="vendor_banner" id="vendor_banner" style="display: none;" class="user-profile-fields" readonly value="<?php echo (isset($vendor_banner['value']) && (!empty($vendor_banner['value'])) ) ? $vendor_banner['value'] : $WCMp->plugin_url . 'assets/images/banner_placeholder.jpg'; ?>"  />
                            </span>
                            <div class="button-group">   
                                <button class="upload_button wcmp_black_btn moregap two_third_part btn btn-primary" name="vendor_banner_button" id="vendor_banner_button"><span class="dashicons dashicons-upload"></span> <?php _e('Upload', 'dc-woocommerce-multi-vendor') ?></button>
                                <button class="remove_button wcmp_black_btn moregap two_third_part btn btn-primary" name="vendor_banner_remove_button" id="vendor_banner_remove_button"><span class="dashicons dashicons-upload"></span> <?php _e('Replace', 'dc-woocommerce-multi-vendor') ?></button>
                            </div>
                            <div class="clear"></div>
                        </div>       
                    </div>
                    -->
                </div>         
            </div>
        </div>

        <div class="panel panel-default panel-pading pannel-outer-heading">
            <div class="panel-heading d-flex">
                <h3><?php _e('General', 'dc-woocommerce-multi-vendor'); ?></h3>
            </div>
            <div class="panel-body panel-content-padding">
                <div class="wcmp_form1">
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Store Name *', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="text" name="vendor_page_title" value="<?php echo isset($vendor_page_title['value']) ? $vendor_page_title['value'] : ''; ?>"  placeholder="<?php _e('Enter your Store Name here', 'dc-woocommerce-multi-vendor'); ?>">
                        </div>  
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e(' Store Slug *', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon3">
                                    <?php
                                    $dc_vendors_permalinks_array = get_option('dc_vendors_permalinks');
                                    if (isset($dc_vendors_permalinks_array['vendor_shop_base']) && !empty($dc_vendors_permalinks_array['vendor_shop_base'])) {
                                        $store_slug = trailingslashit($dc_vendors_permalinks_array['vendor_shop_base']);
                                    } else {
                                        $store_slug = trailingslashit('vendor');
                                    } echo $shop_page_url = trailingslashit(get_home_url());
                                    echo $store_slug;
                                    ?>
                                </span>		
                                <input class="small no_input form-control" id="basic-url" aria-describedby="basic-addon3" type="text" name="vendor_page_slug" value="<?php echo isset($vendor_page_slug['value']) ? $vendor_page_slug['value'] : ''; ?>" placeholder="<?php _e('Enter your Store Name here', 'dc-woocommerce-multi-vendor'); ?>">
                            </div>	
                        </div>	
                    </div>	
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Store Description', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <?php $vendor_description = isset($vendor_description['value']) ? $vendor_description['value'] : '';
                            $WCMp->wcmp_wp_fields->dc_generate_form_field(array("vendor_description" => array('name' => 'vendor_description', 'type' => $field_type, 'class' => 'no_input form-control regular-textarea', 'value' => $vendor_description, 'settings' => $_wp_editor_settings))); ?>
                            <!--textarea class="no_input form-control" name="vendor_description" cols="" rows=""><?php //echo isset($vendor_description['value']) ? $vendor_description['value'] : ''; ?></textarea-->
                        </div>
                    </div>
                    <?php if (apply_filters('can_vendor_add_message_on_email_and_thankyou_page', true)) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Message to Buyers', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <?php $message_to_buyer = isset($vendor_message_to_buyers['value']) ? $vendor_message_to_buyers['value'] : '';
                            $WCMp->wcmp_wp_fields->dc_generate_form_field(array("vendor_message_to_buyers" => array('name' => 'vendor_message_to_buyers', 'type' => $field_type, 'class' => 'no_input form-control regular-textarea', 'value' => $message_to_buyer, 'settings' => $_wp_editor_settings))); ?>
                            <!--textarea class="no_input form-control" name="vendor_message_to_buyers" cols="" rows=""><?php //echo isset($vendor_message_to_buyers['value']) ? $vendor_message_to_buyers['value'] : ''; ?></textarea-->
                        </div>
                    </div>
                    <?php } ?>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Phone', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="no_input form-control" type="text" name="vendor_phone" placeholder="" value="<?php echo isset($vendor_phone['value']) ? $vendor_phone['value'] : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Email *', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">                            
                            <input class="no_input vendor_email form-control" type="text" placeholder="" readonly  value="<?php echo isset($vendor->user_data->user_email) ? $vendor->user_data->user_email : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Address', 'dc-woocommerce-multi-vendor'); ?></label>     
                        <div class="col-md-6 col-sm-9">                      
                            <div class="row">
                                <div class="col-md-12">
                                    <input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php _e('Address line 1', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_address_1"  value="<?php echo isset($vendor_address_1['value']) ? $vendor_address_1['value'] : ''; ?>">
                                    <input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php _e('Address line 2', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_address_2"  value="<?php echo isset($vendor_address_2['value']) ? $vendor_address_2['value'] : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <select name="vendor_country" id="vendor_country" class="country_to_state user-profile-fields form-control inp-btm-margin regular-select" rel="vendor_country">
                                        <option value=""><?php _e( 'Select a country&hellip;', 'dc-woocommerce-multi-vendor' ); ?></option>
                                        <?php $country_code = get_user_meta($vendor->id, '_vendor_country_code', true);
                                            foreach ( WC()->countries->get_allowed_countries() as $key => $value ) {
                                                echo '<option value="' . esc_attr( $key ) . '"' . selected( esc_attr( $country_code ), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
                                            }
                                        ?>
                                    </select>
                                    <!--input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php //_e('Country', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_country" value="<?php echo isset($vendor_country['value']) ? $vendor_country['value'] : ''; ?>"-->
                                </div>
                                <div class="col-md-6">
                                    <?php $country_code = get_user_meta($vendor->id, '_vendor_country_code', true);
                                    $states = WC()->countries->get_states( $country_code ); ?>
                                    <select name="vendor_state" id="vendor_state" class="state_select user-profile-fields form-control inp-btm-margin regular-select" rel="vendor_state">
                                        <option value=""><?php esc_html_e( 'Select a state&hellip;', 'dc-woocommerce-multi-vendor' ); ?></option>
                                        <?php $state_code = get_user_meta($vendor->id, '_vendor_state_code', true);
                                        if($states):
                                            foreach ( $states as $ckey => $cvalue ) {
                                                echo '<option value="' . esc_attr( $ckey ) . '" ' . selected( $state_code, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
                                            }
                                        endif;
                                        ?>
                                    </select>
                                    <!--input class="no_input form-control inp-btm-margin"  type="text" placeholder="<?php //_e('State', 'dc-woocommerce-multi-vendor'); ?>"  name="vendor_state" value="<?php echo isset($vendor_state['value']) ? $vendor_state['value'] : ''; ?>"-->
                                </div>
                                <div class="col-md-6">
                                    <input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php _e('City', 'dc-woocommerce-multi-vendor'); ?>"  name="vendor_city" value="<?php echo isset($vendor_city['value']) ? $vendor_city['value'] : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php _e('ZIP code', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_postcode" value="<?php echo isset($vendor_postcode['value']) ? $vendor_postcode['value'] : ''; ?>">
                                </div>
                                <?php
                                if (apply_filters('is_vendor_add_external_url_field', false)) {
                                    ?>
                                    <div class="col-md-6">
                                        <input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php _e('External store URL', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_external_store_url" value="<?php echo isset($vendor_external_store_url['value']) ? $vendor_external_store_url['value'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input class="no_input form-control inp-btm-margin" type="text" placeholder="<?php _e('External store URL Label', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_external_store_label" value="<?php echo isset($vendor_external_store_label['value']) ? $vendor_external_store_label['value'] : ''; ?>">
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="timezone_string" class="control-label col-sm-3 col-md-3"><?php _e('Timezone', 'dc-woocommerce-multi-vendor') ?></label>
                        <div class="col-md-6 col-sm-9">
                            <?php
                            $current_offset = get_user_meta($vendor->id, 'gmt_offset', true);
                            $tzstring = get_user_meta($vendor->id, 'timezone_string', true);
                            // Remove old Etc mappings. Fallback to gmt_offset.
                            if (false !== strpos($tzstring, 'Etc/GMT')) {
                                $tzstring = '';
                            }

                            if (empty($tzstring)) { // Create a UTC+- zone if no timezone string exists
                                $check_zone_info = false;
                                if (0 == $current_offset) {
                                    $tzstring = 'UTC+0';
                                } elseif ($current_offset < 0) {
                                    $tzstring = 'UTC' . $current_offset;
                                } else {
                                    $tzstring = 'UTC+' . $current_offset;
                                }
                            }
                            ?>
                            <select id="timezone_string" name="timezone_string" class="form-control" aria-describedby="timezone-description">
                                <?php echo wp_timezone_choice($tzstring, get_user_locale()); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3"><?php _e('Store Location', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">  
                            <?php
                            $api_key = get_wcmp_vendor_settings('google_api_key');
                            if (!empty($api_key)) {
                                ?>
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="text" id="searchStoreAddress" class="store-map-address form-control" placeholder="<?php _e('Enter store location', 'dc-woocommerce-multi-vendor'); ?>">
                                    </div>
                                </div>
                                <div class="vendor_store_map" id="vendor_store_map" style="width: 100%; height: 300px;"></div>
                                <div class="form_area">
                                    <?php
                                    $store_location = get_user_meta($vendor->id, '_store_location', true) ? get_user_meta($vendor->id, '_store_location', true) : '';
                                    $store_lat = get_user_meta($vendor->id, '_store_lat', true) ? get_user_meta($vendor->id, '_store_lat', true) : 0;
                                    $store_lng = get_user_meta($vendor->id, '_store_lng', true) ? get_user_meta($vendor->id, '_store_lng', true) : 0;
                                    ?>
                                    <input type="hidden" name="_store_location" id="store_location" value="<?php echo $store_location; ?>">
                                    <input type="hidden" name="store_address_components" id="store_address_components" value="">
                                    <input type="hidden" name="_store_lat" id="store_lat" value="<?php echo $store_lat; ?>">
                                    <input type="hidden" name="_store_lng" id="store_lng" value="<?php echo $store_lng; ?>">
                                </div>
                                <?php
                                wp_add_inline_script('wcmp-gmaps-api', '(function ($) {
                                    function initialize() {
                                        var latlng = new google.maps.LatLng(' . $store_lat . ',' . $store_lng . ');
                                        var map = new google.maps.Map(document.getElementById("vendor_store_map"), {
                                            center: latlng,
                                            blur : true,
                                            zoom: 15
                                        });
                                        var marker = new google.maps.Marker({
                                            map: map,
                                            position: latlng,
                                            draggable: true,
                                            anchorPoint: new google.maps.Point(0, -29)
                                        });

                                        var input = document.getElementById("searchStoreAddress");
                                        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
                                        var geocoder = new google.maps.Geocoder();
                                        var autocomplete = new google.maps.places.Autocomplete(input);
                                        autocomplete.bindTo("bounds", map);
                                        var infowindow = new google.maps.InfoWindow();   

                                        autocomplete.addListener("place_changed", function() {
                                            infowindow.close();
                                            marker.setVisible(false);
                                            var place = autocomplete.getPlace();
                                            if (!place.geometry) {
                                                window.alert("Autocomplete returned place contains no geometry");
                                                return;
                                            }

                                            // If the place has a geometry, then present it on a map.
                                            if (place.geometry.viewport) {
                                                map.fitBounds(place.geometry.viewport);
                                            } else {
                                                map.setCenter(place.geometry.location);
                                                map.setZoom(17);
                                            }

                                            marker.setPosition(place.geometry.location);
                                            marker.setVisible(true);
                                            
                                            bindDataToForm(place.formatted_address,place.geometry.location.lat(),place.geometry.location.lng(),place.address_components);
                                            infowindow.setContent(place.formatted_address);
                                            infowindow.open(map, marker);
                                            showTooltip(infowindow,marker,place.formatted_address);

                                        });
                                        google.maps.event.addListener(marker, "dragend", function() {
                                            geocoder.geocode({"latLng": marker.getPosition()}, function(results, status) {
                                                if (status == google.maps.GeocoderStatus.OK) {
                                                    if (results[0]) {    
                                                        bindDataToForm(results[0].formatted_address,marker.getPosition().lat(),marker.getPosition().lng(), results[0].address_components);
                                                        infowindow.setContent(results[0].formatted_address);
                                                        infowindow.open(map, marker);
                                                        showTooltip(infowindow,marker,results[0].formatted_address);
                                                        document.getElementById("searchStoreAddress");
                                                    }
                                                }
                                            });
                                        });
                                    }

                                    function bindDataToForm(address,lat,lng,address_components){
                                        document.getElementById("store_location").value = address;
                                        document.getElementById("store_address_components").value = JSON.stringify(address_components);
                                        document.getElementById("store_lat").value = lat;
                                        document.getElementById("store_lng").value = lng;
                                    }
                                    function showTooltip(infowindow,marker,address){
                                       google.maps.event.addListener(marker, "click", function() { 
                                            infowindow.setContent(address);
                                            infowindow.open(map, marker);
                                        });
                                    }
                                    google.maps.event.addDomListener(window, "load", initialize);
                              })(jQuery);');
                                ?>
                            <?php
                            } else {
                                echo trim(__('Please contact your administrator to enable Google map feature.', 'dc-woocommerce-multi-vendor'));
                            }
                            ?>
                        </div>
                    </div>
                    <!-- from group end -->
                </div>
            </div>
        </div>

        <div class="panel panel-default pannel-outer-heading">
            <div class="panel-heading d-flex">
                <h3><?php _e('Social Media', 'dc-woocommerce-multi-vendor'); ?></h3>
            </div>
            <div class="panel-body panel-content-padding form-horizontal">
                <div class="wcmp_media_block">

                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3 facebook"><?php _e('Facebook', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="form-control" type="url"   name="vendor_fb_profile" value="<?php echo isset($vendor_fb_profile['value']) ? $vendor_fb_profile['value'] : ''; ?>">
                        </div>  
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3 twitter"><?php _e('Twitter', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="form-control" type="url"   name="vendor_twitter_profile" value="<?php echo isset($vendor_twitter_profile['value']) ? $vendor_twitter_profile['value'] : ''; ?>">
                        </div>  
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3 linkedin"><?php _e('LinkedIn', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="form-control" type="url"  name="vendor_linkdin_profile" value="<?php echo isset($vendor_linkdin_profile['value']) ? $vendor_linkdin_profile['value'] : ''; ?>">
                        </div>  
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3 google-plus"><?php _e('Google Plus', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="form-control" type="url"   name="vendor_google_plus_profile" value="<?php echo isset($vendor_google_plus_profile['value']) ? $vendor_google_plus_profile['value'] : ''; ?>">
                        </div>  
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3 youtube"><?php _e('YouTube', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="form-control" type="url"   name="vendor_youtube" value="<?php echo isset($vendor_youtube['value']) ? $vendor_youtube['value'] : ''; ?>">
                        </div>  
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3 col-md-3 instagram"><?php _e('Instagram', 'dc-woocommerce-multi-vendor'); ?></label>
                        <div class="col-md-6 col-sm-9">
                            <input class="form-control" type="url"   name="vendor_instagram" value="<?php echo isset($vendor_instagram['value']) ? $vendor_instagram['value'] : ''; ?>">
                        </div>  
                    </div>
                    <?php do_action( 'wcmp_vendor_add_extra_social_link', $vendor ); ?>
                </div>
            </div>
        </div>    

<?php if (apply_filters('can_vendor_edit_shop_template', false)): ?>
            <div class="panel panel-default panel-pading">
                <div class="panel-heading d-flex">
                    <h3><?php _e('Shop Template', 'dc-woocommerce-multi-vendor'); ?></h3>
                </div>
                <div class="panel-body">
                    <ul class="wcmp_template_list list-unstyled">
                        <?php
                        $template_options = apply_filters('wcmp_vendor_shop_template_options', array('template1' => $WCMp->plugin_url . 'assets/images/template1.png', 'template2' => $WCMp->plugin_url . 'assets/images/template2.png', 'template3' => $WCMp->plugin_url . 'assets/images/template3.png'));
                        $shop_template = get_wcmp_vendor_settings('wcmp_vendor_shop_template', 'vendor', 'dashboard', 'template1');
                        $shop_template = get_wcmp_vendor_settings('can_vendor_edit_shop_template', 'vendor', 'dashboard', false) && get_user_meta($vendor->id, '_shop_template', true) ? get_user_meta($vendor->id, '_shop_template', true) : $shop_template;
                        foreach ($template_options as $template => $template_image):
                            ?>
                            <li>
                                <label>
                                    <input type="radio" <?php checked($template, $shop_template); ?> name="_shop_template" value="<?php echo $template; ?>" />
                                    <i class="dashicons dashicons-yes"></i>
                                    <div class="template-overlay"></div>
                                    <img src="<?php echo $template_image; ?>" />
                                </label>
                            </li>
            <?php endforeach; ?>
                    </ul>                    
                </div>
            </div>    
<?php endif; ?>
<?php do_action('wcmp_after_shop_front'); ?>
<?php do_action('other_exta_field_dcmv'); ?>
        <div class="action_div_space"> </div>
        <div class="wcmp-action-container">
            <button type="submit" class="btn btn-default" name="store_save"><?php _e('Save Options', 'dc-woocommerce-multi-vendor'); ?></button>
            <div class="clear"></div>
        </div>
    </form>
</div>