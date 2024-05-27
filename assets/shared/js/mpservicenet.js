(function($) {
  var KEYCODES = {
    ENTER: 13
  };

  var ROUTES = {
    // first key is home page
    LANDING: "",

    PA_LANDING: "/price-availability",
    PA_CATALOG_SUMMARY: "/price-availability/catalog-summary",
    PA_PACKAGING_INFORMATION: "/price-availability/packaging-information",

    QUOTE_LANDING: "/quote",
    QUOTE_SUMMARY: "/quote/summary",
    QUOTE_DETAILS: "/quote/details",

    ORDER_LANDING: "/order",
    ORDER_SUMMARY: "/order/summary",
    ORDER_DETAILS: "/order/details",
    ORDER_PACKAGING: "/order/packaging"
  };

  var FULLSCREEN_CSS_CLASS = "content-fullscreen";

  var $mpservicenet = null;

  // =================================================================================================================
  // MISC FUNCTIONS
  // =================================================================================================================

  function fromQueryString(str) {
    // remove leading ?
    if (str && str.charAt(0) === "?") str = str.slice(1);

    var obj = {};

    var pairs = str.split("&");

    // parse each key-value pair
    pairs.forEach(function(pair) {
      pair = pair.split("=");

      var key = pair[0];
      var value = pair[1];

      if (!value) return;

      if (value.includes(",")) obj[key] = value.split(",");
      else obj[key] = value;
    });

    return obj;
  }

  function toQueryString(obj) {
    if (!obj) return "";

    var str = "";

    Object.keys(obj).forEach(function(key) {
      var value = obj[key];

      if (!value) return;

      if (Array.isArray(value) && !value.length) return;

      var valueString = Array.isArray(value) ? value.join(",") : value;

      str += key + "=" + valueString + "&";
    });

    // remove trailing &
    if (str.charAt(str.length - 1) === "&") str = str.slice(0, -1);

    return str ? "?" + str : "";
  }

  function showSpinner(element) {
    $(element).LoadingOverlay("show");
  }

  function hideSpinner(element) {
    $(element).LoadingOverlay("hide");
  }

  function showContentSpinner() {
    showSpinner("#mpservicenet");
  }

  function hideContentSpinner() {
    hideSpinner("#mpservicenet");
  }

  function ajax(data_in, onSuccessFn, errorFn) {
    errorFn = errorFn || $.noop;

    $.ajax({
      cache: false,
      type: "POST",
      url: maclean_ajax_url,
      data: data_in,
      success: function(data) {
        data = JSON.parse(data);

        if (typeof data !== "object") alert(data);
        else onSuccessFn(data);
      },
      error: errorFn
    });
  }

  function createURL(route, query) {
    return "#" + route + "?" + query;
  }

  function isString(str) {
    return typeof str === "string";
  }

  function stringOrNull(str) {
    return !str || (str && !isString(str)) ? null : str;
  }

  function arrayOrNull(arr) {
    return !arr || (arr && !Array.isArray(arr)) ? null : arr;
  }

  function error(msg) {
    console.log(msg);
    console.trace();
  }

  function getUrlParameter(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    var results = regex.exec(location.search);
    return results === null
      ? ""
      : decodeURIComponent(results[1].replace(/\+/g, " "));
  }

  function removeURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlpartsHash = url.split("#");
    var urlHash = "";
    if (typeof urlpartsHash[1] !== "undefined") {
      urlHash = "#" + urlpartsHash[1];
    }
    url = url.replace(urlHash, "");
    var urlparts = url.replace("#", "").split("?");
    if (urlparts.length >= 2) {
      var prefix = encodeURIComponent(parameter) + "=";
      var pars = urlparts[1].split(/[&;]/g);

      //reverse iteration as may be destructive
      for (var i = pars.length; i-- > 0; ) {
        //idiom for string.startsWith
        if (pars[i].lastIndexOf(prefix, 0) !== -1) {
          pars.splice(i, 1);
        }
      }

      return (
        urlparts[0] + (pars.length > 0 ? "?" + pars.join("&") : "") + urlHash
      );
    }
    return url + urlHash;
  }

  function JSONToCSVConverter(arrData, ReportTitle, ShowLabel) {
    if (Object.getOwnPropertyNames(arrData).length === 1) {
      for (var index in arrData) {
        arrData = [arrData[index]];
        break;
      }
    }
    var CSV = "";
    var CSV_part_2 = "";
    CSV += ReportTitle + "\r\n\n";
    if (ShowLabel) {
      var row = "";
      for (var index in arrData) {
        if (index === "Line Items" || index === "items") {
          continue;
        }
        row += index + ",";
      }
      row = row.slice(0, -1);
      CSV += row + "\r\n";
    }
    var indexRow = "";
    for (var index in arrData) {
      var row = "";
      if (index === "Line Items" || index === "items") {
        var row = "";
        if (ShowLabel) {
          for (var index_inner in arrData[index][0]) {
            row += index_inner + ",";
          }
          row = row.slice(0, row.length - 1);
          CSV_part_2 += row + "\r\n";
        }
        for (var i = 0; i < arrData[index].length; i++) {
          row = "";
          for (var index_inner in arrData[index][i]) {
            row += '"' + arrData[index][i][index_inner] + '",';
          }
          row = row.slice(0, row.length - 1);
          CSV_part_2 += row + "\r\n";
          row = "";
        }
      } else {
        indexRow += '"' + arrData[index] + '",';
      }
    }
    indexRow = indexRow.slice(0, indexRow.length - 1);
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
      .slice(0, 19)
      .replace(/[^0-9]/g, "");
    var fileName = "Maclean_";

    fileName += ReportTitle.replace(/ /g, "_");

    if (navigator.msSaveBlob) {
      // IE 10+
      var blob = new Blob([CSV + "\r\n\n" + CSV_part_2], {
        type: "text/csv;charset=utf8;"
      });
      navigator.msSaveBlob(blob, fileName + "_" + formattedDate + ".csv");
    } else {
      var uri =
        "data:text/csv;charset=utf-8," + escape(CSV + "\r\n\n" + CSV_part_2);
      var link = document.createElement("a");
      link.href = uri;
      link.style = "visibility:hidden";
      link.download = fileName + "_" + formattedDate + ".csv";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }

  function equivalentParameters(params1, params2) {
    return JSON.stringify(params1) === JSON.stringify(params2);
  }

  // =================================================================================================================
  // DATA TABLE UTILITIES
  // =================================================================================================================
  function resetTables() {
    jQuery("table").each(function(index, element) {
      var t = jQuery(element).DataTable();
      t.columns.adjust().draw();
    });
  }

  function resolveSideTable(table_element) {
    // Cache the highest
    var highestBox = 0;
    // Select and loop the elements you want to equalise
    $(table_element)
      .find("th")
      .each(function(index, element) {
        // If this box is higher than the cached highest then store it
        if ($(element).height() > highestBox) {
          highestBox = $(element).height();
        }
      });

    $(table_element)
      .find("td")
      .each(function(index, element) {
        // If this box is higher than the cached highest then store it
        if ($(element).height() > highestBox) {
          highestBox = $(element).height();
        }
      });

    // Set the height of all those children to whichever was highest
    $(table_element)
      .find("th")
      .height(highestBox);
    $(table_element)
      .find("td")
      .height(highestBox);
  }

  function setTableData(table, data) {
    table.clear().draw();
    table.rows.add(data);
    table.draw();
    table.columns.adjust().draw();
  }

  function dataTableCommaSeparatedNumberRenderer(data, type, row) {
    if (type === "display" || type === "filter") {
      return parseFloat(data).toLocaleString("en-US");
    }
    return data;
  }

  function dataTablePriceRenderer(data, type, row) {
    if (type === "display" || type === "filter") {
      if (data === "0.00") {
        return "";
      }

      return (
        "$" +
        parseFloat(data).toLocaleString("en-US", {
          maximumFractionDigits: 2,
          minimumFractionDigits: 2
        })
      );
    }

    return data;
  }

  function dataTableNumberRenderer(data, type, row) {
    return data;
  }

  // =================================================================================================================
  // EVENTS
  // =================================================================================================================

  var Events = (function() {
    var e = new Eev();

    var CHANNELS = {
      FILTER: "filter",
      ROUTE: "route"
    };

    function on(channel, callbackFn) {
      e.on(channel, callbackFn);
    }

    function emit(channel, data) {
      e.emit(channel, data);
    }

    function onFilterEvent(route, handlerFn) {
      Events.on(CHANNELS.FILTER, function(data) {
        if (Router.lastRouteResolved().url === route) handlerFn(data);
      });
    }

    function onRouteEvent(route, handlerFn) {
      Events.on(CHANNELS.ROUTE, function(data) {
        if (Router.lastRouteResolved().url === route) handlerFn(data);
      });
    }

    function emitFilterEvent(data) {
      emit(CHANNELS.FILTER, data);
    }

    function emitRouteEvent(data) {
      emit(CHANNELS.ROUTE, data);
    }

    return {
      CHANNELS: CHANNELS,

      on: on,
      emit: emit,

      onFilterEvent: onFilterEvent,
      onRouteEvent: onRouteEvent,

      emitRouteEvent: emitRouteEvent,
      emitFilterEvent: emitFilterEvent
    };
  })();

  // =================================================================================================================
  // COMPONENTS
  // =================================================================================================================

  var Announcements = (function() {
    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
    }

    function show() {
      viewModel.visible(true);
    }

    function hide() {
      viewModel.visible(false);
    }

    function init() {
      viewModel = new ViewModel();

      ko.applyBindings(viewModel, $("#mps-announcements")[0]);
    }

    return {
      init: init,

      show: show,
      hide: hide
    };
  })();

  var BreadCrumbs = (function() {
    var viewModel = null;

    var crumb1 = null;
    var crumb2 = null;

    function ViewModel() {
      var self = this;

      self.crumbs = ko.observableArray([]);
    }

    function createCrumb(url, label) {
      return {
        url: url,
        label: label
      };
    }

    function resetCrumbs() {
      viewModel.crumbs([crumb1, crumb2]);
    }

    function prepareForRoute(route) {
      resetCrumbs();

      switch (route) {
        case ROUTES.PA_LANDING:
          viewModel.crumbs.push(PALandingPage.createBreadCrumb());
          break;
        case ROUTES.PA_CATALOG_SUMMARY:
          viewModel.crumbs.push(PALandingPage.createBreadCrumb());
          viewModel.crumbs.push(PACatalogSummaryPage.createBreadCrumb());
          break;
        case ROUTES.PA_PACKAGING_INFORMATION:
          viewModel.crumbs.push(PALandingPage.createBreadCrumb());
          viewModel.crumbs.push(PACatalogSummaryPage.createBreadCrumb());
          viewModel.crumbs.push(PAPackagingInformationPage.createBreadCrumb());
          break;

        case ROUTES.QUOTE_LANDING:
          viewModel.crumbs.push(QuoteLandingPage.createBreadCrumb());
          break;
        case ROUTES.QUOTE_SUMMARY:
          viewModel.crumbs.push(QuoteLandingPage.createBreadCrumb());
          viewModel.crumbs.push(QuoteSummaryPage.createBreadCrumb());
          break;
        case ROUTES.QUOTE_DETAILS:
          viewModel.crumbs.push(QuoteLandingPage.createBreadCrumb());
          viewModel.crumbs.push(QuoteSummaryPage.createBreadCrumb());
          viewModel.crumbs.push(QuoteDetailsPage.createBreadCrumb());
          break;

        case ROUTES.ORDER_LANDING:
          viewModel.crumbs.push(OrderLandingPage.createBreadCrumb());
          break;
        case ROUTES.ORDER_SUMMARY:
          viewModel.crumbs.push(OrderLandingPage.createBreadCrumb());
          viewModel.crumbs.push(OrderSummaryPage.createBreadCrumb());
          break;
        case ROUTES.ORDER_DETAILS:
          viewModel.crumbs.push(OrderLandingPage.createBreadCrumb());
          viewModel.crumbs.push(OrderSummaryPage.createBreadCrumb());
          viewModel.crumbs.push(OrderDetailsPage.createBreadCrumb());
          break;
        case ROUTES.ORDER_PACKAGING:
          viewModel.crumbs.push(OrderLandingPage.createBreadCrumb());
          viewModel.crumbs.push(OrderSummaryPage.createBreadCrumb());
          viewModel.crumbs.push(OrderDetailsPage.createBreadCrumb());
          viewModel.crumbs.push(OrderPackagingPage.createBreadCrumb());
          break;
      }
    }

    function init() {
      viewModel = new ViewModel();

      var $container = $("#mps-breadcrumbs");

      crumb1 = createCrumb($container.data("homeurl"), "Home");
      crumb2 = createCrumb($container.data("mpsurl"), "MPServiceNet");

      resetCrumbs();

      Events.on(Events.CHANNELS.ROUTE, function() {
        prepareForRoute(Router.lastRouteResolved().url);
      });

      ko.applyBindings(viewModel, $container[0]);
    }

    return {
      init: init,
      createCrumb: createCrumb
    };
  })();

  var Filter = (function() {
    var BUTTONS = {
      FILTER_UPPER: 0,
      SUBMIT_LOWER: 1,
      SUBMIT_UPPER: 2
    };

    var FILTER_TYPES = {
      CATALOG_NUMBER: "catalog-number",
      QUOTE_NUMBER: "quote-number",
      ORDER_NUMBER: "order-number",
      PO_NUMBER: "po-number"
    };

    var $container = null;

    var viewModel = null;

    function ViewModel(accounts) {
      var self = this;

      self.allAccounts = accounts;

      self.accounts = ko.observableArray(accounts);
      self.useRadios = ko.observable(true);

      self.visible = ko.observable(true);
      self.visibility = {
        // sections
        sectionUpper: ko.observable(true),
        sectionLower: ko.observable(true),
        sectionLinks: ko.observable(true),

        // buttons
        buttonFilterUpper: ko.observable(true),
        buttonSubmitUpper: ko.observable(true),
        buttonSubmitLower: ko.observable(true),

        // input text fields
        inputFieldTextUpper: ko.observable(true),
        inputFieldTextLower: ko.observable(true),

        // inputs
        filterInputTypes: ko.observable(true),
        filterInputDate: ko.observable(true),
        filterInputStatus: ko.observable(true),

        // filter types
        filterInputTypeCatalogNumber: ko.observable(true),
        filterInputTypeQuoteNumber: ko.observable(true),
        filterInputTypeOrderNumber: ko.observable(true),
        filterInputTypePoNumber: ko.observable(true),

        // text
        title: ko.observable(true)
      };

      self.text = {
        buttonSubmitLower: ko.observable("Go"),
        subtitle: ko.observable("Catalog")
      };

      self.on = {
        click: {
          filterUpper: function() {
            filterAccounts();
          },

          submitUpper: function() {
            submit(BUTTONS.SUBMIT_UPPER);
          },

          submitLower: function() {
            submit(BUTTONS.SUBMIT_LOWER);
          },

          deselectAccount: function(accountNumberToDeselect) {
            self.userInput.selectedAccountCheckboxes(
              self.userInput
                .selectedAccountCheckboxes()
                .filter(function(accountNumber) {
                  return accountNumber !== accountNumberToDeselect;
                })
            );
          },

          deselectAllAccounts: function() {
            self.userInput.selectedAccountCheckboxes([]);
          }
        },
        keypress: function(data, event) {
          if (event.keyCode === KEYCODES.ENTER) {
            if (event && event.target && event.target.id)
              switch (event.target.id) {
                case "account-filter-text-input":
                  viewModel.userInput.textUpper($(event.target).val());
                  filterAccounts();
                  break;
                case "id-filter-text-input":
                  viewModel.userInput.textLower($(event.target).val());
                  submit(BUTTONS.SUBMIT_LOWER);
                  break;
                case "from-date-input":
                  //viewModel.userInput.textLower($(event.target).val());
                  submit(BUTTONS.SUBMIT_LOWER);
                  break;
                case "to-date-input":
                  //viewModel.userInput.textLower($(event.target).val());
                  submit(BUTTONS.SUBMIT_LOWER);
                  break;
              }
            else return true;
          } else return true;
        }
      };

      self.infoLinks = {
        catalogPage: ko.observable(),
        orderStatus: ko.observable(),
        quoteDetail: ko.observable(),
        productDrawing: ko.observable()
      };

      var qsVal = getUrlParameter("catalogInput");

      var urlFixed = removeURLParameter(window.location.href, "catalogInput");

      history.pushState({}, $("title").html(), urlFixed);

      self.userInput = {
        textUpper: ko.observable(""),
        textLower: ko.observable(qsVal),

        filterType: ko.observable(),

        dateFrom: ko.observable(),
        dateTo: ko.observable(),

        status: ko.observable(),

        selectedAccountCheckboxes: ko.observableArray([]),
        selectedAccountRadio: ko.observable()
      };

      self.userInput.selectedAccounts = ko.computed(function() {
        if (!self.useRadios())
          return self.userInput.selectedAccountCheckboxes();
        else {
          var selectedAccountRadio = self.userInput.selectedAccountRadio();
          if (selectedAccountRadio) return [selectedAccountRadio];
          else return [];
        }
      });
    }

    function filterAccounts() {
      var searchString = viewModel.userInput
        .textUpper()
        .trim()
        .toLowerCase();

      if (searchString)
        viewModel.accounts(
          viewModel.allAccounts.filter(function(account) {
            return account.name.toLowerCase().includes(searchString);
          })
        );
      else viewModel.accounts(viewModel.allAccounts);
    }

    function getContent() {
      var content = {};
      Object.keys(viewModel.userInput).forEach(function(key) {
        content[key] = viewModel.userInput[key]() || null;
      });
      return content;
    }

    function setContent(content) {
      Object.keys(content).forEach(function(key) {
        if (viewModel.userInput.hasOwnProperty(key))
          viewModel.userInput[key](content[key]);
      });
    }

    function setInfoLinks(
      catalogPage,
      orderStatus,
      quoteDetail,
      productDrawing
    ) {
      viewModel.infoLinks.catalogPage(catalogPage);
      viewModel.infoLinks.orderStatus(orderStatus);
      viewModel.infoLinks.quoteDetail(quoteDetail);
      viewModel.infoLinks.productDrawing(productDrawing);

      viewModel.visibility.sectionLinks(
        catalogPage || orderStatus || quoteDetail || productDrawing
      );
    }

    function getSelectedAccounts() {
      return viewModel.userInput.selectedAccounts() || [];
    }

    function submit(button) {
      Events.emit(Events.CHANNELS.FILTER, {
        button: button,
        content: getContent()
      });
    }

    function prepareForRoute(route) {
      // all visible
      Object.keys(viewModel.visibility).forEach(function(key) {
        viewModel.visibility[key](true);
      });

      // commonly hidden
      viewModel.visibility.sectionLinks(false);
      Announcements.hide();

      // show self
      viewModel.visible(true);

      $mpservicenet.removeClass(FULLSCREEN_CSS_CLASS);

      switch (route) {
        case ROUTES.PA_LANDING:
        case ROUTES.PA_CATALOG_SUMMARY:
        case ROUTES.PA_PACKAGING_INFORMATION:
        case ROUTES.QUOTE_LANDING:
        case ROUTES.QUOTE_SUMMARY:
        case ROUTES.ORDER_LANDING:
        case ROUTES.ORDER_SUMMARY:
          $("#mps-filters")
            .parent()
            .show();
          $(".fl-node-5d5a9499d8873")
            .first()
            .css("width", "75%");
          resetTables();
          break;
        case ROUTES.QUOTE_DETAILS:
        case ROUTES.ORDER_DETAILS:
        case ROUTES.ORDER_PACKAGING:
          $("#mps-filters")
            .parent()
            .hide();
          $(".fl-node-5d5a9499d8873")
            .first()
            .css("width", "100%");
          resetTables();
          break;
      }

      // grouped behavior
      switch (route) {
        case ROUTES.PA_LANDING:
        case ROUTES.PA_CATALOG_SUMMARY:
        case ROUTES.PA_PACKAGING_INFORMATION:
          viewModel.useRadios(true);
          viewModel.visibility.filterInputTypes(false);
          viewModel.visibility.filterInputDate(false);
          viewModel.visibility.filterInputStatus(false);
          viewModel.visibility.buttonSubmitUpper(false);
          viewModel.text.subtitle("Catalog");
          break;

        case ROUTES.QUOTE_LANDING:
        case ROUTES.QUOTE_SUMMARY:
        case ROUTES.QUOTE_DETAILS:
          viewModel.useRadios(false);
          viewModel.visibility.filterInputStatus(false);
          viewModel.visibility.filterInputTypeOrderNumber(false);
          viewModel.visibility.filterInputTypePoNumber(false);
          setIfEmpty(
            viewModel.userInput.filterType,
            FILTER_TYPES.CATALOG_NUMBER
          );
          viewModel.text.subtitle("Filter Quote");
          break;

        case ROUTES.ORDER_LANDING:
        case ROUTES.ORDER_SUMMARY:
        case ROUTES.ORDER_DETAILS:
        case ROUTES.ORDER_PACKAGING:
          viewModel.useRadios(false);
          viewModel.visibility.filterInputTypeQuoteNumber(false);
          setIfEmpty(
            viewModel.userInput.filterType,
            FILTER_TYPES.CATALOG_NUMBER
          );
          viewModel.text.subtitle("Filter Order");
          break;
      }

      // individual route behavior
      switch (route) {
        case ROUTES.LANDING:
          Announcements.show();
          viewModel.visible(false);
          break;

        case ROUTES.PA_LANDING:
          break;
        case ROUTES.PA_CATALOG_SUMMARY:
          break;
        case ROUTES.PA_PACKAGING_INFORMATION:
          viewModel.visibility.sectionLinks(true);
          break;

        case ROUTES.QUOTE_LANDING:
          break;
        case ROUTES.QUOTE_SUMMARY:
          break;
        case ROUTES.QUOTE_DETAILS:
          viewModel.visible(false);
          $mpservicenet.addClass(FULLSCREEN_CSS_CLASS);
          break;

        case ROUTES.ORDER_LANDING:
          break;
        case ROUTES.ORDER_SUMMARY:
          break;
        case ROUTES.ORDER_DETAILS:
          viewModel.visible(false);
          $mpservicenet.addClass(FULLSCREEN_CSS_CLASS);
          break;
        case ROUTES.ORDER_PACKAGING:
          viewModel.visible(false);
          $mpservicenet.addClass(FULLSCREEN_CSS_CLASS);
          break;
      }
    }

    function init(accounts) {
      viewModel = new ViewModel(accounts);

      $container = $("#mps-filters");

      Events.on(Events.CHANNELS.ROUTE, function() {
        prepareForRoute(Router.lastRouteResolved().url);
      });

      ko.applyBindings(viewModel, $container[0]);
    }

    function setIfEmpty(obs, value) {
      if (!obs()) obs(value);
    }

    return {
      BUTTONS: BUTTONS,
      FILTER_TYPES: FILTER_TYPES,

      init: init,

      getContent: getContent,
      setContent: setContent,

      getSelectedAccounts: getSelectedAccounts,

      setInfoLinks: setInfoLinks
    };
  })();

  var Navigator = (function() {
    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.tabs = [
        {
          name: "PRICE AND AVAILABILITY",
          key: ROUTES.PA_LANDING
        },
        {
          name: "QUOTE",
          key: ROUTES.QUOTE_LANDING
        },
        {
          name: "ORDER",
          key: ROUTES.ORDER_LANDING
        }
      ];

      self.selectedTab = ko.observable();

      self.toggleActive = function(tab) {
        self.selectedTab(tab);
        return true;
      };

      return self;
    }

    function init() {
      viewModel = new ViewModel();

      Events.on(Events.CHANNELS.ROUTE, function() {
        var searchRoute = "";

        switch (Router.lastRouteResolved().url) {
          case ROUTES.PA_LANDING:
          case ROUTES.PA_CATALOG_SUMMARY:
          case ROUTES.PA_PACKAGING_INFORMATION:
            searchRoute = ROUTES.PA_LANDING;
            break;

          case ROUTES.QUOTE_LANDING:
          case ROUTES.QUOTE_SUMMARY:
          case ROUTES.QUOTE_DETAILS:
            searchRoute = ROUTES.QUOTE_LANDING;
            break;

          case ROUTES.ORDER_LANDING:
          case ROUTES.ORDER_SUMMARY:
          case ROUTES.ORDER_DETAILS:
          case ROUTES.ORDER_PACKAGING:
            searchRoute = ROUTES.ORDER_LANDING;
            break;
        }

        viewModel.selectedTab(
          viewModel.tabs.find(function(tab) {
            return tab.key === searchRoute;
          })
        );
      });

      ko.applyBindings(viewModel, $("#mps-navigation")[0]);
    }

    function clearSelectedTab() {
      viewModel.selectedTab({ key: "" });
    }

    return {
      init: init,
      clearSelectedTab: clearSelectedTab
    };
  })();

  var PageManager = (function() {
    var viewModels = [];

    function isValid(viewModel) {
      return !!(viewModel && viewModel.visible);
    }

    function register(viewModel) {
      if (isValid(viewModel)) viewModels.push(viewModel);
      else throw "invalid view model";
    }

    function show(viewModel) {
      if (isValid(viewModel))
        viewModels.forEach(function(currentViewModel) {
          currentViewModel.visible(currentViewModel === viewModel);
        });
      else throw "invalid view model";
    }

    return {
      register: register,
      show: show
    };
  })();

  var Router = (function() {
    var router = new Navigo(null, true, "#");
    var routes = {};

    function lastRouteResolved() {
      return router.lastRouteResolved();
    }

    function register(route, handler) {
      routes[route] = function(params, query) {
        var queryParams = fromQueryString(decodeURI(query));
        handler(queryParams);
        Events.emitRouteEvent(queryParams);
      };
    }

    function resolve() {
      router.on(routes).resolve();
    }

    function navigate(route, params) {
      router.navigate(route + toQueryString(params));
    }

    function createURL(route, params) {
      return "#" + route + toQueryString(params);
    }

    return {
      ROUTES: ROUTES,

      navigate: navigate,
      register: register,
      resolve: resolve,

      lastRouteResolved: lastRouteResolved,

      createURL: createURL
    };
  })();

  // =================================================================================================================
  // PAGES
  // =================================================================================================================

  var LandingPage = (function() {
    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
    }

    function onRoute(queryParams) {
      PageManager.show(viewModel);
    }

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTES.LANDING, onRoute);

      ko.applyBindings(viewModel, $("#landing-page")[0]);
    }

    return {
      init: init
    };
  })();

  // PRICE AND AVAILABILITY ============================================

  var PALandingPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.PA_LANDING;

    function onNavigate(queryParams) {
      PageManager.show(viewModel);
    }

    function onFilter(data) {
      var params = PACatalogSummaryPage.parseFilterData(data);
      if (!params) return;

      Router.navigate(ROUTES.PA_CATALOG_SUMMARY, params);
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      ko.applyBindings(viewModel, $("#price-availability-default-page")[0]);
    }

    // MISC ==========================================================

    function createBreadCrumb() {
      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.PA_LANDING),
        "Price Availability"
      );
    }

    return {
      init: init,
      createBreadCrumb: createBreadCrumb
    };
  })();

  var PACatalogSummaryPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);

      self.accountNumber = ko.observable();
      self.resultsCount = ko.observable();
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.PA_CATALOG_SUMMARY;

    function onFilter(data) {
      var params = parseFilterData(data);
      if (!params) return;

      Router.navigate(ROUTE, params);
    }

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      var catalogsCsv = queryParams.catalogs.join(",");

      viewModel.accountNumber(params.account);

      var lastRoute = Router.lastRouteResolved();
      PAPackagingInformationPage.setBackLink(
        createURL(lastRoute.url, lastRoute.query)
      );

      Filter.setContent({
        selectedAccountRadio: params.account,
        textLower: catalogsCsv
      });

      ajax(
        {
          action: "price_and_availability_account_ajax",
          account_numbers: params.account,
          catalog_numbers: catalogsCsv
        },
        function(data) {
          viewModel.resultsCount(data.length);
          setTableData(catalogSummaryTable, data);
          complete();
        },
        function(xhr, ajaxOptions, error) {
          complete();
        }
      );

      function complete() {
        PageManager.show(viewModel);
        resetTables();
        hideContentSpinner();
      }
    }

    function createParams(accountNumberString, catalogNumbersArray) {
      if (!accountNumberString || !isString(accountNumberString + "")) {
        error("invalid accountString");
        return null;
      }
      if (!catalogNumbersArray || !Array.isArray(catalogNumbersArray)) {
        error("invalid catalogNumbersArray");
        return null;
      }

      if (catalogNumbersArray.length === 0) {
        error("empty catalogNumbersArray");
        return null;
      }

      return {
        account: accountNumberString,
        catalogs: catalogNumbersArray
      };
    }

    function parseFilterData(data) {
      if (data.button !== Filter.BUTTONS.SUBMIT_LOWER) return null;

      if (data.content.selectedAccounts.length !== 1) {
         alert("Please select an account.");
         return null;
      }

      if (!data.content.textLower) {
        alert("Please enter one or more comma-separated catalog numbers");
        return null;
      }

      return createParams(
        data.content.selectedAccounts[0],
        data.content.textLower.split(",")
      );
    }

    function parseQueryParams(queryParams) {
      // treat single value as first entry in array
      if (queryParams.catalogs && !Array.isArray(queryParams.catalogs))
        queryParams.catalogs = [queryParams.catalogs];

      return createParams(queryParams.account, queryParams.catalogs);
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      $catalogSummaryTable = $("#price-availability-account-listing-table");
      $catalogSummaryTable.on("click", ".catalog-details-button", function(
        event
      ) {
        event.preventDefault();
        event.stopPropagation();
        onClickCatalogDetailsLink($(event.target).data("catalognumber"));
      });
      catalogSummaryTable = $catalogSummaryTable.DataTable({
        responsive: false,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            className: "catalog-number",
            data: "catalog-number",
            render: function(data, type, row) {
              if (type === "display" || type === "filter") {
                if (row["product-link"] !== "#") {
                  return (
                    '<a target="_blank" href="' +
                    row["product-link"] +
                    '">' +
                    "<span>" +
                    row["catalog-number"] +
                    "</span>" +
                    "</a>"
                  );
                } else {
                  return "<span>" + row["catalog-number"] + "</span>";
                }
              }

              return data;
            }
          },
          {
            className: "list-price",
            data: "list-price",
            render: dataTablePriceRenderer
          },
          {
            className: "standard-discount-price",
            data: "standard-discount-price",
            render: dataTablePriceRenderer
          },
          {
            className: "lead-time",
            data: "lead-time"
          },
          {
            className: "details-link",
            data: "product-link",
            orderable: false,
            render: function(data, type, row) {
              if (type === "display" || type === "filter") {
                return (
                  '<a class="catalog-details-button" data-catalognumber="' +
                  row["catalog-number"] +
                  '">DETAILS</a>'
                );
              }

              return data;
            }
          }
        ]
      });

      ko.applyBindings(
        viewModel,
        $("#price-availability-catalog-summary-page")[0]
      );
    }

    // MISC ==========================================================

    var previousParams = null;

    var $catalogSummaryTable = null;

    var catalogSummaryTable = null;

    function onClickCatalogDetailsLink(catalogNumber) {
      Router.navigate(
        ROUTES.PA_PACKAGING_INFORMATION,
        PAPackagingInformationPage.createParams(
          previousParams.account,
          catalogNumber
        )
      );
    }

    function createBreadCrumb() {
      var label = "Catalog Summary";

      if (previousParams) {
        if (previousParams.account) label += " for " + previousParams.account;
        if (previousParams.catalogs)
          label += " (" + previousParams.catalogs.join(", ") + ")";
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.PA_CATALOG_SUMMARY, previousParams),
        label
      );
    }

    return {
      init: init,

      createParams: createParams,

      parseFilterData: parseFilterData,
      parseQueryParams: parseQueryParams,

      createBreadCrumb: createBreadCrumb
    };
  })();

  var PAPackagingInformationPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);

      self.catalogNumber = ko.observable();

      self.onClickBackToCatalogSummary = function() {
        if (backLink) Router.navigate(backLink);
        else
          Router.navigate(
            ROUTES.PA_CATALOG_SUMMARY,
            PACatalogSummaryPage.createParams(previousParams.account, [
              previousParams.catalog
            ])
          );
      };
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.PA_PACKAGING_INFORMATION;

    var previousParams = null;

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      viewModel.catalogNumber(queryParams.catalog);

      Filter.setContent({
        selectedAccountRadio: queryParams.account
      });

      ajax(
        {
          action: "price_and_availability_id_ajax",
          account_numbers: queryParams.account,
          catalog_numbers: queryParams.catalog
        },
        function(data) {
          var packagingInfoTableData = [data[0]] || [];
          var availabilityInfoTableData = [data[1]] || [];
          var additionalInfoData = data[2] || [];
          var sidebarData = data[3] || [];

          setTableData(packagingInfoTable, packagingInfoTableData);
          setTableData(availabilityInfoTable, availabilityInfoTableData);

          var catalogPage = sidebarData["product_link"];
          var orderStatus = null;
          var quoteDetail = null;
          var productDrawing = sidebarData["drawing_link"];

          if (sidebarData["catalog_number"]) {
            var accountArray = [queryParams.account];
            var catalogArray = [sidebarData["catalog_number"]];

            orderStatus =
              "#" +
              ROUTES.ORDER_SUMMARY +
              toQueryString(
                OrderSummaryPage.createParams(accountArray, catalogArray)
              );

            quoteDetail =
              "#" +
              ROUTES.QUOTE_SUMMARY +
              toQueryString(
                QuoteSummaryPage.createParams(accountArray, catalogArray)
              );
          }

          Filter.setInfoLinks(
            catalogPage,
            orderStatus,
            quoteDetail,
            productDrawing
          );
          ajax(
            {
              action: "price_and_availability_production_schedule_ajax",
              account_numbers: queryParams.account,
              catalog_numbers: queryParams.catalog
            },
            function(data) {
              var scheduleInfoTableData = data[1] || [];
              if (
                typeof data !== "undefined" &&
                data[0] === "true" &&
                data[1].length > 0
              ) {
                $scheduleInfoTable
                  .parent()
                  .parent()
                  .show();
                setTableData(scheduleInfoTable, scheduleInfoTableData);
              } else {
                $scheduleInfoTable
                  .parent()
                  .parent()
                  .hide();
              }
              complete();
            },
            function(xhr, ajaxOptions, error) {
              complete();
            }
          );
          complete();
        },
        function(xhr, ajaxOptions, error) {
          complete();
        }
      );

      function complete() {
        PageManager.show(viewModel);
        resolveSideTable($packagingInfoTable);
        resolveSideTable($availabilityInfoTable);
        resolveSideTable($scheduleInfoTable);
        resetTables();
        hideContentSpinner();
      }
    }

    function onFilter(data) {
      var params = parseFilterData(data);
      if (!params) return;

      Router.navigate(ROUTES.PA_CATALOG_SUMMARY, params);
    }

    function createParams(accountNumberString, catalogNumberString) {
      if (!accountNumberString || !isString(accountNumberString + ""))
        return error("invalid accountString");
      if (!catalogNumberString || !isString(catalogNumberString + ""))
        return error("invalid catalogString");

      return {
        account: accountNumberString,
        catalog: catalogNumberString
      };
    }

    function parseQueryParams(queryParams) {
      return createParams(queryParams.account, queryParams.catalog);
    }

    function parseFilterData(data) {
      return PACatalogSummaryPage.parseFilterData(data);
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      
      $packagingInfoTable = $("#price-availability-packaging-info-table");
      packagingInfoTable = $packagingInfoTable.DataTable({
        responsive: true,
        bSort: false,
        buttons: [{ extend: "excel" }],
        columns: [
          {
            data: "standard-package-quantity",
            render: dataTableCommaSeparatedNumberRenderer
          },
          {
            data: "pallet-quantity",
            render: dataTableCommaSeparatedNumberRenderer
          },
          {
            data: "weight-ea"
          },
          {
            data: "unit-of-measure"
          }
        ]
      });

      // $(document).on("click", "#pa-packaging-print-button", function() {
      //   window.print();
      // });

      $('#price-availability-packaging-information-page').on("click", ".export_to_excel", function(event) {
        event.preventDefault();
        event.stopPropagation();
        var data = {};
        packagingInfoTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            for (var i = 0; i < 4; i++) {
              data[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
          });
        data["Line Items"] = [];
        availabilityInfoTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            var subData = {};
            for (var i = 0; i < 7; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
            for (var i = 8; i < 11; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
            // subData[Object.keys(this.data())[11]] = this.data()[
            //   Object.keys(this.data())[11]
            // ];
            data["Line Items"].push(subData);
          });
        JSONToCSVConverter(
          data,
          "PACKAGING INFORMATION FOR: "+ viewModel.catalogNumber(),
          true
        );
      });

   
      $availabilityInfoTable = $("#availability-info-table");
      availabilityInfoTable = $availabilityInfoTable.DataTable({
        responsive: true,
        bSort: false,
        columns: [
          {
            data: "catalog-number"
          },
          {
            data: "stock-status"
          },
          {
            data: "description"
          },
          {
            data: "list-price",
            render: dataTablePriceRenderer
          },
          {
            data: "standard-discount-price",
            render: dataTablePriceRenderer
          },
          {
            data: "quantity-in-stock"
          },
          
          {
            data: "mfg-lead-time"
          },

        ]
      });

      $scheduleInfoTable = $("#schedule-info-table");
      scheduleInfoTable = $scheduleInfoTable.DataTable({
        responsive: true,
        bSort: false,
        columns: [
          {
            data: "availPromiseDate",
            type: "date"
          },
          {
            data: "qty"
          },
          {
            data: "total"
          }
        ]
      });

      ko.applyBindings(
        viewModel,
        $("#price-availability-packaging-information-page")[0]
      );
    }

    // MISC ==========================================================

    var $packagingInfoTable = null;
    var $availabilityInfoTable = null;
    var $scheduleInfoTable = null;

    var packagingInfoTable = null;
    var availabilityInfoTable = null;
    var scheduleInfoTable = null;

    var backLink = null;

    function setBackLink(url) {
      if (!url || !isString(url)) return error("invalid url");

      backLink = url;
    }

    function createBreadCrumb() {
      var label = "Packaging Information";

      if (previousParams) {
        if (previousParams.catalog) label += " for " + previousParams.catalog;
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.PA_PACKAGING_INFORMATION, previousParams),
        label
      );
    }

    return {
      init: init,
      createParams: createParams,
      setBackLink: setBackLink,
      createBreadCrumb: createBreadCrumb
    };
  })();

  // QUOTE =============================================================

  var QuoteLandingPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.QUOTE_LANDING;

    function onNavigate(queryParams) {
      PageManager.show(viewModel);
      $("#id-filter-date > h5").html("Expiration Date");
    }

    function onFilter(data) {
      var params = QuoteSummaryPage.parseFilterData(data);
      if (!params) return;

      Router.navigate(ROUTES.QUOTE_SUMMARY, params);
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      ko.applyBindings(viewModel, $("#quote-default-page")[0]);
    }

    // MISC ==========================================================

    function createBreadCrumb() {
      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.QUOTE_LANDING),
        "Quote"
      );
    }

    return {
      init: init,
      createBreadCrumb: createBreadCrumb
    };
  })();

  var QuoteSummaryPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
      self.visibility = {
        catalogPackagingTable: ko.observable(false)
      };

      self.accountNumber = ko.observable();
      self.resultsCount = ko.observable();
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.QUOTE_SUMMARY;

    var previousParams = null;

    function onFilter(filterData) {
      var params = parseFilterData(filterData);
      if (!params) return;

      Router.navigate(ROUTE, params);
    }

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      // set details page back link
      QuoteDetailsPage.setBackLink(Router.createURL(ROUTE, params));

      var isCatalogFiltered = !!params.catalogs;
      var isQuoteFiltered = !!params.quotes;

      var accounts = params.accounts || [];
      var catalogs = params.catalogs || [];
      var quotes = params.quotes || [];
      var dateFrom = params.from || "";
      var dateTo = params.to || "";

      // set filter content
      var filterContent = {};
      if (accounts) filterContent.selectedAccountCheckboxes = accounts;
      if (isCatalogFiltered && catalogs) {
        filterContent["textLower"] = catalogs.join(",");
        filterContent["filterType"] = Filter.FILTER_TYPES.CATALOG_NUMBER;
      } else if (isQuoteFiltered && quotes) {
        filterContent["textLower"] = quotes.join(",");
        filterContent["filterType"] = Filter.FILTER_TYPES.QUOTE_NUMBER;
      }
      if (dateFrom) filterContent["dateFrom"] = dateFrom;
      if (dateTo) filterContent["dateTo"] = dateTo;
      Filter.setContent(filterContent);

      // create POST data
      var postData = {
        action: "quotes_account_ajax"
      };
      if (accounts) postData.accounts = accounts;
      if (catalogs) postData.catalogs = catalogs;
      if (quotes) postData.quotes = quotes;
      if (dateFrom) postData.from = dateFrom;
      if (dateTo) postData.to = dateTo;

      var accountsCsv = accounts.join(", ");

      ajax(
        postData,
        function(data) {
          setTableData(listingTable, data["quotes"]);

          if (
            isCatalogFiltered &&
            typeof data["catalogs"] !== "undefined" &&
            data["catalogs"].length > 0
          ) {
            setTableData(catalogPackagingTable, data["catalogs"]);
          }

          viewModel.accountNumber(accountsCsv);
          viewModel.resultsCount(data["quotes"].length);

          // hide columns
          if (isCatalogFiltered) {
            listingTable.column("catalogNumber:name").visible(true);
            listingTable.column("price:name").visible(true);
            listingTable.column("quoteQuantity:name").visible(true);
            listingTable.column("orderQuantity:name").visible(true);
          } else {
            listingTable.column("catalogNumber:name").visible(false);
            listingTable.column("price:name").visible(false);
            listingTable.column("quoteQuantity:name").visible(false);
            listingTable.column("orderQuantity:name").visible(false);
          }

          // show catalog table
          if (
            isCatalogFiltered &&
            typeof data["catalogs"] !== "undefined" &&
            data["catalogs"].length > 0
          )
            viewModel.visibility.catalogPackagingTable(true);
          else viewModel.visibility.catalogPackagingTable(false);

          complete();
        },
        function(xhr, ajaxOptions, error) {
          viewModel.accountNumber(accountsCsv);
          viewModel.resultsCount(0);

          complete();
        }
      );

      function complete() {
        PageManager.show(viewModel);
        $("#id-filter-date > h5").html("Expiration Date");
        resetTables();
        hideContentSpinner();
      }
    }

    function createParams(
      accountNumbersArray,
      catalogNumbersArray,
      quoteNumbersArray,
      dateFromString,
      dateToString
    ) {
      accountNumbersArray = accountNumbersArray || null;
      catalogNumbersArray = catalogNumbersArray || null;
      quoteNumbersArray = quoteNumbersArray || null;
      dateFromString = dateFromString || null;
      dateToString = dateToString || null;

      // type checks

      accountNumbersArray = arrayOrNull(accountNumbersArray);
      catalogNumbersArray = arrayOrNull(catalogNumbersArray);
      quoteNumbersArray = arrayOrNull(quoteNumbersArray);

      dateFromString = stringOrNull(dateFromString);
      dateToString = stringOrNull(dateToString);

      // keys

      return {
        accounts: accountNumbersArray,
        catalogs: catalogNumbersArray,
        quotes: quoteNumbersArray,
        from: dateFromString,
        to: dateToString
      };
    }

    function parseFilterData(filterData) {
      if (
        filterData.button !== Filter.BUTTONS.SUBMIT_UPPER &&
        filterData.button !== Filter.BUTTONS.SUBMIT_LOWER
      )
        return null;

      if (!filterData.content.selectedAccounts.length) {
         alert("Please select an account.");
         return null;
      }

      var catalogNumbers = null;
      var quoteNumbers = null;

      var filterText = filterData.content.textLower;
      if (filterText)
        switch (filterData.content.filterType) {
          case Filter.FILTER_TYPES.CATALOG_NUMBER:
            catalogNumbers = filterText.split(",");
            break;
          case Filter.FILTER_TYPES.QUOTE_NUMBER:
            quoteNumbers = filterText.split(",");
            break;
        }

      return createParams(
        filterData.content.selectedAccounts,
        catalogNumbers,
        quoteNumbers,
        filterData.content.dateFrom,
        filterData.content.dateTo
      );
    }

    function parseQueryParams(queryParams) {
      var accounts = queryParams.accounts;
      if (accounts && !Array.isArray(accounts)) accounts = [accounts];

      var catalogs = queryParams.catalogs;
      if (catalogs && !Array.isArray(catalogs)) catalogs = [catalogs];

      var quoteNumbers = queryParams.quotes;
      if (quoteNumbers && !Array.isArray(quoteNumbers))
        quoteNumbers = [quoteNumbers];

      return createParams(
        accounts,
        catalogs,
        quoteNumbers,
        queryParams.from,
        queryParams.to
      );
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      $listingTable = $("#quote-summary-table");
      listingTable = $listingTable.DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            className: "quote-number",
            data: "quoteNumber",
            render: function(data, type, row) {
              var accountNumber = row["accountNumber"];
              var quoteNumber = row["quoteNumber"];

              var isCatalogFiltered =
                previousParams &&
                previousParams.catalogs &&
                previousParams.catalogs.length;

              var catalogNumber = isCatalogFiltered
                ? row["catalogNumber"]
                : null;

              if (type === "display") {
                var href = QuoteDetailsPage.createNavigationUrl(
                  accountNumber,
                  quoteNumber
                );

                href = href || "#";

                return (
                  '<a href="' +
                  href +
                  '">' +
                  "<span>" +
                  quoteNumber +
                  "</span>" +
                  "</a>"
                );
              } else return data;
            }
          },
          {
            className: "account-number",
            data: "accountNumber"
          },
          {
            className: "end-user",
            data: "endUser"
          },
          {
            className: "expiration-date",
            data: "expirationDate"
          },
          {
            className: "catalog-number",
            data: "catalogNumber",
            name: "catalogNumber",
            render: function(data, type, row) {
              var accountNumber = row["accountNumber"];
              var quoteNumber = row["quoteNumber"];

              var isCatalogFiltered =
                previousParams &&
                previousParams.catalogs &&
                previousParams.catalogs.length;

              var catalogNumber = isCatalogFiltered
                ? row["catalogNumber"]
                : null;

              if (type === "display") {
                var href = QuoteDetailsPage.createNavigationUrl(
                  accountNumber,
                  quoteNumber,
                  catalogNumber
                );

                href = href || "#";

                return (
                  '<a href="' +
                  href +
                  '">' +
                  "<span>" +
                  catalogNumber +
                  "</span>" +
                  "</a>"
                );
              } else return data;
            }
          },
          {
            className: "price",
            data: "price",
            name: "price",
            render: dataTablePriceRenderer
          },
          {
            className: "quote-quantity",
            data: "quoteQuantity",
            name: "quoteQuantity"
          },
          {
            className: "order-quantity",
            data: "orderQuantity",
            name: "orderQuantity"
          }
        ]
      });

      $catalogPackagingTable = $("#quote-catalog-packaging-info-table");
      catalogPackagingTable = $catalogPackagingTable.DataTable({
        responsive: true,
        bSort: false,
        dom: 'Bfrtip',
        paginate: false,
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            data: "catalog_number"
          },
          {
            data: "standard_package_quantity"
          },
          {
            data: "pallet_quantity"
          },
          {
            data: "weight_ea"
          },
          {
            data: "unit_of_measure"
          },
          {
            data: "stock-status"
          },
          {
            data: "standard-discount-price"
          },
          {
            data: "quantity-in-stock"
          },
          {
            data: "lead-time"
          }           
        ]
      });
      catalogPackagingTable.buttons(".buttonsToHide").nodes().css("display", "none");
      listingTable.buttons(".buttonsToHide").nodes().css("display", "none");


      $('#quote-account-page').on("click", ".export_to_excel", function(event) {
        event.preventDefault();
        event.stopPropagation();
        var data = {};
        data["Line Items"] = [];
          catalogPackagingTable
            .rows()
            .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
              var subData = {};
              for (var i = 0; i < 5; i++) {
                subData[Object.keys(this.data())[i]] = this.data()[
                  Object.keys(this.data())[i]
                ];
              }
    
              data["Line Items"].push(subData);
            });

          
        data["items"] = [];
        var isCatalogFiltered =
        previousParams &&
        previousParams.catalogs &&
        previousParams.catalogs.length;
     
        listingTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            var subData = {};
            if(isCatalogFiltered){
            for (var i = 1; i < 9; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }

          }else{
            for (var i = 1; i < 9; i++) {
              if(i!=2 && i!=5 && i!=7 &&i!=8) //filter some column out
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];

            }

          }
            // for (var i = 8; i < 10; i++) {
            //   subData[Object.keys(this.data())[i]] = this.data()[
            //     Object.keys(this.data())[i]
            //   ];
            // }
            // subData[Object.keys(this.data())[11]] = this.data()[
            //   Object.keys(this.data())[11]
            // ];
            data["items"].push(subData);
          });
        


          //console.log(data);
        JSONToCSVConverter(
          data,
          "QUOTE SUMMARY LIST FOR: "+ viewModel.accountNumber(),
          true
        );
      });

      ko.applyBindings(viewModel, $("#quote-account-page")[0]);
    }

    // MISC ==========================================================


    var $catalogPackagingTable = null;
    var $listingTable = null;

    var catalogPackagingTable = null;
    var listingTable = null;

    function createBreadCrumb() {
      var label = "Summary";

      if (previousParams) {
        if (previousParams.accounts)
          label += " for " + previousParams.accounts.join(", ");
        // if ( previousParams.catalogs )
        //     label += ' (Catalogs: ' + previousParams.catalogs.join( ', ' ) + ')';
        // if ( previousParams.quotes )
        //     label += ' (Quotes: ' + previousParams.quotes.join( ', ' ) + ')';
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.QUOTE_SUMMARY, previousParams),
        label
      );
    }

    return {
      init: init,

      createParams: createParams,

      parseFilterData: parseFilterData,
      parseQueryParams: parseQueryParams,

      createBreadCrumb: createBreadCrumb
    };
  })();

  var QuoteDetailsPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
      self.quoteNumber = ko.observable("");

      self.onClickBackToQuoteSummary = function() {
        backToQuoteSummary();
      };
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.QUOTE_DETAILS;

    var previousParams = null;

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      var account = params.account;
      var catalog = params.catalog;
      var quote = params.quote;

      // set filter content
      var filterContent = {};
      if (account) filterContent.selectedAccountCheckboxes = [params.account];
      Filter.setContent(filterContent);

      // create post data
      var postData = {
        action: "quotes_id_ajax"
      };
      if (account) postData.account = account;
      if (catalog) postData.catalog = catalog;
      if (quote) postData.quote = quote;

      ajax(
        postData,
        function(data) {
          viewModel.quoteNumber(quote);

          var quoteData = data[Object.keys(data)[0]];
          if (quoteData) setTableData(quoteStatusTable, [quoteData]);

          var lineItems = quoteData["items"];
          if (lineItems) setTableData(quoteLineItemsTable, lineItems);

          complete();
        },
        function(xhr, ajaxOptions, error) {
          complete();
        }
      );

      function complete() {
        PageManager.show(viewModel);
        $("#id-filter-date > h5").html("Expiration Date");
        resetTables();
        hideContentSpinner();
      }
    }

    function onFilter(filterData) {
      var params = parseFilterData(filterData);
      if (!params) return;

      Router.navigate(ROUTE, params);
    }

    function createParams(
      accountNumberString,
      quoteNumberString,
      catalogNumberString
    ) {
      accountNumberString = stringOrNull(accountNumberString);
      quoteNumberString = stringOrNull(quoteNumberString);
      catalogNumberString = stringOrNull(catalogNumberString);

      return {
        account: accountNumberString,
        quote: quoteNumberString,
        catalog: catalogNumberString
      };
    }

    function parseFilterData(filterData) {
      if (
        filterData.button !== Filter.BUTTONS.SUBMIT_UPPER &&
        filterData.button !== Filter.BUTTONS.SUBMIT_LOWER
      )
        return null;

      if (!filterData.content.selectedAccounts.length) {
        alert("Please select an account.");
        return null;
      }
    }

    function parseQueryParams(queryParams) {
      return createParams(
        queryParams.account,
        queryParams.quote,
        queryParams.catalog
      );
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      $quoteLineItemsTable = $("#quote-line-items");
      quoteLineItemsTable = $quoteLineItemsTable.DataTable({
        order: [[3, "asc"]],
        responsive: true,
        paging: false,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            data: "itemNumber"
          },
          {
            data: "qtyQuoted"
          },

          {
            className: "catalog-number",
            data: "catalogNum"
          },
          {
            data: "custPart",
            render: dataTableNumberRenderer,
            type: "string"
          },
          {
            data: "upcCode"
          },
          {
            data: "description"
          },
          {
            data: "price",
            render: dataTablePriceRenderer
          },
          {
            data: "extLeadTime"
          },

          {
            data: "packQty"
          }
        ]
      });

      $quoteStatusTable = $("#quote-status-table");
      quoteStatusTable = $quoteStatusTable.DataTable({
        responsive: true,
        paging: false,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            data: "customerNumber"
          },
          {
            data: "quoteNumber"
          },
          {
            data: "endUser"
          },
          {
            data: "issuedDate"
          },

          {
            data: "firmPriceDate"
          }
        ]
      });

      quoteStatusTable
        .buttons(".buttonsToHide")
        .nodes()
        .css("display", "none");
      quoteLineItemsTable
        .buttons(".buttonsToHide")
        .nodes()
        .css("display", "none");
    

      $quoteLineItemsTable.on("click", ".mps-quote-comment-link", function(
        event
      ) {
        event.preventDefault();
        event.stopPropagation();

        // TODO
        // showCommentsModal( event );
      });

      $("#quote-id-page").on("click", ".export_to_excel", function(event) {
        event.preventDefault();
        event.stopPropagation();
        var data = {};
        quoteStatusTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            for (var i = 0; i < 6; i++) {
              data[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
          });
        data["Line Items"] = [];
        quoteLineItemsTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            var subData = {};
            for (var i = 0; i < 7; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
            for (var i = 8; i < 10; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
            subData[Object.keys(this.data())[11]] = this.data()[
              Object.keys(this.data())[11]
            ];
            data["Line Items"].push(subData);
          });
        JSONToCSVConverter(
          data,
          "Quote Details List For: " + viewModel.quoteNumber(),
          true
        );
      });

      ko.applyBindings(viewModel, $("#quote-id-page")[0]);
    }

    // MISC ==========================================================

    var backLink = null;

    var $quoteStatusTable = null;
    var $quoteLineItemsTable = null;

    var quoteStatusTable = null;
    var quoteLineItemsTable = null;

    function backToQuoteSummary() {
      if (backLink) Router.navigate(backLink);
      else
        Router.navigate(
          ROUTES.QUOTE_SUMMARY,
          QuoteSummaryPage.createParams([previousParams.account], null, [
            previousParams.quote
          ])
        );
    }

    function setBackLink(url) {
      backLink = url;
    }

    function createNavigationUrl(
      accountNumberString,
      quoteNumberString,
      catalogNumberString
    ) {
      if (!accountNumberString || !quoteNumberString) return "#";

      var params = createParams(
        accountNumberString,
        quoteNumberString,
        catalogNumberString
      );

      return Router.createURL(ROUTES.QUOTE_DETAILS, params);
    }

    function createBreadCrumb() {
      var label = "Details";

      if (previousParams) {
        if (previousParams.account) label += " for " + previousParams.account;
        // if ( previousParams.catalog )
        //     label += ', Catalog: ' + previousParams.catalog;
        if (previousParams.quote) label += ", " + previousParams.quote;
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.QUOTE_DETAILS, previousParams),
        label
      );
    }

    return {
      init: init,
      createParams: createParams,
      setBackLink: setBackLink,
      createNavigationUrl: createNavigationUrl,
      createBreadCrumb: createBreadCrumb
    };
  })();

  // ORDER =============================================================

  var OrderLandingPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.ORDER_LANDING;

    function onNavigate(queryParams) {
      PageManager.show(viewModel);
      $("#id-filter-date > h5").html("PO Date");
    }

    function onFilter(filterData) {
      var params = OrderSummaryPage.parseFilterData(filterData);
      if (!params) return;

      Router.navigate(ROUTES.ORDER_SUMMARY, params);
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      ko.applyBindings(viewModel, $("#order-default-page")[0]);
    }

    // MISC ==========================================================

    function createBreadCrumb() {
      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.ORDER_LANDING),
        "Order"
      );
    }

    return {
      init: init,
      createBreadCrumb: createBreadCrumb
    };
  })();

  var OrderSummaryPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);

      self.accountNumbers = ko.observable("");
      self.resultsCount = ko.observable(0);
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.ORDER_SUMMARY;

    var previousParams = null;

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      // set details page back link
      var backLink = Router.lastRouteResolved();
      OrderDetailsPage.setBackLink(createURL(backLink.url, backLink.query));

      var isCatalogFiltered = !!params.catalogs;
      var isOrderFiltered = !!params.orders;
      var isPoFiltered = !!params.pos;

      var accounts = params.accounts || [];
      var catalogs = params.catalogs || [];
      var orders = params.orders || [];
      var pos = params.pos || [];
      var dateFrom = params.from || "";
      var dateTo = params.to || "";
      var status = params.status || "";

      // set filter content
      var filterContent = {};
      if (accounts) filterContent.selectedAccountCheckboxes = accounts;
      if (isCatalogFiltered && catalogs) {
        filterContent["textLower"] = catalogs.join(",");
        filterContent["filterType"] = Filter.FILTER_TYPES.CATALOG_NUMBER;
      } else if (isOrderFiltered && orders) {
        filterContent["textLower"] = orders.join(",");
        filterContent["filterType"] = Filter.FILTER_TYPES.ORDER_NUMBER;
      } else if (isPoFiltered && pos) {
        filterContent["textLower"] = pos.join(",");
        filterContent["filterType"] = Filter.FILTER_TYPES.PO_NUMBER;
      }
      if (dateFrom) filterContent["dateFrom"] = dateFrom;
      if (dateTo) filterContent["dateTo"] = dateTo;
      if (status) filterContent["status"] = status;
      Filter.setContent(filterContent);

      // hide columns
      if (isCatalogFiltered) {
        orderSummaryTable.column("catalogNumber:name").visible(true);
        orderSummaryTable.column("lineItem:name").visible(true);
        orderSummaryTable.column("price:name").visible(true);
        orderSummaryTable.column("qty:name").visible(true);
        orderSummaryTable.column("origAckDate:name").visible(true);
        orderSummaryTable.column("currAvailDate:name").visible(true);
        orderSummaryTable.column("dateShipped:name").visible(true);
        orderSummaryTable.column("proNo:name").visible(true);
        orderSummaryTable.column("track:name").visible(true);
        orderSummaryTable.column("custName:name").visible(false);
        orderSummaryTable.column("shipTo:name").visible(false);
        orderSummaryTable.column("poDate:name").visible(false);
        orderSummaryTable.column("cityStZip:name").visible(false);
      } else {
        orderSummaryTable.column("catalogNumber:name").visible(false);
        orderSummaryTable.column("lineItem:name").visible(false);
        orderSummaryTable.column("price:name").visible(false);
        orderSummaryTable.column("qty:name").visible(false);
        orderSummaryTable.column("origAckDate:name").visible(false);
        orderSummaryTable.column("currAvailDate:name").visible(false);
        orderSummaryTable.column("dateShipped:name").visible(false);
        orderSummaryTable.column("proNo:name").visible(false);
        orderSummaryTable.column("track:name").visible(false);
        orderSummaryTable.column("custName:name").visible(true);
        orderSummaryTable.column("shipTo:name").visible(true);
        orderSummaryTable.column("poDate:name").visible(true);
        orderSummaryTable.column("cityStZip:name").visible(true);
      }

      var postData = {
        action: "orders_account_ajax"
      };

      if (accounts) postData.accounts = accounts;
      if (isCatalogFiltered && catalogs) postData.catalogs = catalogs;
      if (isOrderFiltered && orders) postData.orders = orders;
      if (isPoFiltered && pos) postData.pos = pos;
      if (dateFrom) postData.from = dateFrom;
      if (dateTo) postData.to = dateTo;
      if (status) postData.status = status;

      var accountsCsv = accounts.join(", ");

      ajax(
        postData,
        function(data) {
          setTableData(orderSummaryTable, data);

          viewModel.accountNumbers(accountsCsv);
          viewModel.resultsCount(data.length);

          complete();
        },
        function(xhr, ajaxOptions, error) {
          complete();
        }
      );

      function complete() {
        PageManager.show(viewModel);
        $("#id-filter-date > h5").html("PO Date");
        resetTables();
        hideContentSpinner();
      }
    }

    function onFilter(filterData) {
      var params = parseFilterData(filterData);
      if (!params) return;

      Router.navigate(ROUTE, params);
    }

    function createParams(
      accountNumbersArray,
      catalogNumbersArray,
      orderNumbersArray,
      poNumbersArray,
      dateFromString,
      dateToString,
      statusString
    ) {
      accountNumbersArray = arrayOrNull(accountNumbersArray);
      catalogNumbersArray = arrayOrNull(catalogNumbersArray);
      orderNumbersArray = arrayOrNull(orderNumbersArray);
      poNumbersArray = arrayOrNull(poNumbersArray);

      dateFromString = stringOrNull(dateFromString);
      dateToString = stringOrNull(dateToString);
      statusString = stringOrNull(statusString);

      // keys

      return {
        accounts: accountNumbersArray,
        catalogs: catalogNumbersArray,
        orders: orderNumbersArray,
        pos: poNumbersArray,
        from: dateFromString,
        to: dateToString,
        status: statusString
      };
    }

    function parseFilterData(filterData) {
      if (
        filterData.button !== Filter.BUTTONS.SUBMIT_UPPER &&
        filterData.button !== Filter.BUTTONS.SUBMIT_LOWER
      )
        return null;

      if (!filterData.content.selectedAccounts.length) {
        alert("Please select an account.");
         return null;
      }

      var catalogNumbers = null;
      var orderNumbers = null;
      var poNumbers = null;

      var filterText = filterData.content.textLower;
      if (filterText) {
        var items = filterText.split(",").map(function(item) {
          return item.trim();
        });

        switch (filterData.content.filterType) {
          case Filter.FILTER_TYPES.CATALOG_NUMBER:
            catalogNumbers = items;
            break;
          case Filter.FILTER_TYPES.ORDER_NUMBER:
            orderNumbers = items;
            break;
          case Filter.FILTER_TYPES.PO_NUMBER:
            poNumbers = items;
            break;
        }
      }

      return createParams(
        filterData.content.selectedAccounts,
        catalogNumbers,
        orderNumbers,
        poNumbers,
        filterData.content.dateFrom,
        filterData.content.dateTo,
        filterData.content.status
      );
    }

    function parseQueryParams(queryParams) {
      var accounts = queryParams.accounts;
      if (accounts && !Array.isArray(accounts)) accounts = [accounts];

      var catalogs = queryParams.catalogs;
      if (catalogs && !Array.isArray(catalogs)) catalogs = [catalogs];

      var orders = queryParams.orders;
      if (orders && !Array.isArray(orders)) orders = [orders];

      var pos = queryParams.pos;
      if (pos && !Array.isArray(pos)) pos = [pos];

      var from = queryParams.from;
      if (from && !isString(from)) from = "";

      var to = queryParams.to;
      if (to && !isString(to)) to = "";

      var status = queryParams.status;
      if (status && !isString(status)) status = "";

      return createParams(accounts, catalogs, orders, pos, from, to, status);
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      Events.onFilterEvent(ROUTE, onFilter);

      $orderSummaryTable = $("#order-account-listing-table");
      orderSummaryTable = $orderSummaryTable.DataTable({
        responsive: true,
        dom: 'Bfrtip',
        order: [[9, "desc"]],
        columns: [
          {
            className: "order-number",
            data: "orderNoShort"
          },
          {
            className: "po-number",
            data: "poNo",
            render: function(data, type, row) {
              var accountNumber = row["custNo"];
              var poNumber = row["poNo"];
              var catalogNumber = row["catNo"];

              var isCatalogFiltered =
                previousParams &&
                previousParams.catalogs &&
                previousParams.catalogs.length;

              if (type === "display") {
                var href = OrderDetailsPage.createNavigationUrl(
                  accountNumber,
                  poNumber
                );

                href = href || "#";

                return (
                  '<a href="' +
                  href +
                  '">' +
                  "<span>" +
                  poNumber +
                  "</span>" +
                  "</a>"
                );
              } else return data;
            }
          },
          {
            className: "account",
            data: "custNo",
            name: "custNo"
          },
          {
            className: "status",
            data: "status",
            name: "status"
          },
          {
            className: "line-item",
            data: "lineItem",
            name: "lineItem"
          },
          {
            className: "catalog-number",
            data: "catNo",
            name: "catalogNumber",
            render: function(data, type, row) {
              var accountNumber = row["custNo"];
              var poNumber = row["poNo"];

              var isCatalogFiltered =
                previousParams &&
                previousParams.catalogs &&
                previousParams.catalogs.length;

              var catalogNumber = isCatalogFiltered ? row["catNo"] : null;

              if (type === "display") {
                var href = OrderDetailsPage.createNavigationUrl(
                  accountNumber,
                  poNumber,
                  catalogNumber
                );

                href = href || "#";

                return (
                  '<a href="' +
                  href +
                  '">' +
                  "<span>" +
                  catalogNumber +
                  "</span>" +
                  "</a>"
                );
              } else return data;
            }
          },
          {
            className: "price",
            data: "price",
            name: "price",
            render: dataTablePriceRenderer
          },
          {
            className: "qty",
            data: "qty",
            name: "qty"
          },
          {
            className: "customer-name",
            data: "custName",
            name: "custName"
          },
          {
            className: "po-date",
            data: "poDate",
            type: "date",
            name: "poDate"
          },
          {
            className: "ship-to",
            data: "shipTo",
            name: "shipTo"
          },
          {
            className: "city-state-zip",
            data: "cityStZip",
            name: "cityStZip"
          },
          {
            className: "original-acknowledge-date",
            data: "origAckDate",
            name: "origAckDate",
            type: "date"
          },
          {
            className: "current-available-date",
            data: "currAvailDate",
            name: "currAvailDate",
            type: "date"
          },
          {
            className: "date-shipped",
            data: "dateShipped",
            name: "dateShipped",
            type: "date"
          },
          {
            className: "pro-no",
            data: "proNo",
            name: "proNo"
          },
          {
            className: "track",
            data: "track",
            name: "track"
          }
        ]
      });
      $orderSummaryTable.on("click", ".mps-order-ponumber-link", function(
        event
      ) {
        event.preventDefault();
        event.stopPropagation();

        // TODO

        // var poNumber = $( event.target ).data( 'ponumber' );
        //
        // OrderDetailsPage.requireLoad( OrderDetailsPage.queryParams( getLoadedAccountNumbers(), poNumber ) );
        // Router.navigate( ROUTES.ORDER_DETAILS + OrderDetailsPage.queryString() );
      });

      ko.applyBindings(viewModel, $("#order-account-page")[0]);
    }

    // MISC ==========================================================

    var $orderSummaryTable = null;
    var orderSummaryTable = null;

    function createBreadCrumb() {
      var label = "Summary";

      if (previousParams) {
        if (previousParams.accounts)
          label += " for " + previousParams.accounts.join(", ");
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.ORDER_SUMMARY, previousParams),
        label
      );
    }

    return {
      init: init,
      createParams: createParams,
      parseFilterData: parseFilterData,
      createBreadCrumb: createBreadCrumb
    };
  })();

  var OrderDetailsPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);

      self.poNumber = ko.observable("");

      self.onClickBackToOrderSummary = function() {
        backToQuoteSummary();
      };
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.ORDER_DETAILS;

    var previousParams = null;

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      var backLink = Router.lastRouteResolved();
      OrderPackagingPage.setBackLink(createURL(backLink.url, backLink.query));

      var accountNumber = params.account;
      var poNumber = params.po;
      var catalogNumber = params.catalog;

      // create POST data

      var postData = {
        action: "orders_id_ajax"
      };
      if (accountNumber) postData.account = accountNumber;
      if (poNumber) postData.po = poNumber;
      if (catalogNumber) postData.catNo = catalogNumber;

      ajax(
        postData,
        function(data) {
          setTableData(orderDetailsStatusTable, [data[0]]);
          setTableData(orderDetailsLineItemsTable, data[1]);

          viewModel.poNumber(poNumber);

          complete();
        },
        function(xhr, ajaxOptions, error) {
          complete();
        }
      );

      function complete() {
        PageManager.show(viewModel);
        resetTables();
        $("#id-filter-date > h5").html("PO Date");
        hideContentSpinner();
      }
    }

    function createParams(
      accountNumberString,
      poNumberString,
      catalogNumberString,
      orderNumberString,
      dateFromString,
      dateToString,
      statusString
    ) {
      accountNumberString = accountNumberString || null;
      poNumberString = poNumberString || null;
      catalogNumberString = catalogNumberString || null;
      orderNumberString = orderNumberString || null;
      dateFromString = dateFromString || null;
      dateToString = dateToString || null;
      statusString = statusString || null;

      if (accountNumberString && !isString(accountNumberString))
        accountNumberString = null;

      if (poNumberString && !isString(poNumberString)) poNumberString = null;

      if (catalogNumberString && !isString(catalogNumberString))
        catalogNumberString = null;

      if (orderNumberString && !isString(orderNumberString))
        orderNumberString = null;

      if (dateFromString && !isString(dateFromString)) dateFromString = null;

      if (dateToString && !isString(dateToString)) dateToString = null;

      if (statusString && !isString(statusString)) statusString = null;

      return {
        account: accountNumberString,
        po: poNumberString,
        order: orderNumberString,
        catalog: catalogNumberString,
        dateFrom: dateFromString,
        dateTo: dateToString,
        status: statusString
      };
    }

    function parseQueryParams(queryParams) {
      return createParams(
        queryParams.account,
        queryParams.po,
        queryParams.catalog,
        queryParams.orders,
        queryParams.from,
        queryParams.to,
        queryParams.status
      );
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      $orderDetailsStatusTable = $("#order-status-table");
      $orderDetailsLineItemsTable = $("#order-line-items-table");

      orderDetailsStatusTable = $orderDetailsStatusTable.DataTable({
        responsive: true,
        paging: false,
        ordering: false,
        bAutoWidth: false,
        //dom: 'Bfrtip',
        //buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            className: "customer-number",
            data: "Customer #"
          },
          {
            className: "po-number",
            data: "PO #"
          },
          {
            className: "po-date",
            data: "PO Date"
          },
          {
            className: "shipping-info",
            data: "Shipping Info"
          }
        ]
      });
      orderDetailsLineItemsTable = $orderDetailsLineItemsTable.DataTable({
        responsive: true,
        paging: false,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            className: "order-number",
            data: "Order #"
          },
          {
            className: "line-item",
            data: "Line Item"
          },
          {
            className: "status",
            data: "Status"
          },
          {
            className: "catalog-number",
            data: "Catalog #",
            render: function(data, type, row) {
              if (type === "display") {
                var accountNumber = previousParams.account;
                var poNumber = previousParams.po;
                var catalogNumber = row["catalogNumberCleaned"];
                var shouldRenderLink = row["shouldRenderLink"];

                if (
                  accountNumber &&
                  poNumber &&
                  catalogNumber &&
                  shouldRenderLink == "true"
                ) {
                  return (
                    '<a href="' +
                    OrderPackagingPage.createNavigationUrl(
                      accountNumber,
                      poNumber,
                      catalogNumber
                    ) +
                    '">' +
                    catalogNumber +
                    "</a>"
                  );
                } else return data;
              } else return data;
            }
          },
          {
            className: "cust-part",
            data: "Cust Part"
          },
          {
            className: "price",
            data: "Price",
            render: dataTablePriceRenderer
          },
          {
            className: "qty",
            data: "Qty"
          },
          {
            className: "Extended",
            data: "Extended Price"
          },          
          {
            className: "original-acknowledge-date",
            data: "Orig Ack Date"
          },
          {
            className: "current-available-date",
            data: "Current Avail Date"
          },
          {
            className: "date-shipped",
            data: "Date Shipped"
          },
          {
            className: "Invoice",
            data: "Invoice"
          }
        ]
      });

      orderDetailsStatusTable
        .buttons(".buttonsToHide")
        .nodes()
        .css("display", "none");
      orderDetailsLineItemsTable
        .buttons(".buttonsToHide")
        .nodes()
        .css("display", "none");

      $("#order-id-page").on("click", ".export_to_excel", function(event) {
        event.preventDefault();
        event.stopPropagation();

        var data = {};
        data["Line Items"] = [];

        orderDetailsStatusTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            for (var i = 0; i < 5; i++) {
              data[Object.keys(this.data())[i]] = stripHtml(this.data()[
                Object.keys(this.data())[i]
              ]);
            }
          });
        orderDetailsLineItemsTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            var subData = {};
            for (var i = 0; i < 12; i++) {
                  if(i!=5){
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
           
            }

            data["Line Items"].push(subData);

          });
        JSONToCSVConverter(
          data,
          "Order Details List For: " + viewModel.poNumber(),
          true
        );
      });

      ko.applyBindings(viewModel, $("#order-id-page")[0]);
    }
    
    function stripHtml(html)
{
  
   return html.replace(/<(.|\n)*?>/g, ',');
}
    // MISC ==========================================================

    var backLink = null;

    var $orderDetailsStatusTable = null;
    var $orderDetailsLineItemsTable = null;

    var orderDetailsStatusTable = null;
    var orderDetailsLineItemsTable = null;

    function setBackLink(url) {
      backLink = url;
    }

    function backToQuoteSummary() {
      if (backLink) Router.navigate(backLink);
      else
        Router.navigate(
          ROUTES.ORDER_SUMMARY,
          OrderSummaryPage.createParams(
            [previousParams.account],
            [previousParams.catalog],
            [previousParams.order],
            [previousParams.poNo],
            [previousParams.dateFrom],
            [previousParams.dateTo],
            [previousParams.status]
          )
        );
    }

    function createNavigationUrl(
      accountNumberString,
      poNumberString,
      catalogNumberString
    ) {
      if (!accountNumberString || !poNumberString) return "#";

      var params = createParams(
        accountNumberString,
        poNumberString,
        catalogNumberString
      );

      return Router.createURL(ROUTE, params);
    }

    function createBreadCrumb() {
      var label = "Details";

      if (previousParams) {
        if (previousParams.account) label += " for " + previousParams.account;
        if (previousParams.po) label += ", " + previousParams.po;
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.ORDER_DETAILS, previousParams),
        label
      );
    }

    return {
      init: init,
      setBackLink: setBackLink,
      createNavigationUrl: createNavigationUrl,
      createParams: createParams,
      createBreadCrumb: createBreadCrumb
    };
  })();

  var OrderPackagingPage = (function() {
    // VIEW MODEL ====================================================

    var viewModel = null;

    function ViewModel() {
      var self = this;

      self.visible = ko.observable(false);

      self.catalogNumber = ko.observable("");

      self.onClickBackToOrderDetails = function() {
        backToOrderDetails();
      };
    }

    // CORE ==========================================================

    var ROUTE = ROUTES.ORDER_PACKAGING;

    var previousParams = null;

    function onNavigate(queryParams) {
      var params = parseQueryParams(queryParams);

      showContentSpinner();

      if (equivalentParameters(params, previousParams)) return complete();
      previousParams = JSON.parse(JSON.stringify(params));

      var account = params.account;
      var poNumber = params.po;
      var catalogNumber = params.catalog;

      // create POST data

      var postData = {
        action: "orders_packaging_ajax"
      };
      if (account) postData.account = account;
      if (poNumber) postData.po = poNumber;
      if (catalogNumber) postData.catalog = catalogNumber;

      ajax(
        postData,
        function(data) {
          setTableData(packagingInfoTable, data["packaging_info"]);
          setTableData(lineItemsTable, data["line_items"]);

          viewModel.catalogNumber(catalogNumber);

          complete(data["packaging_info"].length === 0);
        },
        function(xhr, ajaxOptions, error) {
          complete();
        }
      );

      function complete(hide) {
        hide = hide === true || false;

        PageManager.show(viewModel);
        $("#id-filter-date > h5").html("PO Date");
        if (hide === true) {
          $(packagingInfoTable)
            .parent()
            .parent()
            .hide();
        } else {
          $(packagingInfoTable)
            .parent()
            .parent()
            .show();
        }
        hideContentSpinner();
      }
    }

    function createParams(
      accountNumberString,
      poNumberString,
      catalogNumberString
    ) {
      accountNumberString = accountNumberString || null;
      poNumberString = poNumberString || null;
      catalogNumberString = catalogNumberString || null;

      accountNumberString = stringOrNull(accountNumberString);
      poNumberString = stringOrNull(poNumberString);
      catalogNumberString = stringOrNull(catalogNumberString);

      return {
        account: accountNumberString,
        po: poNumberString,
        catalog: catalogNumberString
      };
    }

    function parseQueryParams(queryParams) {
      return createParams(
        queryParams.account,
        queryParams.po,
        queryParams.catalog
      );
    }

    // INIT ==========================================================

    function init() {
      viewModel = new ViewModel();

      PageManager.register(viewModel);

      Router.register(ROUTE, onNavigate);

      $orderPackagingInfoTable = $("#order-packaging-info-table");
      $orderPackagingLineItemsTable = $("#order-packaging-line-items-table");

      packagingInfoTable = $orderPackagingInfoTable.DataTable({
        responsive: true,
        bSort: false,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            data: "standard_package_quantity"
          },
          {
            data: "pallet_quantity"
          },
          {
            data: "weight_ea"
          },
          {
            data: "unit_of_measure"
          }
        ]
      });
      packagingInfoTable
        .buttons(".buttonsToHide")
        .nodes()
        .css("display", "none");

      lineItemsTable = $orderPackagingLineItemsTable.DataTable({
        responsive: true,
        paging: false,
        dom: 'Bfrtip',
        buttons: [{ extend: "excel", className: "buttonsToHide" }],
        columns: [
          {
            className: "order-number",
            data: "orderNumber"
          },
          {
            className: "po-number",
            data: "poNumber"
          },
          {
            className: "status",
            data: "status"
          },
          {
            className: "line-item",
            data: "lineItem"
          },
          {
            className: "price",
            data: "price",
            render: dataTablePriceRenderer
          },
          {
            className: "qty",
            data: "quantity"
          },
          {
            className: "original-acknowledge-date",
            data: "origAckDate"
          },
          {
            className: "current-available-date",
            data: "currentAvailDate"
          },
          {
            className: "date-shipped",
            data: "dateShipped"
          },
          {
            className: "pro-number",
            data: "proNumber"
          },
          {
            className: "tracking",
            data: "track"
          }
        ]
      });
      lineItemsTable
        .buttons(".buttonsToHide")
        .nodes()
        .css("display", "none");

      $(document).on("click", ".order-tracking-link", function(e) {
        $(e.target)
          .find(".modal")
          .clone()
          .dialog({
            dialogClass: "tracking-popup",
            autoOpen: true,
            close: function(ev, ui) {
              $(this)
                .dialog("destroy")
                .remove();
            }
          });
      });

   

      $('#order-packaging-page').on("click", ".export_to_excel", function(event) {
        event.preventDefault();
        event.stopPropagation();
        var data = {};
        packagingInfoTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            for (var i = 0; i < 4; i++) {
              data[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
          });
        data["Line Items"] = [];
        lineItemsTable
          .rows()
          .every(function(rowIndex, tableLoopCounter, rowLoopCounter, und) {
            var subData = {};
            for (var i = 0; i < 7; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
            for (var i = 8; i < 11; i++) {
              subData[Object.keys(this.data())[i]] = this.data()[
                Object.keys(this.data())[i]
              ];
            }
            // subData[Object.keys(this.data())[11]] = this.data()[
            //   Object.keys(this.data())[11]
            // ];
            data["Line Items"].push(subData);
          });
        JSONToCSVConverter(
          data,
          "PACKAGING INFORMATION FOR: "+ viewModel.catalogNumber(),
          true
        );
      });

      ko.applyBindings(viewModel, $("#order-packaging-page")[0]);
    }

    // MISC ==========================================================

    var backLink = null;

    var $orderPackagingInfoTable = null;
    var $orderPackagingLineItemsTable = null;

    var packagingInfoTable = null;
    var lineItemsTable = null;

    function setBackLink(url) {
      backLink = url;
    }

    function backToOrderDetails() {
      if (backLink) Router.navigate(backLink);
      else
        Router.navigate(
          ROUTES.ORDER_DETAILS,
          OrderDetailsPage.createParams(
            previousParams.account,
            previousParams.po,
            previousParams.catalog,
            previousParams.order,
            previousParams.from,
            previousParams.to,
            previousParams.status
          )
        );
    }

    function createNavigationUrl(
      accountNumberString,
      poNumberString,
      catalogNumberString
    ) {
      if (!accountNumberString || !poNumberString) return "#";

      var params = createParams(
        accountNumberString,
        poNumberString,
        catalogNumberString
      );

      return Router.createURL(ROUTE, params);
    }

    function createBreadCrumb() {
      var label = "Packaging";

      if (previousParams) {
        if (previousParams.catalog) label += " for " + previousParams.catalog;
      }

      return BreadCrumbs.createCrumb(
        Router.createURL(ROUTES.ORDER_PACKAGING, previousParams),
        label
      );
    }

    return {
      init: init,
      createNavigationUrl: createNavigationUrl,
      setBackLink: setBackLink,
      createBreadCrumb: createBreadCrumb
    };
  })();

  $(document).ready(function() {
    mpservicenet_params.accounts.sort(function(a, b) {
      return a.customerName < b.customerName ? -1 : 1;
    });

    jQuery(document).on("click", "#id-submit-button", function(e) {
      jQuery("#breadcrumbs-list")[0].scrollIntoView();
    });

    $(document).on("keypress", function(e) {
      if (e.which == 13) {
        jQuery("#breadcrumbs-list")[0].scrollIntoView();
      }
    });

    Announcements.init();
    BreadCrumbs.init();
    Filter.init(mpservicenet_params.accounts);
    Navigator.init();

    LandingPage.init();

    PALandingPage.init();
    PACatalogSummaryPage.init();
    PAPackagingInformationPage.init();

    QuoteLandingPage.init();
    QuoteSummaryPage.init();
    QuoteDetailsPage.init();

    OrderLandingPage.init();
    OrderSummaryPage.init();
    OrderDetailsPage.init();
    OrderPackagingPage.init();

    $mpservicenet = $("#mpservicenet");
    $mpservicenet.show();

    // only resolve after all other initialization
    Router.resolve();

  //account filter pre select first one checked
  $('.mps-navigation-button').click(function() {
    var accounts = [];
    $('.filter-deselect-account-items ul li').each(function(){
      var span = $(this).find('span')
      accounts.push(span.text());
    })
    $('#account-filter-list-radio input:checked').each(function() {
      $( this ).prop('checked', false);
    });
    $('input[type="radio"][value="'+ accounts[0] + '"]').prop("checked", true).trigger('click');
    $('input[type="checkbox"][value="'+ accounts[0] + '"]').prop("checked", true); 
  });

  //for single account always check
  if ( $('.account-options ul li').length == 2 ) {
    $('#account-filter-list-radio').find('input[type="radio"]').prop("checked", true).trigger('click');;
    $('#account-filter-list-checkbox').find('input[type="checkbox"]').trigger('click');
  }

  });
})(jQuery);
