/* global front_end_param */

jQuery(document).ready(function ($) { 
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
    
    // Modal Close
    $(".wcmp-report-abouse-wrapper .close").on('click', function () {
        $(".wcmp-report-abouse-wrapper #report_abuse_form").slideToggle(500);
    });

    $('.wcmp-report-abouse-wrapper #report_abuse').on('click', function () {
        $(".wcmp-report-abouse-wrapper #report_abuse_form").slideToggle(1000);
    });
    
    var modal = document.getElementById('report_abuse_form');
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    $('.submit-report-abuse').on('click', function (e) {
        var inpObjName = document.getElementById("report_abuse_name");
        if (inpObjName.checkValidity() === false) {
            $('#report_abuse_name').next('span').html(inpObjName.validationMessage);
        } else {
            $('#report_abuse_name').next('span').html('');
        }
        var inpObjEmail = document.getElementById("report_abuse_email");
        if (inpObjEmail.checkValidity() === false) {
            $('#report_abuse_email').next('span').html(inpObjEmail.validationMessage);
        } else {
            $('#report_abuse_email').next('span').html('');
        }
        var inpObjMessage = document.getElementById("report_abuse_msg");
        if (inpObjMessage.checkValidity() === false) {
            $('#report_abuse_msg').next('span').html(inpObjMessage.validationMessage);
        } else {
            $('#report_abuse_msg').next('span').html('');
        }
        e.preventDefault();
        
        var data = {
            action: 'send_report_abuse',
            product_id: $('.report_abuse_product_id').val(),
            name: $('.report_abuse_name').val(),
            email: $('.report_abuse_email').val(),
            msg: $('.report_abuse_msg').val(),
        };
        if (inpObjName.checkValidity() && inpObjEmail.checkValidity() && inpObjMessage.checkValidity()) {
            block($( '#report_abuse_form' ));
            $.post(frontend_js_script_data.ajax_url, data, function (responsee) {
                unblock($( '#report_abuse_form' ));
                $(".wcmp-report-abouse-wrapper #report_abuse_form").slideToggle(500);
                $('#report_abuse').text(frontend_js_script_data.messages.report_abuse_msg);
            });
        }
    });

    $('#wcmp_widget_vendor_search .search_keyword').on('input', function () {

        var vendor_search_data = {
            action: 'vendor_list_by_search_keyword',
            s: $(this).val(),
            vendor_search_nonce: $('#wcmp_vendor_search_nonce').val()
        }

        $.post(frontend_js_script_data.ajax_url, vendor_search_data, function (response) {
            $('#wcmp_widget_vendor_list').html('');
            $('#wcmp_widget_vendor_list').html(response);

        });

    });

    $('.vendors_sort_shipping_fields').hide();
    $('#vendor_state').hide();
    $('#vendor_sort_type').change(function () {
        if ($(this).val() == 'category') {
            $('#vendor_sort_category').show();
        } else {
            $('#vendor_sort_category').hide();
        }
        // shipping zone
        if ($(this).val() == 'shipping') {
            $('.vendors_sort_shipping_fields').show();
            $('#vendor_state').show();
        } else {
            $('.vendors_sort_shipping_fields').hide();
            $('#vendor_state').hide();
        }
    }).change();
    
    /* Delete Product */
    $('.wcmp_fpm_delete').each(function() {
        $(this).click(function(event) {
            event.preventDefault();
            var rconfirm = confirm(frontend_js_script_data.messages.confirm_dlt_pro);
            if(rconfirm) deleteProduct($(this));
            return false;
        });
    });
	
    function deleteProduct(item) {
        $('.woocommerce').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
        var data = {
            action : 'delete_fpm_product',
            proid : item.data('proid')
        }	
        $.ajax({
            type: 'POST',
            url: frontend_js_script_data.ajax_url,
            data: data,
            success: function(response) {
                if(response) {
                    $response_json = $.parseJSON(response);
                    if($response_json.status == 'success') {
                        window.location = $response_json.shop_url;
                    } else {
                        $('.woocommerce').unblock();
                    }
                } else {
                    $('.woocommerce').unblock();
                }
            }
        });
    }

    // Wcmp shop page tab
    $( 'body' )
    // Tabs
    .on( 'init', '.wcmp-shop-tabs', function() {
        $( this ).find( '.wcmp-shop-tabs .panel:not(.panel .panel)' ).hide();

        var hash  = window.location.hash;
        var url   = window.location.href;
        var $tabs = $( this ).find( '.wcmp-tabs, ul.tabs' ).first();
        
        $tabs.find( '.wcmp-tablink:first a' ).click();
                
    } )
    .on( 'click', '.wcmp-tabs .wcmp-tablink a, ul.tabs .wcmp-tablink a', function( e ) {
        e.preventDefault();
        var $tab          = $( this );
        var $tabs_wrapper = $tab.closest( '.wcmp-tabs-wrapper, .wcmp-shop-tabs' );
        var $tabs         = $tabs_wrapper.find( '.wcmp-tabs, ul.tabs' );

        $tabs.find( '.wcmp-tablink' ).removeClass( 'active' );
        $tabs_wrapper.find( '.panel:not(.panel .panel)' ).hide();
        $tab.closest( '.wcmp-tablink' ).addClass( 'active' );
        $tabs_wrapper.find( $tab.attr( 'href' ) ).show();
    } )

    // Init Tabs and Star Ratings
    $( '.wcmp-shop-tabs' ).trigger( 'init' );
    
});