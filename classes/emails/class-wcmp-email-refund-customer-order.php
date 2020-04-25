<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('WC_Email_Refund_Customer_order')) :

    /**
     * Customer New Account
     *
     * An email sent to the customer when they create an account.
     *
     * @class 		WC_Email_Refund_Customer_order
     * @version		2.0.0
     * @package		WooCommerce/Classes/Emails
     * @author 		WooThemes
     * @extends 	WC_Email
     */
    class WC_Email_Refund_Customer_order extends WC_Email {

        var $user_login;
        var $user_email;
        var $user_pass;

        /**
         * Constructor
         *
         * @access public
         * @return void
         */
        function __construct() {
            global $WCMp;
            $this->id = 'refund_customer_order';
            $this->title = __('Refund Customer Order', 'dc-woocommerce-multi-vendor');
            $this->description = __('Customer refund request.', 'dc-woocommerce-multi-vendor');

            $this->template_html = 'emails/customer-refund-order.php';
            $this->template_plain = 'emails/plain/customer-refund-order.php';

            $this->template_base = $WCMp->plugin_path . 'templates/';
            // Call parent constuctor
            parent::__construct();
        }

        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger($user_id , $order_id , $refund_details = array() ) {

            if ($user_id) {
                $this->object = new WP_User($user_id);

                $this->user_login = stripslashes($this->object->user_login);
                $this->user_email = stripslashes($this->object->user_email);
                $this->recipient = $this->user_email;
                $this->id = $user_id;
                $this->refund_details = $refund_details;
                $this->order = $order_id;
               
            }

            if (!$this->is_enabled() || !$this->get_recipient())
                return;

            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }
        
        /**
         * Get email subject.
         *
         * @access  public
         * @return string
         */
        public function get_default_subject() {
            return apply_filters('wcmp_approved_vendor_new_account_email_subject', __('Your account on {site_title}', 'dc-woocommerce-multi-vendor'), $this->object);
        }

        /**
         * Get email heading.
         *
         * @access  public
         * @return string
         */
        public function get_default_heading() {
            return apply_filters('wcmp_approved_vendor_new_account_email_heading', __(' Refund request from customer ', 'dc-woocommerce-multi-vendor'), $this->object);
        }

        /**
         * get_content_html function.
         *
         * @access public
         * @return string
         */
        function get_content_html() {
            ob_start();
            wc_get_template($this->template_html, array(
                'email_heading' => $this->get_heading(),
                'user_login' => $this->user_login,
                'order' => $this->order,
                'id' => $this->id,
                'refund_details' => $this->refund_details,
                'blogname' => $this->get_blogname(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email'         => $this,
                    ), 'dc-product-vendor/', $this->template_base);
            return ob_get_clean();
        }

        /**
         * get_content_plain function.
         *
         * @access public
         * @return string
         */
        function get_content_plain() {
            ob_start();
            wc_get_template($this->template_plain, array(
                'email_heading' => $this->get_heading(),
                'user_login' => $this->user_login,
                'user_pass' => $this->user_pass,
                'blogname' => $this->get_blogname(),
                'password_generated' => $this->password_generated,
                'sent_to_admin' => false,
                'plain_text' => true,
                'email'         => $this,
                    ), 'dc-product-vendor/', $this->template_base);
            return ob_get_clean();
        }

    }

endif;
