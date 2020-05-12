<?php
/**
 * WCMp Hooks Function
 *
 * Action/filter hooks used for WCMp functions/templates.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vendor List.
 *
 * @see wcmp_vendor_list_main_wrapper()
 * @see wcmp_vendor_list_main_wrapper_end()
 * @see wcmp_vendor_list_map_wrapper()
 * @see wcmp_vendor_list_map_wrapper_end()
 * @see wcmp_vendor_list_form_wrapper()
 * @see wcmp_vendor_list_form_wrapper_end()
 * @see wcmp_vendor_list_map_filters()
 * @see wcmp_vendor_list_display_map()
 * @see wcmp_vendor_list_catalog_ordering()
 * @see wcmp_vendor_list_content_wrapper()
 * @see wcmp_vendor_list_content_wrapper_end()
 * @see wcmp_vendor_list_pagination()
 * @see wcmp_vendor_list_vendors_loop()
 */
add_action( 'wcmp_before_vendor_list', 'wcmp_vendor_list_main_wrapper', 5 );
add_action( 'wcmp_after_vendor_list', 'wcmp_vendor_list_main_wrapper_end', 20 );
add_action( 'wcmp_before_vendor_list_map_section', 'wcmp_vendor_list_map_wrapper', 5 );
add_action( 'wcmp_after_vendor_list_map_section', 'wcmp_vendor_list_map_wrapper_end', 20 );
add_action( 'wcmp_after_vendor_list_map_section', 'wcmp_vendor_list_form_wrapper', 5 );
add_action( 'wcmp_after_vendor_list_map_section', 'wcmp_vendor_list_form_wrapper_end', 15 );
add_action( 'wcmp_after_vendor_list_map_section', 'wcmp_vendor_list_map_filters', 10 );

add_action( 'wcmp_vendor_list_map_section', 'wcmp_vendor_list_display_map_setting', 4 );
add_action( 'wcmp_vendor_list_map_section', 'wcmp_vendor_list_display_map', 5 );
add_action( 'wcmp_before_vendor_list_vendors_section', 'wcmp_vendor_list_catalog_ordering', 5 );
add_action( 'wcmp_before_vendor_list_vendors_section', 'wcmp_vendor_list_content_wrapper', 10 );
add_action( 'wcmp_after_vendor_list_vendors_section', 'wcmp_vendor_list_content_wrapper_end', 10 );
add_action( 'wcmp_after_vendor_list_vendors_section', 'wcmp_vendor_list_pagination', 15 );
add_action( 'wcmp_vendor_list_vendors_section', 'wcmp_vendor_list_vendors_loop', 10 );

if ( ! function_exists( 'wcmp_vendor_list_main_wrapper' ) ) {

	/**
	 * Get vendor list main wrapper template.
	 */
	function wcmp_vendor_list_main_wrapper() {
        echo apply_filters( 'wcmp_vendor_list_main_wrapper_start', '<div id="wcmp-store-conatiner">' );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_main_wrapper_end' ) ) {

	/**
	 * Get vendor list main wrapper end template.
	 */
	function wcmp_vendor_list_main_wrapper_end() {
        echo apply_filters( 'wcmp_vendor_list_main_wrapper_end', '</div>' );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_map_wrapper' ) ) {

	/**
	 * Get vendor list map wrapper template.
	 */
	function wcmp_vendor_list_map_wrapper() {
        echo apply_filters( 'wcmp_vendor_list_map_wrapper_start', '<div class="wcmp-store-locator-wrap">' );
        
        
        
        
	}
}

if ( ! function_exists( 'wcmp_vendor_list_form_wrapper' ) ) {

	/**
	 * Get vendor list form wrapper template.
	 */
	function wcmp_vendor_list_form_wrapper() {
        echo apply_filters( 'wcmp_vendor_list_form_wrapper_start', '<form name="vendor_list_form" method="post">' );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_map_filters' ) ) {

	/**
	 * Get vendor list map filters template.
	 */
	function wcmp_vendor_list_map_filters() {
        /*global $WCMp;
        if ( !apply_filters( 'wcmp_vendor_list_enable_store_locator_map', true ) ) return;
		$WCMp->template->get_template('shortcode/vendor-list/map-filters.php');*/
	}
}

if ( ! function_exists( 'wcmp_vendor_list_form_wrapper_end' ) ) {

	/**
	 * Get vendor list form wrapper end template.
	 */
	function wcmp_vendor_list_form_wrapper_end() {
        echo apply_filters( 'wcmp_vendor_list_form_wrapper_end', '</form>' );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_map_wrapper_end' ) ) {

	/**
	 * Get vendor list map wrapper end template.
	 */
	function wcmp_vendor_list_map_wrapper_end() {
        echo apply_filters( 'wcmp_vendor_list_map_wrapper_end', '</div>' );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_display_map' ) ) {

	/**
	 * Get vendor list map template.
	 */
	function wcmp_vendor_list_display_map() {
        
		global $WCMp;

        if ( !apply_filters( 'wcmp_vendor_list_enable_store_locator_map', true ) ) return;
		$WCMp->template->get_template('shortcode/vendor-list/map.php');
	}
}

if( !function_exists('wcmp_vendor_list_display_map_setting') ){
	function wcmp_vendor_list_display_map_setting(){
		global $WCMp;

        echo apply_filters( 'wcmp_vendor_list_form_wrapper_start', '<form name="vendor_list_form" method="post">' );

        if ( !apply_filters( 'wcmp_vendor_list_enable_store_locator_map', true ) ) return;
		$WCMp->template->get_template('shortcode/vendor-list/map-filters.php');
        
		echo apply_filters( 'wcmp_vendor_list_form_wrapper_end', '</form>' );
	}
}





if ( ! function_exists( 'wcmp_vendor_list_catalog_ordering' ) ) {

	/**
	 * Get vendor list catalog ordering template.
	 */
	function wcmp_vendor_list_catalog_ordering() {
        global $WCMp;
        if ( !apply_filters( 'wcmp_vendor_list_enable_store_locator_map', true ) ) return;
		$WCMp->template->get_template('shortcode/vendor-list/catalog-ordering.php');
	}
}

if ( ! function_exists( 'wcmp_vendor_list_paging_info' ) ) {

	/**
	 * Get vendor list paging information.
	 */
	function wcmp_vendor_list_paging_info() {
        global $WCMp, $vendor_list;
        extract($vendor_list);
        if ( $vendor_total <= $per_page || -1 === $per_page ) {
            /* translators: %d: total results */
            printf( _n( 'Viewing the single vendor', 'Viewing all %d vendors', $vendor_total, 'dc-woocommerce-multi-vendor' ), $vendor_total );
        } else {
            $first = ( $per_page * $current ) - $per_page + 1;
            if(!apply_filters('wcmp_vendor_list_ignore_pagination', false)) {
                $last  = min( $vendor_total, $per_page * $current );
            }else{
                $last  = $vendor_total;
            }
            /* translators: 1: first result 2: last result 3: total results */
            printf( _nx( 'Viewing the single vendor', 'Viewing %1$d&ndash;%2$d of %3$d vendors', $vendor_total, 'with first and last result', 'dc-woocommerce-multi-vendor' ), $first, $last, $vendor_total );
        }
	}
}

if ( ! function_exists( 'wcmp_vendor_list_order_sort' ) ) {

	/**
	 * Get vendor list order sort template.
	 */
	function wcmp_vendor_list_order_sort() {
        global $WCMp;
        $WCMp->template->get_template('shortcode/vendor-list/order-sort.php');
	}
}

if ( ! function_exists( 'wcmp_vendor_list_content_wrapper' ) ) {

	/**
	 * Get vendor list content wrapper template.
	 */
	function wcmp_vendor_list_content_wrapper() {
        echo apply_filters( 'wcmp_vendor_list_content_wrapper_start', '<div class="wcmp-store-list-wrap list-3">' );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_content_wrapper_end' ) ) {

	/**
	 * Get vendor list content wrapper end template.
	 */
	function wcmp_vendor_list_content_wrapper_end() {
        echo apply_filters( 'wcmp_vendor_list_content_wrapper_end', '</div>' );
        echo '<div class="load_more"></div>';
	}
}

if ( ! function_exists( 'wcmp_vendor_list_vendors_loop' ) ) {

	/**
	 * Get vendor list vendor loop section.
	 */
	function wcmp_vendor_list_vendors_loop() {
		global $WCMp, $vendor_list;
		if ( $vendor_list['vendors'] ) {
            foreach ( $vendor_list['vendors'] as $vendor_id ) {
        		$WCMp->template->get_template(
					'shortcode/vendor-list/content-vendor.php', 
					array( 'vendor_id' => $vendor_id )
				);
			}
		} else {
			wcmp_no_vendors_found_data();
		}
	}
}

if ( ! function_exists( 'wcmp_no_vendors_found_data' ) ) {

	/**
	 * No vendors found section.
	 */
	function wcmp_no_vendors_found_data() {
		echo apply_filters( 'wcmp_no_vendors_found_data', __('No vendor found!', 'dc-woocommerce-multi-vendor') );
	}
}

if ( ! function_exists( 'wcmp_vendor_list_pagination' ) ) {

	/**
	 * Get vendor list pagination templates.
	 */
	function wcmp_vendor_list_pagination() {
		global $WCMp, $vendor_list;
		extract( $vendor_list );

		if(!apply_filters('wcmp_vendor_list_ignore_pagination', false)) : ?>
			<div class="wcmp-pagination">
				<?php
					echo paginate_links( apply_filters( 'wcmp_vendor_list_pagination_args', array( 
							'base'         => $base,
							'format'       => $format,
							'add_args'     => false,
							'current'      => max( 1, $current ),
							'total'        => $total,
							'prev_text'    => 'Prev',
							'next_text'    => 'Next',
							'type'         => 'list',
							'end_size'     => 3,
							'mid_size'     => 3,
					) ) );
			?>
			</div>
		<?php endif; 
	}
}