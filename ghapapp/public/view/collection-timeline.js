(function () {

    // Import ArcGIS JS modules.
    require([
        "esri/Map",
        "esri/layers/GeoJSONLayer",
        "esri/views/MapView",
        "esri/widgets/TimeSlider",
        "esri/geometry/Extent",
        "esri/widgets/Expand",
        "esri/widgets/BasemapGallery",
        "esri/core/promiseUtils",
        "esri/widgets/LayerList",
    ], function (Map, GeoJSONLayer, MapView, TimeSlider, Extent, Expand, BasemapGallery, promiseUtils, LayerList) {

        // Get the base map from the query string.
        const urlParams = new URLSearchParams(window.location.search);
        const urltoload = urlParams.get("load");
        let baselayer = "hybrid";
        if (urlParams.has("base")) {
            baselayer = urlParams.get("base");
        }

        // Initiate the color generator.
        const colorGen = new LegendColorGenerator();

        // Initiate collection legend.
        const legend = new CollectionLegend();

        // Map of layer ID to layer data.
        const layerDataMap = {};

        $.get(urltoload).done(function (data) {

            // Set the popup template
            const template = {
                title: "{name}",
                content: getInfo,
                outFields: ["*"]
            };

            // Create array of layer instances.
            const layers = [];
            if (typeof data.datasets !== undefined && Array.isArray(data.datasets)) {
                const datasets = data.datasets;
                for (let i = 0; i < datasets.length; i++) {
                    const color = colorGen.generate();
                    legend.addItem(datasets[i].name, color);
                    const layer = new GeoJSONLayer({
                        id: datasets[i].id,
                        url: datasets[i].jsonURL + '?sort=start',
                        title: datasets[i].name,
                        copyright: "Check copyright and permissions of this dataset at http://tlcmap.org/ghap.",
                        timeInfo: {
                            startField: "udatestart",
                            endField: "udateend",
                            interval: {
                                unit: "years",
                                value: 1
                            }
                        },
                        popupTemplate: template,
                        renderer: {
                            type: "simple",
                            field: "mag",
                            symbol: {
                                type: "simple-marker",
                                color: color,
                                outline: {
                                    color: "white"
                                }
                            }
                        }
                    });
                    layers.push(layer);
                    layerDataMap[datasets[i].id] = {
                        ...datasets[i],
                        color: color
                    };
                }
            }

            // Create the map instance.
            const map = new Map({
                basemap: baselayer,
                layers: layers
            });

            // Create the map view instance.
            const view = new MapView({
                container: "viewDiv",
                center: [131.034742, -25.345113],
                zoom: 4,
                map: map
            });

            // Merge all extents of layers and go to the merged extent.
            const layerQueryPromises = [];
            for (let i = 0; i < layers.length; i++) {
                layerQueryPromises.push(layers[i].queryExtent());
            }
            promiseUtils.eachAlways(layerQueryPromises).then(function (results) {
                let extent = null;
                for (let i = 0; i < results.length; i++) {
                    if (typeof results[i].value !== 'undefined') {
                        if (extent === null) {
                            extent = results[i].value.extent;
                        } else {
                            extent.union(results[i].value.extent);
                        }
                    }
                }
                view.goTo(extent);
            });

            // Create the layer list widget.
            let layerList = new LayerList({
                view: view,
                listItemCreatedFunction: function (event) {

                    // The event object contains properties of the
                    // layer in the LayerList widget.
                    const item = event.item;
                    const layerID = item.layer.id;
                    const layerData = layerDataMap[layerID];

                    // Create the information panel.
                    if (layerData) {
                        item.panel = {
                            className: 'esri-icon-notice-round',
                            title: 'View layer properties',
                            content: CollectionUtility.createLayerInfoPanelElement(layerData)
                        };
                    }

                    // Add actions.
                    item.actionsSections = [[{
                        title: "Go to full extent",
                        className: "esri-icon-zoom-out-fixed",
                        id: "full-extent"
                    }]];
                }
            });

            // Action handler of going to full extent.
            layerList.on("trigger-action", function (event) {
                if (event.action.id === 'full-extent') {
                    event.item.layer.queryExtent().then(function (result) {
                        view.goTo(result.extent);
                    });
                }
            });

            // Create the expand widget to contain the layer list widget.
            const layerListExpand = new Expand({
                collapsedIconClass: "esri-icon-collapse",
                expandIconClass: "esri-icon-expand",
                expandTooltip: "Show",
                view: view,
                content: layerList,
                expanded: true
            });
            view.ui.add(layerListExpand, {
                position: "top-left",
                index: 0
            });

            // time slider widget initialization
            const timeSlider = new TimeSlider({
                container: "timeSlider",
                view: view,
                timeVisible: true, // show the time stamps on the timeslider
                loop: true,
                labelFormatFunction: (value, type, element) => {
                    if (!timeSlider.fullTimeExtent) {
                        return;
                    }
                    const normal = new Intl.DateTimeFormat("en-gb");
                    switch (type) {
                        case "min":
                        case "max":
                            element.innerText = normal.format(value);
                            break;
                        case "extent":
                            const start = timeSlider.fullTimeExtent.start;
                            const end = timeSlider.fullTimeExtent.end;
                            element.innerText = `${normal.format(value[0].getTime())} ${normal.format(value[1].getTime())}`;
                            break;
                    }
                }
            });

            // Create the promises of layers view.
            const layerViewPromises = [];
            for (let i = 0; i < layers.length; i++) {
                layerViewPromises.push(view.whenLayerView(layers[i]));
            }
            // Calculate the time line properties once the views of layers are created.
            promiseUtils.eachAlways(layerViewPromises).then(function (results) {
                const timeRange = CollectionUtility.getLayersTimeExtent(layers);
                const start = timeRange[0];
                const end = timeRange[1];
                if (start && end) {
                    const tunit = CollectionUtility.getTimelineIntervalUnit(start, end);
                    for (let i = 0; i < layers.length; i++) {
                        layers[i].timeInfo.interval.unit = tunit;
                    }

                    timeSlider.fullTimeExtent = {
                        start: start,
                        end: end
                    };
                    timeSlider.values = [start, end];
                    timeSlider.stops = {
                        interval: {
                            value: 1,
                            unit: tunit
                        }
                    };
                }
            });

            // Create the expand widget to contain the metadata block.
            const infoDiv = document.getElementById("infoDiv");
            const infoDivExpand = new Expand({
                collapsedIconClass: "esri-icon-collapse",
                expandIconClass: "esri-icon-expand",
                expandTooltip: "Show",
                view: view,
                content: infoDiv,
                expanded: true
            });
            view.ui.add(infoDivExpand, "top-right");

            // Display collection metadata.
            CollectionUtility.createCollectionMetadataDisplay($(infoDiv), data);
            legend.render($(infoDiv).find('.legend-container'));

            // Create the basemap gallery widget with expand.
            const basemapGallery = new BasemapGallery({
                view: view,
                container: document.createElement("div")
            });
            const bgExpand = new Expand({
                view: view,
                content: basemapGallery.container,
                expandIconClass: "esri-icon-basemap"
            });
            // Add the expand instance to the ui
            view.ui.add(bgExpand, "top-right");

        }).fail(function () {
            console.log('Failed to load the collection data');
        });
    });
})();
