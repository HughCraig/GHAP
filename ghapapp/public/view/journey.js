var urlParams = new URLSearchParams(window.location.search);
var urltoload = urlParams.get("load");

var baselayer = "hybrid";
if (urlParams.has("base")) {
    baselayer = urlParams.get("base");
}

var urlline = "";
var lineoption = urlParams.get('line');
if (lineoption === "route") {
    urlline = urltoload + "?line=route";
} else if (lineoption === "time") {
    urlline = urltoload + "?line=time";
}

console.log(urlline);

require([
    "esri/Map",
    "esri/layers/GeoJSONLayer",
    "esri/views/SceneView",
    "esri/geometry/Extent",
    "esri/widgets/Expand",
    "esri/widgets/BasemapGallery"
], function (Map, GeoJSONLayer, MapView, Extent, Expand, BasemapGallery) {

    var template = {
        title: "{name}",
        content: getInfo,
        outFields: ["*"]
    };

    var renderer = {
        type: "simple",
        field: "name",
        symbol: {
            type: "simple-marker",
            color: "orange",
            outline: {
                color: "white"
            }
        }
    };

    var renderer2 = {
        type: "simple",
        symbol: {
            type: "simple-line",
            color: "white",
            width: '2'
        }
    };

    const geojsonLayer = new GeoJSONLayer({
        title: "TLCMap Layer",
        url: urltoload,
        copyright: "Check copyright and permissions of this dataset at http://tlcmap.org/ghap.",
        popupTemplate: template,
        renderer: renderer //optional
    });

    var map = new Map({
        basemap: baselayer,
        ground: "world-elevation",
        layers: [geojsonLayer]
    });

    var geojsonLineLayer;
    if (lineoption) {
        geojsonLineLayer = new GeoJSONLayer({
            url: urlline,
            copyright: "Check copyright and permissions of this dataset at http://tlcmap.org/ghap.",
            popupTemplate: template,
            renderer: renderer2 //optional
        });
        map.layers.add(geojsonLineLayer);
    }

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
