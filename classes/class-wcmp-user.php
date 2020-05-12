<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @class 		WCMp User Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_User {

    public function __construct() {
        add_action('user_register', array(&$this, 'vendor_registration'), 10, 1);
        // Add column product in users dashboard
        add_filter('manage_users_columns', array(&$this, 'column_register_product'));
        add_filter('manage_users_custom_column', array(&$this, 'column_display_product'), 10, 3);
        // Set vendor_action links in user dashboard
        add_filter('user_row_actions', array(&$this, 'vendor_action_links'), 10, 2);
        // Add addistional user fields
        add_action('show_user_profile', array(&$this, 'additional_user_fields'));
        add_action('edit_user_profile', array(&$this, 'additional_user_fields'));
        // Validate addistional user fields
        add_action('user_profile_update_errors', array(&$this, 'validate_user_fields'), 10, 3);
        // Save addistional user fields
        add_action('personal_options_update', array(&$this, 'save_vendor_data'));
        add_action('edit_user_profile_update', array(&$this, 'save_vendor_data'));
        // Delete vendor
        add_action('delete_user', array(&$this, 'delete_vendor'));
        // Created customer notification
        add_action('woocommerce_created_customer_notification', array($this, 'wcmp_woocommerce_created_customer_notification'), 9, 3);
        // Create woocommerce term and shipping on change user role
        add_action('set_user_role', array(&$this, 'set_user_role'), 30, 3);
        // Add message in my account page after vendore registrtaion
        add_action('woocommerce_before_my_account', array(&$this, 'woocommerce_before_my_account'));
        // Add vendor new order email template
        add_filter('woocommerce_resend_order_emails_available', array($this, 'wcmp_order_emails_available'));
        // Redirect user to vendor dashboard or my account page
        add_filter('woocommerce_registration_redirect', array($this, 'vendor_login_redirect'), 30, 1);
        // Register vendor from vendor dashboard
        $this->register_vendor_from_vendor_dashboard();
        // wordpress, woocommerce login redirest
        add_filter('woocommerce_login_redirect', array($this, 'wcmp_vendor_login'), 10, 2);
        add_filter('login_redirect', array($this, 'wp_wcmp_vendor_login'), 10, 3);
        // set cookie
        $this->set_wcmp_user_cookies();
        //User Avatar override
        add_filter( 'get_avatar', array( &$this, 'wcmp_user_avatar_override' ), 10, 6 );
    
        // Disable backend access for suspended vendor
        add_filter('wcmp_vendor_dashboard_header_right_panel_nav', array( &$this, 'remove_backend_access_for_suspended_vendor'));
        add_action('init', array( &$this, 'remove_wp_admin_access_for_suspended_vendor'), 11);
        // Enable media handler caps for vendor, mainly for policy media handler
        add_filter( 'map_meta_cap', array( &$this, 'media_handler_map_meta_cap'), 99, 4 );
        // restrict wp-editor quick-link links query
        add_filter( 'wp_link_query_args', array( &$this, 'userwise_wp_link_query_args'), 99 );
        // filter user query, if orderby setted as 'rand'
        add_filter( 'pre_user_query', array( &$this, 'wcmp_pre_user_query_filtered'), 99 );
    }
    
    function remove_wp_admin_access_for_suspended_vendor() {
		if(is_user_wcmp_vendor(get_current_vendor_id())) {
			$is_block = get_user_meta(get_current_vendor_id(), '_vendor_turn_off', true);
			if( $is_block && is_admin() ) {
				wp_redirect(get_permalink(wcmp_vendor_dashboard_page_id()));
				exit;
			}
		}
	}
	
	function remove_backend_access_for_suspended_vendor($panel_nav) {
		if(is_user_wcmp_vendor(get_current_vendor_id())) {
			$is_block = get_user_meta(get_current_vendor_id(), '_vendor_turn_off', true);
			if($is_block) unset($panel_nav['wp-admin']);
		}
					
		return $panel_nav;
	}

    /**
     * Wordpress Login redirect
     * @param string $redirect_to
     * @param string $requested_redirect_to
     * @param WP_User $user
     * @return string
     */
    public function wp_wcmp_vendor_login($redirect_to, $requested_redirect_to, $user) {
        //is there a user to check?
        if ($requested_redirect_to == admin_url()) {
            if (isset($user->roles) && is_array($user->roles)) {
                //check for admins
                if (in_array('dc_vendor', $user->roles)) {
                    // redirect them to the default place
                    if(is_wcmp_vendor_completed_store_setup($user)){
                        $redirect_to = get_permalink(wcmp_vendor_dashboard_page_id());
                    }else{
                        $redirect_to = get_permalink(wcmp_vendor_dashboard_page_id()).'?page=vendor-store-setup';
                    }
                }
            }
        }
        return apply_filters( 'wcmp_vendor_login_redirect_url', $redirect_to);
    }

    /**
     * WooCommerce login redirect
     * @param string $redirect
     * @param WP_User $user
     * @return string
     */
    public function wcmp_vendor_login($redirect, $user) {
        if (!isset($_POST['wcmp-login-vendor'])) {
            if (is_array($user->roles)) {
                if (in_array('dc_vendor', $user->roles)) {
                    if(is_wcmp_vendor_completed_store_setup($user)){
                        $redirect = get_permalink(wcmp_vendor_dashboard_page_id());
                    }else{
                        $redirect = get_permalink(wcmp_vendor_dashboard_page_id()).'?page=vendor-store-setup';
                    }
                }
            } else if ($user->roles == 'dc_vendor') {
                $redirect = get_permalink(wcmp_vendor_dashboard_page_id());
            }
        }
        return apply_filters( 'wcmp_vendor_login_redirect_url', $redirect);
    }

    /**
     * Register vendor from vendor dashboard
     * @return void
     */
    public function register_vendor_from_vendor_dashboard() {
        $user = wp_get_current_user();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['wcmp_vendor_fields']) && isset($_POST['pending_vendor']) && isset($_POST['vendor_apply'])) {
                $customer_id = $user->ID;
                $validation_errors = new WP_Error();
                $wcmp_vendor_registration_form_data = get_option('wcmp_vendor_registration_form_data');
                if(isset($_POST['g-recaptchatype']) && $_POST['g-recaptchatype'] == 'v2'){
                    if (isset($_POST['g-recaptcha-response']) && empty($_POST['g-recaptcha-response'])) {
                        $validation_errors->add('recaptcha is not validate', __('Please Verify  Recaptcha', 'dc-woocommerce-multi-vendor'));
                    }
                }elseif(isset($_POST['g-recaptchatype']) && $_POST['g-recaptchatype'] == 'v3') {
                    $recaptcha_secret = isset($_POST['recaptchav3_secretkey']) ? $_POST['recaptchav3_secretkey'] : '';
                    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
                    $recaptcha_response = isset($_POST['recaptchav3Response']) ? $_POST['recaptchav3Response'] : '';

                    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
                    $recaptcha = json_decode($recaptcha);

                    if (!$recaptcha->success || $recaptcha->score < 0.5) {
                        $validation_errors->add('recaptcha is not validate', __('Please Verify  Recaptcha', 'dc-woocommerce-multi-vendor'));
                    }
                }

                if (isset($_FILES['wcmp_vendor_fields'])) {
                    $attacment_files = $_FILES['wcmp_vendor_fields'];
                    if (!empty($attacment_files) && is_array($attacment_files)) {
                        foreach ($attacment_files['name'] as $key => $value) {
                            $file_type = array();
                            foreach ($wcmp_vendor_registration_form_data[$key]['fileType'] as $key1 => $value1) {
                                if ($value1['selected']) {
                                    array_push($file_type, $value1['value']);
                                }
                            }
                            foreach ($attacment_files['type'][$key] as $file_key => $file_value) {
                                if ($wcmp_vendor_registration_form_data[$key]['required'] && !in_array($file_value, $file_type)) {
                                    $validation_errors->add('file type error', __('Please Upload valid file', 'dc-woocommerce-multi-vendor'));
                                }
                            }
                            foreach ($attacment_files['size'][$key] as $file_size_key => $file_size_value) {
                                if (!empty($wcmp_vendor_registration_form_data[$key]['fileSize'])) {
                                    if ($wcmp_vendor_registration_form_data[$key]['required'] && $file_size_value > $wcmp_vendor_registration_form_data[$key]['fileSize']) {
                                        $validation_errors->add('file size error', __('File upload limit exceeded', 'dc-woocommerce-multi-vendor'));
                                    }
                                }
                            }
                        }
                    }
                }

                if ($validation_errors->get_error_code()) {
                    WC()->session->set('wc_notices', array('error' => array($validation_errors->get_error_message())));
                    return;
                }

                if (isset($_FILES['wcmp_vendor_fields'])) {
                    $attacment_files = $_FILES['wcmp_vendor_fields'];
                    $files = array();
                    $count = 0;
                    if (!empty($attacment_files) && is_array($attacment_files)) {
                        foreach ($attacment_files['name'] as $key => $attacment) {
                            foreach ($attacment as $key_attacment => $value_attacment) {
                                $files[$count]['name'] = $value_attacment;
                                $files[$count]['type'] = $attacment_files['type'][$key][$key_attacment];
                                $files[$count]['tmp_name'] = $attacment_files['tmp_name'][$key][$key_attacment];
                                $files[$count]['error'] = $attacment_files['error'][$key][$key_attacment];
                                $files[$count]['size'] = $attacment_files['size'][$key][$key_attacment];
                                $files[$count]['field_key'] = $key;
                                $count++;
                            }
                        }
                    }
                    $upload_dir = wp_upload_dir();
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    if (!function_exists('wp_handle_upload')) {
                        require_once( ABSPATH . 'wp-admin/includes/file.php' );
                    }
                    foreach ($files as $file) {
                        $uploadedfile = $file;
                        $upload_overrides = array('test_form' => false);
                        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                        if ($movefile && !isset($movefile['error'])) {
                            $filename = $movefile['file'];
                            $filetype = wp_check_filetype($filename, null);
                            $attachment = array(
                                'post_mime_type' => $filetype['type'],
                                'post_title' => $file['name'],
                                'post_content' => '',
                                'post_status' => 'inherit',
                                'guid' => $movefile['url']
                            );
                            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
                            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                            wp_update_attachment_metadata($attach_id, $attach_data);
                            $_POST['wcmp_vendor_fields'][$file['field_key']]['value'][] = $attach_id;
                        }
                    }
                }
                $wcmp_vendor_fields = $_POST['wcmp_vendor_fields'];

                $wcmp_vendor_fields = apply_filters('wcmp_save_registration_fields', $wcmp_vendor_fields, $customer_id);
                update_user_meta($customer_id, 'wcmp_vendor_fields', $wcmp_vendor_fields);
            }

            if (isset($_POST['vendor_apply']) && $user) {
                if (isset($_POST['pending_vendor']) && ( $_POST['pending_vendor'] == 'true' )) {
                    $this->vendor_registration($user->ID);
                    $this->wcmp_customer_new_account($user->ID);
                    $redirect_to = apply_filters( 'wcmp_user_apply_vendor_redirect_url', get_permalink( wcmp_vendor_dashboard_page_id() ), $_POST );
                    wp_redirect( $redirect_to );
                    exit;
                }
            }
        }
    }

    /**
     * Vendor login template redirect
     */
    function vendor_login_redirect($redirect_to) {
        if (isset($_POST['email'])) {
            $user = get_user_by('email', $_POST['email']);
            if (is_object($user) && isset($user->ID) && is_user_wcmp_vendor($user->ID)) {
                $redirect_to = get_permalink(wcmp_vendor_dashboard_page_id());
                return apply_filters('wcmp_vendor_login_redirect', $redirect_to, $user);
            }
            return apply_filters('wcmp_vendor_login_redirect', $redirect_to, $user);
        }
        return apply_filters('wcmp_vendor_login_redirect', $redirect_to, $user);
    }

    /**
     * WCMp Vendor message at WC myAccount
     * @access public
     * @return void
     */
    public function woocommerce_before_my_account() {
        $current_user = wp_get_current_user();
        if (is_user_wcmp_pending_vendor($current_user)) {
            _e('Congratulations! You have successfully applied as a Vendor. Please wait for further notifications from the admin.', 'dc-woocommerce-multi-vendor');
            do_action('add_vendor_extra_information_my_account');
        }
        if (is_user_wcmp_vendor($current_user)) {
            $dashboard_page_link = wcmp_vendor_dashboard_page_id() ? get_permalink(wcmp_vendor_dashboard_page_id()) : '#';
            echo apply_filters('wcmp_vendor_goto_dashboard', '<a href="' . $dashboard_page_link . '">' . __('Dashboard - manage your account here', 'dc-woocommerce-multi-vendor') . '</a>');
        }
    }

    /**
     * Set vendor user role and associate capabilities
     *
     * @access public
     * @param user_id, new role, old role
     * @return void
     */
    public function set_user_role($user_id, $new_role, $old_role) {
        if ($new_role == 'dc_rejected_vendor') {
            $user_dtl = get_userdata(absint($user_id));
            $email = WC()->mailer()->emails['WC_Email_Rejected_New_Vendor_Account'];
            $email->trigger($user_id, $user_dtl->user_pass);
        } else if ($new_role == 'dc_vendor') {
            $vendor = get_wcmp_vendor($user_id);
            if ($vendor) {
                $vendor->generate_shipping_class();
                $vendor->generate_term();
            }
        }
        do_action('wcmp_set_user_role', $user_id, $new_role, $old_role);
    }

    /**
     * Set up array of vendor admin capabilities
     *
     * @access public
     * @return arr Vendor capabilities
     * @deprecated since version 2.7.6
     */
    public function get_vendor_caps() {
        global $WCMp;
        _deprecated_function('get_vendor_caps', '2.7.6', 'WCMp_Capabilities::get_vendor_caps');
        return $WCMp->vendor_caps->get_vendor_caps();
    }

    /**
     * Get vendor details
     *
     * @param $user_id
     * @access public
     * @return array
     */
    public function get_vendor_fields($user_id) {
        global $WCMp;

        $vendor = new WCMp_Vendor($user_id);
        $settings_capabilities = array_merge(
                (array) get_option('wcmp_general_settings_name', array())
                , (array) get_option('wcmp_capabilities_product_settings_name', array())
                , (array) get_option('wcmp_capabilities_order_settings_name', array())
                , (array) get_option('wcmp_capabilities_miscellaneous_settings_name', array())
        );
        $policies_settings = get_option('wcmp_general_policies_settings_name');

        $fields = apply_filters('wcmp_vendor_fields', array(
            "vendor_page_title" => array(
                'label' => __('Vendor Page Title', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->page_title,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_page_slug" => array(
                'label' => __('Vendor Page Slug', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->page_slug,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_description" => array(
                'label' => __('Description', 'dc-woocommerce-multi-vendor'),
                'type' => 'wpeditor',
                'value' => $vendor->description,
                'class' => "user-profile-fields"
            ), //Wp Eeditor
            "vendor_hide_address" => array(
                'label' => __('Hide address in frontend', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->hide_address,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            ),
            "vendor_hide_phone" => array(
                'label' => __('Hide phone in frontend', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->hide_phone,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            ),
            "vendor_hide_email" => array(
                'label' => __('Hide email in frontend', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->hide_email,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            ),
            "vendor_hide_description" => array(
                'label' => __('Hide description in frontend', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->hide_description,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            ),
            "vendor_address_1" => array(
                'label' => __('Address 1', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->address_1,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_address_2" => array(
                'label' => __('Address 2', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->address_2,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_city" => array(
                'label' => __('City', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->city,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_postcode" => array(
                'label' => __('Postcode', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->postcode,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_external_store_url" => array(
                'label' => __('External store URL', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->external_store_url,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_external_store_label" => array(
                'label' => __('External store URL label', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->external_store_label,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_state" => array(
                'label' => __('State', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->state,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_country" => array(
                'label' => __('Country', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->country,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_phone" => array(
                'label' => __('Phone', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->phone,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_fb_profile" => array(
                'label' => __('Facebook Profile', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->fb_profile,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_twitter_profile" => array(
                'label' => __('Twitter Profile', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->twitter_profile,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_google_plus_profile" => array(
                'label' => __('Google+ Profile', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->google_plus_profile,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_linkdin_profile" => array(
                'label' => __('LinkedIn Profile', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->linkdin_profile,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_youtube" => array(
                'label' => __('YouTube Channel', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->youtube,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_instagram" => array(
                'label' => __('Instagram Profile', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->instagram,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_image" => array(
                'label' => __('Logo', 'dc-woocommerce-multi-vendor'),
                'type' => 'upload',
                'prwidth' => 125,
                'url' => $vendor->get_image() ? $vendor->get_image() : '',
                'value' => $vendor->image,
                'class' => "user-profile-fields"
            ), // Upload
            "vendor_banner" => array(
                'label' => __('Banner', 'dc-woocommerce-multi-vendor'),
                'type' => 'upload',
                'prwidth' => 600,
                'url' => $vendor->get_image('banner') ? $vendor->get_image('banner') : '',
                'value' => $vendor->banner,
                'class' => "user-profile-fields"
            ), // Upload			
            "vendor_csd_return_address1" => array(
                'label' => __('Customer address1', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->csd_return_address1,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_csd_return_address2" => array(
                'label' => __('Customer address2', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->csd_return_address2,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_csd_return_country" => array(
                'label' => __('Customer Country', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->csd_return_country,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_csd_return_state" => array(
                'label' => __('Customer Return State', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->csd_return_state,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_csd_return_city" => array(
                'label' => __('Customer Return City', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->csd_return_city,
                'class' => "user-profile-fields regular-text"
            ), // Text 
            "vendor_csd_return_zip" => array(
                'label' => __('Customer Return Zip', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->csd_return_zip,
                'class' => "user-profile-fields regular-text"
            ), // Text  
            "vendor_customer_phone" => array(
                'label' => __('Customer Phone', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->customer_phone,
                'class' => "user-profile-fields regular-text"
            ), // Text
            "vendor_customer_email" => array(
                'label' => __('Customer Email', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->customer_email,
                'class' => "user-profile-fields regular-text"
            ), // Text
                ), $user_id);

        if (!apply_filters('is_vendor_add_external_url_field', false)) {
            unset($fields['vendor_external_store_url']);
            unset($fields['vendor_external_store_label']);
        }

        $payment_admin_settings = get_option('wcmp_payment_settings_name');
        $payment_mode = array('' => __('Payment Mode', 'dc-woocommerce-multi-vendor'));
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

        $fields["vendor_payment_mode"] = array(
            'label' => __('Payment Mode', 'dc-woocommerce-multi-vendor'),
            'type' => 'select',
            'options' => apply_filters('wcmp_vendor_payment_mode', $payment_mode),
            'value' => $vendor->payment_mode,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_bank_account_type"] = array(
            'label' => __('Bank Account Type', 'dc-woocommerce-multi-vendor'),
            'type' => 'select',
            'options' => array('current' => __('Current', 'dc-woocommerce-multi-vendor'), 'savings' => __('Savings', 'dc-woocommerce-multi-vendor')),
            'value' => $vendor->bank_account_type,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_bank_account_number"] = array(
            'label' => __('Bank Account Number', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->bank_account_number,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_bank_name"] = array(
            'label' => __('Bank Name', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->bank_name,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_aba_routing_number"] = array(
            'label' => __('ABA Routing Number', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->aba_routing_number,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_bank_address"] = array(
            'label' => __('Bank Address', 'dc-woocommerce-multi-vendor'),
            'type' => 'textarea',
            'value' => $vendor->bank_address,
            'class' => "user-profile-fields"
        ); // Text

        $fields["vendor_destination_currency"] = array(
            'label' => __('Destination Currency', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->destination_currency,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_iban"] = array(
            'label' => __('IBAN', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->iban,
            'class' => "user-profile-fields regular-text"
        ); // Text

        $fields["vendor_account_holder_name"] = array(
            'label' => __('Account Holder Name', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->account_holder_name,
            'class' => "user-profile-fields regular-text"
        ); // Text
        $fields["vendor_paypal_email"] = array(
            'label' => __('PayPal Email', 'dc-woocommerce-multi-vendor'),
            'type' => 'text',
            'value' => $vendor->paypal_email,
            'class' => "user-profile-fields regular-text"
        ); // Text

        if (apply_filters('wcmp_vendor_can_overwrite_policies', true) && get_wcmp_vendor_settings('is_policy_on', 'general') == 'Enable') {
            $_wp_editor_settings = array('tinymce' => true);
            if (!$WCMp->vendor_caps->vendor_can('is_upload_files')) {
                $_wp_editor_settings['media_buttons'] = false;
            }
            $_wp_editor_settings = apply_filters('wcmp_vendor_policies_wp_editor_settings', $_wp_editor_settings);
            $fields['vendor_cancellation_policy'] = array(
                'label' => __('Cancellation/Return/Exchange Policy', 'dc-woocommerce-multi-vendor'),
                'type' => 'wpeditor',
                'value' => $vendor->cancellation_policy,
                'class' => 'user-profile-fields'
            );
            $fields['vendor_refund_policy'] = array(
                'label' => __('Refund Policy', 'dc-woocommerce-multi-vendor'),
                'type' => 'wpeditor',
                'value' => $vendor->refund_policy,
                'class' => 'user-profile-fields',
                'settings' => $_wp_editor_settings
            );
            $fields['vendor_shipping_policy'] = array(
                'label' => __('Shipping Policy', 'dc-woocommerce-multi-vendor'),
                'type' => 'wpeditor',
                'value' => $vendor->shipping_policy,
                'class' => 'user-profile-fields regular-text',
                'settings' => $_wp_editor_settings
            );
        }
        $_wp_editor_settings = array('tinymce' => true);
        if (!$WCMp->vendor_caps->vendor_can('is_upload_files')) {
            $_wp_editor_settings['media_buttons'] = false;
        }
        $_wp_editor_settings = apply_filters('wcmp_vendor_msg_to_buyer_wp_editor_settings', $_wp_editor_settings);
        $fields['vendor_message_to_buyers'] = array(
            'label' => __('Message to Buyers', 'dc-woocommerce-multi-vendor'),
            'type' => 'wpeditor',
            'value' => $vendor->message_to_buyers,
            'class' => 'user-profile-fields',
            'settings' => $_wp_editor_settings
        );
        
        $user = wp_get_current_user();
        if (is_array($user->roles) && in_array('administrator', $user->roles)) {
            $fields['vendor_commission'] = array(
                'label' => __('Commission Amount', 'dc-woocommerce-multi-vendor'),
                'type' => 'text',
                'value' => $vendor->commission,
                'class' => "user-profile-fields regular-text"
            );
            $fields['vendor_give_tax'] = array(
                'label' => __('Withhold Tax', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->give_tax,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            );
            $fields['vendor_give_shipping'] = array(
                'label' => __('Withhold Shipping', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->give_shipping,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            );
            $fields['vendor_turn_off'] = array(
                'label' => __('Block this vendor with all items', 'dc-woocommerce-multi-vendor'),
                'type' => 'checkbox',
                'dfvalue' => $vendor->turn_off,
                'value' => 'Enable',
                'class' => 'user-profile-fields'
            );



            if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage') {
                unset($fields['vendor_commission']);
                $fields['vendor_commission_percentage'] = array(
                    'label' => __('Commission Percentage(%)', 'dc-woocommerce-multi-vendor'),
                    'type' => 'text',
                    'value' => $vendor->commission_percentage,
                    'class' => 'user-profile-fields regular-text'
                );
                $fields['vendor_commission_fixed_with_percentage'] = array(
                    'label' => __('Commission(fixed), Per Transaction', 'dc-woocommerce-multi-vendor'),
                    'type' => 'text',
                    'value' => $vendor->commission_fixed_with_percentage,
                    'class' => 'user-profile-fields regular-text'
                );
            }

            if ($WCMp->vendor_caps->payment_cap['commission_type'] == 'fixed_with_percentage_qty') {
                unset($fields['vendor_commission']);
                $fields['vendor_commission_percentage'] = array(
                    'label' => __('Commission Percentage(%)', 'dc-woocommerce-multi-vendor'),
                    'type' => 'text',
                    'value' => $vendor->commission_percentage,
                    'class' => 'user-profile-fields regular-text'
                );
                $fields['vendor_commission_fixed_with_percentage_qty'] = array(
                    'label' => __('Commission Fixed Per Unit', 'dc-woocommerce-multi-vendor'),
                    'type' => 'text',
                    'value' => $vendor->commission_fixed_with_percentage_qty,
                    'class' => 'user-profile-fields regular-text'
                );
            }
        }

        return apply_filters('wcmp_vendor_user_fields', $fields, $vendor->id);
    }

    /**
     * Actions at Vendor Registration
     *
     * @access public
     * @param $user_id
     */
    public function vendor_registration($user_id) {
        global $WCMp;
        $is_approve_manually = $WCMp->vendor_caps->vendor_general_settings('approve_vendor_manually');
        if (isset($_POST['pending_vendor']) && ($_POST['pending_vendor'] == 'true') && !is_user_wcmp_vendor($user_id)) {
            if ($is_approve_manually) {
                $user = new WP_User(absint($user_id));
                $user->set_role('dc_pending_vendor');
            } else {
                $user = new WP_User(absint($user_id));
                $user->set_role('dc_vendor');
            }
            do_action('after_register_wcmp_vendor', $user_id);
        }
    }

    /**
     * ADD commission column on user dashboard
     *
     * @access public
     * @return array
     */
    function column_register_product($columns) {
        $columns['product'] = __('Products', 'dc-woocommerce-multi-vendor');
        return $columns;
    }

    /**
     * Display commission column on user dashboard
     *
     * @access public
     * @return string
     */
    function column_display_product($empty, $column_name, $user_id) {
        if ('product' != $column_name) {
            return $empty;
        }
        $vendor = get_wcmp_vendor($user_id);
        if ($vendor) {
            $product_count = count($vendor->get_products_ids());
            return "<a href='edit.php?post_type=product&dc_vendor_shop=" . $vendor->page_slug . "'><strong>{$product_count}</strong></a>";
        } else {
            return "<strong></strong>";
        }
    }

    /**
     * Add vendor action link in user dashboard
     *
     * @access public
     * @return array
     */
    function vendor_action_links($actions, $user_object) {
        if (is_user_wcmp_vendor($user_object)) {
            $vendor = get_wcmp_vendor($user_object->ID);
            if ($vendor) {
                unset($actions['view']);
                $actions['view_vendor'] = "<a target=_blank class='view_vendor' href='" . $vendor->permalink . "'>" . __('View', 'dc-woocommerce-multi-vendor') . "</a>";
            }
        }

        if (is_user_wcmp_pending_vendor($user_object)) {
            $vendor = get_wcmp_vendor($user_object->ID);
            unset($actions['view']);
            $actions['activate'] = "<a class='activate_vendor' data-id='" . $user_object->ID . "'href=#>" . __('Approve', 'dc-woocommerce-multi-vendor') . "</a>";
            $actions['reject'] = "<a class='reject_vendor' data-id='" . $user_object->ID . "'href=#>" . __('Reject', 'dc-woocommerce-multi-vendor') . "</a>";
        }

        if (is_user_wcmp_rejected_vendor($user_object)) {
            $vendor = get_wcmp_vendor($user_object->ID);
            unset($actions['view']);
            $actions['activate'] = "<a class='activate_vendor' data-id='" . $user_object->ID . "'href=#>" . __('Approve', 'dc-woocommerce-multi-vendor') . "</a>";
        }
        return $actions;
    }

    /**
     * Additional user  fileds at Profile page
     *
     * @access private
     * @param $user obj
     * @return void
     */
    function additional_user_fields($user) {
        global $WCMp;
        $vendor = get_wcmp_vendor($user->ID);
        if ($vendor) {
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th>
                            <label for="View Vendor" > <?php _e('View Vendor', 'dc-woocommerce-multi-vendor'); ?></label>
                        </th>
                        <td>
                            <a class="button-primary" target="_blank" href=<?php echo $vendor->permalink; ?>>View</a>
                        </td>
                    </tr>
                    <?php $WCMp->wcmp_wp_fields->dc_generate_form_field($this->get_vendor_fields($user->ID), array('in_table' => 1)); ?>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * Validate user additional fields
     */
    function validate_user_fields(&$errors, $update, &$user) {
        global $WCMp;
        if (isset($_POST['vendor_page_slug'])) {
            if (!$update) {
                if (term_exists(sanitize_title($_POST['vendor_page_slug']), $WCMp->taxonomy->taxonomy_name)) {
                    $errors->add('vendor_slug_exists', __('Slug Already Exists', 'dc-woocommerce-multi-vendor'));
                }
            } else {
                if (is_user_wcmp_vendor($user->ID)) {
                    $vendor = get_wcmp_vendor($user->ID);
                    if (isset($vendor->term_id)) {
                        $vendor_term = get_term($vendor->term_id, $WCMp->taxonomy->taxonomy_name);
                    }
                    if (isset($_POST['vendor_page_slug']) && isset($vendor_term->slug) && $vendor_term->slug != $_POST['vendor_page_slug']) {
                        if (term_exists(sanitize_title($_POST['vendor_page_slug']), $WCMp->taxonomy->taxonomy_name)) {
                            $errors->add('vendor_slug_exists', __('Slug already exists', 'dc-woocommerce-multi-vendor'));
                        }
                    }
                }
            }
        }
    }

    /**
     * Saves additional user fields to the database
     * function save_vendor_data
     * @access private
     * @param int $user_id
     * @return void
     */
    function save_vendor_data($user_id) {
        // only saves if the current user can edit user profiles
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        $errors = new WP_Error();
        $fields = $this->get_vendor_fields($user_id);
        $vendor = get_wcmp_vendor($user_id);
        if ($vendor) {
            foreach ($fields as $fieldkey => $value) {
                $fieldvalue = filter_input(INPUT_POST, $fieldkey);
                if ($fieldvalue) {
                    if ($fieldkey == 'vendor_page_title') {
                        if (!$vendor->update_page_title(wc_clean($fieldvalue))) {
                            $errors->add('vendor_title_exists', __('Title Update Error', 'dc-woocommerce-multi-vendor'));
                        } else {
                            if(apply_filters('wcmp_update_user_display_name_with_vendor_store_name', false, $user_id)){
                                wp_update_user(array('ID' => $user_id, 'display_name' => $fieldvalue));
                            }
                        }
                    } elseif ($fieldkey == 'vendor_page_slug') {
                        if (!$vendor->update_page_slug(wc_clean($fieldvalue))) {
                            $errors->add('vendor_slug_exists', __('Slug already exists', 'dc-woocommerce-multi-vendor'));
                        }
                    } elseif ($fieldkey == 'vendor_description') {
                        update_user_meta($user_id, '_' . $fieldkey, $_POST[$fieldkey]);
                    } else {
                        update_user_meta($user_id, '_' . $fieldkey, $_POST[$fieldkey]);
                    }
                } else if (isset($_POST['vendor_commission']) && $fieldkey == 'vendor_commission') {
                    update_user_meta($user_id, '_vendor_commission', $_POST[$fieldkey]);
                } else if (!isset($_POST['vendor_hide_description']) && $fieldkey == 'vendor_hide_description') {
                    delete_user_meta($user_id, '_vendor_hide_description');
                } else if (!isset($_POST['vendor_hide_address']) && $fieldkey == 'vendor_hide_address') {
                    delete_user_meta($user_id, '_vendor_hide_address');
                } else if (!isset($_POST['vendor_hide_phone']) && $fieldkey == 'vendor_hide_phone') {
                    delete_user_meta($user_id, '_vendor_hide_phone');
                } else if (!isset($_POST['vendor_hide_email']) && $fieldkey == 'vendor_hide_email') {
                    delete_user_meta($user_id, '_vendor_hide_email');
                } else if (!isset($_POST['vendor_give_tax']) && $fieldkey == 'vendor_give_tax') {
                    delete_user_meta($user_id, '_vendor_give_tax');
                } else if (!isset($_POST['vendor_give_shipping']) && $fieldkey == 'vendor_give_shipping') {
                    delete_user_meta($user_id, '_vendor_give_shipping');
                } else if (!isset($_POST['vendor_turn_off']) && $fieldkey == 'vendor_turn_off') {
                    delete_user_meta($user_id, '_vendor_turn_off');
                } else if (!isset($_POST['vendor_is_policy_off']) && $fieldkey == 'vendor_is_policy_off') {
                    delete_user_meta($user_id, '_vendor_is_policy_off');
                }
            }
        }
    }

    /**
     * Delete vendor data on user delete
     * function delete_vendor
     * @access private
     * @param int $user_id
     * @return void
     */
    function delete_vendor($user_id) {
        global $WCMp;
        $wcmp_vendor_registration_form_id = get_user_meta($user_id, 'wcmp_vendor_registration_form_id', true);
        if ($wcmp_vendor_registration_form_id) {
            wp_delete_post($wcmp_vendor_registration_form_id);
        }
        if (is_user_wcmp_vendor($user_id)) {

            $vendor = get_wcmp_vendor($user_id);

            do_action('delete_dc_vendor', $vendor);

            if (isset($_POST['reassign_user']) && !empty($_POST['reassign_user']) && ( $_POST['delete_option'] == 'reassign' )) {
                if (is_user_wcmp_vendor(absint($_POST['reassign_user']))) {
                    if ($products = wp_list_pluck( $vendor->get_products_ids(), 'ID' )) {
                        foreach ($products as $product_id) {
                            $new_vendor = get_wcmp_vendor(absint($_POST['reassign_user']));
                            wp_set_object_terms($product_id, absint($new_vendor->term_id), $WCMp->taxonomy->taxonomy_name);
                        }
                    }
                } else {
                    wp_die(__('Select a Vendor.', 'dc-woocommerce-multi-vendor'));
                }
            }

            wp_delete_term($vendor->term_id, $WCMp->taxonomy->taxonomy_name);
            delete_user_meta($user_id, '_vendor_term_id');
        }
    }

    /**
     * created customer notification
     *
     * @access public
     * @return void
     */
    function wcmp_woocommerce_created_customer_notification() {
        if (isset($_POST['pending_vendor']) && !empty($_POST['pending_vendor'])) {
            remove_action('woocommerce_created_customer_notification', array(WC()->mailer(), 'customer_new_account'), 10, 3);
            add_action('woocommerce_created_customer_notification', array($this, 'wcmp_customer_new_account'), 10, 3);
        }
    }

    /**
     * Send mail on new vendor creation
     *
     * @access public
     * @return void
     */
    function wcmp_customer_new_account($customer_id, $new_customer_data = array(), $password_generated = false) {
        if (!$customer_id) {
            return;
        }
        $user_pass = !empty($new_customer_data['user_pass']) ? $new_customer_data['user_pass'] : '';
        $email = WC()->mailer()->emails['WC_Email_Vendor_New_Account'];
        $email->trigger($customer_id, $user_pass, $password_generated);
        $email_admin = WC()->mailer()->emails['WC_Email_Admin_New_Vendor_Account'];
        $email_admin->trigger($customer_id, $user_pass, $password_generated);
    }

    /**
     * WCMp Order available emails
     *
     * @param array $available_emails
     * @return available_emails
     */
    public function wcmp_order_emails_available($available_emails) {
        $available_emails[] = 'vendor_new_order';
        return $available_emails;
    }
    
    /**
     * WCMp set user cookies
     */
    public function set_wcmp_user_cookies() {
        $current_user_id = get_current_user_id();
        $_cookie_id = "_wcmp_user_cookie_".$current_user_id;
        if ( ! headers_sent() ) {
            $secure = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );
            if(!isset($_COOKIE[$_cookie_id])) { 
                setcookie( $_cookie_id, uniqid('wcmp_cookie'), time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure );
            }else{
                setcookie( $_cookie_id, $_COOKIE[$_cookie_id], time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure );
            }
        }
    }
    
    /**
	* avatar_override()
	*
	* Overrides an avatar with a profile image
	*
	* @param string $avatar SRC to the avatar
	* @param mixed $id_or_email 
	* @param int $size Size of the image
	* @param string $default URL to the default image
	* @param string $alt Alternative text
	**/
    public function wcmp_user_avatar_override( $avatar, $id_or_email, $size, $default, $alt, $args=array()) {
        //Get user data
        if ( is_numeric( $id_or_email ) ) {
                $user = get_user_by( 'id', ( int )$id_or_email );
        } elseif( is_object( $id_or_email ) )  {
            $comment = $id_or_email;
            if ( empty( $comment->user_id ) ) {
                    $user = get_user_by( 'id', $comment->user_id );
            } else {
                    $user = get_user_by( 'email', $comment->comment_author_email );
            }
            if ( !$user ) return $avatar;
        } elseif( is_string( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
        } else {
            return $avatar;
        }
        if ( !$user ) return $avatar;
        $classes = array(
            'avatar',
            sprintf( 'avatar-%s', esc_attr( $size ) ),
            'photo'
        );	
        if ( isset( $args[ 'class' ] ) ) {
            if ( is_array( $args['class'] ) ) {
                $classes = array_merge( $classes, $args['class'] );
            } else {
                $args[ 'class' ] = explode( ' ', $args[ 'class' ] );
                $classes = array_merge( $classes, $args[ 'class' ] );
            }
        } 
        //Get custom filter classes
        $classes = (array)apply_filters( 'wcmp_user_avatar_classes', $classes );

        //Determine if the user is WCMp vendor
        $wcmp_vendor_avatar = '';
        if(is_user_wcmp_vendor($user->ID) && apply_filters('is_wcmp_user_avatar_overridden', true, $user->ID)){
            $vendor = get_wcmp_vendor($user->ID);
            $wcmp_vendor_avatar = sprintf(
                "<img alt='%s' src='%s' class='%s' height='%d' width='%d' %s/>",
                esc_attr( $args['alt'] ),
                esc_url( $vendor->get_image('image', array($size, $size)) ),
                esc_attr(implode( ' ', $classes ) ),
                (int) $size,
                (int) $size,
                $args['extra_attr']
            );
        }

        if(!empty($wcmp_vendor_avatar)){
            return $wcmp_vendor_avatar;
        }else{
            return $avatar; 
        }
    }
    
    /**
     * Enable edit_post capability for vendor in dashboard store policies.
     *
     * @param  array  $caps    The user's capabilities.
     * @param  string $cap     Capability name.
     * @param  int    $user_id The user ID.
     * @return array  $caps    The user's capabilities, with 'edit_post' potentially added.
     */
    public function media_handler_map_meta_cap( $caps, $cap, $user_id, $args ){
        // media upload caps added for vendor policies page
        $is_policy_page = (isset($args[0]) && get_wcmp_vendor_settings( 'wcmp_vendor', 'vendor', 'general') == $args[0]) ? true : false;
        if ( 'edit_post' == $cap && is_user_wcmp_vendor($user_id) && apply_filters('wcmp_vendor_has_policy_media_handle_meta_cap', $is_policy_page) ) {
            return array('edit_post');
        }
        return $caps;
    }
    
    public function userwise_wp_link_query_args( $query ) {
        if( !is_user_wcmp_vendor( get_current_user_id() ) ) return $query;
        $query['author'] = get_current_user_id();
        return $query;
    }
    
    public function wcmp_pre_user_query_filtered( $query ) {
        if( $query->query_vars["orderby"] != 'rand' ) return $query;
        $query->query_orderby = 'ORDER by RAND()';
        return $query;
    }
    
}
