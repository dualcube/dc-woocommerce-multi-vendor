<?php
/**
 * The template for displaying vendor transaction details
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-transaction_detail.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
global $WCMp;
$transactions_list_table_headers = apply_filters('wcmp_datatable_vendor_transactions_list_table_headers', array(
    'select_transaction'  => array('label' => '', 'class' => 'text-center'),
    'date'      => array('label' => __( 'Date', 'dc-woocommerce-multi-vendor' )),
    'order_id'      => array('label' => __( 'Order IDs', 'dc-woocommerce-multi-vendor' )),
    'transaction_id'    => array('label' => __( 'Transc.ID', 'dc-woocommerce-multi-vendor' )),
    'commission_ids'=> array('label' => __( 'Commission IDs', 'dc-woocommerce-multi-vendor' )),
    'fees'  => array('label' => __( 'Fee', 'dc-woocommerce-multi-vendor' )),
    'net_earning'        => array('label' => __( 'Net Earnings', 'dc-woocommerce-multi-vendor' )),
), get_current_user_id());
?>
<div class="col-md-12">
    
    <div class="panel panel-default">
        <div class="panel-body">
            <div id="vendor_transactions_date_filter" class="form-inline datatable-date-filder">
                <div class="form-group">
                    <span class="date-inp-wrap">
                        <input id="wcmp_from_date" class="form-control" name="from_date" class="pickdate gap1" placeholder="From" value ="<?php echo date('Y-m-01'); ?>"/>
                    </span>
                </div>
                <div class="form-group">
                    <span class="date-inp-wrap">
                        <input id="wcmp_to_date" class="form-control" name="to_date" class="pickdate" placeholder="To" value ="<?php echo   date('Y-m-d'); ?>"/>
                    </span>
                </div>
                <button type="button" name="order_export_submit" id="do_filter"  class="btn btn-default" ><?php _e('Show', 'dc-woocommerce-multi-vendor') ?></button>
            </div>  
            <form method="post" name="export_transaction">
                <div class="wcmp_table_holder">
                    <table id="vendor_transactions" class="get_wcmp_transactions table table-striped table-bordered" width="100%">
                        <thead>
                            <tr>
                            <?php 
                                if($transactions_list_table_headers) :
                                    foreach ($transactions_list_table_headers as $key => $header) {
                                        if($key == 'select_transaction'){ ?>
                                <th class="<?php if(isset($header['class'])) echo $header['class']; ?>"><input type="checkbox" class="select_all_transaction" onchange="toggleAllCheckBox(this, 'vendor_transactions');" /></th>
                                    <?php }else{ ?>
                                <th class="<?php if(isset($header['class'])) echo $header['class']; ?>"><?php if(isset($header['label'])) echo $header['label']; ?></th>         
                                    <?php }
                                    }
                                endif;
                            ?>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
                <div id="export_transaction_wrap" class="wcmp-action-container wcmp_table_loader" style="display: none;">
                    <input type="hidden" id="export_transaction_start_date" name="from_date" value="<?php echo date('Y-m-01'); ?>" />
                    <input id="export_transaction_end_date" type="hidden" name="to_date" value="<?php echo date('Y-m-d'); ?>" />
                    <button type="submit" name="export_transaction" class="btn btn-default"><?php _e('Download CSV', 'dc-woocommerce-multi-vendor'); ?></button>
                    <div class="clear"></div>
                </div>
            </form>
        </div>
    </div>  
</div>
<script>
jQuery(document).ready(function($) {
    $( "#wcmp_from_date" ).datepicker({ 
        dateFormat: 'yy-mm-dd',
        onClose: function (selectedDate) {
            $("#wcmp_to_date").datepicker("option", "minDate", selectedDate);
        }
    });
    $( "#wcmp_to_date" ).datepicker({ 
        dateFormat: 'yy-mm-dd',
        onClose: function (selectedDate) {
            $("#wcmp_from_date").datepicker("option", "maxDate", selectedDate);
        }
    });
    var vendor_transactions;
    var columns = [];
    <?php if($transactions_list_table_headers) {
     foreach ($transactions_list_table_headers as $key => $header) { ?>
        obj = {};
        obj['data'] = '<?php echo esc_js($key); ?>';
        obj['className'] = '<?php if(isset($header['class'])) echo esc_js($header['class']); ?>';
        columns.push(obj);
     <?php }
        } ?>
    vendor_transactions = $('#vendor_transactions').DataTable({
        ordering  : false,
        searching  : false,
        processing: true,
        serverSide: true,
        responsive: true,
        language: {
            "emptyTable": "<?php echo trim(__('Sorry. No transactions are available.','dc-woocommerce-multi-vendor')); ?>",
            "processing": "<?php echo trim(__('Processing...', 'dc-woocommerce-multi-vendor')); ?>",
            "info": "<?php echo trim(__('Showing _START_ to _END_ of _TOTAL_ transactions','dc-woocommerce-multi-vendor')); ?>",
            "infoEmpty": "<?php echo trim(__('Showing 0 to 0 of 0 transactions','dc-woocommerce-multi-vendor')); ?>",
            "lengthMenu": "<?php echo trim(__('Number of rows _MENU_','dc-woocommerce-multi-vendor')); ?>",
            "zeroRecords": "<?php echo trim(__('No matching transactions found','dc-woocommerce-multi-vendor')); ?>",
            "search": "<?php echo trim(__('Search:','dc-woocommerce-multi-vendor')); ?>",
            "paginate": {
                "next":  "<?php echo trim(__('Next','dc-woocommerce-multi-vendor')); ?>",
                "previous":  "<?php echo trim(__('Previous','dc-woocommerce-multi-vendor')); ?>"
            }
        },
        initComplete: function (settings, json) {
            var info = this.api().page.info();
            if (info.recordsTotal > 0) {
                $('#export_transaction_wrap').show();
            }
            $('#display_trans_from_dt').text($('#wcmp_from_date').val());
            $('#export_transaction_start_date').val($('#wcmp_from_date').val());
            $('#display_trans_to_dt').text($('#wcmp_to_date').val());
            $('#export_transaction_end_date').val($('#wcmp_to_date').val());
        },
        ajax:{
            url : '<?php echo add_query_arg( 'action', 'wcmp_vendor_transactions_list', $WCMp->ajax_url() ); ?>', 
            type: "post",
            data: function (data) {
                data.from_date = $('#wcmp_from_date').val();
                data.to_date = $('#wcmp_to_date').val();
            },
            error: function(xhr, status, error) {
                $("#vendor_transactions tbody").append('<tr class="odd"><td valign="top" colspan="6" class="dataTables_empty" style="text-align:center;">'+error+' - <a href="javascript:window.location.reload();"><?php _e('Reload', 'dc-woocommerce-multi-vendor'); ?></a></td></tr>');
                $("#vendor_transactions_processing").css("display","none");
            }
        },
        columns: columns
    });
    new $.fn.dataTable.FixedHeader( vendor_transactions );
    $(document).on('click', '#vendor_transactions_date_filter #do_filter', function () {
        $('#display_trans_from_dt').text($('#wcmp_from_date').val());
        $('#export_transaction_start_date').val($('#wcmp_from_date').val());
        $('#display_trans_to_dt').text($('#wcmp_to_date').val());
        $('#export_transaction_end_date').val($('#wcmp_to_date').val());
        vendor_transactions.ajax.reload();
    });
});
</script>