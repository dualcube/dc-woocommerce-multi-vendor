<?php
/*
 * The template for displaying vendor products Q&As
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-products-qna.php
 *
 * @author 	WC Marketplace
 * @package 	WCMp/Templates
 * @version   3.0.0
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
global $WCMp;
$vendor = get_wcmp_vendor(get_current_vendor_id());
do_action('before_wcmp_vendor_dashboard_products_qna_table');
?>
<div class="col-md-12 vendor-products-qna-wrapper">
    <div class="panel panel-default panel-pading">
        <div class="vendor-products-qna-filters form-inline" style="float: right;">
            <div class="form-group">
                <select id="show_qna_by_products" name="show_qna_by_products[]" class="form-control regular-select " multiple="multiple">
                    <?php
                    if ($vendor->get_products_ids()){
                        foreach ($vendor->get_products_ids() as $product) {
                            $product = wc_get_product($product->ID);
                            echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_title()) . '</option>';
                        }
                    } ?>
                </select>
            </div>
            <button id="show_qna_by_products_btn" class="wcmp_black_btn btn btn-default" type="button" name="show_qna_by_products_btn"><?php _e('Show', 'dc-woocommerce-multi-vendor'); ?></button>
        </div>
        <table id="vendor_products_qna_table" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th><?php _e('Customer questions', 'dc-woocommerce-multi-vendor'); ?></th>
                    <th><?php _e('Product', 'dc-woocommerce-multi-vendor'); ?></th>
                    <th><?php _e('Date', 'dc-woocommerce-multi-vendor'); ?></th>
                    <th><?php _e('Vote', 'dc-woocommerce-multi-vendor'); ?></th>
                    <th><?php _e('Status', 'dc-woocommerce-multi-vendor'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div class="wcmp-action-container">
            <!--a href="<?php echo wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_add_coupon_endpoint', 'vendor', 'general', 'add-product'));?>" class="btn btn-default"><?php echo __('Add Product', 'dc-woocommerce-multi-vendor');?></a-->
        </div>
    </div>
</div>
<?php do_action('after_wcmp_vendor_dashboard_products_qna_table'); ?>
<script>
    jQuery(document).ready(function ($) {
        var statuses = [];
        <?php 
        $filter_by_status = apply_filters('wcmp_vendor_dashboard_order_filter_status_arr',array(
            'unanswer' => __('Unanswered', 'dc-woocommerce-multi-vendor'),
            'all' => __('All Q&As', 'dc-woocommerce-multi-vendor')
        ));
        foreach ($filter_by_status as $key => $label) { ?>
            obj = {};
            obj['key'] = "<?php echo trim($key); ?>";
            obj['label'] = "<?php echo trim($label); ?>";
            statuses.push(obj);
        <?php } ?>
        qna_table = $('#vendor_products_qna_table').DataTable({
            ordering: true,
            searching: false,
            processing: true,
            serverSide: true,
            responsive: true,
            language: {
                emptyTable: "<?php echo trim(__('No customer questions found!', 'dc-woocommerce-multi-vendor')); ?>",
                processing: "<?php echo trim(__('Processing...', 'dc-woocommerce-multi-vendor')); ?>",
                info: "<?php echo trim(__('Showing _START_ to _END_ of _TOTAL_ questions', 'dc-woocommerce-multi-vendor')); ?>",
                infoEmpty: "<?php echo trim(__('Showing 0 to 0 of 0 questions', 'dc-woocommerce-multi-vendor')); ?>",
                lengthMenu: "<?php echo trim(__('Number of rows _MENU_', 'dc-woocommerce-multi-vendor')); ?>",
                zeroRecords: "<?php echo trim(__('No matching customer questions found', 'dc-woocommerce-multi-vendor')); ?>",
                search: "<?php echo trim(__('Search:', 'dc-woocommerce-multi-vendor')); ?>",
                paginate: {
                    next: "<?php echo trim(__('Next', 'dc-woocommerce-multi-vendor')); ?>",
                    previous: "<?php echo trim(__('Previous', 'dc-woocommerce-multi-vendor')); ?>"
                }
            },
            drawCallback: function(settings){
                $( "#filter_by_qna_status" ).detach();
                $('thead tr th.cust_qnas').removeClass('sorting_asc');
                var qna_status_sel = $('<select id="filter_by_qna_status" class="wcmp-filter-dtdd wcmp_filter_qna_status form-control">').appendTo("#vendor_products_qna_table_length");
                $(statuses).each(function () {
                    qna_status_sel.append($("<option>").attr('value', this.key).text(this.label));
                });
                if(settings.oAjaxData.qna_status){
                    qna_status_sel.val(settings.oAjaxData.qna_status);
                }
            },
            ajax: {
                url: '<?php echo add_query_arg( 'action', 'wcmp_vendor_products_qna_list', $WCMp->ajax_url() ); ?>',
                type: "post",
                data: function (data) {
                    data.qna_status = $('#filter_by_qna_status').val();
                    data.qna_products = $('#show_qna_by_products').val();
                },
                error: function(xhr, status, error) {
                    $("#vendor_products_qna_table tbody").append('<tr class="odd"><td valign="top" colspan="5" class="dataTables_empty" style="text-align:center;">'+error+' - <a href="javascript:window.location.reload();"><?php _e('Reload', 'dc-woocommerce-multi-vendor'); ?></a></td></tr>');
                    $("#vendor_products_qna_table_processing").css("display","none");
                }
            },
            columns: [
                {data: 'qnas', orderable:false, className: 'cust_qnas'},
                {data: 'product', orderable:false},
                {data: 'date', orderable:false},
                {data: 'vote', orderable:true},
                {data: 'status', orderable:false}
            ],
            "createdRow": function (row, data, index) {
                //$(row).addClass('vendor-product');
            }
        });
        new $.fn.dataTable.FixedHeader( qna_table );
        $(document).on('change', '#filter_by_qna_status', function () {
            qna_table.ajax.reload();
        });
        $(document).on('click', '#show_qna_by_products_btn', function () {
            qna_table.ajax.reload();
        });
        $("#show_qna_by_products").select2({
            placeholder: '<?php echo trim(__('Choose product...', 'dc-woocommerce-multi-vendor'));?>'
        });
    });

</script>