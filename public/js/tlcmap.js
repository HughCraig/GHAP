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

        this.bboxDataitems = null; // Data items from drag/zoom. not search results
        this.dataitems = null; // The results data items.

        this.ignoreExtentChange = true; // Stop refreshing pins when the map extent changes.
        this.isSearchOn = false; // True if the search is applied.
        this.placeMarkers = []; // User placed marker for add place.

        this.addModalMapPicker = addModalMapPicker;

        this.currentMapType = "3d";
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
                        color: "white",
                        font: {
                            weight: "bold",
                            family: "Noto Sans",
                            size: "12px",
                        },
                    },
                    labelPlacement: "center-center",
                },
            ],
            symbol: {
                type: "simple-marker",
                style: "circle",
                color: "#301934",
                outline: {
                    color: "white",
                },
            }
        };

        this.fields = [
            {
                name: "title",
                alias: "Title",
                type: "string",
            },
            {
                name: "placename",
                alias: "Placename",
                type: "string",
            },
            {
                name: "description",
                alias: "Description",
                type: "string",
            },
            {
                name: "uid",
                alias: "ID",
                type: "string",
            },
            {
                name: "state",
                alias: "State",
                type: "string",
            },
            {
                name: "source",
                alias: "Source",
                type: "string",
            },
            {
                name: "latitude",
                alias: "Latitude",
                type: "string",
            },
            {
                name: "longitude",
                alias: "Longitude",
                type: "string",
            },
            {
                name: "datestart",
                alias: "Start Date",
                type: "string",
            },
            {
                name: "dateend",
                alias: "End Date",
                type: "string",
            },
            {
                name: "external_url",
                alias: "External URL",
                type: "string",
            },
            {
                name: "lga",
                alias: "LGA",
                type: "string",
            },
            {
                name: "parish",
                alias: "Parish",
                type: "string",
            },
            {
                name: "created_at",
                alias: "Created At",
                type: "string",
            },
            {
                name: "updated_at",
                alias: "Updated At",
                type: "string",
            },
            {
                name: "image_path",
                alias: "Image",
                type: "string",
            },
            {
                name: "dataset_id",
                alias: "Layer ID",
                type: "string",
            },
            {
                name: "datasource_id",
                alias: "Datasource",
                type: "string",
            },
            {
                name: "datasource_description",
                alias: "Datasource Description",
                type: "string",
            },
            {
                name: "datasource_link",
                alias: "Datasource Link",
                type: "string",
            },
        ];
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
                "esri/PopupTemplate",
                "esri/Graphic",
            ], (
                Map,
                MapView,
                FeatureLayer,
                Sketch,
                GraphicsLayer,
                Expand,
                BasemapGallery,
                webMercatorUtils,
                PopupTemplate,
                Graphic
            ) => {
                const popupTemplate = this.getPopupTemplate(
                    PopupTemplate,
                    this.fields
                );

                this.featureLayer = new FeatureLayer({
                    objectIdField: "ObjectID",
                    geometryType: "point",
                    fields: this.fields,
                    spatialReference: { wkid: 4326 },
                    source: [],
                    renderer: {
                        type: "unique-value",
                        field: "datasource_id",
                        defaultSymbol: { type: "simple-marker" },
                        uniqueValueInfos: [
                            {
                                value: "GHAP",
                                symbol: {
                                    type: "simple-marker",
                                    color: "#FFD580",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                            {
                                value: "ANPS",
                                symbol: {
                                    type: "simple-marker",
                                    color: "orange",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                            {
                                value: "NCG",
                                symbol: {
                                    type: "simple-marker",
                                    color: "#FE6A1B",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                            {
                                value: "Unknown",
                                symbol: {
                                    type: "simple-marker",
                                    color: "purple",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                        ],
                    },
                    popupTemplate: popupTemplate,
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
                        createTools: { polyline: false, point: false },
                        selectionTools: {
                            "lasso-selection": false,
                            "rectangle-selection": false,
                        },
                        settingsMenu: false,
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

                //Right click on map to add place point
                this.view.on("click", (event) => {
                    if (event.button === 2) {
                        event.stopPropagation(); // Prevent the default right-click context menu

                        // Clear any existing graphics
                        this.graphicsLayer.removeAll();
                        $("#minlong").val("");
                        $("#minlat").val("");
                        $("#maxlong").val("");
                        $("#maxlat").val("");
                        $("#polygoninput").val("");

                        $("#addlatitude").val("");
                        $("#addlongitude").val("");
                        this.placeMarkers = [];

                        const point = {
                            type: "point",
                            longitude: event.mapPoint.longitude,
                            latitude: event.mapPoint.latitude,
                        };

                        this.placeMarkers = [
                            event.mapPoint.latitude.toFixed(6),
                            event.mapPoint.longitude.toFixed(6),
                        ];

                        const pointGraphic = new Graphic({
                            geometry: point,
                            symbol: {
                                type: "simple-marker",
                                color: "white",
                                outline: { color: "blue", width: 1 },
                            },
                        });

                        // Add the point graphic to the graphics layer
                        this.graphicsLayer.add(pointGraphic);
                    }
                });
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

    getPopupTemplate(PopupTemplate, fields) {
        const popupTemplate = new PopupTemplate({
            title: "{title}",
            content: function (feature) {
                const attributes = feature.graphic.attributes;
                let content = "<table class='esri-widget__table'>";
                fields.forEach((field) => {
                    const key = field.name;
                    const alias = field.alias;
                    const value = attributes[key];
                    if (
                        value != null &&
                        value != "" &&
                        key != "title" &&
                        key != "image_path" &&
                        key != "dataset_id" &&
                        key != "uid" &&
                        key != "datasource_description" &&
                        key != "datasource_link"
                    ) {
                        content += `<tr>
                            <th>${alias}</th>
                            <td>${value}</td>
                        </tr>`;
                    }
                });

                // Handle image field if present
                if (attributes.image_path) {
                    content += `<tr><th>Image</th><td><img src="${attributes.image_path}" alt="Place Image" style="max-width: 100%; height: auto;"></td></tr>`;
                }

                content += "</table>";

                content += `<div style="margin-top: 1rem"><a style="color: #0000EE" href="${baseUrl}?gotoid=${attributes.uid}&view=list" target="_blank">TLCMap Record: tce9ac</a> `;
                if (attributes.dataset_id) {
                    content += `| <a style="color: #0000EE" href="${baseUrl}layers/${attributes.dataset_id}" target="_blank">TLCMap Layer</a></div>`;
                } else {
                    content += `| <a style="color: #0000EE" href="${attributes.datasource_link}" target="_blank">${attributes.datasource_description}</a></div>`;
                }

                return content;
            },
            outFields: ["*"],
        });

        return popupTemplate;
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
            $("#addlatitude").val("");
            $("#addlongitude").val("");
            this.placeMarkers = [];
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
            "esri-icon-locate esri-widget--button esri-widget esri-interactive";
        locate.addEventListener("click", () => {
            this.gotoUserLocation();
        });

        var addPlace = document.createElement("div");
        addPlace.style.backgroundColor = "orange";
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
                this.addModalMapPicker.createMarkerAt(
                    [
                        parseFloat(this.placeMarkers[1]),
                        parseFloat(this.placeMarkers[0]),
                    ],
                    true,
                    true
                );
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
            var html = `<div class="row">`;

            //Main info
            html += `
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
                                        ? `<dt>Layer</dt><dd><a href="/layers/${item.dataset_id}">${item.dataset.name}</a></dd>`
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
                    </div>`;

            //Details
            html += `<div class="col col-xl-2">
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
                    `;

            //Description
            html += `<div class="col col-xl-3">
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
                    `;

            //Sources
            html += `<div class="col col-xl-2">
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
                    </div>`;

            //Extended Data
            if (item.extended_data) {
                html += `<div class="col col-xl-2"> ${
                    item.extended_data
                        ? `<h4>Extended Data</h4>${item.extended_data}`
                        : ""
                }</div> `;
            }

            //Image
            if (item.image_path) {
                html += `<div class="col col-xl-2">
                            <img src="${item.image_path}" alt="Place Image" style="max-width: 100%; height: auto;">
                        </div>`;
            }

            html += `</div>`;
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
            const defaultLocation = false; // Australia

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

        if (zoomLocation) {
            this.zoomTo(zoomLocation[0], zoomLocation[1]);
        } else {
            this.zoomTo(131.034742, -25.345113, 5); //Australia
        }
    }

    /**
     * Add points to the map based on data items and optionally a bounding box.
     *
     * @param {Array} dataitems - Array of data items to add.
     * @param {Object} [bbox=null] - Optional bounding box to zoom to.
     */
    addPointsToMap(dataitems, bbox = null) {
        require(["esri/Graphic", "esri/geometry/Extent"], (Graphic, Extent) => {
            //Remove all existing point
            this.featureLayer.queryFeatures().then((results) => {
                // edits object tells apply edits that you want to delete the features
                const deleteEdits = {
                    deleteFeatures: results.features,
                };
                // apply edits to the layer
                this.featureLayer.applyEdits(deleteEdits);
            });

            let coordinates = [];

            // Add new points
            dataitems.forEach((dataitem) => {
                var point = {
                    type: "point",
                    latitude: dataitem.latitude,
                    longitude: dataitem.longitude,
                };

                if (dataitem.datasource_id == "1" || dataitem.datasource_id == "GHAP") {
                    dataitem.datasource_id = "GHAP";
                } else if (dataitem.datasource_id == "2" || dataitem.datasource_id == "ANPS") {
                    dataitem.datasource_id = "ANPS";
                } else if (dataitem.datasource_id == "3" || dataitem.datasource_id == "NCG") {
                    dataitem.datasource_id = "NCG";
                } else {
                    dataitem.datasource_id = "Unknown";
                }

                dataitem["datasource_description"] =
                    dataitem.datasource.description;
                dataitem["datasource_link"] = dataitem.datasource.link;

                var pointGraphic = new Graphic({
                    geometry: point,
                    attributes: Object.assign({}, dataitem),
                });

                coordinates.push([dataitem.longitude, dataitem.latitude]);
                this.featureLayer.applyEdits({
                    addFeatures: [pointGraphic],
                });
            });

            //Result from search behavior
            if (bbox == null) {
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
     * Switch the map type between 3D and cluster.
     * @param {string} newMapType - The new map type.
     * @return {void}
     * */
    switchMapType(newMapType) {
        if (newMapType !== "3d" && newMapType !== "cluster") {
            return;
        }

        if (newMapType === this.currentMapType) {
            return;
        }

        this.currentMapType = newMapType;

        require(["esri/layers/FeatureLayer", "esri/PopupTemplate"], (
            FeatureLayer,
            PopupTemplate
        ) => {
            this.featureLayer.queryFeatures().then((results) => {
                this.view.map.layers.forEach((layer) => {
                    if (layer instanceof FeatureLayer) {
                        this.view.map.layers.remove(layer);
                    }
                });

                const popupTemplate = this.getPopupTemplate(
                    PopupTemplate,
                    this.fields
                );

                // Create new feature layer with or without clustering
                this.featureLayer = new FeatureLayer({
                    objectIdField: "ObjectID",
                    geometryType: "point",
                    fields: this.fields,
                    spatialReference: { wkid: 4326 },
                    source: [],
                    renderer: {
                        type: "unique-value",
                        field: "datasource_id",
                        defaultSymbol: { type: "simple-marker" },
                        uniqueValueInfos: [
                            {
                                value: "GHAP",
                                symbol: {
                                    type: "simple-marker",
                                    color: "#FFD580",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                            {
                                value: "ANPS",
                                symbol: {
                                    type: "simple-marker",
                                    color: "orange",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                            {
                                value: "NCG",
                                symbol: {
                                    type: "simple-marker",
                                    color: "#FE6A1B",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                            {
                                value: "Unknown",
                                symbol: {
                                    type: "simple-marker",
                                    color: "purple",
                                    outline: { color: "white", width: 1 },
                                },
                            },
                        ],
                    },
                    popupTemplate: popupTemplate,
                    featureReduction:
                        newMapType === "cluster" ? this.clusterConfig : null,
                });

                const addEdits = {
                    addFeatures: results.features,
                };

                this.featureLayer.applyEdits(addEdits);
                this.view.map.layers.add(this.featureLayer);
            });
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
     * Dataitems results are NOT from search results
     *
     * @param {Object} data - Data to send in the AJAX request.
     */
    updateMap(data) {
        $.ajax({
            type: "POST",
            url: bboxscan,
            data: data,
            success: (response) => {
                this.bboxDataitems = response.dataitems;
                this.addPointsToMap(response.dataitems, data["bbox"]);
                this.renderDataItems(response.dataitems);
            },
            error: (xhr) => {
                console.log(xhr.responseText);
            },
        });
    }
}
