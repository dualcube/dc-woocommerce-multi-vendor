jQuery(document).ready(function ($) {
    $('.question_verify_admin').on('click', function(e){
        e.preventDefault();
        var $this = $(this);
        var question_type = $(this).attr('data-verification');
        var question_id = $(this).attr('data-user_id');
        var data_action = $(this).attr('data-action');
        var product     = $(this).attr('data-product');
        var data = {
            action   : 'wcmp_question_verification_approval',
            question_type : question_type,
            question_id : question_id,
            data_action : data_action,
            product      : product
        }   
        $.post(ajaxurl, data, function(response) {
            location.reload();
        });
    });
    $('.img_tip').each(function () {
        $(this).qtip({
            content: $(this).attr('data-desc'),
            position: {
                my: 'top center',
                at: 'bottom center',
                viewport: $(window)
            },
            show: {
                event: 'mouseover',
                solo: true,
            },
            hide: {
                inactive: 6000,
                fixed: true
            },
            style: {
                classes: 'qtip-dark qtip-shadow qtip-rounded qtip-dc-css'
            }
        });
    });

    $('.dc_datepicker').each(function () {
        $(this).datepicker({
            dateFormat: $(this).data('date_format'),
            changeMonth: true,
            changeYear: true
        });
    });
    
    $( '.wcmp-shipping-zone-method' ).on( 'change', '.wcmp-shipping-zone-method-enabled input', function() {
            if ( $( this ).is( ':checked' ) ) {
                    $( this ).closest( '.wcmp-input-toggle' ).removeClass( 'woocommerce-input-toggle--disabled' );
                    $( this ).closest( '.wcmp-input-toggle' ).addClass( 'checked' );
                    $( this ).closest( '.wcmp-input-toggle' ).find( '.wcmp-input-toggle' ).removeClass( 'hide' );
            } else {
                    $( this ).closest( '.wcmp-input-toggle' ).addClass( 'woocommerce-input-toggle--disabled' );
                    $( this ).closest( '.wcmp-input-toggle' ).removeClass( 'checked' );
                    $( this ).closest( '.wcmp-input-toggle' ).find( '.wcmp-shipping-zone-method' ).addClass( 'hide' );
            }
    } );

    $( '.wcmp-shipping-zone-method' ).on( 'click', '.wcmp-shipping-zone-method-enabled', function( e ) {
            var eventTarget = $( e.target );

            if ( eventTarget.is( 'input' ) ) {
                    e.stopPropagation();
                    return;
            }

            var $checkbox = $( this ).find( 'input[type="checkbox"]' );

            $checkbox.prop( 'checked', ! $checkbox.prop( 'checked' ) ).change();
    } );

    $('.multi_input_holder').each(function () {
        var multi_input_holder = $(this);
        if (multi_input_holder.find('.multi_input_block').length == 1)
            multi_input_holder.find('.remove_multi_input_block').css('display', 'none');
        multi_input_holder.find('.multi_input_block').each(function () {
            if ($(this)[0] != multi_input_holder.find('.multi_input_block:last')[0]) {
                $(this).find('.add_multi_input_block').remove();
            }
        });

        multi_input_holder.find('.add_multi_input_block').click(function () {
            var holder_id = multi_input_holder.attr('id');
            var holder_name = multi_input_holder.data('name');
            var multi_input_blockCount = multi_input_holder.data('length');
            multi_input_blockCount++;
            var multi_input_blockEle = multi_input_holder.find('.multi_input_block:first').clone(true);

            multi_input_blockEle.find('textarea,input:not(input[type=button],input[type=submit])').val('');
            multi_input_blockEle.find('.multi_input_block_element').each(function () {
                var ele = $(this);
                var ele_name = ele.data('name');
                ele.attr('name', holder_name + '[' + multi_input_blockCount + '][' + ele_name + ']');
                ele.attr('id', holder_id + '_' + ele_name + '_' + multi_input_blockCount);
                if (ele.parent().hasClass('dc-wp-fields-uploader')) {
                    var uploadEle = ele.parent();
                    uploadEle.find('img').attr('src', '').attr('id', holder_id + '_' + ele_name + '_' + multi_input_blockCount + '_display').addClass('placeHolder');
                    uploadEle.find('.upload_button').attr('id', holder_id + '_' + ele_name + '_' + multi_input_blockCount + '_button').show();
                    uploadEle.find('.remove_button').attr('id', holder_id + '_' + ele_name + '_' + multi_input_blockCount + '_remove_button').hide();
                }

                if (ele.hasClass('dc_datepicker')) {
                    ele.removeClass('hasDatepicker').datepicker({
                        dateFormat: ele.data('date_format'),
                        changeMonth: true,
                        changeYear: true
                    });
                }

            });

            multi_input_blockEle.find('.add_multi_input_block').remove();
            multi_input_holder.append(multi_input_blockEle);
            multi_input_holder.find('.multi_input_block:last').append($(this));
            if (multi_input_holder.find('.multi_input_block').length > 1)
                multi_input_holder.find('.remove_multi_input_block').css('display', 'block');
            multi_input_holder.data('length', multi_input_blockCount);
        });

        multi_input_holder.find('.remove_multi_input_block').click(function () {
            var addEle = multi_input_holder.find('.add_multi_input_block').clone(true);
            $(this).parent().remove();
            multi_input_holder.find('.add_multi_input_block').remove();
            multi_input_holder.find('.multi_input_block:last').append(addEle);
            if (multi_input_holder.find('.multi_input_block').length == 1)
                multi_input_holder.find('.remove_multi_input_block').css('display', 'none');
        });
    });

    if ($('#commission_typee').val() == 'fixed_with_percentage') {
        $('#default_commissionn').closest("tr").css("display", "none");
        $('#fixed_with_percentage_qty').closest("tr").css("display", "none");
    } else if ($('#commission_typee').val() == 'fixed_with_percentage_qty') {
        $('#default_commissionn').closest("tr").css("display", "none");
        $('#fixed_with_percentage').closest("tr").css("display", "none");
    } else {
        $('#default_percentage').closest("tr").css("display", "none");
        $('#fixed_with_percentage').closest("tr").css("display", "none");
        $('#fixed_with_percentage_qty').closest("tr").css("display", "none");
    }

    $('#commission_typee').change(function () {
        var commission_type = $(this).val();
        if (commission_type == 'fixed_with_percentage') {
            $('#default_commissionn').closest("tr").css("display", "none");
            $('#default_percentage').val('');
            $('#fixed_with_percentage').val('');
            $('#default_percentage').closest("tr").show();
            $('#fixed_with_percentage').closest("tr").show();
            $('#fixed_with_percentage_qty').closest("tr").hide();
        } else if (commission_type == 'fixed_with_percentage_qty') {
            $('#default_commissionn').closest("tr").css("display", "none");
            $('#default_percentage').closest("tr").show();
            $('#fixed_with_percentage_qty').closest("tr").show();
            $('#fixed_with_percentage').closest("tr").hide();
            $('#default_percentage').val('');
            $('#fixed_with_percentage_qty').val('');
        } else {
            $('#default_commissionn').closest("tr").show();
            $('#default_percentage').closest("tr").css("display", "none");
            $('#fixed_with_percentage').closest("tr").css("display", "none");
            $('#fixed_with_percentage_qty').closest("tr").css("display", "none");
        }
    });

    if ($('#wcmp_disbursal_mode_admin').is(':checked')) {
        $('#payment_schedule').closest("tr").show();
    } else {
        $('#payment_schedule').closest("tr").css("display", "none");
    }
    
    $('#wcmp_disbursal_mode_admin').change(function () {
        if ($(this).is(':checked')) {
            $('#payment_schedule').closest("tr").show();
        } else {
            $('#payment_schedule').closest("tr").css("display", "none");
        }
    });
    
    
    if ($('#is_submit_product').is(':checked')) {
        $('#is_published_product').closest("tr").show();
        $('#is_edit_delete_published_product').closest("tr").show();
    } else {
        $('#is_published_product').closest("tr").css("display", "none");
        $('#is_edit_delete_published_product').closest("tr").css("display", "none");
    }
    
    $('#is_submit_product').change(function () {
        if ($(this).is(':checked')) {
            $('#is_published_product').closest("tr").show();
            $('#is_edit_delete_published_product').closest("tr").show();
        } else {
            $('#is_published_product').closest("tr").css("display", "none");
            $('#is_edit_delete_published_product').closest("tr").css("display", "none");
        }
    });
    
    if ($('#is_submit_coupon').is(':checked')) {
        $('#is_published_coupon').closest("tr").show();
        $('#is_edit_delete_published_coupon').closest("tr").show();
    } else {
        $('#is_published_coupon').closest("tr").css("display", "none");
        $('#is_edit_delete_published_coupon').closest("tr").css("display", "none");
    }
    
    $('#is_submit_coupon').change(function () {
        if ($(this).is(':checked')) {
            $('#is_published_coupon').closest("tr").show();
            $('#is_edit_delete_published_coupon').closest("tr").show();
        } else {
            $('#is_published_coupon').closest("tr").css("display", "none");
            $('#is_edit_delete_published_coupon').closest("tr").css("display", "none");
        }
    });
    

    if ($('#wcmp_disbursal_mode_vendor').is(':checked')) {
        $('#commission_transfer').closest("tr").show();
        $('#no_of_orders').closest("tr").show();
    } else {
        $('#commission_transfer').closest("tr").css("display", "none");
        $('#no_of_orders').closest("tr").css("display", "none");
    }
    
    if ($('#wcmp_disbursal_mode_admin').is(':checked')) {
        $('#payment_schedule').closest("tr").show();
    } else {
        $('#payment_schedule').closest("tr").css("display", "none");
    }

    if ($('#testmode').is(':checked')) {
        $('#test_client_id').closest("tr").show();
        $('#test_publishable_key').closest("tr").show();
        $('#test_secret_key').closest("tr").show();
        $('#live_client_id').closest("tr").hide();
        $('#live_publishable_key').closest("tr").hide();
        $('#live_secret_key').closest("tr").hide();
    } else {
        $('#test_client_id').closest("tr").hide();
        $('#test_publishable_key').closest("tr").hide();
        $('#test_secret_key').closest("tr").hide();
        $('#live_client_id').closest("tr").show();
        $('#live_publishable_key').closest("tr").show();
        $('#live_secret_key').closest("tr").show();
    }

    $('#testmode').change(function () {
        if ($(this).is(':checked')) {
            $('#test_client_id').closest("tr").show();
            $('#test_publishable_key').closest("tr").show();
            $('#test_secret_key').closest("tr").show();
            $('#live_client_id').closest("tr").hide();
            $('#live_publishable_key').closest("tr").hide();
            $('#live_secret_key').closest("tr").hide();
        } else {
            $('#test_client_id').closest("tr").hide();
            $('#test_publishable_key').closest("tr").hide();
            $('#test_secret_key').closest("tr").hide();
            $('#live_client_id').closest("tr").show();
            $('#live_publishable_key').closest("tr").show();
            $('#live_secret_key').closest("tr").show();
        }
    });

    $('#wcmp_disbursal_mode_vendor').change(function () {
        if ($(this).is(':checked')) {
            $('#commission_transfer').closest("tr").show();
            $('#no_of_orders').closest("tr").show();
        } else {
            $('#commission_transfer').closest("tr").css("display", "none");
            $('#no_of_orders').closest("tr").css("display", "none");
        }
    });
    // toggle check uncheck event on gatewar charge

    $('#payment_gateway_charge').change(function () {
        if ($(this).prop('checked')) {
            $('.payment_gateway_charge').show();
            $('#payment_gateway_charge_type').closest('tr').show();
            $('#gateway_charges_cost_carrier').closest('tr').show();
        } else {
            $('.payment_gateway_charge').hide();
            $('#payment_gateway_charge_type').closest('tr').hide();
            $('#gateway_charges_cost_carrier').closest('tr').hide();
        }
    }).change();
    
    $( "input[name^='wcmp_payment_settings_name[gateway_charge_fixed_with_']" ).closest('tr').hide();
    $('#payment_gateway_charge_type').on('change', function(){
        var charge_type = $(this).val();
        if (charge_type == 'fixed_with_percentage') {
            $('.automatic_payment_method').each(function(){
                var id = $(this).attr('id');
                if (id !== undefined) {
                    var terget_id = 'gateway_charge' + id.split('payment_method')[1];
                    var terget_fixed_id = 'gateway_charge_fixed_with' + id.split('payment_method')[1];
                    if($(this).is(':checked') && $('#payment_gateway_charge').prop('checked')){
                        $('#' + terget_id).closest('tr').show();
                        $('#' + terget_id).attr('placeholder', wcmp_admin_js_script_data.lang.in_percentage);
                        $('#' + terget_id).siblings('.description').html($('#' + terget_id).siblings('.description').html()+' '+wcmp_admin_js_script_data.lang.in_percentage);
                        $('#' + terget_fixed_id).closest('tr').show();
                        $('#' + terget_fixed_id).attr('placeholder', wcmp_admin_js_script_data.lang.in_fixed);
                        $('#' + terget_fixed_id).siblings('.description').html($('#' + terget_fixed_id).siblings('.description').html()+' '+wcmp_admin_js_script_data.lang.in_fixed);
                    }else{
                        $('#' + terget_id).closest('tr').hide();
                        $('#' + terget_fixed_id).closest('tr').hide();
                    }
                }
            });
            
        }
    }).trigger('change');

    $('.automatic_payment_method').change(function () {
        var id = $(this).attr('id');
        if (id !== undefined) {
            var terget_id = 'gateway_charge' + id.split('payment_method')[1];
            if ($(this).is(':checked') && $('#payment_gateway_charge').prop('checked')) {
                $('#' + terget_id).closest('tr').show();
            } else {
                $('#' + terget_id).closest('tr').hide();
            }
        }
    }).change();
    
    // For color palet
    $('#vendor_color_scheme_picker input[type=radio]').on('change', function (){
        $('#vendor_color_scheme_picker .color-option').removeClass('selected');
        $(this).closest('div').addClass('selected');
    });
    // end
    
    // Vendor Management Tab
    $("#vendor_preview_tabs").tabs();

    var getHasLoc;
    
    $('body').on("click", "#vendor_preview_tabs a.ui-tabs-anchor", function(e) {
        location.hash = '/' + $(this).attr("id");        
    });
    if (location.hash) {
        getHasLoc = location.hash.replace('#/', '');        
        $("#vendor_preview_tabs a[id='" + getHasLoc + "']").click();
    }
    
    // Disable buttons for application archive tab
    $('#vendor_preview_tabs').click('#vendor-application', function (event, ui) {
            $vendor_type = $("#vendor-application").data( 'vendor-type' );
            if($vendor_type) {
                var selectedTabIndex= $("#vendor_preview_tabs").tabs('option', 'active');
                if(selectedTabIndex == 4) $("#wc-backbone-modal-dialog").hide();
                else $("#wc-backbone-modal-dialog").show();
            }
    });
    
    $('#vendor_payment_mode').on('change', function () {
        $('.payment-gateway').hide();
        $('.payment-gateway-' + $(this).val()).show();
    }).change();
    
    $('.vendor-preview').click(function() {
        var $previewButton    = $( this ),
            $vendor_id         = $previewButton.data( 'vendor-id' );
            
        if ( $previewButton.data( 'vendor-data' ) ) {
            $( this ).WCBackboneModal({
                template: 'wcmp-modal-view-vendor',
                variable : $previewButton.data( 'vendor-data' )
            });
        } else {
            $previewButton.addClass( 'disabled' );

            $.ajax({
                url:     wcmp_admin_js_script_data.ajax_url,
                data:    {
                    vendor_id: $vendor_id,
                    action  : 'wcmp_get_vendor_details',
                    nonce: wcmp_admin_js_script_data.vendors_nonce
                },
                type:    'GET',
                success: function( response ) {
                    $( '.vendor-preview' ).removeClass( 'disabled' );
                    if ( response.success ) {
                        $previewButton.data( 'vendor-data', response.data );

                        $( this ).WCBackboneModal({
                            template: 'wcmp-modal-view-vendor',
                            variable : response.data
                        });
                    }
                }
            });
        }
        return false;
    });
    
    $( document.body ).on('click', '#wc-backbone-modal-dialog .wcmp-action-button', function(e) {
        e.preventDefault();
        $('.wcmp-loader').html('<span class="dashicons dashicons-image-rotate"></span>');
        var $actionButton    = $( this ),
            $vendor_id       = $actionButton.data( 'vendor-id' ),
            $vendor_action   = $actionButton.data( 'ajax-action' );
            $pending_vendor_note = $actionButton.closest( '.wcmp-vendor-modal-main' ).find( '.pending-vendor-note' ).val();
            $note_author_id = $actionButton.closest( '.wcmp-vendor-modal-main' ).find( '.pending-vendor-note' ).data( 'note-author-id' );
            
        if(typeof($vendor_id) != "undefined" && $vendor_id !== null && $vendor_id > 0) {
            $.ajax({
                url:  wcmp_admin_js_script_data.ajax_url,
                data: {
                    user_id: $vendor_id,
                    action : $vendor_action,
                    redirect: true,
                    custom_note: $pending_vendor_note,
                    note_by: $note_author_id
                },
                type: 'POST',
                success: function( response ) {
                    $('.wcmp-loader').html('');
                    if(response.redirect) window.location = response.redirect_url;
                }
            });
        }
    });
});