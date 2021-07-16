<?php
/**
 * The template for displaying Seller Review form 
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/wcmp-vendor-review-form.php
 *
 * @author      WC Marketplace
 * @package     WCMp/Templates
 * @version     3.7
 */
global $WCMp, $wpdb;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$vendor_term_id = get_user_meta($vendor_id, '_vendor_term_id', true);
$vendor = get_wcmp_vendor_by_term($vendor_term_id);
$shop_name = $vendor->page_title;
$vendor_id = $vendor->id;
$count = $vendor->get_review_count();
$is_enable = wcmp_seller_review_enable($vendor_term_id);
$current_user = wp_get_current_user();
$reviews_lists = $vendor->get_reviews_and_rating(0);

?>
<div class="wocommerce" >
    <div id="reviews" >
        <div id="wcmp_vendor_reviews">
            <?php if (isset($is_enable) && $is_enable) { ?>
                <div id="review_form_wrapper">
                    <div id="review_form">
                        <div id="respond" class="comment-respond">
                            <?php if ($vendor->id != get_current_vendor_id()) : ?>
                                <h3 id="reply-title" class="comment-reply-title"><?php
                                    if ($count == 0) {
                                        echo sprintf(__('Be the first to review “%s”', 'dc-woocommerce-multi-vendor'), $shop_name);
                                    } else {
                                        echo sprintf(__('Add a review to “%s”', 'dc-woocommerce-multi-vendor'), $shop_name);
                                    }
                                    ?></h3>                
                                <form action="" method="post" id="commentform" class="comment-form" novalidate="">
                                    <p id="wcmp_seller_review_rating"></p>
                                    <p class="comment-form-rating"><label for="rating"><?php esc_html_e('Your Rating', 'dc-woocommerce-multi-vendor'); ?></label>                  
                                        <select name="rating" id="rating">
                                            <option value=""><?php esc_html_e('Rate...', 'dc-woocommerce-multi-vendor'); ?></option>
                                            <option value="5"><?php esc_html_e('Perfect', 'dc-woocommerce-multi-vendor'); ?></option>
                                            <option value="4"><?php esc_html_e('Good', 'dc-woocommerce-multi-vendor'); ?></option>
                                            <option value="3"><?php esc_html_e('Average', 'dc-woocommerce-multi-vendor'); ?></option>
                                            <option value="2"><?php esc_html_e('Not that bad', 'dc-woocommerce-multi-vendor'); ?></option>
                                            <option value="1"><?php esc_html_e('Very Poor', 'dc-woocommerce-multi-vendor'); ?></option>
                                        </select></p>
                                    <p class="comment-form-comment">
                                        <label for="comment"><?php esc_html_e('Your Review', 'dc-woocommerce-multi-vendor'); ?> </label>
                                        <textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>
                                    </p>                    
                                    <p class="form-submit">
                                        <input id="wcmp_vendor_for_rating" name="wcmp_vendor_for_rating" type="hidden" value="<?php echo esc_attr($vendor_id); ?>"  >
                                        <input id="author" name="author" type="hidden" value="<?php echo esc_attr($current_user->display_name); ?>" size="30" aria-required="true">                  
                                        <input id="email" name="email" type="hidden" value="<?php echo esc_attr($current_user->user_email); ?>" size="30" aria-required="true">
                                        <input name="submit" type="button" id="submit" class="submit" value="<?php esc_attr_e('Submit', 'dc-woocommerce-multi-vendor') ?>">

                                    </p>                
                                </form>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            <div id="comments">
                <?php
                if ($count > 0) {
                    $start = 0;
                    $posts_per_page = get_option('posts_per_page');
                    $total_pages = ceil($count / $posts_per_page);
                    ?>
                    <h2><?php printf(_n('%s review for %s', '%s reviews for %s', $count, 'dc-woocommerce-multi-vendor'), $count, $shop_name); ?>    </h2>
                    <form id="vendor_review_rating_pagi_form" >
                        <input type="hidden" name="pageno" id="wcmp_review_rating_pageno" value="1" >
                        <input type="hidden" name="postperpage" id="wcmp_review_rating_postperpage" value="<?php echo esc_attr($posts_per_page); ?>" >
                        <input type="hidden" name="totalpage" id="wcmp_review_rating_totalpage" value="<?php echo esc_attr($total_pages); ?>" >
                        <input type="hidden" name="totalreview" id="wcmp_review_rating_totalreview" value="<?php echo esc_attr($count); ?>" >   
                        <input type="hidden" name="term_id" id="wcmp_review_rating_term_id" value = "<?php echo esc_attr($vendor_term_id); ?>">
                    </form>
                    <?php
                    if (isset($reviews_lists) && count($reviews_lists) > 0) {
                        echo '<ol class="commentlist vendor_comment_list">';
                        $WCMp->template->get_template('review/wcmp-vendor-review.php', array('reviews_lists' => $reviews_lists, 'vendor_term_id' => $vendor_term_id));
                        echo '</ol>';
                        if ($total_pages > 1) {
                            echo '<div class="wcmp_review_loader"><img src="' . $WCMp->plugin_url . 'assets/images/ajax-loader.gif" alt="ajax-loader" /></div>';
                            echo '<input name="loadmore" type="button" id="wcmp_review_load_more" class="submit wcmp_load_more" style="float:right;" value="' . esc_attr('Load More', 'dc-woocommerce-multi-vendor') . '">';
                        }
                    }
                } elseif ($count == 0) {
                    ?>
                    <p class="woocommerce-noreviews"><?php esc_html_e('There are no reviews yet.', 'dc-woocommerce-multi-vendor'); ?> </p>
                <?php } ?>
            </div>  
            <div class="clear"></div>

        </div>
    </div>
</div>
