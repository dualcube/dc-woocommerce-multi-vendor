<?php

if (!defined('ABSPATH')) 
    exit; // Exit if accessed directly

if (!class_exists('WC_Email_Vendor_Review')) :

    /**
     * Verified Alert Email
     *
     * An email sent to the vendors when vendor review by customer..
     *
     * @class       WC_Email_Vendor_Review
     * @version     3.5.0
     * @package     WooCommerce/Classes/Emails
     * @author      WooThemes
     * @extends     WC_Email
     */
    class WC_Email_Vendor_Review extends WC_Email {
        public $vendor;
        public $customer_name;
        public $review;
        public $rating;
        /**
         * Constructor
         */
        function __construct() {
            global $WCMp;
            $this->id = 'review_vendor_alert';
            $this->title = __('Review Vendor', 'dc-woocommerce-multi-vendor');
            $this->description = __('Review Vendor notification emails are sent when vendor get review from customer.', 'dc-woocommerce-multi-vendor');

            $this->template_html = 'emails/vendor-review.php';
            $this->template_plain = 'emails/plain/vendor-review.php';
            $this->template_base = $WCMp->plugin_path . 'templates/';

            // Call parent constructor
            parent::__construct();
        }


        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger( $vendor , $rating , $review , $customer_name ) {
            if($vendor) {
                $this->vendor = $vendor;
                $this->find[] = '{vendor_name}';
                $this->vendor_name = $vendor->page_title;
                $this->replace[] = $this->vendor_name;

                $this->find[] = '{customer_name}';
                $this->customer_name = $customer_name;
                $this->replace[] = $this->customer_name;

                $this->review = $review;

                $this->find[] = '{rating}';
                $this->rating = $rating;
                $this->replace[] = $this->rating; 

                $this->recipient = get_option('admin_email');
                if( apply_filters( 'wcmp_vendor_review_email_goes_to_vendor', true, $this->vendor ) )
                $this->recipient .= ',' . sanitize_email($vendor->user_data->user_email);
            
                if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                    return;
                }
                
                $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            }
        }

        /**
         * Get email subject.
         *
         * @since  3.1.0
         * @return string
         */
        public function get_default_subject() {
            return apply_filters('wcmp_vendor_review_vendor_email_subject',__('{customer_name} left a review for {vendor_name} shop ', 'dc-woocommerce-multi-vendor'), $this->object);
        } 

        /**
         * Get email heading.
         *
         * @since  3.1.0
         * @return string
         */
        public function get_default_heading() {
            return apply_filters('wcmp_vendor_new_review_email_heading', __('{vendor_name} shop got a new {rating}-star review', 'dc-woocommerce-multi-vendor'), $this->object);
        }

        /**
         * get_content_html function.
         *
         * @access public
         * @return string
         */
        function get_content_html() {
            return wc_get_template_html($this->template_html, array(
                'email_heading' => $this->get_heading(),
                'vendor' => $this->vendor,
                'customer_name' => $this->customer_name,
                'review' => $this->review,
                'rating' => $this->rating,
                'sent_to_admin' => false,
                'plain_text' => false,
                'email'         => $this,
                    ), '', $this->template_base);
        }

        /**
         * get_content_plain function.
         *
         * @access public
         * @return string
         */
        function get_content_plain() {
            return wc_get_template_html($this->template_plain, array(
                'email_heading' => $this->get_heading(),
                'vendor' => $this->vendor,
                'customer_name' => $this->customer_name,
                'review' => $this->review,
                'rating' => $this->rating,
                'sent_to_admin' => false,
                'plain_text' => true,
                'email'         => $this,
                    ), '', $this->template_base);
        }

        /**
         * Initialise Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields() {
            global $WCMp;
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'dc-woocommerce-multi-vendor'),
                    'type' => 'checkbox',
                    'label' => __('Enable this email notification.', 'dc-woocommerce-multi-vendor'),
                    'default' => 'yes'
                ),
                'subject' => array(
                    'title' => __('Subject', 'dc-woocommerce-multi-vendor'),
                    'type' => 'text',
                    'description' => sprintf(__('This controls the email subject line. Leave it blank to use the default subject: <code>%s</code>.', 'dc-woocommerce-multi-vendor'), $this->get_default_subject()),
                    'placeholder' => '',
                    'default' => ''
                ),
                'heading' => array(
                    'title' => __('Email Heading', 'dc-woocommerce-multi-vendor'),
                    'type' => 'text',
                    'description' => sprintf(__('This controls the main heading contained within the email notification. Leave it blank to use the default heading: <code>%s</code>.', 'dc-woocommerce-multi-vendor'), $this->get_default_heading()),
                    'placeholder' => '',
                    'default' => ''
                ),
                'email_type' => array(
                    'title' => __('Email Type', 'dc-woocommerce-multi-vendor'),
                    'type' => 'select',
                    'description' => __('Choose which format of email to be sent.', 'dc-woocommerce-multi-vendor'),
                    'default' => 'html',
                    'class' => 'email_type',
                    'options' => array(
                        'plain' => __('Plain Text', 'dc-woocommerce-multi-vendor'),
                        'html' => __('HTML', 'dc-woocommerce-multi-vendor'),
                        'multipart' => __('Multipart', 'dc-woocommerce-multi-vendor'),
                    )
                )
            );
        }
    }

endif;