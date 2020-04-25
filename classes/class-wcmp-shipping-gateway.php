<?php

if (!defined('ABSPATH'))
    exit;

/**
 * WCMp Shipping Class
 *
 * @version		3.2.2
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Shipping_Gateway {

    /**
     * Initialize shipping.
     */
    public function __construct() {
        
        add_action('woocommerce_shipping_init', array(&$this, 'load_shipping_methods'));
        add_filter('woocommerce_shipping_methods', array(&$this, 'add_shipping_methods'));
        add_filter( 'woocommerce_cart_shipping_packages', array(&$this, 'add_vendor_id_to_package'), 99);
        
        // Load vendor shipping methods configure fields
        add_action('wcmp_vendor_shipping_free_shipping_configure_form_fields', array($this, 'free_shipping_configure_form_fields'), 10, 2);
        add_action('wcmp_vendor_shipping_flat_rate_configure_form_fields', array($this, 'flat_rate_configure_form_fields'), 10, 2);
        add_action('wcmp_vendor_shipping_local_pickup_configure_form_fields', array($this, 'local_pickup_configure_form_fields'), 10, 2);
    }
    
    /**
     * Loads shipping zones & methods.
     * 
     */
    public function load_shipping_methods() {
        self::load_class( 'shipping-zone', 'helpers' );
        self::load_class( 'shipping-method' );
    }
    /**
     * WCMp Shipping methods register themselves by returning their main class name through the woocommerce_shipping_methods filter.
     *
     * @return array
     */
    public function add_shipping_methods($methods) {
        $methods['wcmp_vendor_shipping'] = 'WCMP_Vendor_Shipping_Method';
        return apply_filters( 'wcmp_vendor_shipping_method_init', $methods );
    }

    public function add_vendor_id_to_package($packages) {
        foreach ($packages as $key => $package) {
            $packages[$key]['vendor_id'] = ($key) ? $key : $package['user']['ID']; // $key is the vendor_id
        }
        return $packages;
    }
    
    /**
     * CLass Loader
     *
     * @access public
     * @param mixed $class_name
     * @param mixed $dir
     * @return void
     */
    public static function load_class($class_name = '', $dir = '') {
        global $WCMp;
        if ('' != $class_name && defined( 'WCMp_PLUGIN_TOKEN' ) ) {
            $token = WCMp_PLUGIN_TOKEN;
            if($dir)
                require_once ('shipping-gateways/' . trailingslashit( $dir ) . 'class-' . esc_attr($token) . '-' . esc_attr($class_name) . '.php');
            else
                require_once ('shipping-gateways/class-' . esc_attr($token) . '-' . esc_attr($class_name) . '.php');
        }
    }
    
    
    public function free_shipping_configure_form_fields( $shipping_method, $postdata ){
        ?>
        <div id="wrapper-<?php echo $shipping_method['id'] ?>">
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Method Title', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="method_title_fs" class="form-control" type="text" name="title" value="<?php echo isset($shipping_method['title']) ? $shipping_method['title'] : ''; ?>" placholder="<?php _e( 'Enter method title', 'dc-woocommerce-multi-vendor' ); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Minimum order amount for free shipping', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="minimum_order_amount_fs" class="form-control" type="text" name="min_amount" value="<?php echo isset($shipping_method['settings']['min_amount']) ? $shipping_method['settings']['min_amount'] : ''; ?>" placholder="<?php _e( '0.00', 'dc-woocommerce-multi-vendor' ); ?>">
                </div>
            </div>
            <input type="hidden" id="method_description_fs" name="description" value="<?php echo isset($shipping_method['settings']['description']) ? $shipping_method['settings']['description'] : ''; ?>" />
            <input type="hidden" id="method_cost_fs" name="cost" value="0" />
            <input type="hidden" id="method_tax_status_fs" name="tax_status" value="none" />
        </div>
        <?php
    }
    
    public function flat_rate_configure_form_fields( $shipping_method, $postdata ){
        $is_method_taxable_array = array(
            'none'      => __( 'None', 'dc-woocommerce-multi-vendor' ),
            'taxable'   => __( 'Taxable' , 'dc-woocommerce-multi-vendor' )
        );

        $calculation_type = array(
            'class' => __( 'Per class: Charge shipping for each shipping class individually', 'dc-woocommerce-multi-vendor' ),
            'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'dc-woocommerce-multi-vendor' ),
        );
        ?>
        <div id="wrapper-<?php echo $shipping_method['id'] ?>">
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Method Title', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="method_title_fr" class="form-control" type="text" name="title" value="<?php echo isset($shipping_method['title']) ? $shipping_method['title'] : ''; ?>" placholder="<?php _e( 'Enter method title', 'dc-woocommerce-multi-vendor' ); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Cost', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="method_cost_fr" class="form-control" type="text" name="cost" value="<?php echo isset($shipping_method['settings']['cost']) ? $shipping_method['settings']['cost'] : ''; ?>" placholder="<?php _e( '0.00', 'dc-woocommerce-multi-vendor' ); ?>">
                </div>
            </div>
            <?php if( apply_filters( 'show_shipping_zone_tax', true ) ) { ?>
                <div class="form-group">
                    <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Tax Status', 'dc-woocommerce-multi-vendor' ); ?></label>
                    <div class="col-md-9 col-sm-9">
                        <select id="method_tax_status_fr" class="form-control" name="tax_status">
                            <?php foreach( $is_method_taxable_array as $key => $value ) { 
                                $selected = '';
                                if (isset($shipping_method['settings']['tax_status']) && $shipping_method['settings']['tax_status'] == $key) {
                                   $selected = 'selected="selected"';
                                }?>
                                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>
            <input type="hidden" id="method_description_fr" name="description" value="<?php echo isset($shipping_method['settings']['description']) ? $shipping_method['settings']['description'] : ''; ?>" />
            <?php
            if (!apply_filters( 'hide_vendor_shipping_classes', false )) { ?>
                <div class="wcmp_shipping_classes">
                    <hr>
                    <h2><?php _e('Shipping Class Cost', 'dc-woocommerce-multi-vendor'); ?></h2> 
                    <div class="description mb-15"><?php _e('These costs can be optionally entered based on the shipping class set per product( This cost will be added with the shipping cost above).', 'dc-woocommerce-multi-vendor'); ?></div>
                    <?php

                    // $shipping_classes =  WC()->shipping->get_shipping_classes();
                    $shipping_classes =  get_vendor_shipping_classes();

                    if(empty($shipping_classes)) {
                        echo '<div class="no_shipping_classes">' . __("No Shipping Classes set by Admin", 'dc-woocommerce-multi-vendor') . '</div>';
                    } else {
                        foreach ($shipping_classes as $shipping_class ) {
                            ?>
                            <div class="form-group">
                                <label for="" class="control-label col-sm-3 col-md-3"><?php printf( __( 'Cost of Shipping Class: "%s"', 'dc-woocommerce-multi-vendor' ), $shipping_class->name ); ?></label>
                                <div class="col-md-9 col-sm-9">
                                    <input id="<?php echo $shipping_class->slug; ?>" class="form-control sc_vals" type="text" name="class_cost_<?php echo $shipping_class->term_id; ?>" value="<?php echo isset($shipping_method['settings']['class_cost_'.$shipping_class->term_id]) ? $shipping_method['settings']['class_cost_'.$shipping_class->term_id] : ''; ?>" placholder="<?php _e( 'N/A', 'dc-woocommerce-multi-vendor' ); ?>" data-shipping_class_id="<?php echo $shipping_class->term_id; ?>">
                                    <div class="description"><?php _e( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'dc-woocommerce-multi-vendor' ) . '<br/><br/>' . _e( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'dc-woocommerce-multi-vendor' ); ?></div>
                                </div>
                            </div>
                            <?php 
                        }
                        ?>
                        <div class="form-group">
                            <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Calculation type', 'dc-woocommerce-multi-vendor' ); ?></label>
                            <div class="col-md-9 col-sm-9">
                                <select id="calculation_type" class="form-control" name="calculation_type">
                                    <?php foreach( $calculation_type as $key => $value ) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <?php
                    } ?>
                </div>
            <?php } ?>
        </div> 
        <?php
    }
    
    public function local_pickup_configure_form_fields( $shipping_method, $postdata ){
        $is_method_taxable_array = array(
            'none'      => __( 'None', 'dc-woocommerce-multi-vendor' ),
            'taxable'   => __( 'Taxable' , 'dc-woocommerce-multi-vendor' )
        );

        ?>
        <div id="wrapper-<?php echo $shipping_method['id'] ?>">
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Method Title', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="method_title_lp" class="form-control" type="text" name="title" value="<?php echo isset($shipping_method['title']) ? $shipping_method['title'] : ''; ?>" placholder="<?php _e( 'Enter method title', 'dc-woocommerce-multi-vendor' ); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Cost', 'dc-woocommerce-multi-vendor' ); ?></label>
                <div class="col-md-9 col-sm-9">
                    <input id="method_cost_lp" class="form-control" type="text" name="cost" value="<?php echo isset($shipping_method['settings']['cost']) ? $shipping_method['settings']['cost'] : ''; ?>" placholder="<?php _e( '0.00', 'dc-woocommerce-multi-vendor' ); ?>">
                </div>
            </div>
            <?php if( apply_filters( 'show_shipping_zone_tax', true ) ) { ?>
                <div class="form-group">
                    <label for="" class="control-label col-sm-3 col-md-3"><?php _e( 'Tax Status', 'dc-woocommerce-multi-vendor' ); ?></label>
                    <div class="col-md-9 col-sm-9">
                        <select id="method_tax_status_lp" class="form-control" name="tax_status">
                            <?php foreach( $is_method_taxable_array as $key => $value ) { ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>
            <input type="hidden" id="method_description_lp" name="description" value="<?php echo isset($shipping_method['settings']['description']) ? $shipping_method['settings']['description'] : ''; ?>" />
        </div> 
        <?php
    }

}
