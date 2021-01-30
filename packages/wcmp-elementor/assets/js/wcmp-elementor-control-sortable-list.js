( function ( $ ) {
    var SortableListRowView = elementor.modules.controls.RepeaterRow.extend( {
        template: Marionette.TemplateCache.get( '#tmpl-elementor-sortable-list-row' ),

        setTitle: function() {
            this.ui.itemTitle.html( this.model.get( 'title' ) );
        },
    } );

    var ControlSortableListView = elementor.modules.controls.Repeater.extend( {
        childView: SortableListRowView,

        ui: {
            fieldContainer: '.elementor-repeater-fields-wrapper',
            itemToggler: '.elementor-switch-input',
        },

        events: function() {
            return {
                'sortstart @ui.fieldContainer': 'onSortStart',
                'sortupdate @ui.fieldContainer': 'onSortUpdate',
                'sortstop @ui.fieldContainer': 'onSortStop',
                'change @ui.itemToggler': 'toggleItem',
            };
        },

        toggleItem: function( event ) {
            // finding index should be easier than this :(
            var input = $( event.target ),
                parent = input.parents( '.elementor-repeater-fields' ),
                index = parent.parent().children().index( parent.get(0) ),
                item = this.collection.at( index );

            item.set( 'show', ! item.get( 'show' ) );
        }
    } );

    elementor.addControlView( 'sortable_list', ControlSortableListView );
} )( jQuery );

