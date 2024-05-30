/**
 * TLCMap class handles map initialization, interaction, and data rendering.
 */
class TLCMap {
    constructor(addModalMapPicker) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $("#csrfToken").val(),
            },
        });

        this.map = null;
        this.view = null;
        this.featureLayer = null;
        this.graphicsLayer = null;

        this.ignoreExtentChange = true; // Stop refreshing pins when the map extent changes.
        this.isSearchOn = false; // True if the search is applied.
        this.dataitems = null; // The results data items.
        this.placeMarkers = []; // User placed marker for add place.

        this.addModalMapPicker = addModalMapPicker;

        this.clusterConfig = {
            type: "cluster",
            clusterRadius: "100px",
            popupTemplate: {
                title: "Cluster summary",
                content:
                    "{cluster_count} places in this cluster. Zoom in or click Browse Features.",
                fieldInfos: [
                    {
                        fieldName: "cluster_count",
                        format: {
                            places: 0,
                            digitSeparator: true,
                        },
                    },
                ],
            },
            clusterMinSize: "24px",
            clusterMaxSize: "60px",
            labelingInfo: [
                {
                    deconflictionStrategy: "none",
                    labelExpressionInfo: {
                        expression: "Text($feature.cluster_count, '#,###')",
                    },
                    symbol: {
                        type: "text",
                        color: "#004a5d",
                        font: {
                            weight: "bold",
                            family: "Noto Sans",
                            size: "12px",
                        },
                    },
                    labelPlacement: "center-center",
                },
            ],
        };
    }

    // Initialize the map.
    // Set listeners for the map drag/zoom events.
    // Set listener for drawing event
    async initializeMap() {
        return new Promise((resolve) => {
            require([
                "esri/Map",
                "esri/views/MapView",
                "esri/layers/FeatureLayer",
                "esri/widgets/Sketch",
                "esri/layers/GraphicsLayer",
                "esri/widgets/Expand",
                "esri/widgets/BasemapGallery",
                "esri/geometry/support/webMercatorUtils",
            ], (
                Map,
                MapView,
                FeatureLayer,
                Sketch,
                GraphicsLayer,
                Expand,
                BasemapGallery,
                webMercatorUtils
            ) => {
                this.featureLayer = new FeatureLayer({
                    objectIdField: "ObjectID",
                    geometryType: "point",
                    spatialReference: { wkid: 4326 },
                    source: [],
                    renderer: {
                        type: "simple",
                        symbol: {
                            type: "simple-marker",
                            color: "orange",
                            outline: { color: "white" },
                        },
                    },
                    popupTemplate: { title: "{Name}" },
                });

                this.graphicsLayer = new GraphicsLayer({
                    title: "graphicsLayer",
                });

                this.map = new Map({
                    basemap: "hybrid",
                    layers: [this.featureLayer, this.graphicsLayer],
                });

                this.view = new MapView({
                    container: "viewDiv",
                    center: [131.034742, -25.345113],
                    zoom: 3,
                    map: this.map,
                });

                const sketch = new Sketch({
                    layer: this.graphicsLayer,
                    view: this.view,
                    creationMode: "update",
                    snappingOptions: {
                        enabled: true,
                        featureSources: [
                            { layer: this.graphicsLayer, enabled: true },
                        ],
                    },
                    visibleElements: {
                        createTools: { polyline: false },
                        selectionTools: {
                            "lasso-selection": false,
                            "rectangle-selection": false,
                        },
                    },
                });

                this.view.when(() => {
                    resolve();
                });

                let debounceTimer;
                const debounceDelay = 250;

                this.view.watch("extent", () => {
                    if (this.ignoreExtentChange) {
                        return;
                    }

                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                    }

                    debounceTimer = setTimeout(() => {
                        this.refreshMapPins();
                    }, debounceDelay);
                });

                this.setupSketchHandlers(sketch, webMercatorUtils);
                this.setupViewUI(sketch, Expand, BasemapGallery);
            });
        });
    }

    // Refresh the current map withing the bounding box by new pins.
    refreshMapPins() {
        require([
            "esri/geometry/projection",
            "esri/geometry/SpatialReference",
        ], (projection, SpatialReference) => {
            projection.load().then(() => {
                if (this.view.extent) {
                    var sr4326 = new SpatialReference({
                        wkid: 4326,
                    });
                    var LatLongExtent = projection.project(
                        this.view.extent,
                        sr4326
                    );

                    var data = {};
                    data.places = getNumPlaces();

                    data.bbox = {
                        minLat: LatLongExtent.ymin,
                        minLng: LatLongExtent.xmin,
                        maxLat: LatLongExtent.ymax,
                        maxLng: LatLongExtent.xmax,
                    };

                    if (this.isSearchOn && this.dataitems != null) {
                        const filteredDateitems = this.sliceDataitemsByBbox(
                            data.bbox
                        );
                        const dataitemsInMap = selectRandomDataitems(
                            filteredDateitems,
                            getNumPlaces()
                        );

                        this.addPointsToMap(dataitemsInMap, data.bbox);
                        this.renderDataItems(dataitemsInMap);
                    } else {
                        this.updateMap(data);
                    }
                }
            });
        });
    }

    /**
     * Function to check if the drawn shape is a rectangle.
     *
     * @param {Array} ring - Array of coordinates.
     * @return {boolean} - True if the shape is a rectangle, false otherwise.
     */
    isRectangle(ring) {
        if (ring.length !== 5) return false;

        const angleBetween = (p1, p2, p3) => {
            const dx1 = p2[0] - p1[0];
            const dy1 = p2[1] - p1[1];
            const dx2 = p3[0] - p2[0];
            const dy2 = p3[1] - p2[1];

            const dotProduct = dx1 * dx2 + dy1 * dy2;
            const mag1 = Math.sqrt(dx1 * dx1 + dy1 * dy1);
            const mag2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
            const cosTheta = dotProduct / (mag1 * mag2);

            return Math.acos(cosTheta) * (180 / Math.PI);
        };

        for (let i = 0; i < 4; i++) {
            const angle = angleBetween(ring[i], ring[i + 1], ring[(i + 2) % 5]);
            if (Math.abs(angle - 90) > 10) {
                return false;
            }
        }
        return true;
    }

    /**
     * Function to log coordinates in latitude and longitude in the search form.
     *
     * @param {Object} graphic - The graphic object from the sketch.
     * @param {Object} webMercatorUtils - Utility for converting coordinates.
     */
    logCoordinates(graphic, webMercatorUtils) {
        const geometry = graphic.geometry;

        if (geometry.type === "polygon") {
            const ring = geometry.rings[0].map((coord) =>
                webMercatorUtils.xyToLngLat(coord[0], coord[1])
            );

            if (this.isRectangle(ring)) {
                const lats = ring.map((coord) => coord[1]);
                const lngs = ring.map((coord) => coord[0]);

                const minLat = Math.min(...lats).toFixed(6);
                const maxLat = Math.max(...lats).toFixed(6);
                const minLng = Math.min(...lngs).toFixed(6);
                const maxLng = Math.max(...lngs).toFixed(6);

                changeShapeType("bbox");
                $("#minlong").val(minLng);
                $("#minlat").val(minLat);
                $("#maxlong").val(maxLng);
                $("#maxlat").val(maxLat);
            } else {
                let latLongCoords = ring.map((coord) => [
                    parseFloat(coord[0].toFixed(6)),
                    parseFloat(coord[1].toFixed(6)),
                ]);

                changeShapeType("polygon");

                var out = "";
                for (var i = 0; i < latLongCoords.length; i++) {
                    out +=
                        latLongCoords[i][0] + " " + latLongCoords[i][1] + ", ";
                }

                $("#polygoninput").val(out.substring(0, out.length - 2));
            }
        } else if (geometry.type === "point") {
            const coords = webMercatorUtils.xyToLngLat(geometry.x, geometry.y);
            const lat = coords[1].toFixed(6);
            const lon = coords[0].toFixed(6);

            this.placeMarkers = [lat, lon];
        }
    }

    /**
     * Bind event handlers for the sketch drawing tool.
     *
     * @param {Object} sketch - The sketch tool instance.
     * @param {Object} webMercatorUtils - Utility for converting coordinates.
     */
    setupSketchHandlers(sketch, webMercatorUtils) {
        sketch.on("create", (event) => {
            if (event.state === "start") {
                this.graphicsLayer.removeAll();
                $("#minlong").val("");
                $("#minlat").val("");
                $("#maxlong").val("");
                $("#maxlat").val("");
                $("#polygoninput").val("");

                $("#addlatitude").val("");
                $("#addlongitude").val(""); //add place modal.
                this.placeMarkers = [];
            } else if (event.state === "complete") {
                this.logCoordinates(event.graphic, webMercatorUtils);
            }
        });

        sketch.on("update", (event) => {
            if (event.state === "complete") {
                event.graphics.forEach((graphic) =>
                    this.logCoordinates(graphic, webMercatorUtils)
                );
            }
        });

        sketch.on("delete", () => {
            $("#minlong").val("");
            $("#minlat").val("");
            $("#maxlong").val("");
            $("#maxlat").val("");
            $("#polygoninput").val("");
        });
    }

    /**
     * Function to get the coordinates of the sketch drawing (only 1 shape is possible).
     *
     * @return {Promise} - Promise that resolves to the coordinates of the drawn shape.
     */
    getSketchCoordinates() {
        if (this.graphicsLayer.graphics.length === 0) {
            return;
        }

        require(["esri/geometry/support/webMercatorUtils"], (
            webMercatorUtils
        ) => {
            const graphic = this.graphicsLayer.graphics.getItemAt(0);
            this.logCoordinates(graphic, webMercatorUtils);
        });
    }

    /**
     * Set up UI elements for basemap gallery, sketch tool, locate button, and place button.
     *
     * @param {Object} sketch - The sketch tool instance.
     * @param {Object} Expand - The expand widget for the basemap gallery.
     * @param {Object} BasemapGallery - The basemap gallery widget.
     */
    setupViewUI(sketch, Expand, BasemapGallery) {
        const basemapGallery = new BasemapGallery({
            view: this.view,
            container: document.createElement("div"),
        });

        const bgExpand = new Expand({
            view: this.view,
            content: basemapGallery.container,
            expandIconClass: "esri-icon-basemap",
        });

        this.view.ui.add(bgExpand, "top-right");
        this.view.ui.add(sketch, "top-right");

        var locate = document.createElement("div");
        locate.className =
            "esri-icon-locate-circled esri-widget--button esri-widget esri-interactive";
        locate.addEventListener("click", () => {
            this.gotoUserLocation();
        });

        var addPlace = document.createElement("div");
        addPlace.className =
            "esri-icon-plus esri-widget--button esri-widget esri-interactive";
        addPlace.addEventListener("click", () => {
            if (!isLoggedIn) {
                window.location.href = baseUrl + "login";
                return;
            }

            if (this.placeMarkers.length == 2) {
                $("#addlatitude").val(this.placeMarkers[0]);
                $("#addlongitude").val(this.placeMarkers[1]);
                this.addModalMapPicker.createMarkerAt([
                    this.placeMarkers[1],
                    this.placeMarkers[0],
                ]);
            } else {
                $("#addlatitude").val("");
                $("#addlongitude").val("");
                this.addModalMapPicker.clearMarkers();
            }

            $("#addModal").modal("show");
        });

        this.view.ui.add(addPlace, "bottom-right");
        this.view.ui.add(locate, "bottom-right");
    }

    /**
     * Render the list view with data items.
     *
     * @param {Array} dataItems - Array of data items to render.
     */
    renderDataItems(dataItems) {
        const listView = $(".place-list");
        listView.empty();

        dataItems.forEach((item) => {
            const html = `
                <div class="row">
                    <div class="col col-xl-3">
                        <div class="sresultmain">
                            <h4>
                                <button type="button" class="btn btn-primary btn-sm" onclick="copyLink('${
                                    item.uid
                                }', this, 'id')">C</button>
                                <a href="/places/${item.uid}">
                                    ${item.title || item.placename}
                                </a>
                            </h4>
                            <dl>
                                ${
                                    item.placename
                                        ? `<dt>Placename</dt><dd>${item.placename}</dd>`
                                        : ""
                                }
                                ${
                                    item.dataset
                                        ? `<dt>Layer</dt><dd><a href="{baseUrl}/layers/${item.dataset_id}">${item.dataset.name}</a></dd>`
                                        : item.datasource
                                        ? `<dt>Layer</dt><dd><a href="${item.datasource.link}">${item.datasource.description}</a></dd>`
                                        : ""
                                }
                                ${
                                    item.external_url
                                        ? `<dt>Link back to source:</dt><dd><a target="_blank" href="${item.external_url}">${item.external_url}</a></dd>`
                                        : ""
                                }
                                ${
                                    item.recordtype_id
                                        ? `<dt>Type</dt><dd>${
                                              recordTypeMap[item.recordtype_id]
                                          }</dd>`
                                        : ""
                                }
                            </dl>
                        </div>
                    </div>
                    <div class="col col-xl-2">
                        <div>
                            <h4>Details</h4>
                            <dl>
                                ${
                                    item.latitude
                                        ? `<dt>Latitude</dt><dd>${item.latitude}</dd>`
                                        : ""
                                }
                                ${
                                    item.longitude
                                        ? `<dt>Longitude</dt><dd>${item.longitude}</dd>`
                                        : ""
                                }
                                ${
                                    item.datestart
                                        ? `<dt>Start Date</dt><dd>${item.datestart}</dd>`
                                        : ""
                                }
                                ${
                                    item.dateend
                                        ? `<dt>End Date</dt><dd>${item.dateend}</dd>`
                                        : ""
                                }
                                ${
                                    item.state
                                        ? `<dt>State</dt><dd>${item.state}</dd>`
                                        : ""
                                }
                                ${
                                    item.lga
                                        ? `<dt>LGA</dt><dd>${item.lga}</dd>`
                                        : ""
                                }
                                ${
                                    item.parish
                                        ? `<dt>Parish</dt><dd>${item.parish}</dd>`
                                        : ""
                                }
                                ${
                                    item.feature_term
                                        ? `<dt>Feature Term</dt><dd>${item.feature_term}</dd>`
                                        : ""
                                }
                            </dl>
                        </div>
                    </div>
                    <div class="col col-xl-3">
                        <h4>Description</h4>
                        <div>
                            <dl>
                                ${
                                    item.dataset && item.dataset.warning
                                        ? `<dt style="background-color: #ffcc00;">Layer Warning:</dt><dd style="background-color: #ffcc00;">${item.dataset.warning}</dd>`
                                        : ""
                                }
                                ${
                                    item.description
                                        ? `<dd>${item.description}</dd>`
                                        : ""
                                }
                            </dl>
                        </div>
                    </div>
                    <div class="col col-xl-2">
                        <div>
                            <h4>Sources</h4>
                            <dl>
                                ${
                                    item.uid
                                        ? `<dt>ID</dt><dd>${item.uid}</dd>`
                                        : ""
                                }
                                ${
                                    item.source
                                        ? `<dt>Source</dt><dd>${item.source}</dd>`
                                        : ""
                                }
                                ${
                                    item.dataset && item.dataset.flag
                                        ? `<dt>ANPS to TLCMap Import Note</dt><dd>${item.dataset.flag}</dd>`
                                        : ""
                                }
                            </dl>
                        </div>
                    </div>
                    <div class="col col-xl-2">
                        ${
                            item.extended_data
                                ? `<h4>Extended Data</h4>${item.extended_data}`
                                : ""
                        }
                    </div>
                </div>
            `;
            listView.append(html);
        });
    }

    /**
     * Get the current user location.
     *
     * @return {Promise} - Promise that resolves to the user's location coordinates.
     */

    getUserLocation() {
        return new Promise((resolve, reject) => {
            const defaultLocation = [151.2093, -33.8688]; // Sydney

            function handleSuccess(position) {
                resolve([position.coords.longitude, position.coords.latitude]);
            }

            function handleError() {
                resolve(defaultLocation);
            }

            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    handleSuccess,
                    handleError
                );
            } else {
                resolve(defaultLocation);
            }
        });
    }

    /**
     * Zoom to the specified coordinates.
     *
     * @param {number} lng - Longitude.
     * @param {number} lat - Latitude.
     * @param {number} [level=11] - Zoom level.
     */
    zoomTo(lng, lat, level = 11) {
        this.view.goTo({
            center: [lng, lat],
            zoom: level,
        });
    }

    /**
     * Zoom to the current user location.
     */
    async gotoUserLocation() {
        var zoomLocation = await this.getUserLocation();
        this.zoomTo(zoomLocation[0], zoomLocation[1]);
    }

    /**
     * Add points to the map based on data items and optionally a bounding box.
     *
     * @param {Array} dataitems - Array of data items to add.
     * @param {Object} [bbox=null] - Optional bounding box to zoom to.
     */
    addPointsToMap(dataitems, bbox = null) {
        require([
            "esri/Graphic",
            "esri/geometry/Extent",
            "esri/layers/FeatureLayer",
        ], (Graphic, Extent, FeatureLayer) => {
            //Remove point layer
            this.view.map.layers.forEach((layer) => {
                if (layer instanceof FeatureLayer) {
                    this.view.map.layers.remove(layer);
                }
            });

            // Clear existing graphics
            this.featureLayer = new FeatureLayer({
                objectIdField: "ObjectID",
                geometryType: "point",
                spatialReference: { wkid: 4326 },
                source: [],
                renderer: {
                    type: "simple",
                    symbol: {
                        type: "simple-marker",
                        color: "orange",
                        outline: {
                            color: "white",
                        },
                    },
                },
                popupTemplate: {
                    title: "{Name}",
                },
                featureReduction: this.clusterConfig,
            });

            let coordinates = [];

            // Add new points
            dataitems.forEach((dataitem) => {
                var point = {
                    type: "point",
                    latitude: dataitem.latitude,
                    longitude: dataitem.longitude,
                };

                var markerSymbol = {
                    type: "simple-marker",
                    color: "orange",
                    outline: {
                        color: "white",
                        width: 1,
                    },
                };

                var pointGraphic = new Graphic({
                    geometry: point,
                    symbol: markerSymbol,
                });

                coordinates.push([dataitem.longitude, dataitem.latitude]);

                this.featureLayer.applyEdits({
                    addFeatures: [pointGraphic],
                });
            });

            this.view.map.layers.add(this.featureLayer);

            if (bbox != null) {
                var extent = new Extent({
                    xmin: bbox["minLng"],
                    ymin: bbox["minLat"],
                    xmax: bbox["maxLng"],
                    ymax: bbox["maxLat"],
                    spatialReference: { wkid: 4326 }, // WGS84 spatial reference
                });

                this.ignoreExtentChange = true;
                this.view.goTo(extent).then(() => {
                    this.ignoreExtentChange = false;
                });
            } else {
                // Calculate the extent from the coordinates array
                let xCoords = coordinates.map((coord) => coord[0]);
                let yCoords = coordinates.map((coord) => coord[1]);
                let xmin = Math.min(...xCoords);
                let xmax = Math.max(...xCoords);
                let ymin = Math.min(...yCoords);
                let ymax = Math.max(...yCoords);

                var calculatedExtent = new Extent({
                    xmin: xmin,
                    ymin: ymin,
                    xmax: xmax,
                    ymax: ymax,
                    spatialReference: { wkid: 4326 },
                });

                this.ignoreExtentChange = true;
                this.view.goTo(calculatedExtent).then(() => {
                    this.ignoreExtentChange = false;
                });
            }
        });
    }

    /**
     * Slice data items by the bounding box.
     *
     * @param {Object} bbox - Bounding box with minLat, minLng, maxLat, maxLng.
     * @return {Array} - Array of data items within the bounding box.
     */

    sliceDataitemsByBbox(bbox) {
        if (!Array.isArray(this.dataitems) || bbox == null) {
            return [];
        }

        var minLat = bbox.minLat;
        var minLng = bbox.minLng;
        var maxLat = bbox.maxLat;
        var maxLng = bbox.maxLng;

        let items = this.dataitems.slice();

        let res = [];
        items.forEach((item) => {
            if (
                item.latitude >= minLat &&
                item.latitude <= maxLat &&
                item.longitude >= minLng &&
                item.longitude <= maxLng
            ) {
                res.push(item);
            }
        });

        return res;
    }

    /**
     * Update the map with new data items based on the current bounding box.
     *
     * @param {Object} data - Data to send in the AJAX request.
     */
    updateMap(data) {
        $.ajax({
            type: "POST",
            url: bboxscan,
            data: data,
            success: (response) => {
                this.addPointsToMap(response.dataitems, data["bbox"]);
                this.renderDataItems(response.dataitems);
            },
            error: (xhr) => {
                console.log(xhr.responseText);
            },
        });
    }
}
