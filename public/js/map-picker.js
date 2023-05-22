/**
 * The class of map picker widget.
 *
 * A widget allowing user to pick a point from the map and record its coordinates.
 *
 * Note: don't confused with mappicker.js which is used in the home page.
 */
class MapPicker {

    /**
     * Constructor of the map picker widget.
     *
     * @param {jQuery} container
     *   The jQuery element of the widget container.
     *   The container's HTML markups must include the following element:
     *   - `input` element with class `mp-input-lat`, which holds the latitude of the coordinates.
     *   - `input` element with class `mp-input-lng`, which holds the longitude of the coordinates.
     *   - `button` element with class `mp-btn-refresh`, which is used to refresh the location on the map.
     *   - `button` element with class `mp-btn-unset`, which is used to unset the coordinates.
     *   - `div` element with class `mp-map`, which is the container of the actual map.
     */
    constructor(container) {
        this.container = container;
        this.mapElement = this.container.find('.mp-map')[0];
        this.map = null;
        this.view = null;
        this.marker = null;
        this.markerGraphic = null;
    }

    /**
     * Initialise the widget.
     */
    init() {
        const editor = this;
        const coordinates = this.getCoordinates();

        require([
            "esri/Map",
            "esri/views/MapView"
        ], function(Map, MapView) {

            // Get center coordinates. Default to Australia.
            const center = coordinates ?? [134.934082, -26.490240];

            // Create the map.
            editor.map = new Map({
                basemap: 'hybrid'
            });

            // Create the view.
            editor.view = new MapView({
                map: editor.map,
                center: center,
                zoom: 3,
                container: editor.mapElement
            });

            // Set the marker.
            if (coordinates) {
                editor.addMarker(coordinates);
            }

            // Handle click event in the map view.
            editor.view.on('click', function (event) {
                editor.container.find('.mp-input-lat').val(event.mapPoint.latitude);
                editor.container.find('.mp-input-lng').val(event.mapPoint.longitude);
                editor.createMarkerAt([event.mapPoint.longitude, event.mapPoint.latitude], true, false);
            });
        });

        // Handle click event on refresh button.
        this.container.find('.mp-btn-refresh').on('click', function () {
            const coordinates = editor.getCoordinates();
            if (coordinates) {
                editor.createMarkerAt(coordinates);
            }
        });

        // Handle click event on unset button.
        this.container.find('.mp-btn-unset').on('click', function () {
            editor.container.find('.mp-input-lat').val('');
            editor.container.find('.mp-input-lng').val('');
            editor.clearMarkers();
        });
    }

    /**
     * Get the coordinates from the widget.
     *
     * @returns {number[]|null}
     *   The longitude, latitude in an array, or null if it's empty.
     */
    getCoordinates() {
        const lat = this.container.find('.mp-input-lat').val();
        const lng = this.container.find('.mp-input-lng').val();
        if (lat !== '' && lng !== '') {
            return [parseFloat(lng), parseFloat(lat)];
        }
        return null;
    }

    /**
     * Refresh the map.
     *
     * This will apply the according view based on the coordinates.
     */
    refresh() {
        const coordinates = this.getCoordinates();
        if (coordinates) {
            this.createMarkerAt(coordinates);
        } else {
            this.clearMarkers();
        }
    }

    /**
     * Create a marker at a given location.
     *
     * @param {Array} coordinates
     *   The [longitude, latitude] in an array.
     * @param {boolean} clear
     *   Whether to clear all existing markers before create the marker. Default is true.
     * @param {boolean} goto
     *   Whether to view to the marker after it's been created.
     */
    createMarkerAt(coordinates, clear = true, goto = true) {
        if (clear) {
            this.clearMarkers();
        }
        this.addMarker(coordinates);
        if (goto) {
            this.view.goTo(coordinates);
        }
    }

    /**
     * Clear all markers from the map view.
     */
    clearMarkers() {
        this.view.graphics.removeAll();
    }

    /**
     * Add a marker at the given location.
     *
     * @param {Array} coordinates
     *   The [longitude, latitude] in an array.
     */
    addMarker(coordinates) {
        const editor = this;
        require(["esri/Graphic"], function(Graphic) {
            // Create point geometry.
            const point = {
                type: "point",
                longitude: coordinates[0],
                latitude: coordinates[1]
            };

            // Create a symbol for drawing the point
            const markerSymbol = {
                type: "simple-marker",
                color: 'orange',
                outline: {
                    color: 'white',
                    width: 1
                }
            };

            // Create a graphic and add the geometry and symbol to it
            editor.markerGraphic = new Graphic({
                geometry: point,
                symbol: markerSymbol
            });

            // Add graphic to view.
            editor.view.graphics.add(editor.markerGraphic);
        });

    }
}
