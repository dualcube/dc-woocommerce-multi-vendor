<?php
/**
 * WCMp Email Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
 
class WCMp_Notices {
	private $post_type;
  	public $dir;
  	public $file;
  
  	public function __construct() {
    	$this->post_type = 'wcmp_vendor_notice';
    	$this->register_post_type();
		add_action( 'add_meta_boxes', array($this,'vendor_notices_add_meta_boxes') );
		add_action( 'save_post', array( $this, 'vendor_notices_save_meta_boxes' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'vendor_notices_send_email' ), 10, 3 );
  	}

  	public function vendor_notices_add_meta_boxes() {
		add_meta_box(
            'wcmp_vendor_notice_addtional_field',
            __( 'Addtional Fields', 'dc-woocommerce-multi-vendor' ),
            apply_filters( 'wcmp_vendor_notice_addtional_field_edit_callback', array( $this, 'wcmp_vendor_notice_addtional_field_callback' ) ),
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'wcmp_vendor_notice_select_vendor',
            __( 'Vendors', 'dc-woocommerce-multi-vendor' ),
            apply_filters( 'wcmp_vendor_notice_select_vendor_edit_callback', array($this, 'wcmp_vendor_notice_select_vendor_callback') ),
            $this->post_type,
            'side',
            'low'
        );
  	}
  
  	public function wcmp_vendor_notice_addtional_field_callback() {
  		global $post;
		$url = get_post_meta( $post->ID,'_wcmp_vendor_notices_url', true );
  		?>
  		<div id="_wcmp_vendor_notices_url_div" class="_wcmp_vendor_notices_url_div" >
  			<label><?php _e( 'Enter Url', 'dc-woocommerce-multi-vendor' ); ?></label>
  			<input type="text" name="_wcmp_vendor_notices_url" value="<?php echo $url; ?>" class="widefat" style="margin:10px; border:1px solid #888; width:90%;" >			
		</div>
		<?php
	}
	
	public function wcmp_vendor_notice_select_vendor_callback() {
		global $post;
		$selected_vendors = (array)get_post_meta( $post->ID, '_wcmp_vendor_notices_vendors', true );
		wp_add_inline_script( 'wcmp_admin_js', '( function( $ ) {
			$("#show_announcement_vendors").select2({
				placeholder: "'.__('Select vendor...', 'dc-woocommerce-multi-vendor').'"
			});
			$("#anv_select_all").click(function(){
				$("#show_announcement_vendors > option").prop("selected","selected");
				$("#show_announcement_vendors").trigger("change");
			});
			$("#anv_reset").click(function(){
				$("#show_announcement_vendors").val(null).trigger("change");
			});
		} )( jQuery );' );
		?>
		<div class="announcement-vendors-wrap">
			<select id="show_announcement_vendors" name="show_announcement_vendors[]" class="" multiple="multiple" style="width:100%;">
			<?php
				$vendors = get_wcmp_vendors();
				if ($vendors){
					foreach ($vendors as $vendor) {
						$selected = in_array($vendor->id, $selected_vendors) ? 'selected' : '';
						echo '<option value="' . esc_attr($vendor->id) . '" '.$selected.'>' . esc_html($vendor->page_title) . '</option>';
					}
			} ?>
			</select>
			<p>
				<button type="button" class="button button-default" id="anv_select_all"><?php _e( 'Select All', 'dc-woocommerce-multi-vendor' ); ?></button>
				<button type="button" class="button button-default" id="anv_reset"><?php _e( 'Reset', 'dc-woocommerce-multi-vendor' ); ?></button>
			</p>
		</div>
	  <?php
	}

  	public function vendor_notices_save_meta_boxes($post_id, $post, $update) {
  		global $WCMp;
  		if (  $this->post_type != $post->post_type ) {
        	return;
    	}
    	if(isset($_POST['_wcmp_vendor_notices_url'])) {
    		update_post_meta($post_id, '_wcmp_vendor_notices_url', esc_url($_POST['_wcmp_vendor_notices_url']));
		}
		if(isset($_POST['show_announcement_vendors'])) {
			update_post_meta($post_id, '_wcmp_vendor_notices_vendors', array_filter($_POST['show_announcement_vendors']));
		} 
	}

	public function vendor_notices_send_email( $new_status, $old_status, $post ) { 
		if ( $new_status == 'publish' && $old_status != 'publish' && $post->post_type == 'wcmp_vendor_notice' ) {
			$email_vendor = WC()->mailer()->emails['WC_Email_Vendor_New_Announcement'];
			$vendors = (isset($_POST['show_announcement_vendors']) && !empty($_POST['show_announcement_vendors'])) ? $_POST['show_announcement_vendors'] : get_wcmp_vendors( array(), 'ids' );
			$single = ( count($vendors) == 1 ) ? __( 'Your', 'dc-woocommerce-multi-vendor' ) : __( 'All vendors and their', 'dc-woocommerce-multi-vendor' );
			foreach ($vendors as $vendor_id) {
				$email_vendor->trigger( $post, $vendor_id, $single );
			}
		}
	}
  
  	/**
	 * Register vendor_notices post type
	 *
	 * @access public
	 * @return void
	*/
  	function register_post_type() {
		global $WCMp;
		if ( post_type_exists($this->post_type) ) return;
		$labels = array(
			'name' => _x( 'Announcements', 'post type general name' , 'dc-woocommerce-multi-vendor' ),
			'singular_name' => _x( 'Announcements', 'post type singular name' , 'dc-woocommerce-multi-vendor' ),
			'add_new' => _x( 'Add New', $this->post_type , 'dc-woocommerce-multi-vendor' ),
			'add_new_item' => sprintf( __( 'Add New %s' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor' ) ),
			'edit_item' => sprintf( __( 'Edit %s' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor') ),
			'new_item' => sprintf( __( 'New %s' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor') ),
			'all_items' => sprintf( __( 'All %s' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor' ) ),
			'view_item' => sprintf( __( 'View %s' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor' ) ),
			'search_items' => sprintf( __( 'Search %a' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor' ) ),
			'not_found' =>  sprintf( __( 'No %s found' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s found in trash' , 'dc-woocommerce-multi-vendor' ), __( 'Announcements' , 'dc-woocommerce-multi-vendor' ) ),
			'parent_item_colon' => '',
			'all_items' => __( 'Announcements' , 'dc-woocommerce-multi-vendor' ),
			'menu_name' => __( 'Announcements' , 'dc-woocommerce-multi-vendor' )
		);
		
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => current_user_can( 'manage_woocommerce' ) ? 'wcmp' : false,
			'show_in_nav_menus' => false,
			'query_var' => false,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => true,
			'supports' => array( 'title', 'editor' ),
			'menu_position' => 10,
			'menu_icon' => $WCMp->plugin_url.'/assets/images/dualcube.png'
		);		
		register_post_type( $this->post_type, $args );
	}  
	
}
