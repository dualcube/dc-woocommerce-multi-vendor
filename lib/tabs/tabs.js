'use strict';
var scrollTab = ( function ( $, W ) {
    var _$tabs = null,
        _$wrapper = null,
        _$navs = null,
        _wrapBoundary = null, // {left:num,right:num}
        _tabUI = null, // tabs visibility and elem <li> [{active:bool,index:num,item:this},...]
        _tabWidth = null, // tabs width [{width:num},...]
        _leftMargin = 0,
        _maxLeftMargin = 0;
    var tabs = {
        activeIndex: function activeIndex( el ) {
            // return index of tab with '.active' class 
            return this.findActiveClassIndex( el );
        },
        findActiveClassIndex: function findActiveClassIndex( el ) {
            //if first item in the list has class active in it, return its index
            if ( el[0].item.classList.contains( 'active' ) )
                return el[0].index;
            //if this is the last item, return -1
            if ( el.length === 1 )
                return -1;
            //shift one item from the begining and call self
            return findActiveClassIndex( el.slice( 1 ) );
        },
        firstVisibleIndex: function firstVisibleIndex( el ) {
            //return first visible element (li) index
            return this.findIndex( el );
        },
        lastVisibleIndex: function lastVisibleIndex( el ) {
            //reverse the tabElems array and find first visible elem index
            return this.findIndex( el.slice().reverse() );
        },
        findIndex: function findIndex( el ) {
            //find the first visible element index and return the actual tab index
            var index = el.findIndex( this.isVisible );
            if ( ~index ) {
                return el[ index ].index;
            }
            return -1;
        },
        isVisible: function isVisible( tab ) {
            return ( typeof tab.active !== 'undefined' ) ? tab.active : false;
        },
        prevVisibleIndex: function prevVisibleIndex( index, el ) {
            return this.findIndex( el.slice( 0, index ).reverse() );
        },
        nextVisibleIndex: function nextVisibleIndex( index, el ) {
            return this.findIndex( el.slice( index + 1 ) );
        },
        getWidth: function getWidth( el, tWidth, offset ) {
            var start = typeof offset === 'undefined' ? 0 : offset;
            return el.reduce( function ( width, li ) {
                return li.active ? ( width + tWidth[li.index].width ) : width;
            }, start );
        },
        getFullWidth: function getFullWidth( tWidth, offset ) {
            var start = typeof offset === 'undefined' ? 0 : offset;
            return tWidth.reduce( function ( width, li ) {
                return width + li.width;
            }, start );
        }
    };
    var privateApi = {
        init: function init( $tabUl ) {    // run once
            var domElems = this.cacheDom( $tabUl );
            //set timeout helps overcoming a firefox issue where the tabs initailly has a smaller size and after full document load it gets the actual size
            window.setTimeout( this.setupEnvironment( domElems ), 30 );
            return this;
        },
        cacheDom: function cacheDom( $tabUl ) {
            var $tabs = jQuery( $tabUl ),
                $wrapper = $tabs.closest( 'div' ),
                $navs = $wrapper.find( '.tab-nav-direction-wrapper' );
            return { tabs: $tabs, wrap: $wrapper, navs: $navs };
        },
        setupEnvironment: function setupEnvironment( domElems ) {
            // UI elems caching
            _$tabs = domElems.tabs;
            _$wrapper = domElems.wrap;
            _$navs = domElems.navs;
            // state vars
            var states = privateApi.initState( _$tabs, _$wrapper );
            _wrapBoundary = states.boundary;
            _tabUI = states.elem;
            _tabWidth = states.width;
            //update tab <ul> width style property
            var fullWidth = tabs.getFullWidth( _tabWidth ); //all tabs combined width as if all tabs are visible
            privateApi.setTabCssWidth( _$tabs, fullWidth );
            //max left margin value change
            var width = tabs.getWidth( _tabUI, _tabWidth ); //only visible tabs combined width
            _maxLeftMargin = _wrapBoundary.right - _wrapBoundary.left - width;
            // add nav components- prev, next
            // privateApi.addNavComponents( _$navs );
            //enable or disable prev and next button for first and last item in the list
            privateApi.checkNavComponents();
            // add event listners
            this.addEventListners( _$tabs, _$navs );

            return false;
        },
        initState: function initState( $tabs, $wrapper ) {
            var boundary = $wrapper[0].getBoundingClientRect( );
            var pos = { left: boundary.left, right: boundary.right };
            var tabs = this.getTabStates( $tabs );
            return { boundary: pos, elem: tabs.elem, width: tabs.width };
        },
        getTabStates: function getTabStates( $tabs ) {
            var tabUI = [ ];
            var tabWidth = [ ];
            $tabs.children().each( function ( index ) {
                var $this = $( this );
                var boundary = null;
                if ( !$this.is( ":visible" ) ) {
                    var $clone = $this.clone()
                        .show()
                        .css( 'visibility', 'hidden' )
                        .insertAfter( $this );
                    boundary = $clone[0].getBoundingClientRect();
                    $clone.remove();
                } else {
                    boundary = this.getBoundingClientRect();
                }
                tabUI[index] = {
                    index: index,
                    active: $this.is( ":visible" ),
                    item: this
                };
                tabWidth[index] = {
                    width1: $( this ).outerWidth(),
                    width: boundary.right - boundary.left
                };
            } );
            return { elem: tabUI, width: tabWidth };
        },
        setTabCssWidth: function setTabCssWidth( $tabs, width ) {
            $tabs.css( 'width', Math.ceil( width ) );
            return false;
        },
        addNavComponents: function addNavComponents( $navs ) {
            $navs.html( '<button class="nav-btn next"><i class="wcmp-font ico-right-arrow-icon"></i></button><button class="nav-btn prev"><i class="wcmp-font ico-left-arrow-icon"></i></button>' );
            return false;
        },
        checkNavComponents: function checkNavComponents() {
            var currentIndex = tabs.activeIndex( _tabUI ),
                firstIndex = tabs.firstVisibleIndex( _tabUI ),
                lastIndex = tabs.lastVisibleIndex( _tabUI );
            if ( currentIndex === firstIndex ) {
                _$navs.find( 'button.prev' ).prop( 'disabled', true );
            } else {
                _$navs.find( 'button.prev' ).prop( 'disabled', false );
            }
            if ( currentIndex === lastIndex ) {
                _$navs.find( 'button.next' ).prop( 'disabled', true );
            } else {
                _$navs.find( 'button.next' ).prop( 'disabled', false );
            }
            return false;
        },
        addEventListners: function addEventListners( $tabs, $navs ) {
            $tabs.on( 'click', '>li:visible', this.onSelect );
            $tabs.on( 'shown.bs.tab', this.afterTabShown );
            // $tabs.on( 'tab-display-updated', privateApi.updatedTabVisibility );
            $( W ).on( 'resize', privateApi.recalculatePositions );

            $navs.on( 'click', '.prev', this.navPrev.bind( this ) );
            $navs.on( 'click', '.next', this.navNext.bind( this ) );
        },
        navPrev: function navPrev() {
            var currentTab = tabs.activeIndex( _tabUI );
            var prevTab = tabs.prevVisibleIndex( currentTab, _tabUI );
            if ( ~prevTab ) {// two's complement
                this.showTab( prevTab, _tabUI );
            }
            return false;
        },
        navNext: function navNext() {
            var currentTab = tabs.activeIndex( _tabUI );
            var nextTab = tabs.nextVisibleIndex( currentTab, _tabUI );
            if ( ~nextTab ) {// two's complement
                this.showTab( nextTab, _tabUI );
            }
            return false;
        },
        onSelect: function onSelect() {
            var currentTab = $( this ).index();
            privateApi.showTab( currentTab, _tabUI );
            return false;
        },
        afterTabShown: function afterTabShown() {
            var currentTab = tabs.activeIndex( _tabUI );
            privateApi.mayAdjustOffset( currentTab ).checkNavComponents();
        },
        mayAdjustOffset: function mayAdjustOffset( target ) {
            //container width
            var boundaryWidth = _wrapBoundary.right - _wrapBoundary.left;
            //ul width
            var tabWidth = tabs.getWidth( _tabUI, _tabWidth );
            if ( tabWidth <= boundaryWidth ) {
                if ( _leftMargin !== 0 ) {
                    this.updateLeftMargin( 0 );
                }
                return this;
            }
            // Target tab
            var widthBeforeTarget = tabs.getWidth( _tabUI.slice( 0, target ), _tabWidth, _wrapBoundary.left );
            var targetLeft = widthBeforeTarget + _leftMargin;
            var targetRight = targetLeft + _tabWidth[target].width;
            // Edge cases handling introducing offset equals to prev/next element width
            var prevIndex = tabs.prevVisibleIndex( target, _tabUI ),
                nextIndex = tabs.nextVisibleIndex( target, _tabUI );
            var left = ( ~prevIndex ) ? targetLeft - _tabWidth[prevIndex].width : targetLeft;
            var right = ( ~nextIndex ) ? targetRight + _tabWidth[nextIndex].width : targetRight;

            // last visible tab
            var lastTabRight = _wrapBoundary.left + tabWidth + _leftMargin;

            if ( left >= _wrapBoundary.left && right <= _wrapBoundary.right || left <= _wrapBoundary.left && right >= _wrapBoundary.right ) { // tab within viewport or too large than the viewport
                if ( lastTabRight < _wrapBoundary.right ) {
                    this.shiftToViewport( ( lastTabRight - _wrapBoundary.right ), _leftMargin, _$tabs );
                }
                return this;
            } else if ( left < _wrapBoundary.left ) { // left portion outside viewport
                this.shiftToViewport( ( _wrapBoundary.left - left ), _leftMargin, _$tabs );
            } else { // right portion outside viewport
                this.shiftToViewport( ( _wrapBoundary.right - right + 1 ), _leftMargin, _$tabs );
            }
            return this;
        },
        shiftToViewport: function shiftToViewport( offset, leftMargin, $tabs ) {
            var lMargin = leftMargin + Number( offset );
            if ( lMargin > 0 ) {
                lMargin = 0;
            } else if ( lMargin <= _maxLeftMargin + 1 ) {
                lMargin = _maxLeftMargin;
            }
            this.updateLeftMargin( lMargin );
            return false;
        },
        updateLeftMargin: function updateLeftMargin( margin ) {
            _leftMargin = Number( margin );
            this.updateLeftMarginStyle( margin );
            return false;
        },
        updateLeftMarginStyle: function updateLeftMarginStyle( lMargin ) {
            _$tabs.css( 'margin-left', lMargin );
        },
        showTab: function showTab( index, tabElems ) {
            var $elem = $( tabElems[index].item ).find( 'a' );
            if ( typeof $elem.tab === 'function' ) {
                $elem.tab( 'show' );
            }
            return this;
        },
        updatedTabVisibility: function updatedTabVisibility() {
            var currentTab = tabs.activeIndex( _tabUI );
            _tabUI = _tabUI.map( function ( li ) {
                li.active = $( li.item ).is( ":visible" );
                return li;
            } );
            privateApi.readjustWidthMargin();
            if ( !_tabUI[currentTab].active ) { //after visibility change current tab hides
                var activeTab = tabs.nextVisibleIndex( currentTab, _tabUI );
                if ( ~activeTab ) {
                    _$navs.find( '.next' ).trigger( 'click' );
                } else {   //select prev element
                    _$navs.find( '.prev' ).trigger( 'click' );
                }
            } else {
                privateApi.mayAdjustOffset( currentTab ).checkNavComponents( );
            }
        },
        recalculatePositions: function recalculatePositions() {
            //boundary change
            var boundary = _$wrapper[0].getBoundingClientRect( );
            _wrapBoundary = { left: boundary.left, right: boundary.right };
            //<li> element width change
            var tabWidth = [ ];
            _$tabs.children().each( function ( index ) {
                var $this = $( this );
                var boundary = null;
                if ( !$this.is( ":visible" ) ) {
                    var $clone = $this.clone()
                        .show()
                        .css( 'visibility', 'hidden' )
                        .insertAfter( $this );
                    boundary = $clone[0].getBoundingClientRect();
                    $clone.remove();
                } else {
                    boundary = this.getBoundingClientRect();
                }
                tabWidth[index] = {
                    width: boundary.right - boundary.left
                };
            } );
            _tabWidth = tabWidth;
            //update tab <ul> width style property
            var fullWidth = tabs.getFullWidth( _tabWidth ); //all tabs combined width as if all tabs are visible
            privateApi.setTabCssWidth( _$tabs, fullWidth );

            privateApi.readjustWidthMargin();
        },
        readjustWidthMargin: function readjustWidthMargin() {
            //max left margin value change
            var width = tabs.getWidth( _tabUI, _tabWidth ); //only visible tabs combined width
            _maxLeftMargin = _wrapBoundary.right - _wrapBoundary.left - width;
        }
    };
    var publicApi = {
        init: function init( $tabUl ) {   // selector is the id of the UL element #product_data_tabs
            privateApi.init( $tabUl );
            return false;
        }
    };
    return publicApi;
} )( jQuery, window );
jQuery( function ( $ ) {
    $.fn.afmTabInit = function ( ) {
        // scrollTab.init( this );
        // return this;
    };
} );

// New bootstrap version responsive tabs
(function( $ ) {
    function storeTabs($tabs, $destination) {
        // measure width
        $tabs.each(function() {
            var width = $(this).outerWidth(true);
            $(this).data('width', width);          
        });
        $tabs.prependTo($destination);
    }
    function makeTabsResponsive($element) {
        var $tabs = $element.find('li');
        var $firstTab = $tabs.first();

        var individualTabHeight = $firstTab.outerHeight();
        var tabsHeight = $element.outerHeight();

        if(tabsHeight > individualTabHeight) {
                    
            // get y pos of first tab
            var firstTabPos = $firstTab.offset();

            var thisTabPos;
            $tabs.each(function() {

                var $thisTab = $(this);

                thisTabPos = $thisTab.offset();

                if(thisTabPos.top > firstTabPos.top) {

                    var $dropdown = $element.find('.responsivetabs-more');
                            
                    if(!$dropdown.length) {
                        var dropdownMarkup = '<li class="dropdown responsivetabs-more">'
                        + '<a href="#" class="dropdown-toggle" data-toggle="dropdown">...</a>'
                        + '<ul class="dropdown-menu dropdown-menu-right">'
                        + '</ul></li>';
                        $dropdown = $(dropdownMarkup);
                        $element.append($dropdown);
                    }
                    var $previousTab = $thisTab.prev();
                    var $followingTabs = $thisTab.nextAll().not('.dropdown');

                    var $destination = $('.dropdown-menu', $dropdown);
                            
                    if(!$thisTab.hasClass('dropdown')) {
                        storeTabs($followingTabs, $destination);
                        storeTabs($thisTab, $destination);
                    }
                    storeTabs($previousTab, $destination);
                    return;
                }
            });
        } else {
            // check if enough space to move a menu item back out of "..."
            // get parent width
            var parentWidth = $element.parent().width();
            var tabSetWidth = 0;
            var xPxAvailable;

            // calculate total width of tab set (can't just use width of ul because it is 100% by default)
            $element.children('li').each(function() {
                tabSetWidth += $(this).outerWidth(true);
            });

            // calculate available horizontal space
            xPxAvailable = parentWidth - tabSetWidth;


                    
            $element.find('.dropdown-menu li').each(function() {
                if($(this).data('width') <= xPxAvailable) {
                    $(this).insertBefore($element.find('.responsivetabs-more')); 
                    xPxAvailable -= $(this).data('width');
                } else {
                    return false;
                } 
            });

            // if no menu items left, remove "..."
            if(!$element.find('.dropdown-menu li').length) {
                $element.find('.responsivetabs-more').remove();
            }
        }
    }
    $.fn.responsiveTabs = function() {
        this.each(function() {
            var tabs = $(this);
            makeTabsResponsive(tabs); 
            $(window).resize(function() {
                makeTabsResponsive(tabs); 
            });                    
        });
        return this;
    };
            
})( jQuery );

jQuery('.nav-tabs').responsiveTabs();