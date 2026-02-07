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
        this.popupContent = null;
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

            // Ensure popup is docked and always expanded on small screens
            const pop = editor.view.popup;
            pop.dockEnabled = true; // let ArcGIS dock on small screens
            pop.dockOptions = { position: "bottom-center" }; 
            pop.collapseEnabled = false; // <-- key line: disables the collapse toggle

            // If a popup is already opening, force it expanded
            pop.watch("visible", (v) => {
            if (v && typeof pop.collapsed !== "undefined") pop.collapsed = false;
            });

            // Also guard against any future auto-collapse (e.g., orientation change)
            pop.watch("collapsed", (isCollapsed) => {
            if (isCollapsed && pop.collapseEnabled === false) pop.collapsed = false;
            });

            /* tweaks for responsive design, small screens */

            /* full screen button */
            const btn = editor.container.find('.mp-toggle-fullscreen');
            if (btn.length) {
            btn.on('click', () => {
                editor.container.toggleClass('is-fullscreen');
                setTimeout(() => editor.view && editor.view.resize(), 0);
            });
            }

            /* close full screen */
            const closeBtn = editor.container.find('.mp-fs-close');
                if (closeBtn.length) {
                closeBtn.on('click', () => {
                    editor.container.removeClass('is-fullscreen');
                    setTimeout(() => editor.view && editor.view.resize(), 0);
                });
                }

            /* default 70% size on small screen */
            const resize = (() => {
            let t;
            return () => {
                clearTimeout(t);
                t = setTimeout(() => {
                if (editor.view) editor.view.resize();
                }, 150);
            };
            })();

            window.addEventListener('resize', resize);

            // In case the map is initially rendered inside a collapsed/tabbed area,
            // nudge a resize once it’s on screen.
            setTimeout(() => editor.view && editor.view.resize(), 0);

            /* end responsive design tweak */



            // Set the marker.
            if (coordinates) {
                editor.addMarker(coordinates);
            }

            editor.view.popup.autoOpenEnabled = false;

            // Refresh the map when the popup is closed.
            editor.view.watch('popup.visible', function (newValue) {
                if (!newValue) {
                    editor.refresh();
                }
            });

            // Handle click event in the map view.
            editor.view.on('click', function (event) {
                editor.createMarkerAt([event.mapPoint.longitude, event.mapPoint.latitude], true, false);

                // Open Popup.
                editor.popupContent.find('.mp-popup-lat').text(event.mapPoint.latitude);
                editor.popupContent.find('.mp-popup-lng').text(event.mapPoint.longitude);
                editor.view.popup.open({
                    title: `Coordinates`,
                    content: editor.popupContent[0],
                    location: event.mapPoint
                });
                
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

        // Create popup content node.
        let popupHtml = `<div class="mp-popup-content">`;
        popupHtml += `<p class="d-none d-sm-block">Latitude: <span class="mp-popup-lat"></span></p>`;
        popupHtml += `<p class="d-none d-sm-block">Longitude: <span class="mp-popup-lng"></span></p>`;
        popupHtml += `<p><button type="button" class="btn btn-secondary btn-sm mp-popup-btn-set">Use these coordinates</button></p>`;
        popupHtml += `</div>`;
        this.popupContent = $(popupHtml);
        this.popupContent.find('.mp-popup-btn-set').on('click', function () {
            const lat = editor.popupContent.find('.mp-popup-lat').text();
            const lng = editor.popupContent.find('.mp-popup-lng').text();
            if (lat && lng) {
                editor.container.find('.mp-input-lat').val(lat);
                editor.container.find('.mp-input-lng').val(lng);
            }

            // if we're in fullscreen, exit it
            if (editor.container.hasClass('is-fullscreen')) {
                editor.container.removeClass('is-fullscreen');
                // if you ever locked scroll when entering fullscreen, unlock it:
                document.body.style.overflow = '';
                setTimeout(() => editor.view && editor.view.resize(), 0);
            }

            editor.view.popup.close();
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
