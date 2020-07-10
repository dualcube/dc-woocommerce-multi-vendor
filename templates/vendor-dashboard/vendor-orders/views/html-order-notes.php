<?php
/**
 * Order notes template.
 *
 * Used by vendor-order-details.php template
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-orders/views/html-order-notes.php.
 * 
 * @author 	WC Marketplace
 * @package 	WCMp/templates/vendor dashboard/vendor orders/views
 * @version     3.4.0
 */
defined('ABSPATH') || exit;

global $WCMp;
?>
<div class="panel panel-default panel-pading pannel-outer-heading order-action">
    <div class="panel-heading d-flex">
        <?php _e('Order notes :', 'dc-woocommerce-multi-vendor'); ?>
    </div>
    <div class="panel-body">
        <?php
        if (apply_filters('is_vendor_can_view_order_notes', true, $vendor->id)) {
            $args = array(
                'order_id' => $order->get_id(),
            );

            $notes = wc_get_order_notes( $args );
            ?>
            <ul class="order_notes list-group mb-0">
                <?php
                if ($notes) {
                    foreach ($notes as $note) {
                        $note_classes   = array( 'note' );
                        $note_classes[] = $note->customer_note ? 'customer-note' : '';
                        $note_classes[] = 'system' === $note->added_by ? 'system-note' : '';
                        $note_classes   = apply_filters( 'wcmp_order_note_class', array_filter( $note_classes ), $note );
                        ?>
                        <li class="list-group-item list-group-item-action flex-column align-items-start order-notes">
                            <p class="order-note"><span><?php echo wptexturize( wp_kses_post( $note->content ) ); ?></span></p>
                            <p>
                                <abbr class="exact-date" title="<?php echo $note->date_created->date( 'y-m-d h:i:s' ); ?>"><?php printf( __( 'added on %1$s at %2$s', 'woocommerce' ), $note->date_created->date_i18n( wc_date_format() ), $note->date_created->date_i18n( wc_time_format() ) ); ?></abbr>
                                <?php
                                if ( 'system' !== $note->added_by ) :
                                        /* translators: %s: note author */
                                        printf( ' ' . __( 'by %s', 'woocommerce' ), $note->added_by );
                                endif;
                                ?>
                            </p>
                        </li>
                        <?php
                    }
                }else{
                    echo '<li class="list-group-item list-group-item-action flex-column align-items-start order-notes">' . __( 'There are no notes yet.', 'woocommerce' ) . '</li>';
                }
                ?>
                <li class="list-group-item list-group-item-action flex-column align-items-start add_note">
                    <?php if (apply_filters('is_vendor_can_add_order_notes', true, $vendor->id)) : ?>
                    <form method="post" name="add_comment">
                    <?php wp_nonce_field('dc-vendor-add-order-comment', 'vendor_add_order_nonce'); ?> 
                        <h3><?php _e( 'Add note', 'woocommerce' ); ?> <span class="img_tip" data-desc="<?php echo __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce' ); ?>"></span></h3>
                        <div class="form-group">
                            <textarea placeholder="<?php _e('Enter text ...', 'dc-woocommerce-multi-vendor'); ?>" required class="form-control" name="comment_text"></textarea>
                        </div>
                        <input type="hidden" name="order_id" value="<?php echo $order->get_id(); ?>">
                        <select name="note_type" id="order_note_type" class="form-control inline-input">
                                <option value=""><?php _e( 'Private note', 'woocommerce' ); ?></option>
                                <option value="customer"><?php _e( 'Note to customer', 'woocommerce' ); ?></option>
                        </select>
                        <input class="btn btn-default wcmp-add-order-note" type="submit" name="wcmp_submit_comment" value="<?php _e('Submit', 'dc-woocommerce-multi-vendor'); ?>">
                    </form>  
                    <?php endif; ?>  
                </li>
                <li class="list-group-item list-group-item-action flex-column align-items-start">
                    <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#shipping_tracking_wrap"><?php _e('Tracking number', 'dc-woocommerce-multi-vendor'); ?></button>
                </li>
                <li id="shipping_tracking_wrap" class="shipping_tracking collapse" style="padding:  10px 15px;;">
                    <form method="post">
                        <div class="form-group">
                            <label for="tracking_url"><?php _e('Enter Tracking Url', 'dc-woocommerce-multi-vendor'); ?> *</label>
                            <input type="url" class="form-control" id="email" name="tracking_url" required="">
                        </div>
                        <div class="form-group">
                            <label for="tracking_id"><?php _e('Enter Tracking ID', 'dc-woocommerce-multi-vendor'); ?> *</label>
                            <input type="text" class="form-control" id="pwd" name="tracking_id" required="">
                        </div>
                        <div class="form-group">
                            <input type="hidden" name="order_id" id="wcmp-marke-ship-order-id" value="<?php echo $order->get_id(); ?>" />
                            <button type="submit" class="btn btn-primary" name="wcmp-submit-mark-as-ship"><?php _e('Submit', 'dc-woocommerce-multi-vendor'); ?></button>
                        </div>
                    </form>
                </li>
            </ul>
    <?php } ?>
    </div>
</div>