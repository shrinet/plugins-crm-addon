(function($) {
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

    function ViewModel(countries, distances, locations) {
      var self = this;

      self.countries = ko.observableArray(countries);
      self.distances = ko.observableArray(distances);
      self.locations = ko.observableArray(locations);

      self.userCountry = ko.observable(countries[0]);
      self.userDistance = ko.observable(distances[0]);
      self.userAddress = ko.observable("");

      self.submit = function() {
        submit();
      };
    }

    function submit() {
      MpsLocator.showLoadingOverlay();

      ae.Map.controls.closePreviousInfoWindow();
      Results.unFocusLocations();

      var locations = viewModel.locations();

      locations = filterByCountry(locations, viewModel.userCountry());
      filterByDistance(
        locations,
        viewModel.userAddress(),
        previousAddress,
        viewModel.userDistance()
      );
    }

    function filterByCountry(locations, country) {
      if (country === "all") return locations;

      return locations.filter(function(location) {
        return location.country === country;
      });
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

            locations = filter(locations, previousLatLng, distance);
            sortByMenuOrder(locations);
            Results.set(locations);
          });
        else {
          // already geocoded
          locations = filter(locations, previousLatLng, distance);
          sortByMenuOrder(locations);
          Results.set(locations);
        }
      else {
        // no address provided
        if (previousMarker) ae.Map.controls.hideMarker(previousMarker);
        previousMarker = previousLatLng = previousAddress = null;
        sortByMenuOrder(locations);
        Results.set(locations);
      }

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

    function init(countries, distances, locations) {
      viewModel = new ViewModel(countries, distances, locations);
      ko.applyBindings(viewModel, $("#mps-locator-filters")[0]);
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
          $("li.result.focused").html() +
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

      MpsLocator.hideLoadingOverlay();
    }

    function init(translations) {
      viewModel = new ViewModel(translations);
      ko.applyBindings(viewModel, $("#mps-locator-results")[0]);
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

  var MpsLocator = (function() {
    var $container = null;

    function showLoadingOverlay() {
      $container.LoadingOverlay("show");
    }

    function hideLoadingOverlay() {
      $container.LoadingOverlay("hide");
    }

    function init() {
      $container = $("#mps-locator");
      $container.show();
    }

    return {
      init: init,

      // loading overlay
      showLoadingOverlay: showLoadingOverlay,
      hideLoadingOverlay: hideLoadingOverlay
    };
  })();

  $(function() {
    // wait for google maps, then init
    wait();

    function wait() {
      if (typeof google === "undefined" || typeof ae === "undefined")
        setTimeout(wait, 250);
      else init();
    }

    function init() {
      ae.Map.init("#mps-locator-map");

      var locations = [];
      mps_locator_parameters.locations.forEach(function(locationData) {
        locations.push(
          new ae.Location(
            locationData,
            Results.focusOnLocation,
            Results.hoverLocation,
            Results.unHoverLocation
          )
        );
      });

      Filter.init(
        mps_locator_parameters.countries || [],
        mps_locator_parameters.distances || [],
        locations
      );
      Results.init(mps_locator_parameters.translations);
      MpsLocator.init();

      sortByMenuOrder(locations);

      Results.set(locations);
    }
  });
})(jQuery);
