<?php
/**
 * WCMp edit product template
 *
 * Used by WCMp_Products_Edit_Product->output()
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/edit-product.php.
 *
 * HOWEVER, on occasion WCMp will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 		WC Marketplace
 * @package 	WCMp/templates/vendor dashboard/product manager
 * @version     3.3.0
 */
defined( 'ABSPATH' ) || exit;

global $WCMp;
?> 
<div class="col-md-12 add-product-wrapper">
    <?php do_action( 'before_wcmp_add_product_form' ); ?>
    <form id="wcmp-edit-product-form" class="woocommerce form-horizontal" method="post">
        <?php do_action( 'wcmp_add_product_form_start' ); ?>
        <!-- Top product highlight -->
        <?php
        $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-highlights.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post, 'is_update' => $is_update ) );
        ?>
        <!-- End of Top product highlight -->
        <div class="product-primary-info custom-panel"> 
            <div class="right-primary-info"> 
                <div class="form-group-wrapper">
                    <div class="form-group product-short-description">
                        <label class="control-label col-md-12 pt-0" for="product_short_description"><?php esc_html_e( 'Product short description', 'woocommerce' ); ?></label>
                        <div class="col-md-12">
                            <?php
                            $settings = array(
                                'textarea_name' => 'product_excerpt',
                                'textarea_rows' => get_option('default_post_edit_rows', 10),
                                'quicktags'     => array( 'buttons' => 'em,strong,link' ),
                                'tinymce'       => array(
                                    'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                                    'theme_advanced_buttons2' => '',
                                ),
                                'editor_css'    => '<style>#wp-product_excerpt-editor-container .wp-editor-area{height:100px; width:100%;}</style>',
                            );
                            if( !apply_filters( 'wcmp_vendor_product_excerpt_richedit', true ) ) {
                                $settings['tinymce'] = $settings['quicktags'] = $settings['media_buttons'] = false;
                            }
                            wp_editor( htmlspecialchars_decode( $product_object->get_short_description( 'edit' ) ), 'product_excerpt', $settings );
                            ?>  
                        </div>
                    </div>
                    
                    <div class="form-group product-description">
                        <label class="control-label col-md-12" for="product_description"><?php esc_attr_e( 'Product description', 'woocommerce' ); ?></label>
                        <div class="col-md-12">
                            <?php
                            $settings = array(
                                'textarea_name' => 'product_description',
                                'textarea_rows' => get_option('default_post_edit_rows', 10),
                                'quicktags'     => array( 'buttons' => 'em,strong,link' ),
                                'tinymce'       => array(
                                    'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                                    'theme_advanced_buttons2' => '',
                                ),
                                'editor_css'    => '<style>#wp-product_description-editor-container .wp-editor-area{height:175px; width:100%;}</style>',
                            );
                            if( !apply_filters( 'wcmp_vendor_product_description_richedit', true ) ) {
                                $settings['tinymce'] = $settings['quicktags'] = $settings['media_buttons'] = false;
                            }
                            wp_editor( $product_object->get_description( 'edit' ), 'product_description', $settings );
                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="left-primary-info">
                <div class="product-gallery-wrapper">
                    <div class="featured-img upload_image"><?php $featured_img = $product_object->get_image_id( 'edit' ) ? $product_object->get_image_id( 'edit' ) : ''; ?>
                        <a href="#" class="upload_image_button tips <?php echo $featured_img ? 'remove' : ''; ?>" <?php echo current_user_can( 'upload_files' ) ? '' : 'data-nocaps="true" '; ?>data-title="<?php esc_attr_e( 'Product image', 'woocommerce' ); ?>" data-button="<?php esc_attr_e( 'Set product image', 'woocommerce' ); ?>" rel="<?php echo esc_attr( $post->ID ); ?>">
                            <div class="upload-placeholder pos-middle">
                                <i class="wcmp-font ico-image-icon"></i>
                                <p><?php _e( 'Click to upload Image', 'dc-woocommerce-multi-vendor' );?></p>
                            </div>
                            <img src="<?php echo $featured_img ? esc_url( wp_get_attachment_image_src( $featured_img, 'medium' )[0] ) : esc_url( wc_placeholder_img_src() ); ?>" />
                            <input type="hidden" name="featured_img" class="upload_image_id" value="<?php echo esc_attr( $featured_img ); ?>" />
                        </a>
                    </div>
                    <div id="product_images_container" class="custom-panel">
                        <h3><?php _e( 'Product gallery', 'dc-woocommerce-multi-vendor' );?></h3>
                        <ul class="product_images">
                            <?php
                            if ( metadata_exists( 'post', $post->ID, '_product_image_gallery' ) ) {
                                $product_image_gallery = get_post_meta( $post->ID, '_product_image_gallery', true );
                            } else {
                                // Backwards compatibility.
                                $attachment_ids = get_posts( 'post_parent=' . $post->ID . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image&fields=ids&meta_key=_woocommerce_exclude_image&meta_value=0' );
                                $attachment_ids = array_diff( $attachment_ids, array( get_post_thumbnail_id() ) );
                                $product_image_gallery = implode( ',', $attachment_ids );
                            }

                            $attachments = array_filter( explode( ',', $product_image_gallery ) );
                            $update_meta = false;
                            $updated_gallery_ids = array();

                            if ( ! empty( $attachments ) ) {
                                foreach ( $attachments as $attachment_id ) {
                                    $attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

                                    // if attachment is empty skip
                                    if ( empty( $attachment ) ) {
                                        $update_meta = true;
                                        continue;
                                    }

                                    echo '<li class="image" data-attachment_id="' . esc_attr( $attachment_id ) . '">
                                            ' . $attachment . '
                                            <ul class="actions">
                                                <li><a href="#" class="delete tips" data-tip="' . esc_attr__( 'Delete image', 'woocommerce' ) . '">' . __( 'Delete', 'woocommerce' ) . '</a></li>
                                            </ul>
                                        </li>';

                                    // rebuild ids to be saved
                                    $updated_gallery_ids[] = $attachment_id;
                                }

                                // need to update product meta to set new gallery ids
                                if ( $update_meta ) {
                                    update_post_meta( $post->ID, '_product_image_gallery', implode( ',', $updated_gallery_ids ) );
                                }
                            }
                            ?>    
                        </ul>
                        <input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php esc_attr_e( $product_image_gallery ); ?>" />
                        <p class="add_product_images">
                            <a href="#" <?php echo current_user_can( 'upload_files' ) ? '' : 'data-nocaps="true" '; ?>data-choose="<?php esc_attr_e( 'Add images to product gallery', 'woocommerce' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'woocommerce' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'woocommerce' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>"><?php _e( 'Add product gallery images', 'woocommerce' ); ?></a>
                        </p>
                    </div>
                </div>
            </div> 
        </div>
        <div class="row">
            <div class="col-md-12">
                <div id="woocommerce-product-data" class="add-product-info-holder">   

                    <div class="add-product-info-header row-padding">
                        <div class="select-group">
                            <label for="product-type"><?php esc_html_e( 'Product Type', 'woocommerce' ); ?></label>
                            <select class="form-control inline-select" id="product-type" name="product-type">
                                <?php foreach ( wcmp_get_product_types() as $value => $label ) : ?>
                                    <option value="<?php esc_attr_e( $value ); ?>" <?php echo selected( $product_object->get_type(), $value, false ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php
                        $product_type_options = $self->get_product_type_options();
                        $required_types = array();
                        foreach ( $product_type_options as $type ) {
                            if ( isset( $type['wrapper_class'] ) ) {
                                $classes = explode( ' ', str_replace( 'show_if_', '', $type['wrapper_class'] ) );
                                foreach ( $classes as $class ) {
                                    $required_types[$class] = true;
                                }
                            }
                        }
                        ?>
                        <?php if ( wcmp_is_allowed_product_type( array_keys( $required_types ) ) ) :
                            ?>
                            <div class="pull-right">
                                <?php foreach ( $self->get_product_type_options() as $key => $option ) : ?>
                                    <?php
                                    if ( ! empty( $post->ID ) && metadata_exists( 'post', $post->ID, '_' . $key ) ) {
                                        $selected_value = is_callable( array( $product_object, "is_$key" ) ) ? $product_object->{"is_$key"}() : 'yes' === get_post_meta( $post->ID, '_' . $key, true );
                                    } else {
                                        $selected_value = 'yes' === ( isset( $option['default'] ) ? $option['default'] : 'no' );
                                    }
                                    ?>
                                    <label for="<?php esc_attr_e( $option['id'] ); ?>" class="<?php esc_attr_e( $option['wrapper_class'] ); ?> tips" data-tip="<?php echo esc_attr( $option['description'] ); ?>"><input type="checkbox" name="<?php echo esc_attr( $option['id'] ); ?>" id="<?php echo esc_attr( $option['id'] ); ?>" <?php echo checked( $selected_value, true, false ); ?> /> <?php echo esc_html( $option['label'] ); ?></label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- product Info Tab start -->
                    <div class="product-info-tab-wrapper" role="tabpanel">
                        <!-- Nav tabs start -->
                        <div class="product-tab-nav-holder">
                            <div class="tab-nav-direction-wrapper"></div>
                            <ul class="nav nav-tabs" role="tablist" id="product_data_tabs">
                                <?php foreach ( $self->get_product_data_tabs() as $key => $tab ) : ?>
                                    <?php if ( apply_filters( 'wcmp_afm_product_data_tabs_filter', ( ! isset( $tab['p_type'] ) || array_key_exists( $tab['p_type'], wcmp_get_product_types() ) && $WCMp->vendor_caps->vendor_can( $tab['p_type'] ) ), $key, $tab ) ) : ?>
                                        <li role="presentation" class="<?php esc_attr_e( $key ); ?>_options <?php esc_attr_e( $key ); ?>_tab <?php echo esc_attr( isset( $tab['class'] ) ? implode( ' ', (array) $tab['class'] ) : ''  ); ?>">
                                            <a href="#<?php esc_attr_e( $tab['target'] ); ?>" aria-controls="<?php echo $tab['target']; ?>" role="tab" data-toggle="tab"><span><?php echo esc_html( $tab['label'] ); ?></span></a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php do_action( 'wcmp_product_write_panel_tabs', $post->ID ); ?>
                            </ul>
                        </div>
                        <!-- Nav tabs End -->

                        <!-- Tab content start -->
                        <div class="tab-content">
                            <?php
                            $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-data-general.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post ) );
                            $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-data-inventory.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post ) );
                            if ( wcmp_is_allowed_vendor_shipping() ) {
                                $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-data-shipping.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post ) );
                            }
                            $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-data-linked-products.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post ) );
                            $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-data-attributes.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post ) );
                            do_action( 'wcmp_after_attribute_product_tabs_content', $self, $product_object, $post );
                            $WCMp->template->get_template( 'vendor-dashboard/product-manager/views/html-product-data-advanced.php', array( 'self' => $self, 'product_object' => $product_object, 'post' => $post ) );
                            ?>
                            <?php do_action( 'wcmp_product_tabs_content', $self, $product_object, $post ); ?>
                        </div>
                        <!-- Tab content End -->
                    </div>        
                    <!-- product Info Tab End -->
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
            <?php do_action( 'wcmp_after_product_excerpt_metabox_panel', $post->ID ); ?>
            <?php do_action( 'wcmp_afm_after_product_excerpt_metabox_panel', $post->ID ); ?>
            </div>
            <div class="col-md-4">
                <?php if( ( get_wcmp_vendor_settings('is_disable_marketplace_plisting', 'general') == 'Enable' ) ) :
                $product_categories = wcmp_get_product_terms_HTML( 'product_cat', $post->ID, apply_filters( 'wcmp_vendor_can_add_product_category', false, get_current_user_id() ) ); ?>
                <?php if ( $product_categories ) : ?>
                    <div class="panel panel-default pannel-outer-heading">
                        <div class="panel-heading">
                            <h3 class="pull-left"><?php esc_html_e( 'Product categories', 'woocommerce' ); ?></h3>
                        </div>
                        <div class="panel-body panel-content-padding form-group-wrapper"> 
                            <?php
                            echo $product_categories;
                            ?>
                        </div>
                    </div>
                <?php endif;
                endif; ?>
                <?php $product_tags = wcmp_get_product_terms_HTML( 'product_tag', $post->ID, apply_filters( 'wcmp_vendor_can_add_product_tag', true, get_current_user_id() ), false ); ?>
                <?php if ( $product_tags ) : ?>
                    <div class="panel panel-default pannel-outer-heading">
                        <div class="panel-heading">
                            <h3 class="pull-left"><?php esc_html_e( 'Product tags', 'woocommerce' ); ?></h3>
                        </div>
                        <div class="panel-body panel-content-padding form-group-wrapper">
                            <div class="form-group">
                                <div class="col-md-12">
                                    <?php
                                    echo $product_tags;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php 
                $custom_taxonomies = get_object_taxonomies( 'product', 'objects' );
                if( $custom_taxonomies ){
                    foreach ( $custom_taxonomies as $taxonomy ) {
                        if ( in_array( $taxonomy->name, array( 'product_cat', 'product_tag' ) ) ) continue;
                        if ( $taxonomy->public && $taxonomy->show_ui && $taxonomy->meta_box_cb ) { ?>
                            <div class="panel panel-default pannel-outer-heading">
                                <div class="panel-heading">
                                    <h3 class="pull-left"><?php echo $taxonomy->label; ?></h3>
                                </div>
                                <div class="panel-body panel-content-padding form-group-wrapper">
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            <?php
                                            echo wcmp_get_product_terms_HTML( $taxonomy->name, $post->ID, apply_filters( 'wcmp_vendor_can_add_'.$taxonomy->name, false, get_current_user_id() ) );
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    }
                }
                ?>
                <?php do_action( 'after_wcmp_product_tags_metabox_panel', $post->ID ); ?>
            </div>
        </div>
        <?php if ( ! empty( wcmp_get_product_types() ) ) : ?>
            <div class="wcmp-action-container">
                <?php
                $primary_action = __( 'Submit', 'dc-woocommerce-multi-vendor' );    //default value
                if ( current_vendor_can( 'publish_products' ) ) {
                    if ( ! empty( $product_object->get_id() ) && get_post_status( $product_object->get_id() ) === 'publish' ) {
                        $primary_action = __( 'Update', 'dc-woocommerce-multi-vendor' );
                    } else {
                        $primary_action = __( 'Publish', 'dc-woocommerce-multi-vendor' );
                    }
                }
                ?>
                <input type="submit" class="btn btn-default" name="submit-data" value="<?php esc_attr_e( $primary_action ); ?>" id="wcmp_afm_product_submit" />
                <input type="submit" class="btn btn-default" name="draft-data" value="<?php esc_attr_e( 'Draft', 'dc-woocommerce-multi-vendor' ); ?>" id="wcmp_afm_product_draft" />
                <input type="hidden" name="status" value="<?php esc_attr_e( get_post_status( $post ) ); ?>">
                <?php wp_nonce_field( 'wcmp-product', 'wcmp_product_nonce' ); ?>
            </div>
        <?php endif; ?>
        <?php do_action( 'wcmp_add_product_form_end' ); ?>
    </form>
    <?php do_action( 'after_wcmp_add_product_form' ); ?>
</div>