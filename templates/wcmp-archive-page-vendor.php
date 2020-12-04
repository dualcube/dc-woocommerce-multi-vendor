<?php
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: wcmp_before_main_content.
 *
 */

do_action( 'wcmp_before_main_content' );

global $WCMp;

?>
<header class="woocommerce-products-header">
	<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
		<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
	<?php endif; ?>

	<?php
	/**
	 * Hook: wcmp_archive_description.
	 *
	 */
	do_action( 'wcmp_archive_description' );
	?>
</header>
<?php

/**
 * Hook: wcmp_store_tab_contents.
 *
 * Output wcmp store widget
 */

do_action( 'wcmp_store_tab_widget_contents' );


/**
 * Hook: wcmp_after_main_content.
 *
 */
do_action( 'wcmp_after_main_content' );

/**
 * Hook: wcmp_sidebar.
 *
 */
do_action( 'wcmp_sidebar' );

get_footer( 'shop' );