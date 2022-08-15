var urlParams = new URLSearchParams(window.location.search);
var urltoload = urlParams.get("load");
console.log(urltoload);
var baselayer = "hybrid";
if (urlParams.has("base")) {
    baselayer = urlParams.get("base");
}

require([
    "esri/Map",
    "esri/layers/FeatureLayer",
    "esri/layers/GeoJSONLayer",
    "esri/views/MapView",
    "esri/geometry/Extent",
    "esri/widgets/Legend",
    "esri/widgets/Expand",
    "esri/widgets/Home",
    "esri/widgets/BasemapGallery"
], function (Map, FeatureLayer, GeoJSONLayer, MapView, Extent, Legend, Expand, Home, BasemapGallery) {

    var url = urltoload;
    //urltoload;


    const clusterConfig = {
        type: "cluster",
        clusterRadius: "100px",
        // {cluster_count} is an aggregate field containing
        // the number of features comprised by the cluster
        popupTemplate: {
            title: "Cluster summary",
            content: "{cluster_count} places in this cluster. Zoom in or click Browse Features.",
            fieldInfos: [
                {
                    fieldName: "cluster_count",
                    format: {
                        places: 0,
                        digitSeparator: true
                    }
                }
            ]
        },
        clusterMinSize: "24px",
        clusterMaxSize: "60px",
        labelingInfo: [
            {
                deconflictionStrategy: "none",
                labelExpressionInfo: {
                    expression: "Text($feature.cluster_count, '#,###')"
                },
                symbol: {
                    type: "text",
                    color: "#004a5d",
                    font: {
                        weight: "bold",
                        family: "Noto Sans",
                        size: "12px"
                    }
                },
                labelPlacement: "center-center"
            }
        ]
    };


    // Paste the url into a browser's address bar to download and view the attributes
    // in the GeoJSON file. These attributes include:
    // * mag - magnitude
    // * type - earthquake or other event such as nuclear test
    // * place - location of the event
    // * time - the time of the event
    // Use the Arcade Date() function to format time field into a human-readable format

    // construct html table from properties and extended data

    var template = {
        title: "{name}",
        content: [{
            // It is also possible to set the fieldInfos outside of the content
            // directly in the popupTemplate. If no fieldInfos is specifically set
            // in the content, it defaults to whatever may be set within the popupTemplate.
            type: "fields",
            fieldInfos: [{
                fieldName: "name",
                label: "Title"
            }, {
                fieldName: "placename",
                label: "Place Name"
            }, {
                fieldName: "latitude",
                label: "Latitude",
                format: {
                    digitSeparator: true,
                    places: 6
                }
            }, {
                fieldName: "longitude",
                label: "Longitude",
                format: {
                    digitSeparator: true,
                    places: 6
                }
            }, {
                fieldName: "datestart",
                label: "Date Start",
                format: {
                    dateFormat: "short-date"
                }
            }, {
                fieldName: "dateend",
                label: "Date End",
                format: {
                    dateFormat: "short-date"
                }
            }, {
                fieldName: "type",
                label: "Type"
            }, {
                fieldName: "source",
                label: "Source"
            }, {
                fieldName: "TLCMapLinkBack",
                label: "TLCMap LinkBack"
            }, {
                fieldName: "TLCMapDataset",
                label: "TLCMap Layer"
            }]
        }]
    };

    var renderer = {
        type: "simple",
        symbol: {
            type: "simple-marker",
            color: "orange",
            outline: {
                color: "white"
            }
        }
    };

    var geojsonLayer = new GeoJSONLayer({
        url: url,
        copyright: "Check copyright and permissions of this dataset at http://tlcmap.org/ghap.",
        featureReduction: clusterConfig,
        popupTemplate: template,
        renderer: renderer //optional
    });

    var map = new Map({
        basemap: baselayer,
        ground: "world-elevation",
        layers: [geojsonLayer]
    });

    var view = new MapView({
        container: "viewDiv",
        center: [131.034742, -25.345113],
        zoom: 3,
        map: map
    });


    geojsonLayer.queryExtent().then(function (results) {
        // go to the extent of the results satisfying the query
        view.goTo(results.extent);
    });


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


    var basemapGallery = new BasemapGallery({
        view: view,
        container: document.createElement("div")
    });

    var bgExpand = new Expand({
        view: view,
        content: basemapGallery.container,
        expandIconClass: "esri-icon-basemap"
    });

    // Add the expand instance to the ui

    view.ui.add(bgExpand, "top-right");

});
