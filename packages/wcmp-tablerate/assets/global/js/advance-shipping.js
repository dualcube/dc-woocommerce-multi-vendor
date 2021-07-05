/* global wcmp_advanced_shipping_script_data */
jQuery(document).ready(function ($) { 
    $(document).on('click', '.wcmp_add_tablerate_item', function () { 
        var itemCount = $(this).closest('table').find('tbody tr').length;
        var item = $(this).closest('table').find('tbody tr:last').clone();
        item.find('input, select').each(function (index, element) {
            var elm = $(this);
            elm.val('');
            elm.prop('checked', false);
            if (elm.data('name') === 'rate_min' || elm.data('name') === 'rate_max') {
                elm.attr('disabled', 'disabled');
            }
            var name = 'wcmp_table_rate[' + itemCount + '][' + elm.data('name') + ']';
            elm.attr('name', name);
        });
        $(this).closest('table').find('tbody').append(item);
    });

    $(document).on('click', '.wcmp_remove_table_rate_item', function () { 
        var checkedRate = [];
        $('.wcmp_table_rate_shipping .table-rate-item-select input[type=checkbox]').each(function () {
            if ($(this).is(':checked') ) {
                checkedRate.push($(this).val());
            }
        });

        if (checkedRate.length > 0) {
            var data = {
                action: 'delete_table_rate_shipping_row',
                rate_id: checkedRate
            };
            $.post(wcmp_advanced_shipping_script_data.ajax_url, data, function (response) {
                window.location.reload();
            });
        }
    });
});

function toggleDisableRate(element) {
    var self = jQuery(element);
    if (self.val() == '') {
        self.parents().eq(1).children().eq(2).find('input').attr('disabled', 'disabled');
        self.parents().eq(1).children().eq(3).find('input').attr('disabled', 'disabled');
    } else {
        self.parents().eq(1).children().eq(2).find('input').removeAttr('disabled');
        self.parents().eq(1).children().eq(3).find('input').removeAttr('disabled');
    }
}

