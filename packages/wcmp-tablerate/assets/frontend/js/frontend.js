jQuery(document).ready(function($) {
    $rates_table = $( '.wcmp_table_rate_shipping ' ),
	$rates = $rates_table.find( 'tbody.table_rates' );
	
	$rates_table.on( 'change', 'input[name$="rate_abort]"]', onShippingAbortChange );
	
	function onShippingAbortChange() {
		var checked = this.checked;
		var $row 	= $( this ).closest( 'tr' );
		
		if ( checked ) {
			$row.find('td.cost').hide();
			$row.find('td.abort_reason').show();
			$row.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').prop( 'disabled', true ).addClass( 'disabled' );
		} else {
			$row.find('td.cost').show();
			$row.find('td.abort_reason').hide();
			$row.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').prop( 'disabled', false ).removeClass( 'disabled' );
		}

		$( '#woocommerce_table_rate_calculation_type' ).change();
	}
	
});
