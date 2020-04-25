/* global wcmp_meta_boxes_script_data */
(function ($) {
    
    // wcmp_meta_boxes_script_data is required to continue, ensure the object exists
    if ( typeof wcmp_meta_boxes_script_data === 'undefined' ) {
        return false;
    }
    
    var block = function( $node ) {
        if ( ! is_blocked( $node ) ) {
            $node.addClass( 'processing' ).block( {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            } );
        }
    };
    
    var is_blocked = function( $node ) {
        return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
    };

    var unblock = function( $node ) {
        $node.removeClass( 'processing' ).unblock();
    };
    
    // Common scroll to element code.
    var scroll_to = function( scrollElement ) {
        //if ( scrollElement.length ) {
            $( 'html, body' ).animate( {
                    scrollTop: ( scrollElement.offset().top - 100 )
            }, 1000 );
        //}
    };
    
    var product_meta_boxes = {
        $product_title_wrap: $('.product-title-wrap'),
        init: function() {
            // helper functions
            this.$product_title_wrap.on( 'click', '.editable-content-button', this.edit_content);
            this.$product_title_wrap.on( 'click', '.cancel-btn', this.cancel_content);
        },
        edit_content: function() {
            var $editable = $(this).siblings('.editable-content'); 
            var $editableText = $editable.text();
            var $editingWrapItem = $(this).siblings('.editing-content');

            $editingWrapItem.css('display', 'inline-block');
            $editingWrapItem.children('.form-control').val($editableText);
            $editingWrapItem.children('label').hide();
            $editable.hide();
            $(this).hide();
        },
        cancel_content: function() {
            var $editingWrapItem = $(this).parent();
            var $editable = $editingWrapItem.siblings('.editable-content');
            var $editableButton = $editingWrapItem.siblings('.editable-content-button');

            $editingWrapItem.css('display', 'none');
            $editable.show();
            $editableButton.show();    
        },
    };
    
    product_meta_boxes.init();
    
    var coupon_meta_boxes = {
        $coupon_form_wrap: $('#wcmp-afm-add-coupon'),
        init: function() {
            // helper functions
            this.insert_generate_coupon_code_button();
            this.$coupon_form_wrap.on( 'click', '.generate-coupon-code', this.generate_coupon_code.bind(this) );
        },
        /**
         * Insert generate coupon code buttom HTML.
         */
        insert_generate_coupon_code_button: function() {
			this.$coupon_form_wrap.find( '#post_title' ).after(
				'<a href="#" class="btn btn-secondary generate-coupon-code" style="margin-top: 6px;">' + wcmp_meta_boxes_script_data.coupon_meta.coupon_code.generate_button_text + '</a>'
			);
        },
        /**
         * Generate a random coupon code
         */
        generate_coupon_code: function( e ) {
            e.preventDefault();
            var $coupon_code_field = this.$coupon_form_wrap.find( '#post_title' ),
                $result = '';
            for ( var i = 0; i < wcmp_meta_boxes_script_data.coupon_meta.coupon_code.char_length; i++ ) {
                $result += wcmp_meta_boxes_script_data.coupon_meta.coupon_code.characters.charAt(
                    Math.floor( Math.random() * wcmp_meta_boxes_script_data.coupon_meta.coupon_code.characters.length )
                );
            }
            $result = wcmp_meta_boxes_script_data.coupon_meta.coupon_code.prefix + $result + wcmp_meta_boxes_script_data.coupon_meta.coupon_code.suffix;
            $coupon_code_field.focus().val( $result );
        }
    }
    coupon_meta_boxes.init();
    
    $( '#product_visiblity' ).find( '.catalog-visiblity-btn' ).click( function() { 
            $( '#product_visiblity' ).collapse('hide');
            var label = $( 'input[name=_visibility]:checked' ).attr( 'data-label' );
            if ( $( 'input[name=_featured]' ).is( ':checked' ) ) {
                label = label + ', ' + $( 'input[name=_featured]' ).attr( 'data-label' );
                $( 'input[name=_featured]' ).attr( 'checked', 'checked' );
            }
            $( '#catalog-visibility-display' ).text( label );
            return false;
    });
    
    $( '#multiple-cat-hierarchy-lbl' ).find( '.multi-cat-choose-dflt-btn' ).click( function() { 
            $( '#product_visiblity' ).collapse('hide');
            $( '#multi_cat_hierarchy_visiblity' ).collapse('show');
            return false;
    });
    
    $( '#multi_cat_hierarchy_visiblity' ).on( 'click', '.set-default-cat-hierarchy-btn', function() { 
            var label = $( 'input[name=_default_cat_hierarchy_term_id]:checked' ).attr( 'data-hierarchy' );
            console.log(label);
            $( '#multi_cat_hierarchy_visiblity' ).collapse('hide');
            $(".cat-breadcrumb-wrap .wcmp-breadcrumb.has-multiple-cat").html('');
            $(".cat-breadcrumb-wrap .wcmp-breadcrumb.has-multiple-cat").html(label);
            return false;
    });
    
    $( '.coupon-products-wrap' ).on( 'click', 'button.select_all_attributes', function() { 
            $("#products > option").prop("selected","selected");
            $("#products").trigger("change");
            return false;
    });

    $( '.coupon-products-wrap' ).on( 'click', 'button.select_no_attributes', function() {
            $("#products > option").removeAttr( 'selected' );
            $("#products").trigger("change");
            return false;
    });
    
    $(document).click(function(e) {
	if (!$(e.target).parents('.wcmp-clps').length) {
            $('.wcmp-clps.collapse').collapse('hide');	    
        }
    });
   
     
})(jQuery);