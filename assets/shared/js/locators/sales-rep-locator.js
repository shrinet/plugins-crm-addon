var SalesRepLocatorSelectState;

(function($) {
  function sortByMenuOrder(posts) {
    posts.sort(function(p1, p2) {
      return p1.menuOrder > p2.menuOrder ? -1 : 1;
    });
  }

  var Map = (function() {
    var $container;

    var map;
    var regions;

    var regionIdsByName = {};
    var regionNamesById = {};

    function init() {
      $container = $("#sales-rep-locator-map");
      waitForMapSVG();
    }

    function prepareMap() {
      map = MapSVG.get(0);
      regions = map.regions.filter(function(region) {
        return region.data.status !== "0";
      });
      regions.map(function(region) {
        var id = region.data.id;
        var name = region.data.name;

        regionIdsByName[name] = id;
        regionNamesById[id] = name;
      });
      SalesRepLocator.enableInput();
    }

    function waitForMapSVG() {
      if (typeof MapSVG !== "undefined" && typeof MapSVG.get(0) !== "undefined")
        prepareMap();
      else setTimeout(waitForMapSVG, 250);
    }

    function select(name) {
      if (map) map.selectRegion(map.getRegion(regionIdsByName[name]));
    }

    function deselectAllRegions() {
      if (map) map.deselectAllRegions();
    }

    return {
      init: init,
      select: select,
      deselectAllRegions: deselectAllRegions
    };
  })();

  var SalesRepLocator = (function() {
    var $container;

    var awaitingResults = false;

    var markets;
    var countries;

    // =============================================================================================================
    // VIEW MODEL
    // =============================================================================================================

    var viewModel;

    function ViewModel() {
      var self = this;

      self.resultsVisible = ko.observable(false);
      self.results = ko.observableArray();
      self.resultCountText = ko.computed(function() {
        var resultCount = self.results().length;
        return resultCount === 0
          ? "No results found."
          : resultCount + " " + (resultCount === 1 ? "Result" : "Results");
      });

      self.inputEnabled = ko.observable(false);
      self.selectedMarket = ko.observable(markets[0].code);
      self.selectedCountry = ko.observable(countries[0].code);
      self.selectedRegion = ko.observable();

      self.markets = ko.observableArray(markets);
      self.countries = ko.observableArray(countries);
      self.regions = ko.observableArray(
        getCountryByCode(self.selectedCountry()).regions
      );

      self.selectedCountry.subscribe(function(value) {
        self.regions(getCountryByCode(value).regions);
        self.selectedRegion("");
        Map.deselectAllRegions();
      });

      self.selectedRegion.subscribe(function(value) {
        Map.select(value);
      });

      self.onSubmitClick = function() {
        submit();
      };

      self.mapClass = ko.pureComputed(function() {
        return self.resultsVisible() ? "with-results" : "";
      });
    }

    // =============================================================================================================
    // PRIVATE
    // =============================================================================================================

    function setResults(results) {
      sortByMenuOrder(results);
      viewModel.results(results);
      viewModel.resultsVisible(true);
    }

    function getCountryByCode(code) {
      return countries.find(function(country) {
        return country.code === code;
      });
    }

    // =============================================================================================================
    // PUBLIC
    // =============================================================================================================

    function init(_markets, _countries) {
      markets = _markets;
      countries = _countries;

      viewModel = new ViewModel();
      $container = $("#sales-rep-locator");

      ko.applyBindings(viewModel, $container[0]);
    }

    function selectState(region) {
      if (region && region.length === 5) {
        viewModel.selectedCountry(region.slice(0, 2));
        viewModel.selectedRegion(region);
      }
    }

    function submit() {
      if (
        !viewModel.selectedMarket() ||
        !viewModel.selectedCountry() ||
        !viewModel.selectedRegion()
      ) {
        alert(
          "Please select a Market, Country and State or click on a location on the map."
        );
        return;
      }

      if (!awaitingResults) {
        awaitingResults = true;
        $container.LoadingOverlay("show");

        var selectedMarketCode = viewModel.selectedMarket();

        var selectedCountry = getCountryByCode(viewModel.selectedCountry());
        var selectedRegion = selectedCountry.regions.find(function(region) {
          return region.code === viewModel.selectedRegion();
        });

        $.ajax({
          cache: false,
          type: "POST",
          url: maclean_ajax_url,
          data: {
            action: "sales_rep_locator_data",
            market: selectedMarketCode,
            countryCode: selectedCountry.code,
            regionCode: selectedRegion.code
          },
          success: function(data) {
            var results = JSON.parse(data);

            if (results && Array.isArray(results)) setResults(results);

            $container.LoadingOverlay("hide");
            awaitingResults = false;
          }
        });
      }
    }

    function disableInput() {
      viewModel.inputEnabled(false);
    }

    function enableInput() {
      viewModel.inputEnabled(true);
    }

    return {
      init: init,
      selectState: selectState,
      submit: submit,
      disableInput: disableInput,
      enableInput: enableInput
    };
  })();

  $(function() {
    if (!sales_rep_parameters) return;

    var markets = sales_rep_parameters.markets || [];
    var countries = sales_rep_parameters.countries || [];
    var regions = sales_rep_parameters.regions || [];

    countries.forEach(function(country) {
      country["regions"] = regions.filter(function(region) {
        return region.code.slice(0, 2) === country.code;
      });
    });

    Map.init();
    SalesRepLocator.init(markets, countries);

    SalesRepLocatorSelectState = function(name) {
      SalesRepLocator.selectState(name);
      SalesRepLocator.submit();
    };
  });
})(jQuery);
