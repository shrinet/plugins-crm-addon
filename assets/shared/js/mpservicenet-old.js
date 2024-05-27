( function ( $ ) {
    // UTILITIES =======================================================================================================

    function showSpinner( element ) {
        $( element ).LoadingOverlay( 'show' );
    }

    function hideSpinner( element ) {
        $( element ).LoadingOverlay( 'hide' );
    }

    function showContentSpinner() {
        // showSpinner( '.content_pane' );
        showSpinner( '#mpservicenet' );
    }

    function hideContentSpinner() {
        // hideSpinner( '.content_pane' );
        hideSpinner( '#mpservicenet' );
    }

    function setTableData( table, data ) {
        table.clear().draw();
        table.rows.add( data );
        table.draw();
    }

    function dataTablePriceRenderer( data, type, row ) {
        if ( type === 'display' || type === 'filter' ) {
            if ( data === "0.00" ) {
                return '';
            }

            return '$' + parseFloat( data ).toFixed( 2 );
        }
        return data;
    }

    function JSONToCSVConverter( arrData, ReportTitle, ShowLabel ) {
        if ( Object.getOwnPropertyNames( arrData ).length === 1 ) {
            for ( var index in arrData ) {
                arrData = [ arrData[ index ] ];
                break;
            }
        }
        var CSV = "";
        var CSV_part_2 = "";
        CSV += ReportTitle + "\r\n\n";
        if ( ShowLabel ) {
            var row = "";
            for ( var index in arrData ) {
                if ( index === "Line Items" ) {
                    continue;
                }
                row += index + ",";
            }
            row = row.slice( 0, -1 );
            CSV += row + "\r\n";
        }
        var indexRow = "";
        for ( var index in arrData ) {
            var row = "";
            if ( index === "Line Items" ) {
                var row = "";
                if ( ShowLabel ) {
                    for ( var index_inner in arrData[ index ][ 0 ] ) {
                        row += index_inner + ",";
                    }
                    row = row.slice( 0, row.length - 1 );
                    CSV_part_2 += row + "\r\n";
                }
                for ( var i = 0; i < arrData[ index ].length; i++ ) {
                    row = "";
                    for ( var index_inner in arrData[ index ][ i ] ) {
                        row += '"' + arrData[ index ][ i ][ index_inner ] + '",';
                    }
                    row = row.slice( 0, row.length - 1 );
                    CSV_part_2 += row + "\r\n";
                    row = "";
                }
            } else {
                indexRow += '"' + arrData[ index ] + '",';
            }
        }
        indexRow = indexRow.slice( 0, indexRow.length - 1 );
        CSV += indexRow + "\r\n";

        // for ( var i = 0; i < arrData.length; i++ ) {
        //     var row = "";
        //     for ( var index in arrData[i] ) {
        //         if ( index === "Line Items" ) {
        //             row = row.slice( 0, row.length - 1 );
        //             CSV += row + "\r\n";
        //             var row = "";
        //             part_2 = true;
        //             if ( ShowLabel ) {
        //                 for ( var index_inner in arrData[ i ][ index ] ) {
        //                     for ( var index_inner_2 in arrData[ i ][ index ][ index_inner ] ) {
        //                         row += index_inner_2 + ",";
        //                     }
        //                     break;
        //                 }
        //                 row = row.slice( 0, row.length - 1 );
        //                 CSV_part_2 += row + "\r\n";
        //             }
        //             for ( var index_inner in arrData[ i ][ index ] ) {
        //                 row = "";
        //                 for ( var index_inner_2 in arrData[ i ][ index ][ index_inner ] ) {
        //                     row += '"' + arrData[ i ][ index ][ index_inner ][ index_inner_2 ] + '",';
        //                 }
        //                 row = row.slice( 0, row.length - 1 );
        //                 CSV_part_2 += row + "\r\n";
        //                 row = "";
        //             }
        //         } else {
        //             row += '"' + arrData[ i ][ index ] + '",';
        //         }
        //     }
        //     row = row.slice( 0, row.length - 1 );
        //     CSV += row + "\r\n";
        // }
        var formattedDate = new Date(
            Date.now() - new Date().getTimezoneOffset() * 60000
        )
            .toISOString()
            .slice( 0, 19 )
            .replace( /[^0-9]/g, "" );
        var fileName = "Maclean_";

        fileName += ReportTitle.replace( / /g, "_" );

        if ( navigator.msSaveBlob ) {
            // IE 10+
            var blob = new Blob( [ CSV + "\r\n\n" + CSV_part_2 ], {
                type: "text/csv;charset=utf8;"
            } );
            navigator.msSaveBlob( blob, fileName + "_" + formattedDate + ".csv" );
        } else {
            var uri =
                "data:text/csv;charset=utf-8," + escape( CSV + "\r\n\n" + CSV_part_2 );
            var link = document.createElement( "a" );
            link.href = uri;
            link.style = "visibility:hidden";
            link.download = fileName + "_" + formattedDate + ".csv";
            document.body.appendChild( link );
            link.click();
            document.body.removeChild( link );
        }
    }

    function objectToQueryString( obj ) {
        if ( ! obj || obj === {} ) return '';
        var str = '';
        for ( var key in obj ) {
            if ( obj.hasOwnProperty( key ) && obj[ key ] ) {
                var value = Array.isArray( obj[ key ] ) ? obj[ key ].join( ',' ) : obj[ key ];
                if ( value !== '' ) {
                    str += key + '=' + value + '&';
                }
            }
        }
        return ( str.charAt( str.length - 1 ) === '&' ) ? str.slice( 0, -1 ) : str;
    }

    function queryStringToObject( str ) {
        if ( ! str ) {
            return {};
        }
        if ( str.charAt( 0 ) === '?' ) {
            str = str.slice( 1 );
        }
        var obj = {};
        str.split( '&' ).forEach( function ( param ) {
            var entry = param.split( '=' );
            obj[ entry[ 0 ] ] = ( entry[ 1 ].includes( ',' ) ) ? entry[ 1 ].split( ',' ) : entry[ 1 ];
            if ( obj[ entry[ 0 ] ] === 'false' ) {
                obj[ entry[ 0 ] ] = false;
            } else if ( obj[ entry[ 0 ] ] === 'true' ) {
                obj[ entry[ 0 ] ] = true;
            }
        } );
        return obj;
    }

    function removeWhitespaceFromString( str ) {
        return str.replace( /\s/g, '' );
    }

    function mpsAjaxPost( postData, onSuccess ) {
        showContentSpinner();
        $.ajax( {
            cache: false,
            type: 'POST',
            url: maclean_ajax_url,
            data: postData,
            success: function ( data ) {
                data = JSON.parse( data );
                if ( typeof data !== 'object' ) {
                    alert( data );
                } else {
                    onSuccess( data );
                }
                hideContentSpinner();
            }
        } );
    }

    // CONSTANTS =======================================================================================================

    var ROUTES = {
        HOME: '/',

        PA_LANDING: '/price-availability',
        PA_CATALOG_SUMMARY: '/price-availability/catalog-summary',
        PA_PACKAGING_INFORMATION: '/price-availability/packaging-information',

        QUOTE_LANDING: '/quote',
        QUOTE_LISTING: '/quote/listing',
        QUOTE_DETAILS: '/quote/details',

        ORDER_LANDING: '/order',
        ORDER_LISTING: '/order/listing',
        ORDER_DETAILS: '/order/details',
        ORDER_PACKAGING: '/order/packaging'
    };

    var GROUPS = {
        HOME: 0,
        PA: 1,
        QUOTE: 2,
        ORDER: 3
    };

    // MIXINS ==========================================================================================================

    function LoadableMixin( obj, loadFn ) {
        obj.Loadable = {};
        var self = obj.Loadable;

        self.loadFn = loadFn;
        self.loadParams = null;
        self.reload = true;

        self.load = function ( showFn ) {
            if ( self.reload ) {
                self.loadFn( self.loadParams, showFn );
            }
        };

        self.requireLoad = function ( params ) {
            self.reload = true;
            self.loadParams = params;
        };

        self.getLoadParam = function ( key ) {
            if ( self.loadParams && self.loadParams.hasOwnProperty( key ) ) {
                return self.loadParams[ key ];
            } else {
                return '';
            }
        };

        self.isLoaded = function () {
            return self.loadParams !== null;
        }
    }

    function ShowableMixin( obj, $page ) {
        obj.Showable = {};
        var self = obj.Showable;

        self.$page = $page;

        self.show = function () {
            self.$page.show();
        };

        self.hide = function () {
            self.$page.hide();
        };
    }

    function PageMixin( obj, $page, group, loadFn ) {
        LoadableMixin( obj, loadFn );
        ShowableMixin( obj, $page );

        var Loadable = obj.Loadable;
        var Showable = obj.Showable;

        obj.Page = {};
        var self = obj.Page;

        self.group = group;

        self.navigate = function ( params ) {
            Loadable.loadParams = params;
            self.group.prepare();

            if ( Loadable.reload ) {
                Loadable.load( Showable.show );
            } else {
                Showable.show();
            }
        };

        self.queryString = function () {
            if ( ! Loadable.loadParams ) {
                return '';
            }
            return '?' + objectToQueryString( Loadable.loadParams );
        };
    }

    // COMPONENTS ======================================================================================================

    var AccountFilter = ( function () {
        var COMPONENTS = {
            SUBMIT_BUTTON: 0,
            TITLE: 1
        };

        var lastAccounts = null;

        var $container = null;

        var accounts = null;
        var viewModel = null;

        function ViewModel() {
            var self = this;

            self.visible = ko.observable( false );

            self.filteredAccounts = ko.observableArray( accounts );

            self.filterText = ko.observable();

            self.selectedAccountCheckboxes = ko.observableArray( [] );
            self.selectedAccountRadio = ko.observable( '' );

            self.useRadios = ko.observable( true );

            // component visibility
            self.submitButtonVisible = ko.observable( false );
            self.titleVisible = ko.observable( false );

            self.filter = function () {
                self.filteredAccounts( accounts.filter( function ( account ) {
                    var searchText = self.filterText().toLowerCase();
                    return account.name.toLowerCase().includes( searchText )
                        || account.number.toLowerCase().includes( searchText );
                } ) );
            };

            self.submitClicked = function () {
                var selectedAccounts = AccountFilter.getSelectedAccountsArray();

                switch ( Navigator.getActiveGroup() ) {
                    case GROUPS.QUOTE:
                        if ( ! selectedAccounts ) {
                            alert( 'Please select one or more accounts.' );
                            return;
                        }

                        QuoteListingPage.requireLoad( QuoteListingPage.queryParams( selectedAccounts ) );
                        Router.navigate( ROUTES.QUOTE_LISTING + QuoteListingPage.queryString() );

                        break;
                    case GROUPS.ORDER:
                        if ( ! selectedAccounts ) {
                            alert( 'Please select one or more accounts.' );
                            return;
                        }

                        OrderListingPage.requireLoad( OrderListingPage.queryParams( selectedAccounts ) );
                        Router.navigate( ROUTES.ORDER_LISTING + OrderListingPage.queryString() );

                        break;
                }
            }
        }

        function init() {
            $container = $( '#account-filters' );

            accounts = $container.data( 'accounts' );

            viewModel = new ViewModel();
            ko.applyBindings( viewModel, $container[ 0 ] );
        }

        function show( components ) {
            viewModel.visible( true );

            if ( components && Array.isArray( components ) ) {
                components.forEach( function ( component ) {
                    setComponentVisibility( component, true );
                } );
            }
        }

        function hide( components ) {
            if ( components && Array.isArray( components ) ) {
                components.forEach( function ( component ) {
                    setComponentVisibility( component, false );
                } );
            } else {
                viewModel.visible( false );

                Object.keys( COMPONENTS ).forEach( function ( key ) {
                    if ( COMPONENTS.hasOwnProperty( key ) ) {
                        setComponentVisibility( COMPONENTS[ key ] );
                    }
                } );
            }
        }

        function setComponentVisibility( component, visibility ) {
            switch ( component ) {
                case COMPONENTS.SUBMIT_BUTTON:
                    viewModel.submitButtonVisible( visibility );
                    break;
                case COMPONENTS.TITLE:
                    viewModel.titleVisible( visibility );
                    break;
            }
        }

        function useRadioInputs() {
            viewModel.useRadios( true );
        }

        function useCheckboxInputs() {
            viewModel.useRadios( false );
        }

        function getSelectedAccounts() {
            if ( viewModel.useRadios() ) {
                return viewModel.selectedAccountRadio();
            } else {
                return viewModel.selectedAccountCheckboxes();
            }
        }

        function saveSelectedAccountsAsLastAccounts() {
            lastAccounts = getSelectedAccounts();
        }

        function setSelectedAccounts( accounts ) {
            if ( ! Array.isArray( accounts ) ) {
                accounts = [ accounts ];
            }

            if ( viewModel.useRadios() ) {
                viewModel.selectedAccountRadio( accounts[ 0 ] );
            } else {
                viewModel.selectedAccountCheckboxes( accounts );
            }
        }

        function getLastAccounts() {
            return lastAccounts;
        }

        function getAccountNameByNumber( accountNumber ) {
            if ( accounts ) {
                var accountName = '';
                accounts.forEach( function ( account ) {
                    if ( account.number === accountNumber ) {
                        accountName = account.name;
                    }
                } );
                return accountName;
            }
        }

        return {
            COMPONENTS: COMPONENTS,

            init: init,

            show: show,
            hide: hide,

            useRadioInputs: useRadioInputs,
            useCheckboxInputs: useCheckboxInputs,

            getSelectedAccountsArray: getSelectedAccounts,
            getLastAccounts: getLastAccounts,
            saveSelectedAccountsAsLastAccounts: saveSelectedAccountsAsLastAccounts,
            setSelectedAccounts: setSelectedAccounts,

            getAccountNameByNumber: getAccountNameByNumber
        };
    } )();

    var IdFilter = ( function () {
        var COMPONENTS = {
            FILTER_TYPES: 0,
            FILTER_BUTTON: 1,
            SUBMIT_BUTTON: 2,
            DATE_FIlTER: 3,
            STATUS_FILTER: 4
        };

        var FILTER_TYPES = {
            CATALOG_NUMBER: {
                value: 'catalog-number',
                label: 'CATALOG #'
            },
            QUOTE_NUMBER: {
                value: 'quote-number',
                label: 'QUOTE #'
            },
            ORDER_NUMBER: {
                value: 'order-number',
                label: 'ORDER #'
            },
            PO_NUMBER: {
                value: 'po-number',
                label: 'PO #'
            }
        };

        var lastIds = null;

        var $container = null;
        var $submitButton = null;

        var $filterTypeList = null;
        var $filterTypeInputs = null;
        var $idFilterDate = null;
        var $idFilterFromDateInput = null;
        var $idFilterToDateInput = null;
        var $idFilterStatus = null;
        var $idFilterStatusSelect = null;
        var $idFilterTypeCatalogNumber = null;
        var $idFilterTypeQuoteNumber = null;
        var $idFilterTypeOrderNumber = null;
        var $idFilterTypePONumber = null;
        var $idFilterTextInput = null;
        var $subtitle = null;

        var viewModel = null;

        function ViewModel() {
            var self = this;

            self.visible = ko.observable( false );

            self.filterText = ko.observable( '' );
            self.submitButtonText = ko.observable( 'Filter' );
            self.subtitleText = ko.observable( 'Catalog' );

            self.filterTypesVisible = ko.observable( false );
            self.filterButtonVisible = ko.observable( false );
            self.submitButtonVisible = ko.observable( false );
            self.dateFilterVisible = ko.observable( false );
            self.statusFilterVisible = ko.observable( false );
            self.filterTypeQuoteNumberVisible = ko.observable( false );

            self.availableFilterTypes = ko.observableArray();
        }

        function init() {
            $container = $( '#id-filters' );

            viewModel = new ViewModel();
            ko.applyBindings( viewModel, $container[ 0 ] );

            $submitButton = $( '#id-submit-button' );

            $filterTypeList = $( '#id-filter-type-list' );
            $filterTypeInputs = $filterTypeList.find( 'input' );
            $idFilterDate = $( '#id-filter-date' );
            $idFilterFromDateInput = $( '#from-date-input' );
            $idFilterToDateInput = $( '#to-date-input' );
            $idFilterStatus = $( '#id-filter-status' );
            $idFilterStatusSelect = $idFilterStatus.find( "select" );
            $idFilterTypeCatalogNumber = $( '#id-filter-type-catalog-number' );
            $idFilterTypeQuoteNumber = $( '#id-filter-type-quote-number' );
            $idFilterTypeOrderNumber = $( '#id-filter-type-order-number' );
            $idFilterTypePONumber = $( '#id-filter-type-po-number' );
            $idFilterTextInput = $( '#id-filter-text-input' );
            $subtitle = $( '#id-filters-subtitle' );

            initHandlers();
        }

        function initHandlers() {
            $submitButton.on( 'click', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                var selectedAccounts = AccountFilter.getSelectedAccountsArray();
                var searchedIds = IdFilter.getSearchIdsArray();

                var queryParams = null;

                switch ( Navigator.getActiveGroup() ) {
                    case GROUPS.PA:
                        var accountNumber = selectedAccounts;
                        var catalogNumbers = searchedIds;

                        if ( ! accountNumber ) {
                            alert( 'Please select an account.' );
                            return;
                        }

                        if ( ! catalogNumbers || catalogNumbers === '' ) {
                            alert( 'Please enter one or more catalog numbers, separated by a comma.' );
                            return;
                        }

                        PACatalogSummaryPage.requireLoad( PACatalogSummaryPage.queryParams( accountNumber, catalogNumbers ) );
                        Router.navigate( ROUTES.PA_CATALOG_SUMMARY + PACatalogSummaryPage.queryString() );

                        break;
                    case GROUPS.QUOTE:
                        switch ( Router.lastRoute() ) {
                            case ROUTES.QUOTE_LISTING:
                                if ( $filterTypeList.find( 'input:checked' ).val() === 'catalog-number' ) {
                                    queryParams = QuoteListingPage.queryParams( selectedAccounts, searchedIds );
                                } else {
                                    queryParams = QuoteListingPage.queryParams( selectedAccounts );
                                }

                                QuoteListingPage.requireLoad( queryParams );
                                Router.navigate( ROUTES.QUOTE_LISTING + QuoteListingPage.queryString() );
                                QuoteListingPage.filter( getSearchIds(), $idFilterFromDateInput.val(), $idFilterToDateInput.val(), $filterTypeList.find( 'input:checked' ).val() );
                                break;
                            case ROUTES.QUOTE_DETAILS:
                                QuoteDetailsPage.filter( getSearchIds(), $filterTypeList.find( 'input:checked' ).val() );
                                break;
                        }
                        break;
                    case GROUPS.ORDER:
                        switch ( Router.lastRoute() ) {
                            case ROUTES.ORDER_LISTING:
                                if ( $filterTypeList.find( 'input:checked' ).val() === 'catalog-number' ) {
                                    queryParams = OrderListingPage.queryParams( selectedAccounts, searchedIds );
                                } else {
                                    queryParams = OrderListingPage.queryParams( selectedAccounts );
                                }

                                OrderListingPage.requireLoad( queryParams );
                                Router.navigate( ROUTES.ORDER_LISTING + OrderListingPage.queryString() );
                                OrderListingPage.filter( getSearchIds(), $idFilterStatusSelect.val(), $idFilterFromDateInput.val(), $idFilterToDateInput.val(), $filterTypeList.find( 'input:checked' ).val() );
                                break;
                            case ROUTES.ORDER_DETAILS:
                                OrderDetailsPage.filter( getSearchIds(), $idFilterStatusSelect.val(), $idFilterFromDateInput.val(), $idFilterToDateInput.val(), $filterTypeList.find( 'input:checked' ).val() );
                                break;
                        }
                        break;
                }
            } );
        }

        function show( components ) {
            viewModel.visible( true );

            if ( components && Array.isArray( components ) ) {
                components.forEach( function ( component ) {
                    setComponentVisibility( component, true );
                } );
            }
        }

        function hide( components ) {
            if ( components && Array.isArray( components ) ) {
                components.forEach( function ( component ) {
                    setComponentVisibility( component, false );
                } );
            } else {
                viewModel.visible( false );

                Object.keys( COMPONENTS ).forEach( function ( key ) {
                    if ( COMPONENTS.hasOwnProperty( key ) ) {
                        setComponentVisibility( COMPONENTS[ key ] );
                    }
                } );
            }
        }

        function setComponentVisibility( component, visibility ) {
            switch ( component ) {
                case COMPONENTS.FILTER_TYPES:
                    viewModel.filterTypesVisible( visibility );
                    break;
                case COMPONENTS.FILTER_BUTTON:
                    viewModel.filterButtonVisible( visibility );
                    break;
                case COMPONENTS.SUBMIT_BUTTON:
                    viewModel.submitButtonVisible( visibility );
                    break;
                case COMPONENTS.DATE_FIlTER:
                    viewModel.dateFilterVisible( visibility );
                    break;
                case COMPONENTS.STATUS_FILTER:
                    viewModel.statusFilterVisible( visibility );
                    break;
            }
        }

        function setAvailableFilterTypes( filterTypes ) {
            viewModel.availableFilterTypes( filterTypes );
        }

        function setSubmitButtonText( text ) {
            viewModel.submitButtonText( text );
        }

        function setSubtitleText( text ) {
            viewModel.subtitleText( text );
        }

        function getSearchIds() {
            return removeWhitespaceFromString( viewModel.filterText() ).split( ',' );
        }

        function setSearchIds( ids ) {
            if ( ! ids ) {
                return;
            }

            if ( Array.isArray( ids ) ) {
                ids = ids.join( ',' );
            }
            viewModel.filterText( ids );
        }

        function saveSearchIdsAsLastIds() {
            lastIds = getSearchIds();
        }

        function getLastIds() {
            return lastIds;
        }

        function setFilterTypeToCatalog() {
            $filterTypeList.find( '#id-filter-type-input-catalog-number' ).prop( 'checked', true );
        }

        function getFromDate() {
            return $idFilterFromDateInput.val();
        }

        function getToDate() {
            return $idFilterToDateInput.val();
        }

        function getFilterType() {
            $filterTypeList.find( 'input:checked' ).val();
        }

        function getFilterStatus() {
            return $idFilterStatusSelect.val();
        }

        return {
            COMPONENTS: COMPONENTS,
            FILTER_TYPES: FILTER_TYPES,
            init: init,
            show: show,
            hide: hide,
            setAvailableFilterTypes: setAvailableFilterTypes,
            setSubmitButtonText: setSubmitButtonText,
            setSubtitleText: setSubtitleText,
            setSearchIds: setSearchIds,
            saveSearchIdsAsLastIds: saveSearchIdsAsLastIds,
            getLastIds: getLastIds,
            getSearchIdsArray: getSearchIds,
            setFilterTypeToCatalog: setFilterTypeToCatalog,
            getFromDate: getFromDate,
            getToDate: getToDate,
            getFilterType: getFilterType,
            getFilterStatus: getFilterStatus
        };
    } )();

    var Announcements = ( function () {
        var Showable = null;

        function init() {
            ShowableMixin( this, $( '#mps-announcements' ) );
            Showable = this.Showable;
        }

        return {
            init: init,

            show: function () {
                Showable.show()
            },
            hide: function () {
                Showable.hide()
            }
        };
    } )();

    var FilterColumn = ( function () {
        var Showable = null;

        function init() {
            ShowableMixin( this, $( '#mps-filters' ).closest( '.fl-col' ) );
            Showable = this.Showable;
        }

        return {
            init: init,

            show: function () {
                Showable.show()
            },
            hide: function () {
                Showable.hide()
            }
        };
    } )();

    var Breadcrumbs = ( function () {
        var $container = null;

        var viewModel = null;

        var homeURL = null;
        var mpsURL = null;

        function ViewModel() {
            var self = this;

            self.crumbs = ko.observableArray( [] );
        }

        function init() {
            $container = $( '#mps-breadcrumbs' );
            viewModel = new ViewModel();

            ko.applyBindings( viewModel, $container[ 0 ] );

            homeURL = $container.data( 'homeurl' );
            mpsURL = $container.data( 'mpsurl' );
        }

        return {
            init: init,
            clear: function () {
                viewModel.crumbs( [] );
            },
            push: function ( label, url ) {
                viewModel.crumbs.push( {
                    label: label,
                    url: url
                } );
            },
            homeURL: function () {
                return homeURL;
            },
            mpsURL: function () {
                return mpsURL;
            }
        };
    } )();

    var Navigator = ( function () {
        var ACTIVE_BUTTON_CLASS = 'mps-navigation-button-active';

        var activeGroup = -1;

        var $navigator = null;

        var $buttons = null;

        var $paButton = null;
        var $quoteButton = null;
        var $orderButton = null;

        function init() {
            $navigator = $( '#mps-navigation' );

            $buttons = $navigator.find( '.mps-navigation-button' );

            $paButton = $navigator.find( '#price-availability-button' );
            $quoteButton = $navigator.find( '#quote-button' );
            $orderButton = $navigator.find( '#order-button' );

            $paButton.attr( 'href', '#' + ROUTES.PA_LANDING );
            $quoteButton.attr( 'href', '#' + ROUTES.QUOTE_LANDING );
            $orderButton.attr( 'href', '#' + ROUTES.ORDER_LANDING );

            $paButton.on( 'click', function () {
                activeGroup = GROUPS.PA;

                $buttons.removeClass( ACTIVE_BUTTON_CLASS );
                $paButton.addClass( ACTIVE_BUTTON_CLASS );
            } );
            $quoteButton.on( 'click', function () {
                activeGroup = GROUPS.QUOTE;

                $buttons.removeClass( ACTIVE_BUTTON_CLASS );
                $quoteButton.addClass( ACTIVE_BUTTON_CLASS );
            } );
            $orderButton.on( 'click', function () {
                activeGroup = GROUPS.ORDER;

                $buttons.removeClass( ACTIVE_BUTTON_CLASS );
                $orderButton.addClass( ACTIVE_BUTTON_CLASS );
            } );
        }

        function setActiveGroup( groupKey ) {
            activeGroup = groupKey;

            $buttons.removeClass( ACTIVE_BUTTON_CLASS );
            switch ( groupKey ) {
                case GROUPS.PA:
                    $paButton.addClass( ACTIVE_BUTTON_CLASS );
                    break;
                case GROUPS.QUOTE:
                    $quoteButton.addClass( ACTIVE_BUTTON_CLASS );
                    break;
                case GROUPS.ORDER:
                    $orderButton.addClass( ACTIVE_BUTTON_CLASS );
                    break;
            }
        }

        function getActiveGroup() {
            return activeGroup;
        }

        return {
            init: init,
            getActiveGroup: getActiveGroup,
            setActiveGroup: setActiveGroup
        };
    } )();

    var PageManager = ( function () {
        var $pages = null;

        function init() {
            $pages = $( '#mpservicenet .subpage' );
        }

        function hideAll() {
            $pages.hide();
        }

        return {
            init: init,
            hideAll: hideAll
        }
    } )();

    var Router = ( function () {
        var _router = null;

        function init() {
            var root = window.location.origin;
            var useHash = true;
            var hashSymbol = '#';

            _router = new Navigo( root, useHash, hashSymbol );

            initRoutes();
        }

        function initRoutes() {
            _router.notFound( function () {
                console.log( 'Page not found: ' + window.location.href );
            } );

            var routes = {};

            routes[ ROUTES.HOME ] =
                function ( params, query ) {
                    HomeLandingPage.show( queryStringToObject( query ) );
                };

            routes[ ROUTES.PA_LANDING ] =
                function ( params, query ) {
                    PALandingPage.show( queryStringToObject( query ) );
                };
            routes[ ROUTES.PA_CATALOG_SUMMARY ] =
                function ( params, query ) {
                    PACatalogSummaryPage.navigate( queryStringToObject( query ) );
                };
            routes[ ROUTES.PA_PACKAGING_INFORMATION ] =
                function ( params, query ) {
                    PAPackagingInformationPage.navigate( queryStringToObject( query ) );
                };

            routes[ ROUTES.QUOTE_LANDING ] =
                function ( params, query ) {
                    QuoteLandingPage.show( queryStringToObject( query ) );
                };
            routes[ ROUTES.QUOTE_LISTING ] =
                function ( params, query ) {
                    QuoteListingPage.navigate( queryStringToObject( query ) );
                };
            routes[ ROUTES.QUOTE_DETAILS ] =
                function ( params, query ) {
                    QuoteDetailsPage.navigate( queryStringToObject( query ) );
                };

            routes[ ROUTES.ORDER_LANDING ] =
                function ( params, query ) {
                    OrderLandingPage.show( queryStringToObject( query ) );
                };
            routes[ ROUTES.ORDER_LISTING ] =
                function ( params, query ) {
                    OrderListingPage.navigate( queryStringToObject( query ) );
                };
            routes[ ROUTES.ORDER_DETAILS ] =
                function ( params, query ) {
                    OrderDetailsPage.navigate( queryStringToObject( query ) );
                };
            routes[ ROUTES.ORDER_PACKAGING ] =
                function ( params, query ) {
                    OrderPackagingPage.navigate( queryStringToObject( query ) );
                };

            _router.on( routes ).resolve();
        }

        function lastRoute() {
            return _router.lastRouteResolved().url;
        }

        return {
            init: init,
            navigate: function ( path ) {
                _router.navigate( path );
            },
            lastRoute: lastRoute,
            pause: function () {
                _router.pause();
            },
            resume: function () {
                setTimeout( function () {
                    _router.resume();
                }, 500 );
            }
        };
    } )();

    // GROUPS ==========================================================================================================

    var RootGroup = ( function () {
        function prepare() {
            PageManager.hideAll();

            AccountFilter.hide();
            IdFilter.hide();

            Announcements.hide();

            PAPackagingInformationPage.hideAdditionalInfoPanel();

            FilterColumn.show();

            Breadcrumbs.clear();
            Breadcrumbs.push( 'Home', Breadcrumbs.homeURL() );
            Breadcrumbs.push( 'MPServiceNet', '#' + ROUTES.HOME );
        }

        return {
            prepare: prepare
        };
    } )();

    var HomeGroup = ( function () {
        var $wrapper = null;

        function init() {
            $wrapper = $( '#landing-page' );
        }

        function prepare() {
            RootGroup.prepare();
            Announcements.show();
            Navigator.setActiveGroup();
            $wrapper.show();
        }

        return {
            init: init,
            prepare: prepare
        };
    } )();

    var PriceAvailabilityGroup = ( function () {
        var $wrapper = null;

        function init() {
            $wrapper = $( '#price-availability-page' );
        }

        function prepare( queryParams ) {
            RootGroup.prepare();

            Navigator.setActiveGroup( GROUPS.PA );

            AccountFilter.show( [
                AccountFilter.COMPONENTS.TITLE
            ] );
            AccountFilter.useRadioInputs();

            IdFilter.show();
            IdFilter.show( [
                IdFilter.COMPONENTS.SUBMIT_BUTTON
            ] );
            IdFilter.setSubmitButtonText( 'Go' );
            IdFilter.setSubtitleText( 'Catalog' );

            if ( queryParams ) {
                if ( queryParams.accounts ) {
                    AccountFilter.setSelectedAccounts( queryParams.accounts );
                }

                if ( queryParams.ids ) {
                    IdFilter.setSearchIds( queryParams.ids );
                }
            }

            Breadcrumbs.push( 'Price & Availability', '#' + ROUTES.PA_LANDING );

            $wrapper.show();
        }

        return {
            init: init,
            prepare: prepare
        };
    } )();

    var QuoteGroup = ( function () {
        var $wrapper = null;

        function init() {
            $wrapper = $( '#quote-page' );
        }

        function prepare() {
            RootGroup.prepare();

            Navigator.setActiveGroup( GROUPS.QUOTE );

            AccountFilter.show( [
                AccountFilter.COMPONENTS.SUBMIT_BUTTON
            ] );
            AccountFilter.useCheckboxInputs();

            IdFilter.show( [
                IdFilter.COMPONENTS.FILTER_BUTTON,
                IdFilter.COMPONENTS.FILTER_TYPES,
                IdFilter.COMPONENTS.DATE_FIlTER,
                IdFilter.COMPONENTS.SUBMIT_BUTTON,
                IdFilter.COMPONENTS.FILTER_TYPE_QUOTE
            ] );
            IdFilter.setAvailableFilterTypes( [
                IdFilter.FILTER_TYPES.CATALOG_NUMBER,
                IdFilter.FILTER_TYPES.QUOTE_NUMBER
            ] );
            IdFilter.setSubmitButtonText( 'Filter' );
            IdFilter.setSubtitleText( 'Filter Quote' );

            Breadcrumbs.push( 'Quote', '#' + ROUTES.QUOTE_LANDING );

            $wrapper.show();
        }

        return {
            init: init,
            prepare: prepare
        };
    } )();

    var OrderGroup = ( function () {
        var $wrapper = null;

        function init() {
            $wrapper = $( '#order-page' );
        }

        function prepare() {
            RootGroup.prepare();

            Navigator.setActiveGroup( GROUPS.ORDER );

            AccountFilter.show( [
                AccountFilter.COMPONENTS.SUBMIT_BUTTON
            ] );
            AccountFilter.useCheckboxInputs();

            IdFilter.show( [
                IdFilter.COMPONENTS.FILTER_BUTTON,
                IdFilter.COMPONENTS.FILTER_TYPES,
                IdFilter.COMPONENTS.DATE_FIlTER,
                IdFilter.COMPONENTS.SUBMIT_BUTTON,
                IdFilter.COMPONENTS.STATUS_FILTER
            ] );
            IdFilter.setAvailableFilterTypes( [
                IdFilter.FILTER_TYPES.CATALOG_NUMBER,
                IdFilter.FILTER_TYPES.ORDER_NUMBER,
                IdFilter.FILTER_TYPES.PO_NUMBER
            ] );
            IdFilter.setSubmitButtonText( 'Filter' );
            IdFilter.setSubtitleText( 'Filter Order' );

            Breadcrumbs.push( 'Order', '#' + ROUTES.ORDER_LANDING );

            $wrapper.show();
        }

        return {
            init: init,
            prepare: prepare
        };
    } )();

    // PAGES ===========================================================================================================

    var HomeLandingPage = ( function () {
        var $page = null;

        function init() {
            $page = $( '#landing-subpage' );
        }

        function show() {
            HomeGroup.prepare();
            $page.show();
        }

        return {
            init: init,
            show: show
        };
    } )();

    var PALandingPage = ( function () {
        var $page = null;

        function init() {
            $page = $( '#price-availability-default-subpage' );
        }

        function show( queryParams ) {
            PriceAvailabilityGroup.prepare( queryParams );
            $page.show();
        }

        return {
            init: init,
            show: show
        };
    } )();
    var PACatalogSummaryPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $resultsFor = null;
        var $catalogSummaryTable = null;
        var $resultsCount = null;

        var catalogSummaryTable = null;

        function init() {
            PageMixin( this, $( '#price-availability-catalog-summary-subpage' ), PriceAvailabilityGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $resultsFor = $( '#catalog-summary-for-accounts' );
            $catalogSummaryTable = $( '#price-availability-account-listing-table' );
            $resultsCount = $( '#catalog-summary-results-count' );

            initTables();
            initHandlers();
        }

        function initTables() {
            catalogSummaryTable = $catalogSummaryTable.DataTable( {
                'responsive': true,
                'columns': [
                    {
                        'className': 'catalog-number',
                        'data': 'catalog-number',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' || type === 'filter' ) {
                                if ( row[ 'product-link' ] !== '#' ) {
                                    return '<a target="_blank" href="' + row[ 'product-link' ] + '">' +
                                        '<span>' + row[ 'catalog-number' ] + '</span>' +
                                        '</a>';
                                } else {
                                    return '<span>' + row[ 'catalog-number' ] + '</span>';
                                }
                            }

                            return data;
                        }
                    },
                    {
                        'className': 'list-price',
                        'data': 'list-price',
                        'render': dataTablePriceRenderer
                    },
                    {
                        'className': 'lead-time',
                        'data': 'lead-time'
                    },
                    {
                        'className': 'details-link',
                        'data': 'product-link',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' || type === 'filter' ) {
                                return '<a class="catalog-details-button" data-catalognumber="' + row[ 'catalog-number' ] + '">DETAILS</a>';
                            }

                            return data;
                        }
                    }
                ]
            } );
        }

        function initHandlers() {
            $catalogSummaryTable.on( 'click', '.catalog-details-button', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                var accountNumber = getLoadedAccountNumber();
                var catalogNumber = $( event.target ).data( 'catalognumber' );

                PAPackagingInformationPage.requireLoad( PAPackagingInformationPage.queryParams( accountNumber, catalogNumber ) );
                Router.navigate( ROUTES.PA_PACKAGING_INFORMATION + PAPackagingInformationPage.queryString() );
            } );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var account = params.account;
            var catalogNumbers = params.catalogs;
            if ( Array.isArray( catalogNumbers ) ) {
                catalogNumbers = catalogNumbers.join( ',' );
            }

            mpsAjaxPost( {
                action: 'price_and_availability_account_ajax',
                catalog_numbers: catalogNumbers,
                account_numbers: account
            }, function ( data ) {
                AccountFilter.setSelectedAccounts( [ params.account ] );

                setTableData( catalogSummaryTable, data );
                $resultsCount.text( catalogSummaryTable.rows().count() );
                $resultsFor.text( getLoadedAccountNumber() );

                Loadable.reload = false;

                showFn();
            } );
        }

        function queryParams( accountNumber, catalogNumbers ) {
            return {
                account: accountNumber,
                catalogs: catalogNumbers
            };
        }

        function getLoadedAccountNumber() {
            return Loadable.getLoadParam( 'account' );
        }

        function getLoadedCatalogNumbers() {
            return Loadable.getLoadParam( 'catalogs' );
        }

        function pushCrumb() {
            if ( Loadable.isLoaded() && getLoadedAccountNumber() !== '' && getLoadedCatalogNumbers() !== '' ) {
                Breadcrumbs.push(
                    'Catalog Summary List: ' + getLoadedCatalogNumbers(),
                    '#' + ROUTES.PA_CATALOG_SUMMARY + PACatalogSummaryPage.queryString()
                );
            } else {
                Breadcrumbs.push( 'Catalog Summary List', '#' + ROUTES.PA_CATALOG_SUMMARY );
            }
        }

        function navigate( params ) {
            Page.navigate( params );
            pushCrumb();
        }

        return {
            init: init,

            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },

            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams,

            pushCrumb: pushCrumb,
            isLoaded: function () {
                return Loadable.isLoaded();
            }
        };
    } )();
    var PAPackagingInformationPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $container = null;

        var $packagingInfoTable = null;
        var $availabilityInfoTable = null;
        var $scheduleInfoTable = null;

        var packagingInfoTable = null;
        var availabilityInfoTable = null;
        var scheduleInfoTable = null;

        var $backToAccountsButton = null;
        var $packagingInformationIdLabel = null;

        var $additionalInfoPanel = null;
        var $additionalInfoCatalogLink = null;
        var $additionalInfoOrderStatus = null;
        var $additionalInfoQuoteDetail = null;
        var $additionalInfoProductDrawing = null;

        var $printButton = null;

        function init() {
            $container = $( '#price-availability-packaging-information-subpage' );

            PageMixin( this, $container, PriceAvailabilityGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $packagingInfoTable = $( '#price-availability-packaging-info-table' );
            $availabilityInfoTable = $( '#availability-info-table' );
            $scheduleInfoTable = $( '#schedule-info-table' );

            $scheduleInfoTable.hide();

            $backToAccountsButton = $( '#back-to-catalog-summary' );
            $packagingInformationIdLabel = $( '#packaging-information-for-id' );

            $additionalInfoPanel = $( '#pa-info-links' );
            $additionalInfoCatalogLink = $( '#pa-info-links-catalog-page' );
            $additionalInfoOrderStatus = $( '#pa-info-links-order-status' );
            $additionalInfoQuoteDetail = $( '#pa-info-links-quote-detail' );
            $additionalInfoProductDrawing = $( '#pa-info-links-product-drawing' );

            $printButton = $( '#pa-packaging-print-button' );

            initHandlers();
            initTables();
        }

        function initHandlers() {
            $backToAccountsButton.on( 'click', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                Router.navigate( ROUTES.PA_CATALOG_SUMMARY + PACatalogSummaryPage.queryString() );
            } );

            $printButton.on( 'click', function ( event ) {
                event.stopPropagation();
                event.preventDefault();

                window.print();
            } );

            $additionalInfoOrderStatus.on( 'click', function () {
                QuoteListingPage.requireLoad();
            } );

            $additionalInfoQuoteDetail.on( 'click', function () {
                QuoteListingPage.requireLoad();
            } );
        }

        function initTables() {
            packagingInfoTable = $packagingInfoTable.DataTable( {
                'responsive': true,
                'bSort': false,
                'columns': [
                    {
                        'data': 'standard-package-quantity'
                    },
                    {
                        'data': 'pallet-quantity'
                    },
                    {
                        'data': 'weight-ea'
                    },
                    {
                        'data': 'unit-of-measure'
                    }
                ]
            } );

            availabilityInfoTable = $availabilityInfoTable.DataTable( {
                'responsive': true,
                'bSort': false,
                'columns': [
                    {
                        'data': 'catalog-number'
                    },
                    {
                        'data': 'stock-status'
                    },
                    {
                        'data': 'description'
                    },
                    {
                        'data': 'list-price',
                        'render': dataTablePriceRenderer
                    },
                    {
                        'data': 'standard-discount-price',
                        'render': dataTablePriceRenderer
                    },
                    {
                        'data': 'quantity-in-stock'
                    },
                    {
                        'data': 'plant-primary-ship-from'
                    },
                    {
                        'data': 'mfg-lead-time'
                    },
                    {
                        'data': 'secondary-inv-location'
                    },
                    {
                        'data': 'manufacturing-code'
                    }
                ]
            } );
            // scheduleInfoTable = $scheduleInfoTable.DataTable( {
            //     'responsive': true,
            //     'bSort': false
            // } );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var account = params.account;
            var catalog = params.catalog;

            mpsAjaxPost( {
                action: 'price_and_availability_id_ajax',
                catalog_numbers: catalog,
                account_numbers: account
            }, function ( data ) {
                var packagingInfoTableData = [ data[ 0 ] ];
                var availabilityInfoTableData = [ data[ 1 ] ];
                var additionalInfoData = data[ 2 ];

                setTableData( packagingInfoTable, packagingInfoTableData );
                setTableData( availabilityInfoTable, availabilityInfoTableData );

                if ( additionalInfoData.hasOwnProperty( 'drawing_link' ) ) {
                    $additionalInfoProductDrawing.attr( 'href', additionalInfoData[ 'drawing_link' ] );
                    $additionalInfoProductDrawing.show();
                }
                if ( additionalInfoData.hasOwnProperty( 'product_link' ) ) {
                    $additionalInfoCatalogLink.attr( 'href', additionalInfoData[ 'product_link' ] );
                }
                if ( additionalInfoData.hasOwnProperty( 'catalog_number' ) ) {
                    // pass current account to Order and filter by catalog number
                    $additionalInfoOrderStatus.attr(
                        'href',
                        '#' + ROUTES.ORDER_LISTING + '?'
                        + objectToQueryString( OrderListingPage.queryParams(
                        account,
                        [ additionalInfoData[ 'catalog_number' ] ]
                        ) )
                    );

                    // pass current account to Quote and filter by catalog number
                    $additionalInfoQuoteDetail.attr(
                        'href',
                        '#' + ROUTES.QUOTE_LISTING + '?'
                        + objectToQueryString( QuoteListingPage.queryParams(
                        account,
                        [ additionalInfoData[ 'catalog_number' ] ]
                        ) )
                    );
                }

                AccountFilter.setSelectedAccounts( account.split( ',' ) );
                IdFilter.setSearchIds( catalog.split( ',' ) );

                $packagingInformationIdLabel.text( account );

                showFn();
            } );
        }

        function navigate( params ) {
            Page.navigate( params );

            if ( ! PACatalogSummaryPage.isLoaded() ) {
                PACatalogSummaryPage.requireLoad( PACatalogSummaryPage.queryParams(
                    params.account,
                    params.catalog
                ) );
            }

            PACatalogSummaryPage.pushCrumb();
            pushCrumb();

            $additionalInfoPanel.show();

            $additionalInfoProductDrawing.hide();
            $additionalInfoProductDrawing.attr( 'href', '#' );
            $additionalInfoCatalogLink.attr( 'href', '#' );
            $additionalInfoOrderStatus.attr( 'href', '#' + ROUTES.ORDER_LISTING );
            $additionalInfoQuoteDetail.attr( 'href', '#' + ROUTES.QUOTE_LISTING );
        }

        function queryParams( accountNumber, catalogNumber ) {
            return {
                account: accountNumber,
                catalog: catalogNumber
            };
        }

        function pushCrumb() {
            if ( Loadable.isLoaded() ) {
                Breadcrumbs.push(
                    'Packaging Information: ' + AccountFilter.getAccountNameByNumber( getLoadedAccountNumber() ),
                    '#' + ROUTES.PA_PACKAGING_INFORMATION + PAPackagingInformationPage.queryString()
                );
            } else {
                Breadcrumbs.push(
                    'Packaging Information',
                    '#' + ROUTES.PA_PACKAGING_INFORMATION
                );
            }
        }

        function getLoadedAccountNumber() {
            var account = Loadable.getLoadParam( 'account' );
            if ( Array.isArray( account ) && account.length > 0 ) {
                account = account[ 0 ];
            }
            return account;
        }

        return {
            init: init,

            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },

            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams,

            hideAdditionalInfoPanel: function () {
                $additionalInfoPanel.hide()
            },

            pushCrumb: pushCrumb
        };
    } )();

    var QuoteLandingPage = ( function () {
        var $page = null;

        function init() {
            $page = $( '#quote-default-subpage' );
        }

        function show( queryParams ) {
            QuoteGroup.prepare();
            $page.show();
        }

        return {
            init: init,
            show: show
        };
    } )();
    var QuoteListingPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $resultsFound = null;
        var $resultsFor = null;
        var $listingTable = null;

        var listingTable = null;

        function init() {
            PageMixin( this, $( '#quote-account-subpage' ), QuoteGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $listingTable = $( '#quote-summary-table' );
            $resultsFound = $( '#quote-account-subpage .results_found_count' );
            $resultsFor = $( '#quote-summary-for-accounts' );

            initHandlers();
            initTables();
        }

        function initHandlers() {
            $listingTable.on( 'click', '.mps-quote-quotenumber-link', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                var accounts = getLoadedAccountNumbers();
                var quoteNumber = $( event.target ).data( 'quotenumber' );

                QuoteDetailsPage.requireLoad( QuoteDetailsPage.queryParams( accounts, quoteNumber ) );
                Router.navigate( ROUTES.QUOTE_DETAILS + QuoteDetailsPage.queryString() );
            } );
        }

        function initTables() {
            listingTable = $listingTable.DataTable( {
                'responsive': true,
                'columns': [
                    {
                        'className': 'quote-number',
                        'data': 'Quote Number',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' ) {
                                if ( row[ 'QuoteLink' ] ) {
                                    return '<a class="mps-quote-quotenumber-link">' +
                                        '<span data-quotenumber="' + row[ 'Quote Number' ] + '">' + row[ 'Quote Number' ] + '</span>' +
                                        '</a>';
                                } else {
                                    return row[ 'Quote Number' ];
                                }
                            }

                            return data;
                        }
                    },
                    {
                        'className': 'account-number',
                        'data': 'Account Number'
                    },
                    {
                        'className': 'end-user',
                        'data': 'End User'
                    },
                    {
                        'className': 'issued-date',
                        'data': 'Issued date'
                    },
                    {
                        'className': 'for-acceptance-by',
                        'data': 'For acceptance by'
                    },
                    {
                        'className': 'firm-price-period',
                        'data': 'Firm price period'
                    }
                ]
            } );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var accountsCsv = params.accounts;
            if ( Array.isArray( accountsCsv ) ) {
                accountsCsv = accountsCsv.join( ',' );
            }

            var catalogsCsv = params.catalogs !== '' ? params.catalogs : null;
            if ( Array.isArray( catalogsCsv ) ) {
                catalogsCsv = catalogsCsv.join( ',' );
            }

            mpsAjaxPost( {
                action: 'quotes_account_ajax',
                account_numbers: accountsCsv,
                catalog_numbers: catalogsCsv
            }, function ( data ) {
                AccountFilter.setSelectedAccounts( params.accounts );

                if ( catalogsCsv ) {
                    IdFilter.setSearchIds( catalogsCsv );
                    IdFilter.setFilterTypeToCatalog();
                }

                setTableData( listingTable, data );
                filterFn( IdFilter.getSearchIdsArray(), IdFilter.getFromDate(), IdFilter.getToDate(), IdFilter.getFilterType() );

                $resultsFound.text(
                    data.length + ' ' +
                    ( data.length === 1 ? 'Result' : 'Results' )
                );
                var loadedAccountNumbers = getLoadedAccountNumbers();
                $resultsFor.text(
                    Array.isArray( loadedAccountNumbers ) ?
                        loadedAccountNumbers.join( ',' ) : loadedAccountNumbers
                );

                Loadable.reload = false;
                showFn();
            } );
        }

        function queryParams( accounts, catalogs ) {
            var queryParams = {};

            if ( accounts && accounts !== '' )
                queryParams.accounts = accounts;

            if ( catalogs && catalogs !== '' )
                queryParams.catalogs = catalogs;

            return queryParams;
        }

        function getLoadedAccountNumbers() {
            return Loadable.getLoadParam( 'accounts' ) || [];
        }

        function pushCrumb() {
            var loadedAccountNumbersCsv = getLoadedAccountNumbers();
            if ( Array.isArray( loadedAccountNumbersCsv ) ) {
                loadedAccountNumbersCsv = loadedAccountNumbersCsv.join( ',' );
            }
            if ( Loadable.isLoaded() && getLoadedAccountNumbers() !== '' && getLoadedAccountNumbers() !== [] ) {
                Breadcrumbs.push(
                    'Quote Summary List: ' + loadedAccountNumbersCsv,
                    '#' + ROUTES.QUOTE_LISTING + QuoteListingPage.queryString()
                );
            } else {
                Breadcrumbs.push( 'Quote Summary List', '#' + ROUTES.QUOTE_LISTING );
            }
        }

        function filterFn( idInput, fromDateVal, toDateVal, filterType ) {
            listingTable.columns( '.quote-number' ).search( '' ).draw();
            listingTable.columns( '.issued-date' ).search( '' ).draw();

            if ( filterType === 'quote-number' ) {
                listingTable.columns( '.quote-number' ).search( idInput ).draw();
            }

            // if ( toDateInput )
            $.fn.DataTable.ext.search.push(
                function ( settings, data, dataIndex ) {
                    var fromDate = fromDateVal.split( "-" );
                    var fday = fromDate[ 2 ];
                    var fmonth = fromDate[ 1 ];
                    var fyear = fromDate[ 0 ];
                    var toDate = toDateVal.split( "-" );
                    var tday = toDate[ 2 ];
                    var tmonth = toDate[ 1 ];
                    var tyear = toDate[ 0 ];
                    var min = new Date( fyear, parseInt( fmonth ) - 1, parseInt( fday ) );
                    var max = new Date( tyear, parseInt( tmonth ) - 1, parseInt( tday ) + 1 );
                    var startDate = data[ 3 ].split( "/" );
                    var sday = startDate[ 1 ];
                    var smonth = startDate[ 0 ];
                    var syear = startDate[ 2 ];
                    var evalDate = new Date( syear, parseInt( smonth ) - 1, parseInt( sday ) );
                    if ( typeof fday === "undefined" && typeof tday === "undefined" ) {
                        return true;
                    }
                    if ( typeof fday === "undefined" && evalDate <= max ) {
                        return true;
                    }
                    if ( typeof tday === "undefined" && evalDate >= min ) {
                        return true;
                    }
                    return evalDate <= max && evalDate >= min;
                }
            );
            listingTable.columns( '.issued-date' ).search( '' ).draw();
            $.fn.DataTable.ext.search.pop();
        }

        function navigate( params ) {
            Page.navigate( params );
            pushCrumb();
        }

        return {
            init: init,
            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },
            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams,
            getLoadedAccountNumbers: getLoadedAccountNumbers,
            pushCrumb: pushCrumb,
            filter: filterFn,
            isLoaded: function () {
                return Loadable.isLoaded();
            }
        };
    } )();
    var QuoteDetailsPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $backToAccountsButton = null;

        var $quoteStatusTable = null;
        var $quoteLineItemsTable = null;

        var quoteStatusTable = null;
        var quoteLineItemsTable = null;

        var $resultsFor = null;

        function init() {
            PageMixin( this, $( '#quote-id-subpage' ), QuoteGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $backToAccountsButton = $( '#back-to-accounts-button' );

            $quoteStatusTable = $( '#quote-status-table' );
            $quoteLineItemsTable = $( '#quote-line-items' );

            $resultsFor = $( '#quote-details-for-id' );

            initHandlers();
            initTables();
        }

        function initHandlers() {
            $backToAccountsButton.on( 'click', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                Router.navigate( ROUTES.QUOTE_LISTING + QuoteListingPage.queryString() );
            } );

            $quoteLineItemsTable.on( 'click', '.mps-quote-comment-link', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                showCommentsModal( event );
            } );

            $( "#quote-id-subpage" ).on( "click", ".export_to_excel", function ( event ) {
                event.preventDefault();
                event.stopPropagation();
                var data = {};
                quoteStatusTable.rows().every( function ( rowIndex, tableLoopCounter, rowLoopCounter, und ) {
                    for ( var i = 0; i < 5; i++ ) {
                        data[ Object.keys( this.data() )[ i ] ] = this.data()[ Object.keys( this.data() )[ i ] ];
                    }
                } );
                quoteLineItemsTable.rows().every( function ( rowIndex, tableLoopCounter, rowLoopCounter, und ) {
                    var subData = {};
                    data[ "Line Items" ] = [];
                    for ( var i = 0; i < 12; i++ ) {
                        subData[ Object.keys( this.data() )[ i ] ] = this.data()[ Object.keys( this.data() )[ i ] ];
                    }
                    data[ "Line Items" ].push( subData );
                } );
                JSONToCSVConverter( data, "Quote Details List For: " + getLoadedQuoteNumber(), true );
            } );
        }

        function initTables() {
            quoteLineItemsTable = $quoteLineItemsTable.DataTable( {
                'responsive': true,
                "paging": false,
                buttons: [
                    { extend: "excel", className: "buttonsToHide" }
                ],
                'columns': [
                    {
                        'data': 'Line Item'
                    },
                    {
                        'data': 'Qty Quoted'
                    },
                    {
                        'data': 'Qty Purchased'
                    },
                    {
                        'className': 'catalog-number',
                        'data': 'Catalog #'
                    },
                    {
                        'data': 'Cust Part'
                    },
                    {
                        'data': 'UPC CODE'
                    },
                    {
                        'data': 'Description From'
                    },
                    {
                        'data': 'Price',
                        'render': dataTablePriceRenderer
                    },
                    {
                        'data': 'Extension Lead Time'
                    },
                    {
                        'data': 'Package Quantity'
                    }
                ]
            } );

            quoteStatusTable = $quoteStatusTable.DataTable( {
                'responsive': true,
                "paging": false,
                buttons: [
                    { extend: "excel", className: "buttonsToHide" }
                ],
                'columns': [
                    {
                        'data': 'Customer Number'
                    },
                    {
                        'data': 'Quote Number'
                    },
                    {
                        'data': 'End User'
                    },
                    {
                        'data': 'Issued date'
                    },
                    {
                        'data': 'For acceptance by'
                    },
                    {
                        'data': 'Firm price period'
                    }
                ]
            } );

            quoteStatusTable.buttons( '.buttonsToHide' ).nodes().css( "display", "none" );
            quoteLineItemsTable.buttons( '.buttonsToHide' ).nodes().css( "display", "none" );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var accountNumbers = params.accounts;
            var quoteNumber = params.quote_number;

            if ( Array.isArray( accountNumbers ) ) {
                accountNumbers = accountNumbers.join( ',' );
            }

            mpsAjaxPost( {
                action: 'quotes_id_ajax',
                account_numbers: accountNumbers,
                quote_number: quoteNumber
            }, function ( data ) {
                var accounts = [];
                for ( var accountNumber in data ) {
                    if ( data.hasOwnProperty( accountNumber ) )
                        accounts.push( data[ accountNumber ] );
                }

                var lineItems = [];
                accounts.forEach( function ( account ) {
                    account[ 'Line Items' ].forEach( function ( lineItem ) {
                        lineItems.push( lineItem );
                    } );
                } );

                setTableData( quoteStatusTable, accounts );
                setTableData( quoteLineItemsTable, lineItems );

                AccountFilter.setSelectedAccounts( accountNumbers.split( ',' ) );

                $resultsFor.text( getLoadedQuoteNumber() );

                quoteLineItemsTable.columns( '.catalog-number' ).search( '' ).draw();

                showFn();
            } );
        }

        function showCommentsModal( event ) {
            if ( ! $( event.target ).hasClass( 'mps-quote-comment-link' ) ) {
                event.target = $( event.target ).parent();
            }
            showSpinner( $( event.target ) );
            if (
                $( event.target ).hasClass( 'mps-quote-comment-link' ) &&
                $( event.target ).find( '.modal' ).length < 1
            ) {
                $.ajax( {
                    cache: false,
                    type: 'POST',
                    url: maclean_ajax_url,
                    data: {
                        action: 'get_quote_comment',
                        quote_number: $( event.target ).data( 'quoteno' ),
                        item: $( event.target ).data( 'item' )
                    },
                    success: function ( data ) {
                        hideSpinner( $( event.target ) );
                        $( event.target ).append( data );
                        $( event.target )
                            .find( '.modal' )
                            .clone()
                            .dialog( {
                                autoOpen: true,
                                close: function ( ev, ui ) {
                                    $( this )
                                        .dialog( 'destroy' )
                                        .remove();
                                }
                            } );
                    }
                } );
            } else {
                hideSpinner( $( event.target ) );
                $( event.target )
                    .find( '.modal' )
                    .clone()
                    .dialog( {
                        autoOpen: true,
                        close: function ( ev, ui ) {
                            $( this )
                                .dialog( 'destroy' )
                                .remove();
                        }
                    } );
            }
        }

        function queryParams( accounts, quoteNumber ) {
            return {
                accounts: accounts,
                quote_number: quoteNumber
            };
        }

        function getLoadedQuoteNumber() {
            return Loadable.getLoadParam( 'quote_number' );
        }

        function pushCrumb() {
            if ( Loadable.isLoaded() && getLoadedQuoteNumber() !== '' ) {
                Breadcrumbs.push(
                    'Quote Details: ' + getLoadedQuoteNumber(),
                    '#' + ROUTES.QUOTE_DETAILS + QuoteDetailsPage.queryString()
                );
            } else {
                Breadcrumbs.push( 'Quote Details', '#' + ROUTES.QUOTE_DETAILS );
            }
        }

        function navigate( params ) {
            Page.navigate( params );

            if ( ! QuoteListingPage.isLoaded() ) {
                QuoteListingPage.requireLoad( QuoteListingPage.queryParams( params.accounts ) );
            }

            IdFilter.setAvailableFilterTypes( [
                IdFilter.FILTER_TYPES.CATALOG_NUMBER
            ] );

            QuoteListingPage.pushCrumb();
            pushCrumb();
        }

        function filterFn( idInput, filterType ) {
            if ( filterType === 'catalog-number' ) {
                quoteLineItemsTable.columns( '.catalog-number' ).search( idInput ).draw();
            }
        }

        return {
            init: init,
            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },
            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams,
            filter: filterFn
        };
    } )();

    var OrderLandingPage = ( function () {
        var $page = null;

        function init() {
            $page = $( '#order-default-subpage' );
        }

        function show( queryParams ) {
            OrderGroup.prepare();
            $page.show();
        }

        return {
            init: init,
            show: show
        };
    } )();
    var OrderListingPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $orderSummaryTable = null;
        var orderSummaryTable = null;

        var $resultsCount = null;
        var $resultsFor = null;

        function init() {
            PageMixin( this, $( '#order-account-subpage' ), OrderGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $orderSummaryTable = $( '#order-account-listing-table' );
            $resultsCount = $( '#order-summary-results-count' );
            $resultsFor = $( '#order-listing-for-id' );

            initHandlers();
            initTables();
        }

        function initHandlers() {
            $orderSummaryTable.on( 'click', '.mps-order-ponumber-link', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                var poNumber = $( event.target ).data( 'ponumber' );

                OrderDetailsPage.requireLoad( OrderDetailsPage.queryParams( getLoadedAccountNumbers(), poNumber ) );
                Router.navigate( ROUTES.ORDER_DETAILS + OrderDetailsPage.queryString() );
            } );
        }

        function initTables() {
            orderSummaryTable = $orderSummaryTable.DataTable( {
                'responsive': true,
                "order": [ [ 2, 'desc' ] ],
                'columns': [
                    {
                        'className': 'po-number',
                        'data': 'PO #',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' || type === 'filter' ) {
                                if ( row[ 'OrderLink' ] !== '#' ) {
                                    return '<a class="mps-order-ponumber-link">' +
                                        '<span data-ponumber="' + row[ 'PO #' ] + '">' + row[ 'PO #' ] + '</span>' +
                                        '</a>';
                                } else {
                                    return '<span>' + row[ 'PO #' ] + '</span>';
                                }
                            }

                            return data;
                        }
                    },
                    {
                        'className': 'order-number',
                        'data': 'Order #'
                    },
                    {
                        'className': 'po-date',
                        'data': 'PO Date',
                        'type': 'date'
                    },
                    {
                        'className': 'customer-name',
                        'data': 'Customer Name'
                    },
                    {
                        'className': 'ship-to',
                        'data': 'Ship To'
                    },
                    {
                        'className': 'city-state-zip',
                        'data': 'City State Zip'
                    },
                    {
                        'className': 'status',
                        'data': 'Status'
                    }
                ]
            } );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var accounts = params.accounts;
            if ( Array.isArray( accounts ) ) {
                accounts = accounts.join( ',' );
            }

            var catalogsCsv = params.catalogs !== '' ? params.catalogs : null;
            if ( Array.isArray( catalogsCsv ) ) {
                catalogsCsv = catalogsCsv.join( ',' );
            }

            mpsAjaxPost( {
                action: 'orders_account_ajax',
                account_numbers: accounts,
                catalog_numbers: catalogsCsv
            }, function ( data ) {
                AccountFilter.setSelectedAccounts( params.accounts );

                if ( catalogsCsv ) {
                    IdFilter.setSearchIds( catalogsCsv );
                    IdFilter.setFilterTypeToCatalog();
                }

                setTableData( orderSummaryTable, data );
                filterFn( IdFilter.getSearchIdsArray(), IdFilter.getFilterStatus(), IdFilter.getFromDate(), IdFilter.getToDate(), IdFilter.getFilterType() );

                $resultsCount.text( orderSummaryTable.rows().count() );

                var loadedAccounts = getLoadedAccountNumbers();
                var resultsForText = Array.isArray( loadedAccounts ) ? loadedAccounts.join( ',' ) : loadedAccounts;
                $resultsFor.text( resultsForText );

                Loadable.reload = false;
                showFn();
            } );
        }

        function queryParams( accounts, catalogs ) {
            var queryParams = {};

            if ( accounts && accounts !== '' )
                queryParams.accounts = accounts;

            if ( catalogs && catalogs !== '' )
                queryParams.catalogs = catalogs;

            return queryParams;
        }

        function getLoadedAccountNumbers() {
            return Loadable.getLoadParam( 'accounts' ) || [];
        }

        function pushCrumb() {
            if ( Loadable.isLoaded() ) {
                Breadcrumbs.push(
                    'Order Summary List: ' + getLoadedAccountNumbers(),
                    '#' + ROUTES.ORDER_LISTING + OrderListingPage.queryString()
                );
            } else {
                Breadcrumbs.push(
                    'Order Summary List',
                    '#' + ROUTES.ORDER_LISTING
                );
            }
        }

        function navigate( params ) {
            Page.navigate( params );
            pushCrumb();
        }

        function clearFilters() {
            orderSummaryTable.columns( '.po-number' ).search( '' ).draw();
            orderSummaryTable.columns( '.order-number' ).search( '' ).draw();
        }

        function filterFn( idInput, statusInputVal, fromDateVal, toDateVal, filterType ) {
            clearFilters();

            if ( filterType === 'po-number' ) {
                orderSummaryTable.columns( '.po-number' ).search( idInput ).draw();
            } else if ( filterType === 'order-number' ) {
                orderSummaryTable.columns( '.order-number' ).search( idInput ).draw();
            }

            orderSummaryTable.columns( '.status' ).search( statusInputVal ).draw();

            // if ( toDateInput )
            $.fn.DataTable.ext.search.push(
                function ( settings, data, dataIndex ) {
                    var fromDate = fromDateVal.split( "-" );
                    var fday = fromDate[ 2 ];
                    var fmonth = fromDate[ 1 ];
                    var fyear = fromDate[ 0 ];
                    var toDate = toDateVal.split( "-" );
                    var tday = toDate[ 2 ];
                    var tmonth = toDate[ 1 ];
                    var tyear = toDate[ 0 ];
                    var min = new Date( fyear, parseInt( fmonth ) - 1, parseInt( fday ) );
                    var max = new Date( tyear, parseInt( tmonth ) - 1, parseInt( tday ) + 1 );
                    var startDate = data[ 2 ].split( "/" );
                    var sday = startDate[ 1 ];
                    var smonth = startDate[ 0 ];
                    var syear = startDate[ 2 ];
                    var evalDate = new Date( syear, parseInt( smonth ) - 1, parseInt( sday ) );
                    if ( typeof fday === "undefined" && typeof tday === "undefined" ) {
                        return true;
                    }
                    if ( typeof fday === "undefined" && evalDate <= max ) {
                        return true;
                    }
                    if ( typeof tday === "undefined" && evalDate >= min ) {
                        return true;
                    }
                    return evalDate <= max && evalDate >= min;
                }
            );
            orderSummaryTable.columns( '.po-date' ).search( '' ).draw();
            $.fn.DataTable.ext.search.pop();
        }

        return {
            init: init,
            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },
            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams,
            getLoadedAccountNumbers: getLoadedAccountNumbers,
            pushCrumb: pushCrumb,
            filter: filterFn,
            isLoaded: function () {
                return Loadable.isLoaded();
            }
        };
    } )();
    var OrderDetailsPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $orderDetailsStatusTable = null;
        var $orderDetailsLineItemsTable = null;
        var $backButton = null;
        var $orderNumber = null;

        var orderDetailsStatusTable = null;
        var orderDetailsLineItemsTable = null;

        function init() {
            PageMixin( this, $( '#order-id-subpage' ), OrderGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $orderDetailsStatusTable = $( '#order-status-table' );
            $orderDetailsLineItemsTable = $( '#order-line-items-table' );

            $backButton = $( '#back-to-order-summary' );
            $orderNumber = $( '#order-details-for-id' );

            initHandlers();
            initTables();
        }

        function initHandlers() {
            $backButton.on( 'click', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                Router.navigate( ROUTES.ORDER_LISTING + OrderListingPage.queryString() );
            } );

            $orderDetailsLineItemsTable.on( 'click', '.catalog-number a', function ( event ) {
                event.stopPropagation();
                event.preventDefault();

                OrderPackagingPage.requireLoad( OrderPackagingPage.queryParams(
                    $( '#order-status-table td.customer-number' ).text(),
                    $( event.target ).data( 'catalog-number' ),
                    getLoadedPONumber()
                ) );
                Router.navigate( ROUTES.ORDER_PACKAGING + OrderPackagingPage.queryString() );
            } );
        }

        function initTables() {
            orderDetailsStatusTable = $orderDetailsStatusTable.DataTable( {
                'responsive': true,
                "paging": false,
                buttons: [
                    { extend: "excel", className: "buttonsToHide" }
                ],
                'columns': [
                    {
                        'className': 'customer-number',
                        'data': 'Customer #'
                    },
                    {
                        'className': 'po-number',
                        'data': 'PO #'
                    },
                    {
                        'className': 'po-date',
                        'data': 'PO Date'
                    },
                    {
                        'className': 'shipping-info',
                        'data': 'ShippingInfoLineReturned'
                    }
                ]
            } );
            orderDetailsLineItemsTable = $orderDetailsLineItemsTable.DataTable( {
                'responsive': true,
                "paging": false,
                buttons: [
                    { extend: "excel", className: "buttonsToHide" }
                ],
                'columns': [
                    {
                        'className': 'order-number',
                        'data': 'Order #'
                    },
                    {
                        'className': 'line-item',
                        'data': 'Line Item'
                    },
                    {
                        'className': 'status',
                        'data': 'Status'
                    },
                    {
                        'className': 'catalog-number',
                        'data': 'Catalog #',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' || type === 'filter' ) {
                                return '<a data-catalog-number="' + data + '">' + data + '</a>';
                            } else {
                                return data;
                            }
                        }
                    },
                    {
                        'className': 'cust-part',
                        'data': 'Cust Part'
                    },
                    {
                        'className': 'price',
                        'data': 'Price',
                        'render': dataTablePriceRenderer
                    },
                    {
                        'className': 'qty',
                        'data': 'Qty'
                    },
                    {
                        'className': 'original-acknowledge-date',
                        'data': 'Orig Ack Date'
                    },
                    {
                        'className': 'current-available-date',
                        'data': 'Current Avail Date'
                    },
                    {
                        'className': 'date-shipped',
                        'data': 'Date Shipped'
                    },
                    {
                        'className': 'tracking',
                        'data': 'Tracking'
                    },
                    {
                        'className': 'packing-slip',
                        'data': 'Packing Slip',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' || type === 'filter' ) {
                                if ( row[ 'Packing Slip' ] !== '' ) {
                                    return '<a target="_blank" href="' + row[ 'Packing Slip' ] + '">' +
                                        '<span>Packing Slip</span>' +
                                        '</a>';
                                } else {
                                    return '';
                                }
                            }
                            return data;
                        }
                    }
                ]
            } );

            orderDetailsStatusTable.buttons( '.buttonsToHide' ).nodes().css( "display", "none" );
            orderDetailsLineItemsTable.buttons( '.buttonsToHide' ).nodes().css( "display", "none" );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var poNumber = params.po_number;
            var accounts = params.accounts;
            if ( Array.isArray( accounts ) ) {
                accounts = accounts.join( ',' );
            }

            mpsAjaxPost( {
                action: 'orders_id_ajax',
                po_number: poNumber,
                account_numbers: accounts
            }, function ( data ) {
                $orderNumber.text( poNumber );

                setTableData( orderDetailsStatusTable, [ data[ 0 ] ] );
                setTableData( orderDetailsLineItemsTable, data[ 1 ] );

                Loadable.reload = false;

                AccountFilter.setSelectedAccounts( accounts.split( ',' ) );

                showFn();
            } );
        }

        function queryParams( accounts, poNumber ) {
            return {
                accounts: accounts,
                po_number: poNumber
            };
        }

        function pushCrumb() {
            if ( Loadable.isLoaded() ) {
                Breadcrumbs.push(
                    'Order Details: ' + getLoadedPONumber(),
                    '#' + ROUTES.ORDER_DETAILS + OrderDetailsPage.queryString()
                );
            } else {
                Breadcrumbs.push(
                    'Order Details',
                    '#' + ROUTES.ORDER_DETAILS
                );
            }
        }

        function navigate( params ) {
            Page.navigate( params );

            if ( ! OrderListingPage.isLoaded() ) {
                OrderListingPage.requireLoad( OrderListingPage.queryParams( params.accounts ) )
            }

            IdFilter.setAvailableFilterTypes( [
                IdFilter.FILTER_TYPES.CATALOG_NUMBER,
                IdFilter.FILTER_TYPES.ORDER_NUMBER
            ] );

            OrderListingPage.pushCrumb();
            pushCrumb();
        }

        function getLoadedPONumber() {
            return Loadable.getLoadParam( 'po_number' );
        }

        function filterFn( idInput, statusInputVal, fromDateVal, toDateVal, filterType ) {
            orderDetailsLineItemsTable.columns( '.catalog-number' ).search( '' ).draw();
            orderDetailsLineItemsTable.columns( '.order-number' ).search( '' ).draw();

            if ( filterType === 'catalog-number' ) {
                orderDetailsLineItemsTable.columns( '.catalog-number' ).search( idInput ).draw();
            } else if ( filterType === 'order-number' ) {
                orderDetailsLineItemsTable.columns( '.order-number' ).search( idInput ).draw();
            }

            orderDetailsLineItemsTable.columns( '.status' ).search( statusInputVal ).draw();

            // if ( toDateInput )
            $.fn.DataTable.ext.search.push(
                function ( settings, data, dataIndex ) {
                    var fromDate = fromDateVal.split( "-" );
                    var fday = fromDate[ 2 ];
                    var fmonth = fromDate[ 1 ];
                    var fyear = fromDate[ 0 ];
                    var toDate = toDateVal.split( "-" );
                    var tday = toDate[ 2 ];
                    var tmonth = toDate[ 1 ];
                    var tyear = toDate[ 0 ];
                    var min = new Date( fyear, parseInt( fmonth ) - 1, parseInt( fday ) );
                    var max = new Date( tyear, parseInt( tmonth ) - 1, parseInt( tday ) + 1 );
                    var startDate = data[ 9 ].split( "/" );
                    var sday = startDate[ 1 ];
                    var smonth = startDate[ 0 ];
                    var syear = startDate[ 2 ];
                    var evalDate = new Date( syear, parseInt( smonth ) - 1, parseInt( sday ) );
                    if ( typeof fday === "undefined" && typeof tday === "undefined" ) {
                        return true;
                    }
                    if ( typeof fday === "undefined" && evalDate <= max ) {
                        return true;
                    }
                    if ( typeof tday === "undefined" && evalDate >= min ) {
                        return true;
                    }
                    return evalDate <= max && evalDate >= min;
                }
            );
            orderDetailsLineItemsTable.columns( '.date-shipped' ).search( '' ).draw();
            $.fn.DataTable.ext.search.pop();
        }

        return {
            init: init,
            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },
            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams,
            filter: filterFn,
            isLoaded: function () {
                return Loadable.isLoaded();
            }
        };
    } )();
    var OrderPackagingPage = ( function () {
        var Page = null;
        var Loadable = null;
        var Showable = null;

        var $orderNumber = null;
        var $orderPackagingInfoTable = null;
        var $orderPackagingLineItemsTable = null;

        var $backButton = null;

        var packagingInfoTable = null;
        var lineItemsTable = null;

        function init() {
            PageMixin( this, $( '#order-packaging-subpage' ), OrderGroup, load );

            Page = this.Page;
            Loadable = this.Loadable;
            Showable = this.Showable;

            $orderNumber = $( '#order-packaging-for-id' );

            $backButton = $( '#back-to-order-details-from-packaging' );

            $orderPackagingInfoTable = $( '#order-packaging-info-table' );
            $orderPackagingLineItemsTable = $( '#order-packaging-line-items-table' );

            initHandlers();
            initTables();
        }

        function initHandlers() {
            $backButton.on( 'click', function ( event ) {
                event.preventDefault();
                event.stopPropagation();

                Router.navigate( ROUTES.ORDER_DETAILS + OrderDetailsPage.queryString() );
            } );
        }

        function initTables() {
            packagingInfoTable = $orderPackagingInfoTable.DataTable( {
                'responsive': true,
                'bSort': false,
                'columns': [
                    {
                        'data': 'standard-package-quantity'
                    },
                    {
                        'data': 'pallet-quantity'
                    },
                    {
                        'data': 'weight-ea'
                    },
                    {
                        'data': 'unit-of-measure'
                    }
                ]
            } );

            lineItemsTable = $orderPackagingLineItemsTable.DataTable( {
                'responsive': true,
                "paging": false,
                buttons: [
                    { extend: "excel", className: "buttonsToHide" }
                ],
                'columns': [
                    {
                        'className': 'order-number',
                        'data': 'Order #'
                    },
                    {
                        'className': 'status',
                        'data': 'Status'
                    },
                    {
                        'className': 'line-item',
                        'data': 'Line Item'
                    },
                    {
                        'className': 'catalog-number',
                        'data': 'Catalog #',
                        'render': function ( data, type, row ) {
                            if ( type === 'display' || type === 'filter' ) {
                                return '<a data-catalog-number="' + data + '">' + data + '</a>';
                            } else {
                                return data;
                            }
                        }
                    },
                    {
                        'className': 'cust-part',
                        'data': 'Cust Part'
                    },
                    {
                        'className': 'price',
                        'data': 'Price',
                        'render': dataTablePriceRenderer
                    },
                    {
                        'className': 'qty',
                        'data': 'Qty'
                    },
                    {
                        'className': 'original-acknowledge-date',
                        'data': 'Orig Ack Date'
                    },
                    {
                        'className': 'current-available-date',
                        'data': 'Current Avail Date'
                    },
                    {
                        'className': 'date-shipped',
                        'data': 'Date Shipped'
                    },
                    {
                        'className': 'tracking',
                        'data': 'Tracking'
                    }
                ]
            } );
            lineItemsTable.buttons( '.buttonsToHide' ).nodes().css( "display", "none" );
        }

        function load( params, showFn ) {
            if ( $.isEmptyObject( params ) ) {
                return;
            }

            var account = params.account;
            var catalog = params.catalog;
            var poNumber = params.po_number;

            mpsAjaxPost( {
                action: 'price_and_availability_id_ajax',
                catalog_numbers: catalog,
                account_numbers: account
            }, function ( data ) {
                var packagingInfoTableData = [ data[ 0 ] ];
                setTableData( packagingInfoTable, packagingInfoTableData );

                mpsAjaxPost( {
                    action: 'orders_id_ajax',
                    po_number: poNumber,
                    account_numbers: account
                }, function ( data ) {
                    $orderNumber.text( Loadable.getLoadParam( 'catalog' ) );

                    setTableData( lineItemsTable, data[ 1 ] );

                    $( '#orders-packaging-header .export' ).hide();

                    AccountFilter.setSelectedAccounts( account );

                    showFn();
                } );
            } );
        }

        function queryParams( accountNumber, catalogNumber, poNumber ) {
            return {
                account: accountNumber,
                catalog: catalogNumber,
                po_number: poNumber
            };
        }

        function pushCrumb() {
            if ( Loadable.isLoaded() ) {
                Breadcrumbs.push(
                    'Packaging Information: ' + Loadable.getLoadParam( 'catalog' ),
                    '#' + ROUTES.ORDER_PACKAGING + OrderPackagingPage.queryString()
                );
            } else {
                Breadcrumbs.push(
                    'Packaging Information',
                    '#' + ROUTES.ORDER_PACKAGING
                );
            }
        }

        function navigate( params ) {
            Page.navigate( params );

            AccountFilter.hide();
            IdFilter.hide();

            if ( ! OrderDetailsPage.isLoaded() ) {
                OrderDetailsPage.requireLoad( OrderDetailsPage.queryParams(
                    params.account,
                    params.po_number
                ) );
            }

            OrderListingPage.pushCrumb();
            pushCrumb();
        }

        return {
            init: init,

            navigate: navigate,
            requireLoad: function ( params ) {
                Loadable.requireLoad( params );
            },

            queryString: function () {
                return Page.queryString();
            },
            queryParams: queryParams
        };
    } )();

    // INITIALIZATION ==================================================================================================

    $( function () {
        // INIT PAGE GROUPS

        HomeGroup.init();
        PriceAvailabilityGroup.init();
        QuoteGroup.init();
        OrderGroup.init();

        // INIT PAGES

        HomeLandingPage.init();

        PALandingPage.init();
        PACatalogSummaryPage.init();
        PAPackagingInformationPage.init();

        QuoteLandingPage.init();
        QuoteListingPage.init();
        QuoteDetailsPage.init();

        OrderLandingPage.init();
        OrderListingPage.init();
        OrderDetailsPage.init();
        OrderPackagingPage.init();

        Announcements.init();
        AccountFilter.init();
        IdFilter.init();
        Navigator.init();
        FilterColumn.init();

        Breadcrumbs.init();

        PageManager.init();
        PageManager.hideAll();

        Router.init();

        var parts = window.location.href.split( '#' );
        if ( parts.length === 2 ) {
            Router.navigate( parts[ 1 ] );
        } else {
            Router.navigate();
        }

        $( '#mpservicenet form' ).on( 'submit', function ( event ) {
            event.preventDefault();
            event.stopPropagation();
        } );

        $( '#mpsnet' ).show();
    } );
} )( jQuery );