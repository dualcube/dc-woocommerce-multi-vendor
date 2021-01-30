<?php
/**
 * WCMp Store Location Widget
 *
 * @author    WC Marketplace
 * @category  Widgets
 * @package   WCMp/Widgets
 * @version   2.2.0
 * @extends   WP_Widget
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class DC_Woocommerce_Store_Location_Widget extends WP_Widget {

    /**
     * constructor
     *
     * @access public
     * @return void
     */
    function __construct() {
        global $WCMp, $wp_version;

        // Widget variable settings
        $this->widget_idbase = 'dc-vendor-store-location';
        $this->widget_title = __('WCMp: Vendor\'s Store Location', 'dc-woocommerce-multi-vendor');
        $this->widget_description = __('Display the vendor\'s store location on Google Maps.', 'dc-woocommerce-multi-vendor');
        $this->widget_cssclass = 'widget_wcmp_store_location';

        // Widget settings
        $widget_ops = array('classname' => $this->widget_cssclass, 'description' => $this->widget_description);

        // Widget control settings
        $control_ops = array('width' => 250, 'height' => 350, 'id_base' => $this->widget_idbase);

        // Create the widget
        if ($wp_version >= 4.3) {
            parent::__construct($this->widget_idbase, $this->widget_title, $widget_ops, $control_ops);
        } else {
            $this->WP_Widget($this->widget_idbase, $this->widget_title, $widget_ops, $control_ops);
        }
    }

    /**
     * widget function.
     *
     * @see WP_Widget
     * @access public
     * @param array $args
     * @param array $instance
     * @return void
     */
    public function widget($args, $instance) {
        global $WCMp, $woocommerce;
        extract($args, EXTR_SKIP);
        $vendor_id = false;
        $vendors = false;
        // Only show current vendor widget when showing a vendor's product(s)
        $show_widget = false;
        $WCMp->library->load_gmap_api();
        
        if (wcmp_is_store_page()) {
            $vendor_id = wcmp_find_shop_page_vendor();
            if ($vendor_id) {
                $vendor = get_wcmp_vendor($vendor_id);
                $show_widget = true;
            }
        }

        if (is_singular('product')) {
            global $post;
            $vendor = get_wcmp_product_vendors($post->ID);
            if ($vendor) {
                $show_widget = true;
            }
        }

        if ($show_widget && isset($vendor->id)) {
           
            $location = get_user_meta($vendor->id, '_store_location', true);
            $store_lat = get_user_meta($vendor->id, '_store_lat', true);
            $store_lng = get_user_meta($vendor->id, '_store_lng', true);
            
            $args = array(
                'instance' => $instance,
                'gmaps_link' => esc_url(add_query_arg(array('q' => urlencode($location)), '//maps.google.com/')),
                'location' => $location,
                'store_lat' => $store_lat,
                'store_lng' => $store_lng
            );

            // Set up widget title
            if ($instance['title']) {
                $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
            } else {
                $title = false;
            }

            // Before widget (defined by themes)
            echo $before_widget;

            // Display the widget title if one was input (before and after defined by themes).
            if ($title) {
                echo $before_title . $title . $after_title;
            }

            // Action for plugins/themes to hook onto
            do_action($this->widget_cssclass . '_top');

            $WCMp->template->get_template('widget/store-location.php', $args);

            // Action for plugins/themes to hook onto
            do_action($this->widget_cssclass . '_bottom');

            // After widget (defined by themes).
            echo $after_widget;
        }
    }

    /**
     * update function.
     *
     * @see WP_Widget->update
     * @access public
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /**
     * The form on the widget control in the widget administration area
     * @since  1.0.0
     * @param  array $instance The settings for this instance.
     * @return void
     */
    public function form($instance) {
        global $WCMp, $woocommerce;
        $defaults = array(
            'title' => __('Store Location', 'dc-woocommerce-multi-vendor'),
        );

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'dc-woocommerce-multi-vendor') ?>:
                <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
            </label>
        </p>
        <?php
    }

}
