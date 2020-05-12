<?php

/**
 * Product highlights template
 *
  * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/views/html-product-highlights.php
 *
 * @author  WC Marketplace
 * @package     WCMp/Templates
 * @version   3.3.0
 */
defined( 'ABSPATH' ) || exit;
global $WCMp;
?>
<div class="cat-step3">
    <div class="panel-heading">
        <h1>
            <?php if( !get_wcmp_vendor_settings('is_singleproductmultiseller', 'general') == 'Enable' && get_wcmp_vendor_settings('is_disable_marketplace_plisting', 'general') != 'Enable' ) : ?>
            <span class="primary-color"><span><?php _e( 'Step 2 of', 'dc-woocommerce-multi-vendor' );?></span> <?php _e( '2:', 'dc-woocommerce-multi-vendor' );?></span> 
            <?php endif; ?>
            <?php _e( 'Add Product Details', 'dc-woocommerce-multi-vendor' );?>
        </h1>
        <?php if( get_transient( 'classified_product_terms_vendor'. get_current_user_id() ) || ($self->is_spmv() && $post) || $is_update ) : ?>
        <?php do_action( 'wcmp_afm_before_product_highlights_category_wrap', $post->ID, $product_object, $post ); ?> 
        <div class="cat-breadcrumb-wrap">
        <?php 
            if( get_transient( 'classified_product_terms_vendor'. get_current_user_id() ) ){
                $classified_terms = get_transient( 'classified_product_terms_vendor' . get_current_user_id() );
                if( isset( $classified_terms['term_id'] ) && isset( $classified_terms['taxonomy'] ) ){
                    echo '<input type="hidden" name="_default_cat_hierarchy_term_id" id="_default_cat_hierarchy_term_id_' . esc_attr( $classified_terms['term_id'] ) . '" value="' . esc_attr( $classified_terms['term_id'] ) . '" data-label="' . esc_attr( $classified_terms['term_id'] ) . '" />';
                    wcmp_generate_term_breadcrumb_html( array(
                        'term_id' => $classified_terms['term_id'], 
                        'taxonomy' => $classified_terms['taxonomy'],
                        'delimiter' => '',
                        'echo' => true) );

                    // save terms for post save handler 
                    $hierarchy = get_ancestors( $classified_terms['term_id'], $classified_terms['taxonomy'] );
                    $hierarchy[] = $classified_terms['term_id'];
                    foreach ( $hierarchy as $term_id ) {
                        echo '<input type="hidden" name="tax_input[' . $classified_terms['taxonomy'] . '][]" value="' . $term_id . '" />';
                    }
                }
            }elseif( ($self->is_spmv() && $post) || $is_update ){
                $term_tax = 'product_cat';
                $terms = wp_get_post_terms( $post->ID, $term_tax, array( 'fields' => 'ids' ) );
                $default_cat_hierarchy = get_post_meta( $post->ID, '_default_cat_hierarchy_term_id', true );
                $get_different_terms_hierarchy = get_wcmp_different_terms_hierarchy( $terms );
                if( $get_different_terms_hierarchy ){
                    $nos_hierarchy = count( $get_different_terms_hierarchy );
                    $class = ( $nos_hierarchy > 1 ) ? "has-multiple-cat" : "";
                    $flag = 0;
                    foreach ( $get_different_terms_hierarchy as $term_id ) {
                        if( $flag >= 1 ) continue;
                        $term_id = ($default_cat_hierarchy) ? $default_cat_hierarchy : $term_id;
                        wcmp_generate_term_breadcrumb_html( array(
                        'term_id' => $term_id, 
                        'taxonomy' => $term_tax,
                        'wrap_before' => '<ul class="wcmp-breadcrumb breadcrumb '.$class.'">',
                        'delimiter' => '',
                        'echo' => true) );
                        $flag++;
                    }
                    // give option to set default terms hierarchy
                    if( $nos_hierarchy > 1 && ( get_wcmp_vendor_settings('is_disable_marketplace_plisting', 'general') != 'Enable' ) ){ ?>
                    <p class="pull-right multiple-cat-hierarchy"><?php _e( 'Select a different category :', 'dc-woocommerce-multi-vendor' );?>
                        <strong id="multiple-cat-hierarchy-lbl" class="primary-color">
                            <button type="button" class="multi-cat-choose-dflt-btn editabble-button" data-toggle="collapse" data-target="#multi_cat_hierarchy_visiblity"><u><?php _e( 'Choose default', 'dc-woocommerce-multi-vendor' );?></u> <i class="wcmp-font ico-downarrow-2-icon"></i></button>
                        </strong>
                    </p> 
                    <div id="multi_cat_hierarchy_visiblity" class="wcmp-clps collapse dropdown-panel">
                        <div class="product-visibility-toggle-inner">
                            <?php 
                            foreach ( $get_different_terms_hierarchy as $term_id ) {
                                echo '<div class="form-group">' 
                                    . '<label>'
                                    . '<input type="radio" name="_default_cat_hierarchy_term_id" id="_default_cat_hierarchy_term_id_' . esc_attr( $term_id ) . '" value="' . esc_attr( $term_id ) . '" ' . checked( $default_cat_hierarchy, $term_id, false ) . ' data-label="' . esc_attr( $term_id ) . '" data-hierarchy="' . esc_attr(wcmp_generate_term_breadcrumb_html( array( 'term_id' => $term_id, 
                                        'taxonomy' => $term_tax,
                                        'wrap_before'           => '',
                                        'wrap_after'            => '',
                                        'wrap_child_before'     => '<li>',
                                        'wrap_child_after'      => '</li>',
                                        'delimiter'             => ''
                                        ) )) . '" /> '
                                    . '<div for="_visibility_hierarchy_' . esc_attr( $term_id ) . '" class="selectit cat-breadcrumb">'.wcmp_generate_term_breadcrumb_html( array( 'term_id' => $term_id, 
                                        'taxonomy' => $term_tax,
                                        'wrap_before'           => '',
                                        'wrap_after'            => '',
                                        'wrap_child_before'     => '',
                                        'wrap_child_after'      => '',
                                        ) ).'</div>' 
                                    . '</label>'
                                    . '</div>';
                            }
                            ?>
                            <div class="form-group mb-0">
                                <button type="button" class="btn btn-default btn-sm set-default-cat-hierarchy-btn" ><?php _e( 'Ok', 'dc-woocommerce-multi-vendor' ); ?></button>
                                <a href="javascript:void(0)" data-toggle="collapse" data-target="#multi_cat_hierarchy_visiblity"><?php _e( 'Cancel', 'dc-woocommerce-multi-vendor' ); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php }
                    if( get_wcmp_vendor_settings('is_disable_marketplace_plisting', 'general') != 'Enable' ) :
                    // save terms for post save handler 
                    if( $terms ){
                        foreach ( $terms as $term_id ) {
                            echo '<input type="hidden" name="tax_input[' . $term_tax . '][]" value="' . $term_id . '" />';
                        }
                    }
                    endif;
                }
            }
        ?>
        </div>
        <?php do_action( 'wcmp_afm_after_product_highlights_category_wrap', $post->ID, $product_object, $post ); ?> 
        <?php endif; ?>
        <?php do_action( 'wcmp_afm_before_product_highlights_title_wrap', $post->ID, $product_object, $post ); ?> 
        <div class="product-title-wrap <?php echo ( $self->is_spmv() || $is_update ) ? 'product-edit-mode' : 'product-add-mode'; ?>"> <!-- product-add-mode / product-edit-mode according to flow -->
            <div class="pull-left product-title-inner full-1080"> 
                <p class="pro-title">
                    <label><?php _e('Product Title', 'dc-woocommerce-multi-vendor'); ?>: </label>
                    <strong class="editable-content"><?php echo $product_object->get_title( 'edit' ); ?></strong>
                    <?php if( (!$self->is_spmv() && $is_update) || !apply_filters('wcmp_singleproductmultiseller_edit_product_title_disabled', true) ) : ?>
                    <button type="button" class="editable-content-button"><i class="wcmp-font ico-edit-pencil-icon" title="<?php _e('Edit', 'dc-woocommerce-multi-vendor'); ?>"></i> <!--span>edit</span--></button>
                    <?php endif; ?>
                    <span class="editing-content">
                        <input type="text" class="form-control" name="post_title" id="post_title" value="<?php echo $product_object->get_title( 'edit' ); ?>"<?php if ( $self->is_spmv() && apply_filters('wcmp_singleproductmultiseller_edit_product_title_disabled', true) ) echo ' readonly="readonly"'; ?>>
                        <input type="hidden" name="original_post_title" value="<?php echo $product_object->get_title( 'edit' ); ?>">
                        <input type="hidden" name="post_ID" value="<?php echo $self->get_the_id(); ?>">
                        <input type="hidden" name="is_update" value="<?php esc_attr_e( $is_update ); ?>">
                        <input type="hidden" name="original_post_status" value="<?php esc_attr_e( get_post_status( $post ) ); ?>">
                    </span> 
                </p>
                <?php if( $is_update ) : ?>
                <p class="pro-view"><?php echo wcmp_get_post_permalink_html( $product_object->get_id() ); ?></p>
                <?php endif; ?>
                <?php if( get_wcmp_vendor_settings('is_gtin_enable', 'general') == 'Enable' ) : ?>
                <p class="gtin-field-wrap">
                    <?php if( $self->is_spmv() && !empty($self->get_gtin_no()) ) { ?>
                    <label><?php if( $self->get_gtin_term() ) echo $self->get_gtin_term()->name; else _e('GTIN', 'dc-woocommerce-multi-vendor'); ?>: </label>
                    <?php }elseif( $self->is_spmv() && empty($self->get_gtin_no()) ){ }elseif( $is_update ){ ?>
                    <label><?php if( $self->get_gtin_term() ) echo $self->get_gtin_term()->name; else _e('GTIN', 'dc-woocommerce-multi-vendor'); ?>: </label>
                    <?php } ?>
                    <strong class="editable-content"><?php echo $self->get_gtin_no(); ?></strong>
                    <?php if( !$self->is_spmv() && $is_update ) : ?>
                    <button type="button" class="editable-content-button"><i class="wcmp-font ico-edit-pencil-icon" title="<?php _e('Edit', 'dc-woocommerce-multi-vendor'); ?>"></i> <!--span>edit</span--></button>
                    <?php endif; ?>
                    <span class="editing-content">
                        <label><?php _e('GTIN', 'dc-woocommerce-multi-vendor'); ?>:</label>
                        <select class="form-control inline-input" name="_wcmp_gtin_type">
                        <option value=""><?php _e( 'Select type', 'dc-woocommerce-multi-vendor' ); ?></option>  
                        <?php 
                        $gtin_types = apply_filters('wcmp_add_product_default_gtin_types', $WCMp->taxonomy->get_wcmp_gtin_terms(array('fields' => 'id=>name', 'orderby' => 'id')), $post, $self);
                        foreach ($gtin_types as $term_id => $name) {
                            echo '<option value="'.$term_id.'">'.$name.'</option>'; 
                        }
                        ?>
                    </select>
                    <input type="text" class="form-control inline-input" name="_wcmp_gtin_code" placeholder="<?php _e( 'GTIN Code', 'dc-woocommerce-multi-vendor' );?>" value="<?php echo $self->get_gtin_no(); ?>">
                    </span> 
                </p>
                <?php endif; ?>
            </div>
            <div class="pull-right full-1080">
                <?php
                $current_visibility = $product_object->get_catalog_visibility();
                $current_featured   = wc_bool_to_string( $product_object->get_featured() );
                $visibility_options = wc_get_product_visibility_options();
                ?>
                <p class="cat-visiblity"><?php esc_html_e( 'Catalog visibility:', 'woocommerce' ); ?> 
                    <strong id="catalog-visibility-display" class="primary-color">
                        <?php

                        echo isset( $visibility_options[ $current_visibility ] ) ? esc_html( $visibility_options[ $current_visibility ] ) : esc_html( $current_visibility );

                        if ( 'yes' === $current_featured ) {
                                echo ', ' . esc_html__( 'Featured', 'woocommerce' );
                        }
                        ?>
                    </strong>
                    <button type="button" class="editabble-button" data-toggle="collapse" data-target="#product_visiblity"><i class="wcmp-font ico-downarrow-2-icon" title="<?php _e('Edit', 'dc-woocommerce-multi-vendor'); ?>"></i> <!--span>edit</span--></button>
                </p> 
                <div id="product_visiblity" class="wcmp-clps collapse dropdown-panel">
                    <input type="hidden" name="current_visibility" id="current_visibility" value="<?php echo esc_attr( $current_visibility ); ?>" />
                    <input type="hidden" name="current_featured" id="current_featured" value="<?php echo esc_attr( $current_featured ); ?>" />
                    <div class="product-visibility-toggle-inner">
                        <?php 
                        foreach ( $visibility_options as $name => $label ) {
                            echo '<div class="form-group"><label><input type="radio" name="_visibility" id="_visibility_' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . checked( $current_visibility, $name, false ) . ' data-label="' . esc_attr( $label ) . '" /> <span for="_visibility_' . esc_attr( $name ) . '" class="selectit">' . esc_html( $label ) . '</span></label></div>';
                        }
                        echo '<hr><div class="form-group"><label><input type="checkbox" name="_featured" class="mt-0" id="_featured" ' . checked( $current_featured, 'yes', false ) . ' data-label="' . __( 'Featured', 'woocommerce' ) . '" /> <span for="_featured">' . esc_html__( 'This is a featured product', 'woocommerce' ) . '</label></label></div>';
                        ?>
                        <div class="form-group mt-15">
                            <button type="button" class="btn btn-default btn-sm catalog-visiblity-btn"><?php _e('Ok', 'dc-woocommerce-multi-vendor'); ?></button>
                            <a href="javascript:void(0)" class="btn btn-default btn-sm" data-toggle="collapse" data-target="#product_visiblity"><?php _e('Cancel', 'dc-woocommerce-multi-vendor'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php do_action( 'wcmp_afm_after_product_highlights_title_wrap', $post->ID, $product_object, $post ); ?> 
        </div>
    </div>
</div>