<?php
/**
 * WCMp Vendor Product Search Widget
 *
 * @author    WC Marketplace
 * @category  Widgets
 * @package   WCMp/Widgets
 * @version   2.2.0
 * @extends   WP_Widget
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class DC_Widget_Vendor_Product_Search extends WP_Widget {
    /**
     * constructor
     *
     * @access public
     * @return void
     */
    function __construct() {
        global $WCMp, $wp_version;
       
        $this->widget_cssclass = 'widget_product_vendor_product_search';
        $this->widget_description = __('Display list of vendors products on shop page .', 'dc-woocommerce-multi-vendor');
        $this->widget_idbase = 'dc_product_vendors_products_search';
        $this->widget_title = __('WCMp: Vendors Product Search', 'dc-woocommerce-multi-vendor');

        // Widget settings
        $widget_ops = array('classname' => $this->widget_cssclass, 'description' => $this->widget_description);

        $control_ops = array('width' => 250, 'height' => 100, 'id_base' => $this->widget_idbase);

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
    function widget($args, $instance) {
        global $WCMp, $woocommerce;
        extract($args, EXTR_SKIP);

        $show_widget = true;

        if ($show_widget) {
            if (is_tax($WCMp->taxonomy->taxonomy_name)) {
                // enqueue scripts
                wp_enqueue_script( 'frontend_js' );
                
                if ( isset( $instance['title'] ) ) {
                    $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
                } else {
                    $title = false;
                }
                echo $before_widget;

                if ($title) {
                    echo $before_title . $title . $after_title;
                }

                do_action($this->widget_cssclass . '_top');

                $WCMp->template->get_template('widget/vendor-product-search.php');

                do_action($this->widget_cssclass . '_bottom');

                echo $after_widget;
            }
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
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        // Sanitise inputs
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
        global $WCMp;

        $defaults = array(
            'title' => '',
        );
        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title (optional):', 'dc-woocommerce-multi-vendor'); ?></label>
            <input type="text" name="<?php echo $this->get_field_name('title'); ?>"  value="<?php echo $instance['title']; ?>" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" />
        </p>
        <span class="description"><?php _e('This widget shows a vendor product search form on the vendor shop page..', 'dc-woocommerce-multi-vendor') ?> </span>
        <?php
    }
}
