<?php
/**
 * Vendor Review Comments Template
 *
 * Closing li is left out on purpose!.
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/review/review.php.
 *
 * HOWEVER, on occasion WC Marketplace will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * 
 * @author  WC Marketplace
 * @package dc-woocommerce-multi-vendor/Templates
 * @version 3.3.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $WCMp;
$rating   = intval( get_comment_meta( $comment->comment_ID, 'vendor_rating', true ) ) ? intval( get_comment_meta( $comment->comment_ID, 'vendor_rating', true ) ) : intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
$verified = wcmp_review_is_from_verified_owner( $comment, $vendor_term_id );
$vendor = get_wcmp_vendor_by_term($vendor_term_id);
$args = array(
    'status' => 'approve',
    'type' => 'wcmp_vendor_rating',
    'parent' => $comment->comment_ID,
    'meta_key' => 'vendor_rating_id',
    'meta_value' => $vendor->id,
);
$has_reply_comments = get_comments($args);
$multiple_rating = get_comment_meta( $comment->comment_ID, 'vendor_multi_rating', true ) ? unserialize(get_comment_meta( $comment->comment_ID, 'vendor_multi_rating', true )) : '';
?>
<li itemprop="review" itemscope itemtype="http://schema.org/Review" <?php comment_class(); ?> id="li-comment-<?php echo $comment->comment_ID; ?>">

	<div id="comment-<?php echo $comment->comment_ID; ?>" class="comment_container">
		<?php echo '<img width="60" height="60" class="avatar avatar-60 photo" srcset="" src="'.get_avatar_url ($comment->comment_author_email ).'" alt="">'; ?>

		<div class="comment-text">
			<?php if ( $rating && get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) : ?>
				<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo sprintf( __( 'Rated %d out of 5', 'dc-woocommerce-multi-vendor' ), $rating ) ?>">
					<span style="width:<?php echo ( $rating / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php echo $rating; ?></strong> <?php _e( 'out of 5', 'dc-woocommerce-multi-vendor' ); ?></span>
				</div>
			<?php endif; ?>
			<?php do_action( 'wcmp_vendor_review_before_comment_meta', $comment ); ?>
			<?php if ( $comment->comment_approved == '0' ) : ?>
				<p class="meta"><em><?php _e( 'Your comment is awaiting approval', 'dc-woocommerce-multi-vendor' ); ?></em></p>
			<?php else : ?>
				<p class="meta">
					<strong itemprop="author"><?php comment_author($comment->comment_ID); ?></strong> <?php

						if ( get_option( 'woocommerce_review_rating_verification_label' ) === 'yes' )
							if ( $verified )
								echo '<em class="verified">(' . apply_filters('wcmp_varified_buyer_text_filter',__( 'verified buyer', 'dc-woocommerce-multi-vendor' )) . ')</em> ';

					?>&ndash; <time itemprop="datePublished" datetime="<?php echo get_comment_date( 'c',$comment->comment_ID ); ?>"><?php echo get_comment_date( wc_date_format(), $comment->comment_ID ); ?></time>
				</p>

			<?php endif; ?>

			<?php do_action( 'wcmp_vendor_review_before_comment_text', $comment ); ?>

			<div itemprop="description" class="description"><?php echo $comment->comment_content; ?> <div style="height:10px; width:100%">&nbsp;</div></div>

			<?php do_action( 'wcmp_vendor_review_after_comment_text', $comment ); ?>

		</div>
	</div>
	<?php 
	if ($multiple_rating) {
		foreach ($multiple_rating as $rate => $rate_text) { 
			?>
			<div class="rating_box" style="display:flex;">
				<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo sprintf( __( 'Rated %d out of 5', 'dc-woocommerce-multi-vendor' ), $rate ) ?>">
					<span style="width:<?php echo ( $rate / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php echo $rate; ?></strong> <?php _e( 'out of 5', 'dc-woocommerce-multi-vendor' ); ?></span>
				</div>&nbsp
				<span class="rating_text"><?php echo esc_html($rate) . '.'. 0 . ' '. esc_html($rate_text); ?></span>
			</div>
			<?php 
		}
	}
	?>
    <?php if($has_reply_comments) : ?>
    <ul class="children">
        <?php foreach ($has_reply_comments as $comment ) { 
            $rating   = intval( get_comment_meta( $comment->comment_ID, 'vendor_rating', true ) );
            $verified = wcmp_review_is_from_verified_owner( $comment, $vendor_term_id ); ?>
        <li <?php comment_class(); ?> id="li-comment-<?php echo $comment->comment_ID; ?>">
            <div id="comment-<?php echo $comment->comment_ID; ?>" class="comment_container">
		<?php echo '<img width="60" height="60" class="avatar avatar-60 photo" srcset="" src="'.get_avatar_url ($comment->comment_author_email ).'" alt="">'; ?>

		<div class="comment-text">
			<?php if ( $rating && get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) : ?>
				<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo sprintf( __( 'Rated %d out of 5', 'dc-woocommerce-multi-vendor' ), $rating ) ?>">
					<span style="width:<?php echo ( $rating / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php echo $rating; ?></strong> <?php _e( 'out of 5', 'dc-woocommerce-multi-vendor' ); ?></span>
				</div>
			<?php endif; ?>
			<?php do_action( 'wcmp_vendor_review_before_comment_meta', $comment ); ?>
			<?php if ( $comment->comment_approved == '0' ) : ?>
				<p class="meta"><em><?php _e( 'Your comment is awaiting approval', 'dc-woocommerce-multi-vendor' ); ?></em></p>
			<?php else : ?>
				<p class="meta">
					<strong itemprop="author"><?php comment_author($comment->comment_ID); ?></strong> <?php

						if ( get_option( 'woocommerce_review_rating_verification_label' ) === 'yes' )
							if ( $verified )
								echo '<em class="verified">(' . apply_filters('wcmp_varified_buyer_text_filter',__( 'verified buyer', 'dc-woocommerce-multi-vendor' )) . ')</em> ';

					?>&ndash; <time itemprop="datePublished" datetime="<?php echo get_comment_date( 'c',$comment->comment_ID ); ?>"><?php echo get_comment_date( wc_date_format(), $comment->comment_ID ); ?></time>
				</p>

			<?php endif; ?>

			<?php do_action( 'wcmp_vendor_review_before_comment_text', $comment ); ?>

			<div itemprop="description" class="description"><?php echo $comment->comment_content; ?> <div style="height:10px; width:100%">&nbsp;</div></div>

			<?php do_action( 'wcmp_vendor_review_after_comment_text', $comment ); ?>

		</div>
            </div>
        </li>
        <?php } ?>
    </ul>
    <?php endif; ?>
</li>