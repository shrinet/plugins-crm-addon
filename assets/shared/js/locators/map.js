var ae = ae || {};
(function ($) {
    var locationIdCounter = 0;

    function Location(location, onClick, onMouseOver, onMouseOut) {
        onClick = onClick || function () { };
        onMouseOver = onMouseOver || function () { };
        onMouseOut = onMouseOut || function () { };

        location.id = locationIdCounter++;

        location.marker = Map.markers.createSecondary(
            location.title,
            location.lat,
            location.lng
        );

        location.distance = ko.observable(0);

        if (onClick)
            Map.markers.onClick(location.marker, function () {
                onClick(location);
            });

        if (onMouseOver)
            Map.markers.onMouseOver(location.marker, function () {
                onMouseOver(location);
            });

        if (onMouseOut)
            Map.markers.onMouseOut(location.marker, function () {
                onMouseOut(location);
            });

        return location;
    }

    var Map = (function () {
        var INITIAL_ZOOM = 1.5;
        var INITIAL_LAT = 0.442148;
        var INITIAL_LNG = -145.761071;

        var googleMap = null;
        var googleGeoCoder = null;

        var calculations = (function () {
            var MILES_PER_METER = 0.000621371;

            function distanceBetweenLatLngs(lat1, lng1, lat2, lng2) {
                return distanceBetweenPositions(
                    new google.maps.LatLng(lat1, lng1),
                    new google.maps.LatLng(lat2, lng2)
                );
            }

            function distanceBetweenMarkers(marker1, marker2) {
                return distanceBetweenPositions(marker1.position, marker2.position);
            }

            function distanceBetweenPositions(latLng1, latLng2) {
                return google.maps.geometry.spherical.computeDistanceBetween(
                    latLng1,
                    latLng2
                ) * MILES_PER_METER;
            }

            return {
                distanceBetweenLatLngs: distanceBetweenLatLngs,
                distanceBetweenMarkers: distanceBetweenMarkers,
                distanceBetweenPositions: distanceBetweenPositions
            };
        })();

        var controls = (function () {
            var DEFAULT_ZOOM = 12;

            var previousInfoWindow = null;

            // SHOW / HIDE =============================================================================================

            function showMarker(marker) {
                marker.setMap(googleMap);
            }

            function hideMarker(marker) {
                marker.setMap(null);
            }

            function showAllMarkers(markers) {
                forEach(markers, show);
            }

            function hideAllMarkers(markers) {
                forEach(markers, hide);
            }

            // ZOOM / FOCUS ============================================================================================

            function focusOnMarker(marker) {
                if (DEFAULT_ZOOM > googleMap.getZoom())
                    focusOnLatLng(marker.position, DEFAULT_ZOOM);
                else
                    focusOnLatLng(marker.position, googleMap.getZoom());
            }

            function focusOnMarkers(markers) {
                var bounds = new google.maps.LatLngBounds();
                markers.forEach(function (marker) {
                    bounds.extend(marker.position);
                });

                if (markers.length > 1)
                    googleMap.fitBounds(bounds);
                else
                    focusOnMarker(markers[0]);
            }

            function focusOnLatLng(latLng, zoom) {
                googleMap.setCenter(latLng);
                if (zoom)
                    googleMap.setZoom(zoom);
            }

            // INFO WINDOWS ============================================================================================

            function openInfoWindow(infoWindow, marker) {
                if (previousInfoWindow && previousInfoWindow !== infoWindow)
                    previousInfoWindow.close();
                infoWindow.open(googleMap, marker);
                previousInfoWindow = infoWindow;
            }

            function closePreviousInfoWindow() {
                if (previousInfoWindow)
                    previousInfoWindow.close();
            }

            return {
                // show / hide
                showMarker: showMarker,
                hideMarker: hideMarker,
                showAllMarkers: showAllMarkers,
                hideAllMarkers: hideAllMarkers,

                // zoom / focus
                focusOnMarker: focusOnMarker,
                focusOnMarkers: focusOnMarkers,
                focusOnLatLng: focusOnLatLng,

                // info windows
                openInfoWindow: openInfoWindow,
                closePreviousInfoWindow: closePreviousInfoWindow
            };
        })();

        var geocoder = (function () {
            var ERROR_MESSAGE = 'An error occurred while Geo Coding an address. Please refresh the page and try again.';

            function geoCode(address, callbackFn) {
                googleGeoCoder.geocode(
                    { address: address },
                    function (results, status) {
                        if (status === 'OK')
                            callbackFn(results);
                        else
                            alert(ERROR_MESSAGE);
                    }
                );
            }

            return {
                geoCode: geoCode
            };
        })();

        var markers = (function () {
            var IMAGE_ICON_URLS = {
                PRIMARY: 'https://maps.google.com/mapfiles/ms/micons/red-pushpin.png',
                SECONDARY: 'https://macleanpower.wpengine.com/wp-content/uploads/red.png',
                SECONDARY_FOCUSED: 'https://macleanpower.wpengine.com/wp-content/uploads/white.png'
            };

            // CREATION ================================================================================================

            function create(title, lat, lng, iconUrl) {
                var params = {};

                if (title)
                    params.title = title;
                if (lat && lng)
                    params.position = new google.maps.LatLng(lat, lng);
                if (iconUrl)
                    params.icon = {
                        url: iconUrl
                    };

                return new google.maps.Marker(params);
            }

            function createPrimary(title, lat, lng) {
                return create(title, lat, lng, IMAGE_ICON_URLS.PRIMARY);
            }

            function createSecondary(title, lat, lng) {
                return create(title, lat, lng, IMAGE_ICON_URLS.SECONDARY);
            }

            // LISTENERS ===============================================================================================

            function addListener(marker, event, fn) {
                google.maps.event.addListener(marker, event, fn);
            }

            function clearListeners(marker) {
                google.maps.event.clearInstanceListeners(marker);
            }

            function onClick(marker, fn) {
                addListener(marker, 'click', fn);
            }

            function onMouseOver(marker, fn) {
                addListener(marker, 'mouseover', fn);
            }

            function onMouseOut(marker, fn) {
                addListener(marker, 'mouseout', fn);
            }

            return {
                IMAGE_ICON_URLS: IMAGE_ICON_URLS,

                // creation
                createPrimary: createPrimary,
                createSecondary: createSecondary,

                // listeners
                addListener: addListener,
                clearListeners: clearListeners,
                onClick: onClick,
                onMouseOver: onMouseOver,
                onMouseOut: onMouseOut
            };
        })();

        function init(selector) {
            googleMap = new google.maps.Map($(selector)[0], {
                zoom: INITIAL_ZOOM,
                center: new google.maps.LatLng(INITIAL_LAT, INITIAL_LNG)
            });
            googleGeoCoder = new google.maps.Geocoder();
        }

        return {
            init: init,
            calculations: calculations,
            controls: controls,
            geocoder: geocoder,
            markers: markers
        };
    })();

    ae.Location = Location;
    ae.Map = Map;
})(jQuery);