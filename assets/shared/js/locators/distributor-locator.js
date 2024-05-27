(function($) {
  function registerComponents(templates, t9n) {
    ko.components.register("distributor-results-component", {
      viewModel: function(params) {
        var self = this;

        self.parentId = params.parentId || "";

        self.locations = params.locations || ko.observableArray([]);
        self.focusedLocation = params.focusedLocation || ko.observable();
        self.hoveredLocation = params.hoveredLocation || ko.observable();
        self.locationClicked = params.locationClicked || function() {};
        self.locationMouseOut = params.locationMouseOut || function() {};
        self.locationMouseOver = params.locationMouseOver || function() {};
        self.active = ko.observable(false);

        self.active.subscribe(function(value) {
          if (!self.parentId) return;

          var $parent = $("#" + self.parentId);

          if (!$parent.length) return;

          if (value) {
            $parent.addClass("active");
          } else {
            $parent.removeClass("active");
          }
        });

        self.locations.subscribe(function() {
          if (!self.active()) self.active(true);
        });

        self.countText = ko.computed(function() {
          var length = self.locations().length;
          return length + " " + (length === 1 ? t9n.result : t9n.results);
        });
      },
      template: templates.distributor_results
    });
  }

  function sortByMenuOrder(posts) {
    posts.sort(function(p1, p2) {
      return p1.menuOrder > p2.menuOrder ? -1 : 1;
    });
  }

  var Filter = (function() {
    var viewModel = null;

    var previousMarker = null;
    var previousAddress = null;
    var previousLatLng = null;

    function ViewModel(markets, countries, distances) {
      var self = this;

      self.markets = ko.observableArray(markets);
      self.countries = ko.observableArray(countries);
      self.distances = ko.observableArray(distances);
      self.locations = {};

      self.userMarket = ko.observable(markets[0]);
      self.userCountry = ko.observable(countries[0]);
      self.userDistance = ko.observable(distances[0]);
      self.userAddress = ko.observable("");

      self.submit = function() {
        submit();
      };
    }

    function submit() {
      ae.Map.controls.closePreviousInfoWindow();
      Results.unFocusLocations();

      DistributorLocator.showLoadingOverlay();

      loadLocations();
    }

    function loadLocations() {
      var market = viewModel.userMarket();
      var country = viewModel.userCountry();
      var address = viewModel.userAddress();
      var distance = viewModel.userDistance();

      var locations = viewModel.locations;

      if (locations[market] && locations[market][country])
        filterByDistance(
          locations[market][country],
          address,
          previousAddress,
          distance
        );
      else DistributorLocator.ajax(market, country, afterLoad);

      function afterLoad(newLocations) {
        locations[market] = locations[market] || [];
        locations[market][country] = newLocations;
        filterByDistance(
          locations[market][country],
          address,
          previousAddress,
          distance
        );
      }
    }

    function filterByDistance(locations, address, previousAddress, distance) {
      if (address)
        if (address !== previousAddress)
          // user entered an address
          // need to geocode
          ae.Map.geocoder.geoCode(address, function(results) {
            // save formatted address
            var formattedAddress = results[0].formatted_address;
            if (formattedAddress) {
              viewModel.userAddress(formattedAddress);
              address = formattedAddress;
            }

            // save previous address details
            previousAddress = address;
            previousLatLng = results[0].geometry.location;
            if (previousMarker) ae.Map.controls.hideMarker(previousMarker);
            previousMarker = ae.Map.markers.createPrimary(
              address,
              previousLatLng.lat(),
              previousLatLng.lng()
            );

            Results.set(filter(locations, previousLatLng, distance));
          });
        // already geocoded
        else Results.set(filter(locations, previousLatLng, distance));
      else {
        // no address provided
        if (previousMarker) ae.Map.controls.hideMarker(previousMarker);
        previousMarker = previousLatLng = previousAddress = null;
        Results.set(locations);
      }

      DistributorLocator.hideLoadingOverlay();

      function filter(locations, latLng, maxDistance) {
        locations = locations.filter(function(location) {
          var distance = ae.Map.calculations.distanceBetweenPositions(
            latLng,
            location.marker.position
          );
          if (distance <= maxDistance) {
            location.distance(distance);
            return true;
          } else return false;
        });

        // order by distance
        locations.sort(function(a, b) {
          return a.distance() <= b.distance() ? -1 : 1;
        });

        return locations;
      }
    }

    function init(markets, countries, distances) {
      viewModel = new ViewModel(markets, countries, distances);
      ko.applyBindings(viewModel, $("#distributor-locator-filters")[0]);
    }

    return {
      init: init,
      previousMarker: function() {
        return previousMarker;
      }
    };
  })();

  var Results = (function() {
    var FOCUS_MAP_ZOOM = 11;

    var viewModel;

    function ViewModel(translations) {
      var self = this;

      self.results = ko.observableArray([]);
      self.focusedLocation = ko.observable(null);
      self.hoveredLocation = ko.observable(null);

      self.countText = ko.computed(function() {
        var resultCount = self.results().length;
        if (resultCount === 1) return resultCount + " " + translations.result;
        else return resultCount + " " + translations.results;
      });

      self.locationClicked = function(location, event) {
        if (event.target.tagName.toLowerCase() === "a") return true;

        focusOnLocation(location, false);
      };
      self.locationMouseOver = function(location) {
        hoverLocation(location);
        location.marker.setIcon({
          url: ae.Map.markers.IMAGE_ICON_URLS.SECONDARY_FOCUSED
        });
      };
      self.locationMouseOut = function(location) {
        unHoverLocation();
        location.marker.setIcon({
          url: ae.Map.markers.IMAGE_ICON_URLS.SECONDARY
        });
      };
    }

    function focusOnLocation(location, fromMapMarker) {
      if (fromMapMarker === undefined) fromMapMarker = true;

      ae.Map.controls.focusOnMarker(location.marker, FOCUS_MAP_ZOOM);
      viewModel.focusedLocation(location);

      location.infoWindow = new google.maps.InfoWindow({
        content:
          '<div class="info-window-content">' +
          $("li.result-item.focused .result-info").html() +
          "</div>"
      });
      ae.Map.controls.openInfoWindow(location.infoWindow, location.marker);

      // scroll if clicked from map
      if (fromMapMarker) {
        var $location = $("#location-" + location.id);
        var $scrollable = $location.closest(".results-listing");

        if ($location.length && $scrollable.length) {
          $scrollable.scrollTop(0);
          var topPosition =
            $location.position().top - $scrollable.position().top;
          $scrollable.animate({ scrollTop: topPosition });
        }
      }
    }

    function unFocusLocations() {
      viewModel.focusedLocation(null);
    }

    function hoverLocation(location) {
      viewModel.hoveredLocation(location);
    }

    function unHoverLocation() {
      viewModel.hoveredLocation(null);
    }

    function set(locations) {
      sortByMenuOrder(locations);

      viewModel.results().forEach(function(location) {
        ae.Map.controls.hideMarker(location.marker);
      });

      locations.forEach(function(location) {
        ae.Map.controls.showMarker(location.marker);
      });

      var markers = [];

      var previousMarker = Filter.previousMarker();
      if (previousMarker) {
        markers.push(previousMarker);
        ae.Map.controls.showMarker(previousMarker);
      }

      locations.forEach(function(location) {
        markers.push(location.marker);
      });

      ae.Map.controls.focusOnMarkers(markers);
      viewModel.results(locations);
    }

    function init(translations) {
      viewModel = new ViewModel(translations);
      ko.applyBindings(viewModel, $("#distributor-results-primary")[0]);
      ko.applyBindings(viewModel, $("#distributor-results-secondary")[0]);
    }

    return {
      init: init,
      set: set,
      focusOnLocation: focusOnLocation,
      unFocusLocations: unFocusLocations,
      hoverLocation: hoverLocation,
      unHoverLocation: unHoverLocation
    };
  })();

  var DistributorLocator = (function() {
    var $container = null;

    // LOADING OVERLAY =============================================================================================

    function showLoadingOverlay() {
      $container.LoadingOverlay("show");
    }

    function hideLoadingOverlay() {
      $container.LoadingOverlay("hide");
    }

    // AJAX ========================================================================================================

    function ajax(market, country, callbackFn) {
      $.ajax({
        cache: false,
        type: "POST",
        url: maclean_ajax_url,
        data: {
          action: "distributor_locator_data",
          market: market,
          country: country
        },
        success: function(data) {
          var results = JSON.parse(data);
          if (!Array.isArray(results)) alert(results);
          else
            callbackFn(
              results.map(function(result) {
                return new ae.Location(
                  result,
                  Results.focusOnLocation,
                  Results.hoverLocation,
                  Results.unHoverLocation
                );
              })
            );
        }
      });
    }

    // INIT ========================================================================================================

    function init() {
      $container = $("#distributor-locator");
      $container.show();
    }

    return {
      init: init,

      // loading overlay
      showLoadingOverlay: showLoadingOverlay,
      hideLoadingOverlay: hideLoadingOverlay,

      // ajax
      ajax: ajax
    };
  })();

  jQuery(function() {
    // wait for google maps, then init
    wait();

    function wait() {
      if (typeof google === "undefined" || typeof ae === "undefined")
        setTimeout(wait, 250);
      else init();
    }

    function init() {
      var t9n = distributor_locator_params.translations;

      registerComponents(distributor_locator_params.templates, t9n);

      ae.Map.init("#distributor-map");

      Filter.init(
        distributor_locator_params.markets || [],
        distributor_locator_params.countries || [],
        distributor_locator_params.distances || []
      );
      Results.init(t9n);
      DistributorLocator.init();
    }
  });
})(jQuery);
