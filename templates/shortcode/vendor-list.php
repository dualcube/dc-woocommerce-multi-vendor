<?php
/**
 * The template for displaying vendor lists
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/shortcode/vendor-list.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $WCMp, $vendor_list;
?>

<?php
/**
 * Hook: wcmp_before_vendor_list.
 *
 * @hooked wcmp_vendor_list_main_wrapper - 5
 */
do_action( 'wcmp_before_vendor_list' );
?>

<?php
/**
 * Hook: wcmp_before_vendor_list_map_section.
 * 
 * @hooked wcmp_vendor_list_map_wrapper - 5
 */
do_action( 'wcmp_before_vendor_list_map_section' );
?>

<?php
/**
 * Hook: wcmp_vendor_list_map_section.
 * 
 * @hooked wcmp_vendor_list_display_map - 5
 */
do_action( 'wcmp_vendor_list_map_section' );
?>

<?php
/**
 * Hook: wcmp_after_vendor_list_map_section.
 * 
 * @hooked wcmp_vendor_list_form_wrapper - 5
 * @hooked wcmp_vendor_list_map_filters - 10
 * @hooked wcmp_vendor_list_form_wrapper_end - 15
 * @hooked wcmp_vendor_list_map_wrapper_end - 20
 */
do_action( 'wcmp_after_vendor_list_map_section' );
?>

<?php
/**
 * Hook: wcmp_before_vendor_list_vendors_section.
 *
 * @hooked wcmp_vendor_list_catalog_ordering - 5
 * @hooked wcmp_vendor_list_content_wrapper - 10
 */
do_action( 'wcmp_before_vendor_list_vendors_section' );
?>

<?php
/**
 * Hook: wcmp_vendor_list_vendors_section.
 *
 * @hooked wcmp_vendor_list_vendors_loop - 10
 */
do_action( 'wcmp_vendor_list_vendors_section' );
?>

<?php
/**
 * Hook: wcmp_after_vendor_list_vendors_section.
 *
 * @hooked wcmp_vendor_list_content_wrapper_end - 10
 * @hooked wcmp_vendor_list_pagination - 15
 */
do_action( 'wcmp_after_vendor_list_vendors_section' );
?>

<?php 
/**
 * Hook: wcmp_after_vendor_list.
 *
 * @hooked wcmp_vendor_list_main_wrapper_end - 5
 */
do_action( 'wcmp_after_vendor_list' );