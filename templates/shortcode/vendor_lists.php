<?php
/**
 * The template for displaying vendor lists
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/shortcode/vendor_lists.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $WCMp;
?>

<div id="wcmp-store-conatiner">
    <!-- Map Start -->
    <div class="wcmp-store-locator-wrap">
        <?php if(apply_filters('wcmp_vendor_list_enable_store_locator_map', true)) : ?>
        <div id="wcmp-vendor-list-map" class="wcmp-store-map-wrapper"></div>
        <form name="vendor_list_sort" method="post">
            <input type="hidden" id="wcmp_vlist_center_lat" name="wcmp_vlist_center_lat" value=""/>
            <input type="hidden" id="wcmp_vlist_center_lng" name="wcmp_vlist_center_lng" value=""/>
            <div class="wcmp-store-map-filter">
                <div class="wcmp-inp-wrap">
                    <input type="text" name="locationText" id="locationText" placeholder="<?php _e('Enter Address', 'dc-woocommerce-multi-vendor'); ?>" value="<?php echo isset($request['locationText']) ? $request['locationText'] : ''; ?>">
                </div>
                <div class="wcmp-inp-wrap">
                    <select name="radiusSelect" id="radiusSelect">
                        <option value=""><?php _e('Within', 'dc-woocommerce-multi-vendor'); ?></option>
                        <?php if($radius) :
                        $selected_radius = isset($request['radiusSelect']) ? $request['radiusSelect'] : '';
                        foreach ($radius as $value) {
                            echo '<option value="'.$value.'" '.selected( esc_attr( $selected_radius ), $value, false ).'>'.$value.'</option>';
                        }
                        endif;
                        ?>
                    </select>
                </div>
                <div class="wcmp-inp-wrap">
                    <select name="distanceSelect" id="distanceSelect">
                        <?php $selected_distance = isset($request['distanceSelect']) ? $request['distanceSelect'] : ''; ?>
                        <option value="M" <?php echo selected( $selected_distance, "M", false ); ?>><?php _e('Miles', 'dc-woocommerce-multi-vendor'); ?></option>
                        <option value="K" <?php echo selected( $selected_distance, "K", false ); ?>><?php _e('Kilometers', 'dc-woocommerce-multi-vendor'); ?></option>
                        <option value="N" <?php echo selected( $selected_distance, "N", false ); ?>><?php _e('Nautical miles', 'dc-woocommerce-multi-vendor'); ?></option>
                        <?php do_action('wcmp_vendor_list_sort_distanceSelect_extra_options'); ?>
                    </select>
                </div>
                <?php do_action( 'wcmp_vendor_list_vendor_sort_map_extra_filters', $request ); ?>
                <input type="submit" name="vendorListFilter" value="<?php _e('Submit', 'dc-woocommerce-multi-vendor'); ?>">
            </div>
        </form>
        <?php endif; ?>
        <div class="wcmp-store-map-pagination">
            <p class="wcmp-pagination-count wcmp-pull-right">
                <?php
                if ( $vendor_total <= $per_page || -1 === $per_page ) {
                        /* translators: %d: total results */
                        printf( _n( 'Viewing the single vendor', 'Viewing all %d vendors', $vendor_total, 'dc-woocommerce-multi-vendor' ), $vendor_total );
                } else {
                        $first = ( $per_page * $current ) - $per_page + 1;
                        if(!apply_filters('wcmp_vendor_list_ignore_pagination', false)) {
                            $last  = min( $vendor_total, $per_page * $current );
                        }else{
                            $last  = $vendor_total;
                        }
                        /* translators: 1: first result 2: last result 3: total results */
                        printf( _nx( 'Viewing the single vendor', 'Viewing %1$d&ndash;%2$d of %3$d vendors', $vendor_total, 'with first and last result', 'dc-woocommerce-multi-vendor' ), $first, $last, $vendor_total );
                }
                ?>
            </p>
            
            <form name="vendor_sort" method="post" >
                <div class="vendor_sort">
                    <select class="select short" id="vendor_sort_type" name="vendor_sort_type">
                        <?php
                        $vendor_sort_type = apply_filters('wcmp_vendor_list_vendor_sort_type', array(
                            'registered' => __('By date', 'dc-woocommerce-multi-vendor'),
                            'name' => __('By Alphabetically', 'dc-woocommerce-multi-vendor'),
                            'category' => __('By Category', 'dc-woocommerce-multi-vendor'),
                            'shipping' => __('By Shipping', 'dc-woocommerce-multi-vendor')

                        ));
                        if ($vendor_sort_type && is_array($vendor_sort_type)) {
                            foreach ($vendor_sort_type as $key => $label) {
                                $selected = '';
                                if (isset($request['vendor_sort_type']) && $request['vendor_sort_type'] == $key) {
                                    $selected = 'selected="selected"';
                                }
                                echo '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php
                    $product_category = get_terms('product_cat');
                    $options_html = '';
                    $sort_category = isset($request['vendor_sort_category']) ? $request['vendor_sort_category'] : '';
                    foreach ($product_category as $category) {
                        if ($category->term_id == $sort_category) {
                            $options_html .= '<option value="' . esc_attr($category->term_id) . '" selected="selected">' . esc_html($category->name) . '</option>';
                        } else {
                            $options_html .= '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                    }
                    ?>
                    <select name="vendor_country" id="vendor_country" class="country_to_state vendors_sort_shipping_fields form-control regular-select" rel="vendor_country">
                        <option value=""><?php _e( 'Select a country&hellip;', 'dc-woocommerce-multi-vendor' ); ?></option>
                        <?php $country_code = 0;
                        foreach ( WC()->countries->get_allowed_countries() as $key => $value ) {
                            echo '<option value="' . esc_attr( $key ) . '"' . selected( esc_attr( $country_code ), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
                        }
                        ?>
                    </select>
                    <!-- Sort by Shipping -->
                    <select name="vendor_state" id="vendor_state" class="state_select vendors_sort_shipping_fields form-control regular-select" rel="vendor_state">
                        <option value=""><?php esc_html_e( 'Select a state&hellip;', 'dc-woocommerce-multi-vendor' ); ?></option>
                    </select>
                    <input class="vendors_sort_shipping_fields" type="text" placeholder="<?php _e('ZIP code', 'dc-woocommerce-multi-vendor'); ?>" name="vendor_postcode_list" value="<?php echo isset($request['vendor_postcode_list']) ? $request['vendor_postcode_list'] : ''; ?>">
                    <!-- Sort by Category -->
                    <select name="vendor_sort_category" id="vendor_sort_category" class="select"><?php echo $options_html; ?></select>
                    <?php do_action( 'wcmp_vendor_list_vendor_sort_extra_attributes', $request ); ?>
                    <input value="<?php echo __('Sort', 'dc-woocommerce-multi-vendor'); ?>" type="submit">
                </div>
            </form>

        </div>
    </div>
    <!-- Map End -->

    <div class="wcmp-store-list-wrap">
        <?php
        if ($vendors && is_array($vendors)) {
            foreach ($vendors as $vendor_id) {
                $vendor = get_wcmp_vendor($vendor_id);
                $image = $vendor->get_image() ? $vendor->get_image('image', array(125, 125)) : $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
                $banner = $vendor->get_image('banner') ? $vendor->get_image('banner') : '';
                ?>

                <div class="wcmp-store-list">
                    <?php do_action('wcmp_vendor_lists_single_before_image', $vendor->term_id, $vendor->id); ?>
                    <div class="wcmp-profile-wrap">
                        <div class="wcmp-cover-picture" style="background-image: url('<?php if($banner) echo $banner; ?>');"></div>
                        <div class="store-badge-wrap">
                            <?php do_action('wcmp_vendor_lists_vendor_store_badges', $vendor); ?>
                        </div>
                        <div class="wcmp-store-info">
                            <div class="wcmp-store-picture">
                                <img class="vendor_img" src="<?php echo $image; ?>" id="vendor_image_display">
                            </div>
                            <?php
                                $rating_info = wcmp_get_vendor_review_info($vendor->term_id);
                                $WCMp->template->get_template('review/rating_vendor_lists.php', array('rating_val_array' => $rating_info));
                            ?>
                        </div>
                    </div>
                    <?php do_action('wcmp_vendor_lists_single_after_image', $vendor->term_id, $vendor->id); ?>
                    <div class="wcmp-store-detail-wrap">
                        <?php do_action('wcmp_vendor_lists_vendor_before_store_details', $vendor); ?>
                        <ul class="wcmp-store-detail-list">
                            <li>
                                <i class="wcmp-font ico-store-icon"></i>
                                <?php $button_text = apply_filters('wcmp_vendor_lists_single_button_text', $vendor->page_title); ?>
                                <a href="<?php echo $vendor->get_permalink(); ?>" class="store-name"><?php echo $button_text; ?></a>
                                <?php do_action('wcmp_vendor_lists_single_after_button', $vendor->term_id, $vendor->id); ?>
                                <?php do_action('wcmp_vendor_lists_vendor_after_title', $vendor); ?>
                            </li>
                            <?php if($vendor->get_formatted_address()) : ?>
                            <li>
                                <i class="wcmp-font ico-location-icon2"></i>
                                <p><?php echo $vendor->get_formatted_address(); ?></p>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <?php do_action('wcmp_vendor_lists_vendor_after_store_details', $vendor); ?>
                    </div>
                </div>
                <?php
            }
        } else {
            _e('No vendor found!', 'dc-woocommerce-multi-vendor');
        }
        ?>
    </div>
    <!-- pagination --> 
    <?php if(!apply_filters('wcmp_vendor_list_ignore_pagination', false)) : ?>
    <div class="wcmp-pagination">
        <?php
            echo paginate_links( apply_filters( 'wcmp_vendor_list_pagination_args', array( 
                    'base'         => $base,
                    'format'       => $format,
                    'add_args'     => $request,
                    'current'      => max( 1, $current ),
                    'total'        => $total,
                    'prev_text'    => 'Prev',
                    'next_text'    => 'Next',
                    'type'         => 'list',
                    'end_size'     => 3,
                    'mid_size'     => 3,
            ) ) );
	?>
    </div>
    <?php endif; ?>
</div> 