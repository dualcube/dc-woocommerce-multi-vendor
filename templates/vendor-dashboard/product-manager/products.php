<?php
/*
 * The template for displaying vendor products
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/product-manager/products.php
 *
 * @author 	WC Marketplace
 * @package 	WCMp/Templates
 * @version   3.0.0
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
global $WCMp, $wpdb;
$vendor = get_wcmp_vendor(get_current_vendor_id());
do_action('before_wcmp_vendor_dashboard_product_list_table');
?>
<div class="col-md-12 all-products-wrapper">
    <div class="panel panel-default panel-pading">
        <div class="product-list-filter-wrap">
            <div class="form-group">
                <div class="product_filters pull-left">
                    <?php
                    $statuses = apply_filters('wcmp_vendor_dashboard_product_list_filters_status', array(
                        'all' => __('All', 'dc-woocommerce-multi-vendor'),
                        'publish' => __('Published', 'dc-woocommerce-multi-vendor'),
                        'pending' => __('Pending', 'dc-woocommerce-multi-vendor'),
                        'draft' => __('Draft', 'dc-woocommerce-multi-vendor'),
                        'trash' => __('Trash', 'dc-woocommerce-multi-vendor')
                    ));
                    $current_status = isset($_GET['post_status']) ? $_GET['post_status'] : 'all';
                    echo '<ul class="subsubsub by_status nav nav-pills category-filter-nav">';
                    //$array_keys = array_keys($statuses);
                    foreach ($statuses as $key => $label) {
                        if($key == 'all'){
                            $where = "AND ({$wpdb->prefix}posts.post_status = 'publish' OR {$wpdb->prefix}posts.post_status = 'draft' OR {$wpdb->prefix}posts.post_status = 'pending')";
                            $count_pros = count( $vendor->get_products_ids( array( 'where' => $where ) ) );
                        }else{
                            $count_pros = count( $vendor->get_products_ids( array( 'where' => "AND {$wpdb->prefix}posts.post_status = '$key' " ) ) );
                        }
                        if($count_pros){
                            echo '<li><a href="' . add_query_arg(array('post_status' => sanitize_title($key)), wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_products_endpoint', 'vendor', 'general', 'products'))) . '" class="' . ( $current_status == $key ? 'current' : '' ) . '">' . $label .' ( <span id="count-'.$key.'" data-status="'.$key.'" data-count="'.$count_pros.'">'. $count_pros .'</span> ) </a></li>';
                        }
                    }
                    echo '</ul><br class="clear" />';
                    ?>
                </div>
                <div class="product_search pull-right">
                    <input type="text" class="pro_search_key no_input form-control inline-input" id="pro_search_key" name="search_keyword" />
                    <button class="wcmp_black_btn btn btn-secondary" type="button" id="pro_search_btn"><?php _e('Search', 'dc-woocommerce-multi-vendor'); ?></button>
                </div>
            </div>
        </div>
        <form method="post" name="wcmp_product_list_form" id="wcmp_product_list_form">
        <div class="product-filter-actions">
            <div class="alignleft actions">
                <?php $pro_bulk_actions = apply_filters( 'wcmp_product_list_bulk_actions', array(
                    'trash' => __('Move to trash', 'dc-woocommerce-multi-vendor'),
                    'untrash' => __('Restore', 'dc-woocommerce-multi-vendor'),
                    'delete' => __('Delete Permanently', 'dc-woocommerce-multi-vendor'),
                ));
                // Filter bulk actions according to post status
                if(isset($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'trash'){
                    if(isset($pro_bulk_actions['trash'])) unset($pro_bulk_actions['trash']);
                }else{
                    if(isset($pro_bulk_actions['untrash'])) { 
                        unset($pro_bulk_actions['untrash']);
                        unset($pro_bulk_actions['delete']);
                    }
                }
                ?>
                <select id="product_bulk_actions" name="bulk_action" class="wcmp-filter-dtdd wcmp_product_bulk_actions form-control inline-input">
                    <option value=""><?php _e('Bulk Actions', 'dc-woocommerce-multi-vendor'); ?></option>
                    <?php 
                    if($pro_bulk_actions) :
                        foreach ($pro_bulk_actions as $key => $label) {
                            echo '<option value="'.$key.'">'.$label.'</option>';
                        }
                    endif;
                    ?>
                </select>
                <button class="wcmp_black_btn btn btn-secondary" type="button" id="product_list_do_bulk_action"><?php _e('Apply', 'dc-woocommerce-multi-vendor'); ?></button>
                <select id="product_cat" name="product_cat" class="wcmp-filter-dtdd wcmp_filter_product_cat form-control inline-input">
                    <option value=""><?php _e('Select a Category', 'dc-woocommerce-multi-vendor'); ?></option>
                    <?php 
                    $product_taxonomy_terms = get_terms('product_cat', 'orderby=name&hide_empty=0&parent=0');
                    if ($product_taxonomy_terms) {
                        WCMpGenerateTaxonomyHTML('product_cat', $product_taxonomy_terms, array());
                    }
                    ?>
                </select>
                <select id="product_types" name="product_type" class="wcmp-filter-dtdd wcmp_filter_product_types form-control inline-input">
                    <option value=""><?php _e('Filter by product type', 'dc-woocommerce-multi-vendor'); ?></option>
                    <?php 
                    $product_types = wcmp_get_available_product_types();
                    if($product_types) :
                        foreach ($product_types as $key => $label) {
                            if(in_array($key, array( 'virtual', 'downloadable'))) continue;
                            echo '<option value="'.$key.'">'.$label.'</option>';
                            if ( 'simple' === $key ) {
                                if(array_key_exists('downloadable', $product_types))
                                        echo '<option value="downloadable">' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . $product_types['downloadable'].'</option>';
                                if(array_key_exists('virtual', $product_types))
                                        echo '<option value="virtual">' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . $product_types['virtual'].'</option>';
                            }
                        }
                    endif;
                    ?>
                </select>
                <?php do_action( 'wcmp_products_list_add_extra_filters' ); ?>
                <button class="wcmp_black_btn btn btn-secondary" type="button" id="product_list_do_filter"><?php _e('Filter', 'dc-woocommerce-multi-vendor'); ?></button>
            </div>
        </div>
            
        <table id="product_table" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead><tr>
            <?php
            if ($products_table_headers) {
                foreach ($products_table_headers as $key => $value) {
                    if($key == 'select_product'){ ?>
                        <th class="text-center" data-lable="<?php echo $key ?>"><input type="checkbox" class="select_all_all" onchange="toggleAllCheckBox(this, 'product_table');" /></th>
                    <?php }else{ ?>
                        <th data-lable="<?php echo $key ?>"><?php echo $value ?></th>
                    <?php }
                }
            }
            ?>
            </tr></thead>
        </table>
        <div class="wcmp-action-container">
            <?php do_action('before_wcmp_vendor_dash_product_list_page_header_action_btn'); ?>
            <a href="<?php echo apply_filters('wcmp_vendor_dashboard_add_product_url', wcmp_get_vendor_dashboard_endpoint_url(get_wcmp_vendor_settings('wcmp_add_product_endpoint', 'vendor', 'general', 'add-product')));?>" class="btn btn-default"><i class="wcmp-font ico-add-booking"></i><?php echo __('Add Product', 'dc-woocommerce-multi-vendor');?></a>
            <?php do_action('after_wcmp_vendor_dash_product_list_page_header_action_btn'); ?>
        </div>
        </form>
    </div>
</div>
<?php do_action('after_wcmp_vendor_dashboard_product_list_table'); ?>
<script>
    jQuery(document).ready(function ($) { 
        var product_table;
        var columns = [];
        //var filter_by_category_list = [];
<?php
if ($products_table_headers) {
    $enable_ordering = apply_filters('wcmp_vendor_dashboard_product_list_table_orderable_columns', array('name', 'date'));
    foreach ($products_table_headers as $key => $value) {
        $orderable = 'false';
        if (in_array($key, $enable_ordering)) {
            $orderable = 'true';
        }
        if($key == 'select_product') $orderable = 'false';
        ?>
                obj = {};
                obj['data'] = '<?php echo $key; ?>';
                obj['className'] = '<?php echo $key; ?>';
                obj['orderable'] = <?php echo $orderable; ?>;
                columns.push(obj);
    <?php
    }
}

?>
        product_table = $('#product_table').DataTable({
            'ordering': <?php echo isset($table_init['ordering']) ? trim($table_init['ordering']) : 'true'; ?>,
            'searching': <?php echo isset($table_init['searching']) ? trim($table_init['searching']) : 'true'; ?>,
            "processing": true,
            "serverSide": true,
            "lengthChange": false,
            "responsive": true,
            "language": {
                "emptyTable": "<?php echo isset($table_init['emptyTable']) ? trim($table_init['emptyTable']) : __('No products found!', 'dc-woocommerce-multi-vendor'); ?>",
                "processing": "<?php echo isset($table_init['processing']) ? trim($table_init['processing']) : __('Processing...', 'dc-woocommerce-multi-vendor'); ?>",
                "info": "<?php echo isset($table_init['info']) ? trim($table_init['info']) : __('Showing _START_ to _END_ of _TOTAL_ products', 'dc-woocommerce-multi-vendor'); ?>",
                "infoEmpty": "<?php echo isset($table_init['infoEmpty']) ? trim($table_init['infoEmpty']) : __('Showing 0 to 0 of 0 products', 'dc-woocommerce-multi-vendor'); ?>",
                "lengthMenu": "<?php echo isset($table_init['lengthMenu']) ? trim($table_init['lengthMenu']) : __('Number of rows _MENU_', 'dc-woocommerce-multi-vendor'); ?>",
                "zeroRecords": "<?php echo isset($table_init['zeroRecords']) ? trim($table_init['zeroRecords']) : __('No matching products found', 'dc-woocommerce-multi-vendor'); ?>",
                "search": "<?php echo isset($table_init['search']) ? trim($table_init['search']) : __('Search:', 'dc-woocommerce-multi-vendor'); ?>",
                "paginate": {
                    "next": "<?php echo isset($table_init['next']) ? trim($table_init['next']) : __('Next', 'dc-woocommerce-multi-vendor'); ?>",
                    "previous": "<?php echo isset($table_init['previous']) ? trim($table_init['previous']) : __('Previous', 'dc-woocommerce-multi-vendor'); ?>"
                },
            },
            "drawCallback": function(settings){
                //$( "#product_cat" ).detach();
                $('thead tr th.select_product').removeClass('sorting_asc');
                $('thead tr th.image').removeClass('sorting_asc');
//                var product_cat_sel = $('<select id="product_cat" class="wcmp-filter-dtdd wcmp_filter_product_cat form-control">').appendTo("#product_table_length");
//                product_cat_sel.append($("<option>").attr('value', '').text('<?php echo trim(__('Select a Category', 'dc-woocommerce-multi-vendor')); ?>'));
//                $(filter_by_category_list).each(function () {
//                    product_cat_sel.append($("<option>").attr('value', this.key).text(this.label));
//                });
//                if(settings.oAjaxData.product_cat){
//                    product_cat_sel.val(settings.oAjaxData.product_cat);
//                }
                if(settings.json.notices.length > 0 ){
                    $('.wcmp-wrapper .notice-wrapper').html('');
                    $.each(settings.json.notices, function( index, notice ) {
                        if(notice.type == 'success'){
                            $('.wcmp-wrapper .notice-wrapper').append('<div class="woocommerce-message" role="alert">'+notice.message+'</div>');
                        }else{
                            $('.wcmp-wrapper .notice-wrapper').append('<div class="woocommerce-error" role="alert">'+notice.message+'</div>');
                        }
                    });
                }
            },
            "ajax": {
                url: '<?php echo add_query_arg( 'action', 'wcmp_vendor_product_list', $WCMp->ajax_url() ); ?>',
                type: "post",
                data: function (data) {
                    data.products_filter_action = $('form#wcmp_product_list_form').serialize();
                    data.post_status = "<?php echo isset($_GET['post_status']) ? trim($_GET['post_status']) : 'all' ?>";
                    data.product_cat = $('#product_cat').val();
                    data.bulk_action = $('#product_bulk_actions').val();
                    data.search_keyword = $('#pro_search_key').val();
                },
                error: function(xhr, status, error) {
                    $("#product_table tbody").append('<tr class="odd"><td valign="top" colspan="<?php echo count($products_table_headers); ?>" class="dataTables_empty" style="text-align:center;">'+error+' - <a href="javascript:window.location.reload();"><?php _e('Reload', 'dc-woocommerce-multi-vendor'); ?></a></td></tr>');
                    $("#product_table_processing").css("display","none");
                }
            },
            "columns": columns,
            "createdRow": function (row, data, index) {
                $(row).addClass('vendor-product');
            }
        });
        new $.fn.dataTable.FixedHeader( product_table );
//        $(document).on('change', '#product_cat', function () {
//            product_table.ajax.reload();
//        });
        $(document).on('click', '#pro_search_btn', function () {
            product_table.ajax.reload();
        });
        $(document).on('click', '#product_list_do_filter', function (e) {
            product_table.ajax.reload();
        });
        $(document).on('click', '#product_list_do_bulk_action', function (e) {
            product_table.ajax.reload();
        });
    });
</script>