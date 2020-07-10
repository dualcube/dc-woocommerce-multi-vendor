<?php
/**
 * Load WCMp BuddyPress package class.
 *
 */
class WCMp_BuddyPress {
        /**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;
        
        /**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init the plugin.
	 */
	public function init() {
		if ( ! $this->has_dependencies() ) {
			return;
		}
		$this->on_plugins_loaded();
	}
        
	/**
	 * Check dependencies exist.
	 *
	 * @return boolean
	 */
	protected function has_dependencies() {
		return class_exists( 'WCMp' ) && function_exists( 'BuddyPress' );
	}
        
	/**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {
		 /***************** Create a endpoint "Shop page" in buddypress if display member is vendor  *********************/
        add_action( 'bp_setup_nav', array( $this, 'bp_nav_added_vendor_shop' ), 305 );

        add_filter( 'bp_core_fetch_avatar_no_grav', array( $this, 'fetch_avatar_no_grav' ) );

        add_filter( 'bp_core_avatar_default', array( $this, 'set_avatar_default'  ) , 10 , 2 );
		/*************************  Default Avatar cover image  ***********************************/
        add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', array( $this, 'bpcp_profile_cover_default_from_vendor'), 10, 1 );

        /***************** Update from vendor profile to buddypress profile ****************************/
        add_action( 'before_wcmp_vendor_dashboard',array($this, 'save_vendor_dashboard_data_change') , 99 );

        /********************** If members delete there image in buddypress,vendor dashboard image also removed if sync done before **************************/
        add_action( 'bp_core_delete_existing_avatar', array( $this, 'delete_avatar_existing_image' ) );

        /********************** If members delete there Cover in buddypress,vendor dashboard Cover image also removed if sync done before **************************/
        add_action( "xprofile_cover_image_deleted", array( $this, 'cover_image_deleted_from_buddypress' ) );

        /***********************  Display name as per vendor store name  *******************************/
        add_filter( 'bp_get_displayed_user_mentionname', array( $this, 'wcmp_vendor_user_store_name' ) );

        /************************ BuddyPress setting at Storefont  ****************************************/
        add_action('wcmp_after_shop_front', array( $this, 'buddypress_compatibility_at_storefont') );

        /******************** (WCMp->settings->capabilities) Give setting on admin end for capabilities to vendor *************************/
        add_filter( "settings_capabilities_product_tab_options", array( $this, 'wcmp_buddypress_capability_to_vendor' ) );

        /**********************************  Save affiliate for admin capability section **************************************/
        add_filter( "settings_capabilities_product_tab_new_input", array( $this, 'save_wcmp_buddypress_capability_to_vendor' ), 99 , 2 );

        add_filter( 'wcmp_vendor_fields', array( $this, 'wcmp_save_storefont_data' ), 99, 2 );
        add_action( 'wcmp_vendor_add_extra_social_link', array( $this, 'wcmp_add_storefont_buddypress_link' ) );
        add_action( 'wcmp_vendor_store_header_social_link', array( $this, 'wcmp_vendor_store_header_bp_link' ) );
        // Buddypress social link at admin end
        add_filter("settings_vendors_social_tab_options", array( $this, "wcmp_buddypress_tab_admin" ), 10 , 2);
	}
        
	/***************** Create a endpoint "Shop page" in buddypress if display member is vendor  *********************/

    public function bp_nav_added_vendor_shop() {
    	global $bp ,$post;
    	if (!is_user_wcmp_vendor(bp_displayed_user_id())) return;
    	bp_core_new_nav_item( apply_filters( 'bp_nav_wcmp_shop_page', 
    		array(
    			'name' => __('Shop','dc-woocommerce-multi-vendor'),
    			'slug' => 'shop',
    			'screen_function' => array($this, 'bp_custom_menu_page_screen_vendor_function' ),
    			'default_subnav_slug' => 'shop',
    			'position' => 99,
    			)
    		)
    	);
    }

	// screen function callback to initialize the action
    public function bp_custom_menu_page_screen_vendor_function(){
    	add_action( 'bp_template_content', array($this, 'bp_custom_menu_page_screen_function_content_vendor' ) );
    	bp_core_load_template( 'members/single/plugins' );
    }
	// display that vendor products
    public function bp_custom_menu_page_screen_function_content_vendor(){
    	$display_user_id = bp_displayed_user_id();
            do_action( 'woocommerce_before_shop_loop' );

    	echo do_shortcode('[wcmp_products id= "'.$display_user_id.'" ]');
    }

    public function fetch_avatar_no_grav( $false ){
    	$prfile_sync = get_user_meta( bp_displayed_user_id(),'wcmp_sync_profile_buddy', true );
    	if (!is_user_wcmp_vendor(bp_displayed_user_id())  ) return $false;
    	if( !$prfile_sync ) return $false;
    	return true;
    } 

    public function set_avatar_default( $avatar, $params ){    	
        global $WCMp;

    	$prfile_sync = get_user_meta( bp_displayed_user_id(),'wcmp_sync_profile_buddy', true );

    	if (!is_user_wcmp_vendor(bp_displayed_user_id()) ) return $avatar;

    	if( !$prfile_sync ) return $avatar;

    	$vendor = get_wcmp_vendor(bp_displayed_user_id());
    	$default = $vendor->get_image('image');
    	if ( $default ) {
    		$settings = $default;
    	} else{
    		$settings = $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
    	}
    	return $settings;
    }

    public function bpcp_profile_cover_default_from_vendor( $settings = array() ) {
    	global $WCMp;
    	$cover_sync = get_user_meta( bp_displayed_user_id(),'wcmp_sync_cover_buddy', true );

    	if (!is_user_wcmp_vendor(bp_displayed_user_id()) ) return $settings;

    	if( !$cover_sync ) return $settings;

    	$vendor = get_wcmp_vendor(bp_displayed_user_id());
		// set vendor banner to members url
    	$default = $vendor->get_image('banner');
    	if ( $default ) {
    		
    		$settings['default_cover'] = $default;
    	}
    	return $settings;
    }

    public function save_vendor_dashboard_data_change(){
    	global $WCMp;
    	if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    		if( $WCMp->endpoints->get_current_endpoint() == 'storefront' ){

				// members cover url
    			$member_cover_image_url = bp_attachments_get_attachment('url', array(
    				'object_dir' => 'members',
    				'item_id' => get_current_user_id(),
    				));
				// members avatars url
    			$avatar_url = bp_core_fetch_avatar( array(
    				'object'  => 'user',
    				'item_id' => get_current_user_id(),
    				'html'    => false,
    				'type'    => 'full',
    				) ) ;
				// vendor image attachment id
    			$vendor_image =  get_user_meta( get_current_user_id(),'_vendor_image', true );
				// vendor banner attachment id
    			$vendor_banner = get_user_meta( get_current_user_id(),'_vendor_banner', true );

				// Sync from BuddyPress to Dashboard
    			if( $_POST['vendor_sync'] == 'buddypress_to' ){

					// If want to sync profile image
    				if( isset( $_POST['profile_image'] ) ) {
						// Check sync already done or not
    					if( wp_get_attachment_image_src($vendor_image)[0] != $avatar_url  ){
							// check if there is no image only default avatar So skip
    						if( strpos($avatar_url, 'gravatar') != true ) {
								// avatar image
    							$main_path = esc_url( bp_core_fetch_avatar( array(
    								'object'  => 'user',
    								'item_id' => get_current_user_id(),
    								'html'    => false,
    								'type'    => 'full',
    								) ) );

    							$attachment = array(
    								'guid' => $main_path,
    								'post_mime_type' => 'image/png',
    								'post_status' => 'inherit'
    								);

    							$url = explode("/uploads/" , $main_path ) ;
    							$attach_id = wp_insert_attachment( $attachment , $url[1]);
								// Buddypress members image uploaded to vendor
    							update_user_meta( get_current_user_id(),'_vendor_image', $attach_id );
    						}
    					}
    				}

					// If want to sync profile image
    				if( isset( $_POST['profile_cover'] ) ) {
						// check members cover exist or not
    					if( $member_cover_image_url ){
							// Check sync already done or not
    						if( wp_get_attachment_image_src($vendor_banner)[0] != $member_cover_image_url ){

    							$attachment = array(
    								'guid' => $member_cover_image_url, 
    								'post_mime_type' => 'image/png',
    								'post_status' => 'inherit'
    								);
    							$url = explode("uploads/" ,$member_cover_image_url ) ;
    							$attach_id = wp_insert_attachment( $attachment , $url[1]);
								// Buddypress members banner uploaded to vendor
    							update_user_meta( get_current_user_id(),'_vendor_banner', $attach_id );
    						}
    					}
    				}

    				// for profile name
    				if( isset( $_POST['profile_name'] ) ) {
    					$currentvendor = get_wcmp_vendor(get_current_user_id());
    					// update shop page name
    					wp_update_term($currentvendor->term_id, $WCMp->taxonomy->taxonomy_name, array('name' => bp_activity_get_user_mentionname( get_current_user_id() )));
    				}

    			} 

				// Sync from Dashboard to BuddyPress
    			if( $_POST['vendor_sync'] == 'dashboard_to' ){

					// For profile image
    				if( isset( $_POST['profile_image'] ) ) {
    					if( wp_get_attachment_image_src($vendor_image)[0] != $avatar_url ){
    						bp_core_delete_existing_avatar( array( 'item_id' => get_current_user_id(), 'object' => 'user' ) );
    						update_user_meta( get_current_user_id() , 'wcmp_sync_profile_buddy' ,true ); 
    					}
    				}

					// for cover image
    				if( isset( $_POST['profile_cover'] ) ) {
    					if( wp_get_attachment_image_src($vendor_banner)[0] != $member_cover_image_url ){
    						bp_attachments_delete_file( array( 'item_id' => get_current_user_id(), 'object_dir' => 'members', 'type' => 'cover-image' ) );
    						update_user_meta( get_current_user_id() , 'wcmp_sync_cover_buddy' ,true );
    					}            
    				}
					// for profile name
    				if( isset( $_POST['profile_name'] ) ) {
    					update_user_meta( get_current_user_id() , 'wcmp_sync_name_buddy' ,true );        
    				}

    			}

    		}
    	}
    }

    public function delete_avatar_existing_image( $args ){
    	if( !is_vendor_dashboard() ){
    		$vendor_image =  get_user_meta( bp_displayed_user_id(),'_vendor_image', true );
    		$url = wp_get_attachment_image_src($vendor_image)[0];
			// Check buddypress image has or not
    		if (strpos($url, 'avatars') !== false) {
    			delete_user_meta( $args['item_id'], '_vendor_image' );
    		} 
    	}
    }

    public function cover_image_deleted_from_buddypress( $args_id ){
    	if( !is_vendor_dashboard() ){

    		$vendor_banner =  get_user_meta( bp_displayed_user_id(),'_vendor_banner', true );
    		$url = wp_get_attachment_image_src($vendor_banner)[0];
			// check buddypress image has or not
    		if (strpos($url, 'buddypress') !== false) {
    			delete_user_meta( $args_id, '_vendor_banner' );
    		} 
    	}
    }

    public function wcmp_vendor_user_store_name( $defalut_name ){

    	if (is_user_wcmp_vendor(bp_displayed_user_id()) ) {
    		$vendor_store_name = get_user_meta( bp_displayed_user_id(),'wcmp_sync_name_buddy', true ) ? get_user_meta( bp_displayed_user_id(),'wcmp_sync_name_buddy', true ) : false;
    		if( !$vendor_store_name ) return $defalut_name;

    		$currentvendor = get_wcmp_vendor(bp_displayed_user_id());
    		$vendor_term = get_term($currentvendor->term_id);    
    		$defalut_name = $vendor_term->name;
    	}
    	return $defalut_name;
    }

    public function buddypress_compatibility_at_storefont(){

    	$buddypress_option = get_option( 'wcmp_capabilities_product_settings_name' ,true );
		// Check backend setting for vendor capability
    	if( !array_key_exists('profile_sync', $buddypress_option) ) return;
    	?>
		<div class="panel panel-default pannel-outer-heading">
		<div class="panel-heading d-flex">
			<h3><?php _e('BuddyPress Setting', 'dc-woocommerce-multi-vendor'); ?></h3>
		</div>
		<div class="panel-body panel-content-padding form-horizontal">
			<div class="wcmp_media_block">
				<div class="form-group">
					<label class="control-label col-sm-3 col-md-3 facebook"><?php _e('Sync Setting', 'dc-woocommerce-multi-vendor'); ?></label>
					<div class="col-md-6 col-sm-9">
						<?php 
						$buddypress_setting = apply_filters( 'buddypress_setting_at_storefont' , array( 'dashboard_to' => __('My store to BuddyPress', 'dc-woocommerce-multi-vendor'), 'buddypress_to' =>__('BuddyPress to My store','dc-woocommerce-multi-vendor') ) ); ?>
						<select name="vendor_sync" id="vendor_sync" class="state_select user-profile-fields form-control inp-btm-margin regular-select" rel="vendor_sync">
							<option value=""><?php esc_html_e( 'Select a option&hellip;', 'dc-woocommerce-multi-vendor' ); ?></option>
							<?php
							if($buddypress_setting):

								foreach ( $buddypress_setting as $ckey => $cvalue ) {
									?>
									<option value="<?php echo $ckey; ?>" ><?php echo $cvalue; ?></option><?php
								}
								endif;
								?>
							</select>
						</div>  

					</div>
				</div>

				<div class="wcmp_media_block">
					<div class="form-group">
						<label class="control-label col-sm-3 col-md-3 facebook"><?php _e('Sync Optios', 'dc-woocommerce-multi-vendor'); ?></label>
						<div class="col-md-6 col-sm-9">

							<input type="checkbox" id="profile_image" name="profile_image" value="image">
							<label for="profile_image"><?php echo __( 'Store Logo / Profile picture' ,'dc-woocommerce-multi-vendor' ); ?> </label><br>
							<input type="checkbox" id="profile_cover" name="profile_cover" value="cover">
							<label for="profile_cover"><?php echo __( 'Cover Photo / Cover Photo', 'dc-woocommerce-multi-vendor' ) ?></label><br>
							<input type="checkbox" id="profile_name" name="profile_name" value="profile_name">
							<label for="profile_name"> <?php echo __('Name / Name','dc-woocommerce-multi-vendor') ?> </label><br>

						</div>
					</div>
				</div>

				<!-- Social media section -->
				

			</div>
		</div>

		
			
			

    	<?php
    }

    /******************** (WCMp->settings->capabilities) Give setting on admin end for capabilities to vendor *************************/
    public function wcmp_buddypress_capability_to_vendor( $data ){
      $data['sections']['default_buddypress_settings_section_type_option'] = array( "title"  => __( 'BuddyPress Sync Options ', 'dc-woocommerce-multi-vendor' ), // Section one
                    "fields" => apply_filters( "wcmp_vendor_buddypress_options", array(
                        "profile_sync" => array('title' => __('Vendor Capability Sync', 'dc-woocommerce-multi-vendor'), 'type' => 'checkbox', 'id' => 'profile_sync', 'label_for' => 'profile_sync', 'name' => 'profile_sync', 'value' => 'Enable' ,'desc' => __('Ignore if BuddyPress is not active' , 'dc-woocommerce-multi-vendor' ) ), // Select
                        )
                    )
                );

      return $data;
    }
	
	/**********************************  Save buddypress for admin capability section **************************************/
    public function save_wcmp_buddypress_capability_to_vendor( $new_input, $input ){
      if ( isset( $input['profile_sync'] ) ) {
          $new_input['profile_sync'] = sanitize_text_field( $input['profile_sync'] );
        }
      return $new_input;
    }

    public function wcmp_save_storefont_data( $user_data ,$user_id ){
    	$vendor = new WCMp_Vendor($user_id);
    	$user_data['vendor_buddypress'] = array(
                'label' => __('BuddyPress Profile', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->buddypress,
                'class' => "user-profile-fields regular-text"
            );
    	return $user_data;
	}
	
    public function wcmp_add_storefont_buddypress_link( $vendor ){
        ?>
        <div class="form-group">
            <label class="control-label col-sm-3 col-md-3 buddypress"><?php _e('BuddyPress', 'dc-woocommerce-multi-vendor'); ?></label>
            <div class="col-md-6 col-sm-9">
                <input class="form-control" type="url" name="vendor_buddypress" value="<?php echo $vendor->buddypress; ?>" >
            </div>  
        </div>
        <?php
    }

    public function wcmp_vendor_store_header_bp_link( $vendor_id ){
        $vendor_buddypress = get_user_meta($vendor_id, '_vendor_buddypress', true);
        if ($vendor_buddypress) { ?> <a target="_blank" href="<?php echo esc_url($vendor_buddypress); ?>"><i class="wcmp-font ico-buddypress_icon"></i></a><?php } 
    }
    
    public function wcmp_buddypress_tab_admin( $social_tab_options, $vendor_obj ){
        $social_tab_options['vendor_buddypress'] = array('label' => __('BuddyPress', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_buddypress', 'label_for' => 'vendor_buddypress', 'name' => 'vendor_buddypress', 'value' => $vendor_obj->buddypress);
        return $social_tab_options;
    }

}

WCMp_BuddyPress::instance()->init();
