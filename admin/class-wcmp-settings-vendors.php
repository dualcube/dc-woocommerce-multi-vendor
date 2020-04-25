<?php
if (!class_exists('WP_List_Table'))
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class WCMp_Settings_WCMp_Vendors extends WP_List_Table {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;

    /**
     * Start up
     */
    public function __construct($tab) {
    	$screen = get_current_screen();
    	
    	parent::__construct( [
			'singular' => __( 'Vendor', 'dc-woocommerce-multi-vendor' ),
			'plural'   => __( 'Vendors', 'dc-woocommerce-multi-vendor' ),
			'ajax'     => true

		] );
        $this->tab = $tab;
		$this->options = get_option("wcmp_{$this->tab}_settings_name");		
		add_action( 'admin_footer', array( $this, 'wcmp_vendor_preview_template' ) );
    }
    
    public function get_columns() {
    	$columns = [
    		'cb' => '<input type="checkbox" />',
    		'username' => __( 'Name', 'dc-woocommerce-multi-vendor' ),
    		'email' => __( 'Email', 'dc-woocommerce-multi-vendor' ),
    		'registered' => __( 'Registered', 'dc-woocommerce-multi-vendor' ),
    		'products' => __( 'Products', 'dc-woocommerce-multi-vendor' ),
    		'status' => __( 'Status', 'dc-woocommerce-multi-vendor' ),
		];
		return apply_filters('wcmp_list_table_vendors_columns', $columns);	
	}
	
	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'email':
			default:
				return $item[$column_name];
		}
	}
	
	/**
     * column_cb function
     *
     * @param mixed $item
     * @return void
     */
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'ID', $item['ID']);
    }
    
    function column_username( $item ) {
    	$name_link = sprintf('?page=%s&action=%s&ID=%s', $_GET['page'], 'edit', $item['ID']);
    	$action = 'bulk-' . $this->_args['plural'];
    	$actions = array(
			'edit'=> sprintf('<a href="' . $name_link . '">' . __( 'Edit', 'dc-woocommerce-multi-vendor' ) . '</a>'),
			'delete'=> sprintf('<a href="?page=%s&action=%s&ID=%s&_wpnonce=%s">' . __( 'Delete', 'dc-woocommerce-multi-vendor' ) . '</a>', $_GET['page'], 'delete', $item['ID'], wp_create_nonce($action) ),
			'shop' => sprintf('<a href="' . $item['permalink'] . '">' . __( 'Shop', 'dc-woocommerce-multi-vendor' ) . '</a>'),
        );
        
        $vendor_profile_image = get_user_meta($item['ID'], '_vendor_profile_image', true);
        if(isset($vendor_profile_image)) $image_info = wp_get_attachment_image_src( $vendor_profile_image , array(32, 32) );
        
        //Return the title contents
        return sprintf('<div class="pending-vendor-clm"><a href="%1$s"><img src="%2$s" height="32" width="32"></img><span class="name_info">%3$s</span></a> %4$s</div><a href="#" class="vendor-preview" data-vendor-id="%5$s" title="Preview">' . __( 'Preview', 'dc-woocommerce-multi-vendor' ) . '</a>',
        	/*$1%s*/ $name_link,
        	/*$2%s*/ isset($image_info[0]) ? $image_info[0] : get_avatar_url($item['ID'], array('size' => 32)),
            /*$3%s*/ $item['name'],
            /*$4%s*/ $this->row_actions($actions),
            /*$5%s*/ $item['ID']
        );
    }
    
    function column_products( $item ) {
    	return sprintf('<a href="%1$s">' . $item['products'] . '</a>', admin_url('edit.php?post_type=product&dc_vendor_shop=' . $item['username']));
    }
    
	function prepare_items() {
		global $wpdb;
		
		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option('per_page', 'option');
		$per_page = get_user_meta($user, $option, true);
		
		$search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;
		
		$query_role = ( !empty( $_GET['role'] ) ? $_GET['role'] : 'all');
		
		if($query_role == 'approved') {
			$roles_in_array = array('dc_vendor');
			$suspended_check = array(
				'relation' => 'AND',
				0 => array(
					'key' => '_vendor_turn_off',
					'value' => '',
					'compare' => 'NOT EXISTS'
				)
			);
		} else if($query_role == 'suspended') {
			$roles_in_array = array('dc_vendor');
			$suspended_check = array(
				'relation' => 'AND',
				0 => array( 
					'key'  => '_vendor_turn_off',
					'value' => 'Enable'
				),
			);
		}
		else if($query_role == 'pending') $roles_in_array = array('dc_pending_vendor');
		else if($query_role == 'rejected') $roles_in_array = array('dc_rejected_vendor');
		else $roles_in_array = array('dc_vendor', 'dc_pending_vendor', 'dc_rejected_vendor');
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$args = array(
			'role__in' => $roles_in_array,
			);
		if(isset($suspended_check)) $args['meta_query'] = $suspended_check;
		if(isset($search) && $search) {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = array(
						'user_login',
						'user_nicename',
						'user_email',
						'user_url',
						'display_name',
						'ID'
					);
    	}
		
    	// Create the WP_User_Query object
		$wp_user_query = new WP_User_Query( $args );
		
		// Get the results
		$users = $wp_user_query->get_results();

		$user_list = array();
		foreach($users as $user) {
			$vendor = get_wcmp_vendor($user->data->ID);
			$product_count = 0;
			$vendor_permalink = ''; 
			$status = "";
			if($vendor) {
				$vendor_products = $vendor->get_products_ids();
				$vendor_permalink = $vendor->permalink;
				$product_count = count($vendor_products);
			}
			
			if(in_array('dc_vendor', $user->roles)) {
				$is_block = get_user_meta($vendor->id, '_vendor_turn_off', true);
			
				if($is_block) {
					$status = "<p class='vendor-status suspended-vendor'>" . __('Suspended', 'dc-woocommerce-multi-vendor') . "</p>";
				} else {
					$status = "<p class='vendor-status approved-vendor'>" . __('Approved', 'dc-woocommerce-multi-vendor') . "</p>";
				}
			} else if(in_array('dc_rejected_vendor', $user->roles)) {
				$status = "<p class='vendor-status rejected-vendor'>" . __('Rejected', 'dc-woocommerce-multi-vendor') . "</p>";
			} else if(in_array('dc_pending_vendor', $user->roles)) {
				$status = "<p class='vendor-status pending-vendor'>" . __('Pending', 'dc-woocommerce-multi-vendor') . "</p>";
			}	
			$user_list[$user->data->ID] = apply_filters('wcmp_list_table_vendors_columns_data', array(
							'ID' => $user->data->ID,
							'name' => $user->data->display_name,
							'email' => $user->data->user_email,
							'registered' => get_date_from_gmt( $user->data->user_registered ),
							'products' => $product_count,
							'status' => $status,
							'permalink' => $vendor_permalink,
							'username' => $user->data->user_login
							), $user);
		}
		$this->_column_headers = array($columns, $hidden, $sortable);
		usort( $user_list, array( &$this, 'usort_reorder' ) );
		
		$per_page = $this->get_items_per_page('vendors_per_page', 5);
		$current_page = $this->get_pagenum();
		$total_items = count($user_list);
		
		$user_list = array_slice($user_list, ( ( $current_page - 1 ) * $per_page ), $per_page );
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );
		
		$this->items = $user_list;
	}
	
	function get_sortable_columns() {
		$sortable_columns = array(
			'name'  => array('name',false),
			'registered' => array('registered',false),
			'products' => array('products',false),
		);
		return $sortable_columns;
	}
	
	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name';
		// If no order, default to asc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a[$orderby], $b[$orderby] );
		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}
	
	public function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			$action = 'bulk-' . $this->_args['plural'];
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( __('You are not permitted to do this', 'dc-woocommerce-multi-vendor') );
			}  else {
				if(isset($_GET['ID'])) {
					if(is_array($_GET['ID'])) {
						foreach($_GET['ID'] as $id) {
							wp_delete_user( absint( $id ) );
						}
					} else if(absint($_GET['ID']) > 0){
						wp_delete_user( absint($_GET['ID']) );
					}
				}
				
				wp_redirect( $_SERVER['HTTP_REFERER'] );
				exit;
			}
		}
		do_action('wcmp_vendor_process_bulk_action', $this->current_action(), $_REQUEST);
	}
	
	function get_bulk_actions() {
		$actions = array(
			'delete'    => __( 'Delete', 'dc-woocommerce-multi-vendor' )
		);
		return apply_filters('wcmp_vendor_bulk_action', $actions);
	}

	function get_views() {
		$categorywise_vendor_count = array(
			'all' => 0,
			'approved' => 0,
			'pending' => 0,
			'rejected' => 0,
			'suspended' => 0,
		);
		
		// Create the WP_User_Query object
		$wp_user_query = new WP_User_Query( apply_filters( 'wcmp_vendor_get_views_query_args', array(
			'role__in' => array('dc_vendor', 'dc_pending_vendor', 'dc_rejected_vendor'),
			) ) );
		
		
		
		// Get the results
		$users = $wp_user_query->get_results();

		foreach($users as $user) {
			if(in_array('dc_vendor', $user->roles)) {
				$is_block = get_user_meta($user->ID, '_vendor_turn_off', true);
			
				if($is_block) {
					$categorywise_vendor_count['suspended']++;
				} else {
					$categorywise_vendor_count['approved']++;
				}
			} else if(in_array('dc_rejected_vendor', $user->roles)) {
				$categorywise_vendor_count['rejected']++;
			} else if(in_array('dc_pending_vendor', $user->roles)) {
				$categorywise_vendor_count['pending']++;
			}
			$categorywise_vendor_count['all']++;
		}
		
		$views = array();
		$current = ( !empty( $_GET['role'] ) ? $_GET['role'] : 'all');
		
		//All link
		$class = ($current == 'all' ? ' class="current"' :'');
		$all_url = remove_query_arg('role');
		$views['all'] = "<a href='{$all_url }' {$class} >" . __( 'All', 'dc-woocommerce-multi-vendor' ) . " (" . $categorywise_vendor_count['all'] . ")</a>";
		
		$approved_url = add_query_arg('role','approved');
		$class = ($current == 'approved' ? ' class="current"' :'');
		$views['approved'] = "<a href='{$approved_url}' {$class} >" . __( 'Approved', 'dc-woocommerce-multi-vendor' ) . " (" . $categorywise_vendor_count['approved'] . ")</a>";
		
		$pending_url = add_query_arg('role','pending');
		$class = ($current == 'pending' ? ' class="current"' :'');
		$views['pending'] = "<a href='{$pending_url}' {$class} >" . __( 'Pending', 'dc-woocommerce-multi-vendor' ) . " (" . $categorywise_vendor_count['pending'] . ")</a>";
		
		$rejected_url = add_query_arg('role','rejected');
		$class = ($current == 'rejected' ? ' class="current"' :'');
		$views['rejected'] = "<a href='{$rejected_url}' {$class} >" . __( 'Rejected', 'dc-woocommerce-multi-vendor' ) . " (" . $categorywise_vendor_count['rejected'] . ")</a>";
		
		$suspended_url = add_query_arg('role','suspended');
		$class = ($current == 'suspended' ? ' class="current"' :'');
		$views['suspended'] = "<a href='{$suspended_url}' {$class} >" . __( 'Suspended', 'dc-woocommerce-multi-vendor' ) . " (" . $categorywise_vendor_count['suspended'] . ")</a>";
		
		return apply_filters('wcmp_vendor_get_views_list', $views);
	}
	
    /**
     * Register and add settings
     */
    public function settings_page_init() {
        global $WCMp, $wp_version;
        $user = null;
        
        $h1_title = '';
        if(isset($_GET['ID']) && absint($_GET['ID']) > 0) {
        	$user = get_user_by("ID", $_GET['ID']);
			$h1_title = __( "Vendor", "dc-woocommerce-multi-vendor" ) . ' - ' . $user->display_name . ' (' . $user->user_email . ')';
		} else if( 'add_new' === $this->current_action() ) {
			$h1_title = __( "Add New Vendor", "dc-woocommerce-multi-vendor" );
		} else {
			$h1_title = __( "Vendors", "dc-woocommerce-multi-vendor" ) .  
				'<a href="' . admin_url('admin.php?page=vendors&action=add_new') . '" class="page-title-action">' . __( 'Add New', 'dc-woocommerce-multi-vendor' ) . '</a>';
		}
		
		echo '<h1 class="wp-heading-inline">' . apply_filters( 'wcmp_vendor_tab_header', $h1_title ) . '</h1>';
		
		if(isset($_POST['wcmp_vendor_submit'])) {
			if($_POST['wcmp_vendor_submit'] == 'update' && isset($_POST['user_id']) && $_POST['user_id'] > 0) {
				$user_id = $_POST['user_id'];
				
				$errors = new WP_Error();
				$vendor = get_wcmp_vendor($user_id);
				if($vendor) {
					$userdata = array(
						'ID' => $user_id,
						'user_login' => $_POST['user_login'],
						'user_pass' => $_POST['password'],
						'user_email' => $_POST['user_email'],
						'user_nicename' => $_POST['user_nicename'],
						'display_name' => $_POST['display_name'],
						'first_name' => $_POST['first_name'],
						'last_name' => $_POST['last_name'],
					);
					
					$user_id = wp_update_user( $userdata ) ;
					
					foreach($_POST as $key => $value) { 
                                            $skip_vendor_update_data = apply_filters('wcmp_skipped_vendor_update_keys', array('wcmp_commission_type'));
                                            if(in_array($key, $skip_vendor_update_data)) continue;
						if($value != '') {
							if ($key == 'vendor_page_title') {
								if (!$vendor->update_page_title(wc_clean($value))) {
									$errors->add('vendor_title_exists', __('Title Update Error', 'dc-woocommerce-multi-vendor'));
								}
							} else if ($key == 'vendor_page_slug') {
								if (!$vendor->update_page_slug(wc_clean($value))) {
									$errors->add('vendor_slug_exists', __('Slug already exists', 'dc-woocommerce-multi-vendor'));
								}
                                                        } else if($key === "vendor_country") {
                                                            $country_code = $value;
                                                            $country_data = WC()->countries->get_countries();
                                                            $country_name = ( isset( $country_data[ $country_code ] ) ) ? $country_data[ $country_code ] : $country_code; //To get country name by code
                                                            update_user_meta($user_id, '_' . $key, $country_name);
                                                            update_user_meta($user_id, '_' . $key . '_code', $country_code);
							} else if($key === "vendor_state") {
                                                            $country_code = $_POST['vendor_country'];
                                                            $state_code = $value;
                                                            $state_data = WC()->countries->get_states($country_code);
                                                            $state_name = ( isset( $state_data[$state_code] ) ) ? $state_data[$state_code] : $state_code; //to get State name by state code
                                                            update_user_meta($user_id, '_' . $key, $state_name);
                                                            update_user_meta($user_id, '_' . $key . '_code', $state_code);
                                                        } else if(substr($key, 0, strlen("vendor_")) === "vendor_") {
								update_user_meta($user_id, "_" . $key, $value);
							}
						} else {
							if(substr($key, 0, strlen("vendor_")) === "vendor_") {
								delete_user_meta($user_id, "_" . $key);
							}
						}
					}
				}
				if ( is_wp_error( $errors ) && ! empty( $errors->errors ) ) {
					$error_string = $errors->get_error_message();
					echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
				} else {
					echo '<div class="notice notice-success"><p>' . __( 'Vendor Information updated successfully!', 'dc-woocommerce-multi-vendor' ) . '</p></div>';
				}
			} else if($_POST['wcmp_vendor_submit'] == 'add_new') {
				$userdata = array(
					'user_login' => $_POST['user_login'],
					'user_pass' => $_POST['password'],
					'user_email' => $_POST['user_email'],
					'user_nicename' => $_POST['user_nicename'],
					'first_name' => $_POST['first_name'],
					'last_name' => $_POST['last_name'],
					'role' => 'dc_vendor'
				);
				$user_id = wp_insert_user( $userdata ) ;
				if ( is_wp_error( $user_id ) ) {
					$error_string = $user_id->get_error_message();
					echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
                                        $user_id = null;
				} else {
                                        $email = WC()->mailer()->emails['WC_Email_Vendor_New_Account'];
                                        $email->trigger( $user_id, $userdata['user_pass'], false);
					if(isset($_POST['vendor_profile_image']) && $_POST['vendor_profile_image'] != '') update_user_meta($user_id, "_vendor_profile_image", $_POST['vendor_profile_image']);
					echo '<div class="notice notice-success"><p>' . __( 'Vendor successfully created!', 'dc-woocommerce-multi-vendor' ) . '</p></div>';
				}
                                wp_safe_redirect(apply_filters('wcmp_add_new_vendor_redirect_url', admin_url('admin.php?page=vendors&action=edit&ID='.$user_id)));
                                die();
			}
		}
		
		$is_approved_vendor = false;
		$is_new_vendor_form = false;
		$vendor_obj = null;
		$display_name_option = array();
				
        if( 'edit' === $this->current_action() || 'add_new' === $this->current_action() ) {
        	if(isset($_GET['ID']) && absint($_GET['ID']) > 0) {
				if(isset($user->display_name)) {
					$display_name_option = array(
						$user->user_login => $user->user_login,
						$user->first_name => $user->first_name,
						$user->last_name => $user->last_name,
						$user->first_name . " " . $user->last_name => $user->first_name . " " . $user->last_name,
						$user->last_name . " " . $user->first_name => $user->last_name . " " . $user->first_name,
						);
				} else {
					$display_name_option = array();
				}
				$vendor_profile_image = get_user_meta($_GET['ID'], '_vendor_profile_image', true);
        	
				$personal_tab_options =  array(
							"user_login" => array('label' => __('Username (required)', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'user_login', 'label_for' => 'user_login', 'name' => 'user_login', 'desc' => __('Usernames cannot be changed.', 'dc-woocommerce-multi-vendor'), 'value' => isset($user->user_login)? $user->user_login : '', 'attributes' => array('readonly' => true)),
							"password" => array('label' => __('Password', 'dc-woocommerce-multi-vendor'), 'type' => 'password', 'id' => 'password', 'label_for' => 'password', 'name' => 'password', 'desc' => __('Keep it blank for not to update.', 'dc-woocommerce-multi-vendor')),
							"first_name" => array('label' => __('First Name', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'first_name', 'label_for' => 'first_name', 'name' => 'first_name', 'value' => isset($user->first_name)? $user->first_name : ''),
							"last_name" => array('label' => __('Last Name', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'last_name', 'label_for' => 'last_name', 'name' => 'last_name', 'value' => isset($user->last_name)? $user->last_name : ''),
							"user_email" => array('label' => __('Email (required)', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'user_email', 'label_for' => 'user_email', 'name' => 'user_email', 'value' => isset($user->user_email)? $user->user_email : '', 'attributes' => array('required' => true)),
							"user_nicename" => array('label' => __('Nick Name (required)', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'user_nicename', 'label_for' => 'user_nicename', 'name' => 'user_nicename', 'value' => isset($user->user_nicename)? $user->user_nicename : '', 'attributes' => array('required' => true)),
							"display_name" => array('label' => __('Display name', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'display_name', 'label_for' => 'display_name', 'name' => 'display_name', 'options' => $display_name_option, 'value' => isset($user->display_name)? $user->display_name : ''),
							"vendor_profile_image" => array('label' => __('Profile Image', 'dc-woocommerce-multi-vendor'), 'type' => 'upload', 'id' => 'vendor_profile_image', 'label_for' => 'vendor_profile_image', 'name' => 'vendor_profile_image', 'mime' => 'image', 'value' => $vendor_profile_image),
							"user_id" => array('label' => '', 'type' => 'hidden', 'id' => 'user_id', 'label_for' => 'user_id', 'name' => 'user_id', 'value' => isset($user->ID)? $user->ID : ''),
						);
				$store_tab_options = array();
								
				if( is_user_wcmp_vendor($_GET['ID']) ) {
					$is_approved_vendor = true;
					$vendor_obj = get_wcmp_vendor($_GET['ID']);
					
					$current_offset = get_user_meta($vendor_obj->id, 'gmt_offset', true);
					$tzstring = get_user_meta($vendor_obj->id, 'timezone_string', true);
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
					$states = ($vendor_obj->country_code && WC()->countries->get_states( $vendor_obj->country_code )) ? WC()->countries->get_states( $vendor_obj->country_code ) : array();
					$store_tab_options =  array(
								"vendor_page_title" => array('label' => __('Store Name *', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_page_title', 'label_for' => 'vendor_page_title', 'name' => 'vendor_page_title', 'value' => $vendor_obj->page_title ),
								"vendor_page_slug" => array('label' => __('Store Slug *', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_page_slug', 'label_for' => 'vendor_page_slug', 'name' => 'vendor_page_slug', 'desc' => sprintf(__('Store URL will be something like - %s', 'dc-woocommerce-multi-vendor'), trailingslashit(get_home_url()) . 'vendor_slug'), 'value' => $vendor_obj->page_slug, 'attributes' => array('readonly' => true)),
								"vendor_description" => array('label' => __('Store Description', 'dc-woocommerce-multi-vendor'), 'type' => 'wpeditor', 'id' => 'vendor_description', 'label_for' => 'vendor_description', 'name' => 'vendor_description', 'cols' => 50, 'rows' => 6, 'value' => $vendor_obj->description), // Textarea
								"vendor_phone" => array('label' => __('Phone', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_phone', 'label_for' => 'vendor_phone', 'name' => 'vendor_phone', 'value' => $vendor_obj->phone),
								"vendor_address_1" => array('label' => __('Address', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_address_1', 'label_for' => 'vendor_address_1', 'name' => 'vendor_address_1', 'value' => $vendor_obj->address_1),
								"vendor_address_2" => array('label' => '', 'type' => 'text', 'id' => 'vendor_address_2', 'label_for' => 'vendor_address_2', 'name' => 'vendor_address_2', 'value' => $vendor_obj->address_2),
								"vendor_country" => array('label' => __('Country', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'vendor_country', 'label_for' => 'vendor_country', 'name' => 'vendor_country', 'class' => 'country_to_state regular-text', 'options' => WC()->countries->get_allowed_countries(), 'value' => $vendor_obj->country_code),
								"vendor_state" => array('label' => __('State', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'vendor_state', 'label_for' => 'vendor_state', 'name' => 'vendor_state', 'class' => 'regular-text', 'options' => $states, 'value' => $vendor_obj->state_code),
								"vendor_city" => array('label' => __('City', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_city', 'label_for' => 'vendor_city', 'name' => 'vendor_city', 'value' => $vendor_obj->city),
								"vendor_postcode" => array('label' => __('ZIP code', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_postcode', 'label_for' => 'vendor_postcode', 'name' => 'vendor_postcode', 'value' => $vendor_obj->postcode),
								"timezone_string" => array('label' => __('Timezone', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'timezone_string', 'label_for' => 'timezone_string', 'name' => 'timezone_string', 'value' => $tzstring, 'attributes' => array('readonly' => true)),
							);
					
					$social_tab_options =  array(
								"vendor_fb_profile" => array('label' => __('Facebook', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_fb_profile', 'label_for' => 'vendor_fb_profile', 'name' => 'vendor_fb_profile', 'value' => $vendor_obj->fb_profile),
								"vendor_twitter_profile" => array('label' => __('Twitter', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_twitter_profile', 'label_for' => 'vendor_twitter_profile', 'name' => 'vendor_twitter_profile', 'value' => $vendor_obj->twitter_profile),
								"vendor_linkdin_profile" => array('label' => __('LinkedIn', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_linkdin_profile', 'label_for' => 'vendor_linkdin_profile', 'name' => 'vendor_linkdin_profile', 'value' => $vendor_obj->linkdin_profile),
								"vendor_google_plus_profile" => array('label' => __('Google Plus', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_google_plus_profile', 'label_for' => 'vendor_google_plus_profile', 'name' => 'vendor_google_plus_profile', 'value' => $vendor_obj->google_plus_profile),
								"vendor_youtube" => array('label' => __('YouTube', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_youtube', 'label_for' => 'vendor_youtube', 'name' => 'vendor_youtube', 'value' => $vendor_obj->youtube),
								"vendor_instagram" => array('label' => __('Instagram', 'dc-woocommerce-multi-vendor'), 'type' => 'url', 'id' => 'vendor_instagram', 'label_for' => 'vendor_instagram', 'name' => 'vendor_instagram', 'value' => $vendor_obj->instagram),
							);
					
					$payment_admin_settings = get_option('wcmp_payment_settings_name');
					$payment_mode = array('payment_mode' => __('Payment Mode', 'dc-woocommerce-multi-vendor'));
					if (isset($payment_admin_settings['payment_method_paypal_masspay']) && $payment_admin_settings['payment_method_paypal_masspay'] = 'Enable') {
						$payment_mode['paypal_masspay'] = __('PayPal Masspay', 'dc-woocommerce-multi-vendor');
					}
					if (isset($payment_admin_settings['payment_method_paypal_payout']) && $payment_admin_settings['payment_method_paypal_payout'] = 'Enable') {
						$payment_mode['paypal_payout'] = __('PayPal Payout', 'dc-woocommerce-multi-vendor');
					}
					if (isset($payment_admin_settings['payment_method_stripe_masspay']) && $payment_admin_settings['payment_method_stripe_masspay'] = 'Enable') {
						$payment_mode['stripe_masspay'] = __('Stripe Connect', 'dc-woocommerce-multi-vendor');
					}
					if (isset($payment_admin_settings['payment_method_direct_bank']) && $payment_admin_settings['payment_method_direct_bank'] = 'Enable') {
						$payment_mode['direct_bank'] = __('Direct Bank', 'dc-woocommerce-multi-vendor');
					}
					$vendor_payment_mode_select = apply_filters('wcmp_vendor_payment_mode', $payment_mode);
					
					$vendor_bank_account_type_select = array(
						'current' => __('Current', 'dc-woocommerce-multi-vendor'),
						'savings' => __('Savings', 'dc-woocommerce-multi-vendor'),
					);
					$available_commission_types = wcmp_get_available_commission_types();
					$payment_tab_options =  array(
							"vendor_payment_mode" => array('label' => __('Choose Payment Method', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'vendor_payment_mode', 'label_for' => 'vendor_payment_mode', 'name' => 'vendor_payment_mode', 'options' => $vendor_payment_mode_select, 'value' => $vendor_obj->payment_mode),
                                                        "wcmp_commission_type" => array('label' => __('Commission Type', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'wcmp_commission_type', 'label_for' => 'wcmp_commission_type', 'name' => 'wcmp_commission_type', 'value' => isset($available_commission_types[get_wcmp_vendor_settings('commission_type', 'payment')]) ? $available_commission_types[get_wcmp_vendor_settings('commission_type', 'payment')] : '', 'attributes' => array('readonly' => true)),
							"vendor_commission" => array('label' => __('Commission Amount', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_commission', 'label_for' => 'vendor_commission', 'name' => 'vendor_commission', 'value' => $vendor_obj->commission),
                                                        "vendor_paypal_email" => array('label' => __('Paypal Email', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_paypal_email', 'label_for' => 'vendor_paypal_email', 'name' => 'vendor_paypal_email', 'value' => $vendor_obj->paypal_email, 'wrapper_class' => 'payment-gateway-paypal_masspay payment-gateway-paypal_payout payment-gateway'),
							"vendor_bank_account_type" => array('label' => __('Account type', 'dc-woocommerce-multi-vendor'), 'type' => 'select', 'id' => 'vendor_bank_account_type', 'label_for' => 'vendor_bank_account_type', 'name' => 'vendor_bank_account_type', 'options' => $vendor_bank_account_type_select, 'value' => $vendor_obj->bank_account_type, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_bank_name" => array('label' => __('Bank Name', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_bank_name', 'label_for' => 'vendor_bank_name', 'name' => 'vendor_bank_name', 'value' => $vendor_obj->bank_name, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_aba_routing_number" => array('label' => __('ABA Routing Number', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_aba_routing_number', 'label_for' => 'vendor_aba_routing_number', 'name' => 'vendor_aba_routing_number', 'value' => $vendor_obj->aba_routing_number, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_destination_currency" => array('label' => __('Destination Currency', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_destination_currency', 'label_for' => 'vendor_destination_currency', 'name' => 'vendor_destination_currency', 'value' => $vendor_obj->destination_currency, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_bank_address" => array('label' => __('Bank Address', 'dc-woocommerce-multi-vendor'), 'type' => 'textarea', 'id' => 'vendor_bank_address', 'label_for' => 'vendor_bank_address', 'name' => 'vendor_bank_address', 'rows'=>'6', 'cols'=>'53', 'value' => $vendor_obj->bank_address, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_iban" => array('label' => __('IBAN', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_iban', 'label_for' => 'vendor_iban', 'name' => 'vendor_iban', 'value' => $vendor_obj->iban, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_account_holder_name" => array('label' => __('Account Holder Name', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_account_holder_name', 'label_for' => 'vendor_account_holder_name', 'name' => 'vendor_account_holder_name', 'value' => $vendor_obj->account_holder_name, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
							"vendor_bank_account_number" => array('label' => __('Account Number', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'vendor_bank_account_number', 'label_for' => 'vendor_bank_account_number', 'name' => 'vendor_bank_account_number', 'value' => $vendor_obj->bank_account_number, 'wrapper_class' => 'payment-gateway-direct_bank payment-gateway'),
						);
                                        
                                        if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage') {
                                            unset($payment_tab_options['vendor_commission']);
                                            $payment_tab_options['vendor_commission_percentage'] = array(
                                                'label' => __('Commission Percentage(%)', 'dc-woocommerce-multi-vendor'),
                                                'type' => 'text',
                                                'value' => $vendor_obj->commission_percentage,
                                                'class' => ''
                                            );
                                            $payment_tab_options['vendor_commission_fixed_with_percentage'] = array(
                                                'label' => __('Commission(fixed), Per Transaction', 'dc-woocommerce-multi-vendor'),
                                                'type' => 'text',
                                                'value' => $vendor_obj->commission_fixed_with_percentage,
                                                'class' => ''
                                            );
                                        }

                                        if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage_qty') {
                                            unset($payment_tab_options['vendor_commission']);
                                            $payment_tab_options['vendor_commission_percentage'] = array(
                                                'label' => __('Commission Percentage(%)', 'dc-woocommerce-multi-vendor'),
                                                'type' => 'text',
                                                'value' => $vendor_obj->commission_percentage,
                                                'class' => ''
                                            );
                                            $payment_tab_options['vendor_commission_fixed_with_percentage_qty'] = array(
                                                'label' => __('Commission Fixed Per Unit', 'dc-woocommerce-multi-vendor'),
                                                'type' => 'text',
                                                'value' => $vendor_obj->commission_fixed_with_percentage_qty,
                                                'class' => ''
                                            );
                                        }
				}
			} else {
				$personal_tab_options =  array(
					"user_login" => array('label' => __('Username (required)', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'user_login', 'label_for' => 'user_login', 'name' => 'user_login', 'desc' => __('Usernames cannot be changed.', 'dc-woocommerce-multi-vendor'), 'attributes' => array('required' => true)),
					"password" => array('label' => __('Password', 'dc-woocommerce-multi-vendor'), 'type' => 'password', 'id' => 'password', 'label_for' => 'password', 'name' => 'password', 'desc' => __('Keep it blank for not to update.', 'dc-woocommerce-multi-vendor')),
					"first_name" => array('label' => __('First Name', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'first_name', 'label_for' => 'first_name', 'name' => 'first_name'),
					"last_name" => array('label' => __('Last Name', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'last_name', 'label_for' => 'last_name', 'name' => 'last_name'),
					"user_email" => array('label' => __('Email (required)', 'dc-woocommerce-multi-vendor'), 'type' => 'email', 'id' => 'user_email', 'label_for' => 'user_email', 'name' => 'user_email', 'attributes' => array('required' => true)),
					"user_nicename" => array('label' => __('Nick Name (required)', 'dc-woocommerce-multi-vendor'), 'type' => 'text', 'id' => 'user_nicename', 'label_for' => 'user_nicename', 'name' => 'user_nicename', 'attributes' => array('required' => true)),
					"vendor_profile_image" => array('label' => __('Profile Image', 'dc-woocommerce-multi-vendor'), 'type' => 'upload', 'id' => 'vendor_profile_image', 'label_for' => 'vendor_profile_image', 'name' => 'vendor_profile_image', 'mime' => 'image'),
					"user_id" => array('label' => '', 'type' => 'hidden', 'id' => 'user_id', 'label_for' => 'user_id', 'name' => 'user_id'),
				);
				$is_new_vendor_form = true;
			}
			?>
			<!-- vendor edit start -->
			<div id="vendor_preview_tabs" class="wcmp-ui-tabs ui-tabs-vertical">
				<ul>
					<?php 
					do_action('wcmp_vendor_preview_tabs_pre', $is_approved_vendor);
					if($is_approved_vendor || $is_new_vendor_form) { ?>
					<li>
						<a href="#personal-detail"><span class="dashicons dashicons-admin-users"></span> <?php echo __('Personal', 'dc-woocommerce-multi-vendor'); ?></a>
					</li>
					<?php } ?>
					<?php if($is_approved_vendor) { ?>
					<li> 
						<a href="#store"><span class="dashicons dashicons-store"></span> <?php echo __('Store', 'dc-woocommerce-multi-vendor'); ?></a>
					</li>
					<li> 
						<a href="#social"><span class="dashicons dashicons-networking"></span> <?php echo __('Social', 'dc-woocommerce-multi-vendor'); ?></a>
					</li>
					<li> 
						<a href="#payment"><span class="dashicons dashicons-tickets-alt"></span> <?php echo __('Payment', 'dc-woocommerce-multi-vendor'); ?></a>
					</li>
					<?php } ?>
					<?php if(!$is_new_vendor_form) { ?>
					<li> 
						<a href="#vendor-application"><span class="dashicons dashicons-id-alt"></span> <?php echo __('Vendor Application', 'dc-woocommerce-multi-vendor'); ?></a>
					</li>
					<?php } 
					do_action('wcmp_vendor_preview_tabs_post', $is_approved_vendor);
					?>
				</ul>
				<form action="" class="vendor-preview-form" method="post">
					<?php 
					do_action('wcmp_vendor_preview_tabs_form_pre', $is_approved_vendor);
					if($is_approved_vendor || $is_new_vendor_form) { ?>
					<div id="personal-detail">
						<h2><?php echo __('Personal Information', 'dc-woocommerce-multi-vendor'); ?></h2>
						<?php $WCMp->wcmp_wp_fields->dc_generate_form_field(apply_filters("settings_{$this->tab}_personal_tab_options", $personal_tab_options, $vendor_obj));?>
					</div>
					<?php } ?>
					<?php if($is_approved_vendor) { ?>
					<div id="store">
						<h2><?php echo __('Store Settings', 'dc-woocommerce-multi-vendor'); ?></h2>
						<?php $WCMp->wcmp_wp_fields->dc_generate_form_field(apply_filters("settings_{$this->tab}_store_tab_options", $store_tab_options, $vendor_obj));?>
					</div>
					<div id="social">
						<h2><?php echo __('Social Information', 'dc-woocommerce-multi-vendor'); ?></h2>
						<?php $WCMp->wcmp_wp_fields->dc_generate_form_field(apply_filters("settings_{$this->tab}_social_tab_options", $social_tab_options, $vendor_obj));?>
					</div>
					<div id="payment">
						<h2><?php echo __('Payment Method', 'dc-woocommerce-multi-vendor'); ?></h2>
						<?php $WCMp->wcmp_wp_fields->dc_generate_form_field(apply_filters("settings_{$this->tab}_payment_tab_options", $payment_tab_options, $vendor_obj));?>
					</div>
					<?php } ?>
					<?php if(!$is_new_vendor_form) { ?>
					<div id="vendor-application" data-vendor-type="<?php echo $is_approved_vendor;?>">
						<?php
							if($is_approved_vendor) echo '<h2>' . __('Vendor Application Archive', 'dc-woocommerce-multi-vendor') . '</h2>';
							else echo '<h2>' . __('Vendor Application Data', 'dc-woocommerce-multi-vendor') . '</h2>';
							
							$vendor_application_data = get_user_meta($_GET['ID'], 'wcmp_vendor_fields', true);
							if (!empty($vendor_application_data) && is_array($vendor_application_data)) {
								foreach ($vendor_application_data as $key => $value) {
                                                                    if ($value['type'] == 'recaptcha') continue;
									echo '<div class="wcmp-form-field">';
									echo '<label>' . html_entity_decode($value['label']) . ':</label>';
									if ($value['type'] == 'file') {
										if (!empty($value['value']) && is_array($value['value'])) {
											foreach ($value['value'] as $attacment_id) {
												echo '<span> <a href="' . wp_get_attachment_url($attacment_id) . '" download>' . get_the_title($attacment_id) . '</a> </span>';
											}
										}
									} else if ($value['type'] == 'checkbox') {
										if (!empty($value['value']) && $value['value'] == 'on') {
												echo '<span> <input type="checkbox" name="" checked disabled /></span>';
										} else {
											echo '<span> <input type="checkbox" name="" disabled /></span>';
										}
									} else if ($value['type'] == 'vendor_country') {
                                                                                $country_code = $value['value'];
                                                                                $country_data = WC()->countries->get_countries();
                                                                                $country_name = ( isset( $country_data[ $country_code ] ) ) ? $country_data[ $country_code ] : $country_code; //To get country name by code
										echo '<span> ' . $country_name . '</span>';
									} else if ($value['type'] == 'vendor_state') {
                                                                                $country_field_key = array_search('vendor_country', array_column($vendor_application_data, 'type'));
                                                                                $country_field = $vendor_application_data[$country_field_key];
                                                                                $country_code = $country_field['value'];
                                                                                $state_code = $value['value'];
                                                                                $state_data = WC()->countries->get_states($country_code);
                                                                                $state_name = ( isset( $state_data[$state_code] ) ) ? $state_data[$state_code] : $state_code; //to get State name by state code
										echo '<span> ' . $state_name . '</span>';
									} else {
										if (is_array($value['value'])) {
											echo '<span> ' . implode(', ', $value['value']) . '</span>';
										} else {
											echo '<span> ' . $value['value'] . '</span>';
										}
									}
									echo '</div>';
								}
							} else {
								echo '<div class="wcmp-no-form-data">' . __('No Vendor Application archive data!!', 'dc-woocommerce-multi-vendor') . '</div>';
							}
							
							$wcmp_vendor_rejection_notes = unserialize( get_user_meta( $_GET['ID'], 'wcmp_vendor_rejection_notes', true ) );
							
							if(is_array($wcmp_vendor_rejection_notes) && count($wcmp_vendor_rejection_notes) > 0) {
								echo '<h2>' . __('Notes', 'dc-woocommerce-multi-vendor') . '</h2>';
								echo '<div class="note-clm-wrap">';
								foreach($wcmp_vendor_rejection_notes as $time => $notes) {
									$author_info = get_userdata($notes['note_by']);
									echo '<div class="note-clm"><p class="note-description">' . $notes['note'] . '</p><p class="note_time note-meta">On ' . date( "Y-m-d", $time ) . '</p><p class="note_owner note-meta">By ' . $author_info->display_name . '</p></div>';
								}
								echo '</div>';
							}
						?>
					</div>
					<?php }
					do_action('wcmp_vendor_preview_tabs_form_post', $is_approved_vendor);
					?>
					<div class="clear"></div>
					<?php
					$button_html = '';
					if(!$is_new_vendor_form) {
						if(in_array('dc_vendor', $user->roles)) {
							$is_block = get_user_meta($user->ID, '_vendor_turn_off', true);
						
							if($is_block) {
								$button_html = '<div id="wc-backbone-modal-dialog">
													<button class="button button-primary wcmp-action-button vendor-activate-btn pull-right" data-vendor-id="' . $user->ID . '" data-ajax-action="wcmp_activate_vendor">' . __('Activate', 'dc-woocommerce-multi-vendor') . '</button>
													<button class="button button-primary vendor-update-btn wcmp-primary-btn" name="wcmp_vendor_submit" value="update">' . __('Update', 'dc-woocommerce-multi-vendor') . '</button>
												</div>';
							} else {
								$button_html = '<div id="wc-backbone-modal-dialog">
													<button class="button button-primary wcmp-action-button vendor-suspend-btn pull-right" data-vendor-id="' . $user->ID . '" data-ajax-action="wcmp_suspend_vendor">' . __('Suspend', 'dc-woocommerce-multi-vendor') . '</button>
													<button class="button button-primary vendor-update-btn wcmp-primary-btn" name="wcmp_vendor_submit" value="update">' . __('Update', 'dc-woocommerce-multi-vendor') . '</button>
												</div>';
							}
						} else if(in_array('dc_rejected_vendor', $user->roles)) {
							// Do Nothing
						} else if(in_array('dc_pending_vendor', $user->roles)) {
							$button_html = '<div class="wcmp-vendor-modal-main">
												<textarea class="pending-vendor-note form-control" data-note-author-id="' . get_current_user_id() . '"placeholder="' . __( 'Optional note for acceptance / rejection', 'dc-woocommerce-multi-vendor' ) . '" name=""></textarea>
												<div id="wc-backbone-modal-dialog">
													<button class="button button-primary wcmp-action-button vendor-approve-btn wcmp-primary-btn" data-vendor-id="' . $user->ID . '" data-ajax-action="activate_pending_vendor">' . __('Approve', 'dc-woocommerce-multi-vendor') . '</button>
													<button class="button button-primary wcmp-action-button vendor-reject-btn pull-right" data-vendor-id="' . $user->ID . '" data-ajax-action="reject_pending_vendor">' . __('Reject', 'dc-woocommerce-multi-vendor') . '</button>
												</div>
											</div>';
						}
					} else {
						$button_html = '<div id="wc-backbone-modal-dialog">
											<button class="button button-primary vendor-update-btn wcmp-primary-btn" name="wcmp_vendor_submit" value="add_new">' . __('Add New', 'dc-woocommerce-multi-vendor') . '</button>
										</div>';
					}
					echo $button_html;
					?>
				</form>
			</div>			
			<!-- vednor edit End -->
		<?php
        } else {
        ?>
        <div class="wrap">    
			<div id="nds-wp-list-table-demo">			
				<div id="nds-post-body">		
					<form action="" method="get">
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />					
					<?php
						$this->prepare_items();
						$this->views();
						$this->process_bulk_action();
						$this->search_box( __( 'Search Vendors', 'dc-woocommerce-multi-vendor' ), 'vendors' ); 
						$this->display();
					?>					
					</form>
				</div>			
			</div>
        </div>
        <?php
		}
	}
	
	// vendor preview
	function wcmp_vendor_preview_template() {
		global $WCMp;
		?>
		<script type="text/template" id="tmpl-wcmp-modal-view-vendor">
			<div id="wcmp-vendor-modal-dialog wcmp-vendor-modal-preview">
				<div class="wcmp-vendor-modal wcmp-vendor-preview">
					<div class="wcmp-vendor-modal-content" tabindex="0">
						<section class="wcmp-vendor-modal-main" role="main">
							<header class="wcmp-vendor-modal-header">
								<!--
								
								<i class="status-sprite status-sprite-pending-icon"></i>
								<i class="status-sprite status-sprite-reject-icon"></i>
								<i class="status-sprite status-sprite-suspended-icon"></i>
								-->
								<# if ( data.avg_rating ) { #>
									<h1> {{ data.display_name }} <span>{{ data.avg_rating }}<i class="dashicons dashicons-star-filled"></i></span></h1>
								<# } else { #>
									<h1> {{ data.display_name }}</h1>
								<# } #>
								<div class="vendor-status-header {{ data.status }}-vendor">
									<i class="status-sprite status-sprite-{{ data.status_name }}-icon"></i>
									<span>{{ data.status_name }}</span>
								</div>
								
								<button class="modal-close modal-close-link dashicons dashicons-no-alt">
									<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'dc-woocommerce-multi-vendor' ); ?></span>
								</button>
							</header>
							<article <# if ( data.status_name == 'Pending' ) { #> class="pending-vendor-article" <# } #>>
								<?php do_action( 'wcmp_admin_vendor_preview_start' ); ?>

								<# if ( data.status == 'approved' || data.status == 'suspended' ) { #>
									<div class="vendor-info-row vendor-top-info-holder">
										<div class="pull-left">
											<div class="vendor-img-holder">
												<img src="{{ data.profile_image }}" alt="img" width="150px">
												<ul class="user-social-link">
													<# if ( data.facebook ) { #>
														<li><a href="{{ data.facebook }}"><span class="social-sprite social-sprite-facebook-icon"></span></a></li>
													<# } #>
													<# if ( data.twitter ) { #>
														<li><a href="{{ data.twitter }}"><span class="social-sprite social-sprite-twitter-icon"></span></a></li>
													<# } #>
													<# if ( data.google_plus ) { #>
														<li><a href="{{ data.google_plus }}"><span class="social-sprite social-sprite-googleplus-icon"></span></a></li>
													<# } #>
													<# if ( data.linkdin ) { #>
														<li><a href="{{ data.linkdin }}"><span class="social-sprite social-sprite-linkedin-icon"></span></a></li>
													<# } #>
													<# if ( data.youtube ) { #>
														<li><a href="{{ data.youtube }}"><span class="social-sprite social-sprite-youtube-icon"></span></a></li>
													<# } #>
													<# if ( data.instagram ) { #>
														<li><a href="{{ data.instagram }}"><span class="social-sprite social-sprite-instagram-icon"></span></a></li>
													<# } #>
												</ul>
											</div>
										</div>
										<div class="pull-right">
											<h3>Personal Information</h3>
											<table class="vendor-personal-info-table">
												<tbody>
													<# if ( data.email ) { #>
														<tr>
															<td><?php esc_html_e( 'Email:', 'dc-woocommerce-multi-vendor' ); ?></td>
															<td>{{ data.email }}</td>
														</tr>
													<# } #>

													<# if ( data.phone ) { #>
														<tr>
															<td><?php esc_html_e( 'Phone:', 'dc-woocommerce-multi-vendor' ); ?></td>
															<td>{{ data.phone }}</td>
														</tr>
													<# } #>

													<# if ( data.city ) { #>
														<tr>
															<td><?php esc_html_e( 'City:', 'dc-woocommerce-multi-vendor' ); ?></td>
															<td>{{ data.city }}</td>
														</tr>
													<# } #>

													<# if ( data.state ) { #>
													<tr>
														<td><?php esc_html_e( 'State:', 'dc-woocommerce-multi-vendor' ); ?></td>
														<td>{{ data.state }}</td>
													</tr>
													<# } #>
													
													<# if ( data.country ) { #>
													<tr>
														<td><?php esc_html_e( 'Country:', 'dc-woocommerce-multi-vendor' ); ?></td>
														<td>{{ data.country }}</td>
													</tr>
													<# } #>

													<# if ( data.postcode ) { #>
													<tr>
														<td><?php esc_html_e( 'Postcode:', 'dc-woocommerce-multi-vendor' ); ?></td>
														<td>{{ data.postcode }}</td>
													</tr>
													<# } #>

													<# if ( data.address_1 ) { #>
													<tr>
														<td><?php esc_html_e( 'Address:', 'dc-woocommerce-multi-vendor' ); ?></td>
														<td>{{ data.address_1 }}</td>
													</tr>
													<# } #>

													<# if ( data.address_2 ) { #>
													<tr>
														<td>&nbsp;</td>
														<td>{{ data.address_2 }}</td>
													</tr>
													<# } #>

													<# if ( data.shop_url ) { #>
													<tr>
														<td><?php esc_html_e( 'Shop:', 'dc-woocommerce-multi-vendor' ); ?></td>
														<td>
															<a href="{{ data.shop_url }}" target="_blank">{{ data.shop_title }} <span class="dashicons dashicons-external"></span></a>
														</td>
													</tr>		
													<# } #>	
												</tbody>
											</table>						
										</div>
									</div>
									<div class="vendor-info-row vendor-profile">
										<# if ( data.profile_progress < 100 ) { #>
											<p><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Profile complete is', 'dc-woocommerce-multi-vendor' ); ?> {{ data.profile_progress }}%</p>
										<# } else { #>	
											<p class="vendor-profile-complete"><span class="dashicons dashicons-thumbs-up"></span> <?php esc_html_e( '100% Profile complete', 'dc-woocommerce-multi-vendor' ); ?></p>
										<# } #>	
									</div>

									<div class="vendor-info-row payment-info-row">
										<p><?php esc_html_e( 'Withdrawable balance', 'dc-woocommerce-multi-vendor' ); ?><mark>{{{ data.withdrawable_balance }}}</mark></p>
										<# if ( data.payment_mode != '' && data.payment_mode != 'payment_mode' ) { #>
										<p><?php esc_html_e( 'Payment mode', 'dc-woocommerce-multi-vendor' ); ?> <img src="{{ data.gateway_logo }}" alt="{{ data.payment_mode }} logo"></p>
										<# } #>	
									</div>
									<div class="vendor-info-row">
										<h3><?php esc_html_e( 'Last 30 day\'s performance:', 'dc-woocommerce-multi-vendor' ); ?></h3>
										<table class="wp-list-table widefat bordered vendors">
											<tr>
												<th><?php esc_html_e( 'No of order', 'dc-woocommerce-multi-vendor' ); ?></th>
												<th><?php esc_html_e( 'Sales', 'dc-woocommerce-multi-vendor' ); ?></th>
												<th><?php esc_html_e( 'Earning', 'dc-woocommerce-multi-vendor' ); ?></th>
												<th><?php esc_html_e( 'Withdrawal', 'dc-woocommerce-multi-vendor' ); ?></th>
											</tr>
											<tr class="inline-edit-row">
												<td>{{ data.last_30_days_orders_no }}</td>
												<td>{{{ data.last_30_days_sales_total }}}</td>
												<td>{{{ data.last_30_days_earning }}}</td>
												<td>{{{ data.last_30_days_withdrawal }}}</td>
											</tr>
										</table>
									</div>
								<# } else { #>

									<!-- pending vendor -->
									<div class="vendor-info-row wcmp-vendor-preview-addresses"> 
										<# if ( data.email ) { #>
											<div class="wcmp-form-field">
												<label><?php _e( 'Email:', 'dc-woocommerce-multi-vendor' ); ?></label>
												<span>{{ data.email }}</span>
											</div>
										<# } #>
										{{{ data.vendor_application_data }}}
										
										<# if ( data.vendor_custom_notes ) { #>
											<div class="vendor-quick-view-notes">
												<h2><?php _e( 'Notes:', 'dc-woocommerce-multi-vendor' ); ?></h2>
												<div class="note-clm-wrap">
													{{{ data.vendor_custom_notes }}}
												</div>
											</div>
										<# } #>
									</div>
								<# } #>
								<?php do_action( 'wcmp_admin_vendor_preview_end' ); ?>
							</article>
							<footer>
								<# if ( data.status == 'pending' ) { #>
									<textarea class="pending-vendor-note form-control" data-note-author-id="<?php echo get_current_user_id(); ?>"placeholder="<?php esc_html_e( 'Optional note for acceptance / rejection', 'dc-woocommerce-multi-vendor' ); ?>" name="" ></textarea>
								<# } #>
								<div class="inner">
									<div class="pull-left">
										{{{ data.actions_html }}}
										<p class="wcmp-loader"></p>
									</div>
									<a class="button button-primary button-large" href="<?php echo '?page=' . $_REQUEST['page'] . '&action=edit&ID={{ data.ID }}'; ?>"><?php _e( 'Edit Vendor', 'dc-woocommerce-multi-vendor' );?></a>
								</div>
							</footer>
						</section>
					</div>
				</div>
				<div class="wcmp-vendor-modal-backdrop modal-close"></div>
			</div>
		</script>
		<?php
	}
}
