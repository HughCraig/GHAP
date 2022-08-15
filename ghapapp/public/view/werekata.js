var flyfeatures; // once the view is loaded the array of coords is stored here, so flyto can go through it.
var leg = 0; // just to keep track fo where we are in the journey
var flyview;


var urlParams = new URLSearchParams(window.location.search);
var urltoload = urlParams.get("load");
var baselayer = "hybrid";
if (urlParams.has("base")) {
    baselayer = urlParams.get("base");
}

var sortoption = urlParams.get('sort');
if (sortoption === "end") {
    urltoload = urltoload + "?sort=end";
} else if (sortoption === "start") {
    urltoload = urltoload + "?sort=start";
}

console.log(urltoload);

require([
    "esri/Map",
    "esri/layers/GeoJSONLayer",
    "esri/views/SceneView",
    "esri/geometry/Extent",
    "esri/widgets/Expand",
    "esri/widgets/BasemapGallery"
], function (Map, GeoJSONLayer, MapView, Extent, Expand, BasemapGallery) {

    var url = urltoload;
    //urltoload;

    // Paste the url into a browser's address bar to download and view the attributes
    // in the GeoJSON file. These attributes include:
    // * mag - magnitude
    // * type - earthquake or other event such as nuclear test
    // * place - location of the event
    // * time - the time of the event
    // Use the Arcade Date() function to format time field into a human-readable format

// construct html table from properties and extended data


//        var template = {
    //         title: "TLCMap Data Viewer",
    //        content: "<table id='tlcm_properties' class='tlcm'><tr><td>Title</td><td>{name}</td></tr><tr><td>State:</td><td>{state}</td></tr></table>"
    //     };

    var template = {
        title: "{name}",
        content: getInfo,
        outFields: ["*"]
    };

    var renderer = {
        type: "simple",
        field: "mag",
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
        zoom: 4,
        map: map
    });


    geojsonLayer.queryExtent().then(function (results) {
        // go to the extent of the results, id the midpoint of the whole map to start.
        // and then go to the first one.

        // ultimately the effect we have is:
        // we load the map, and go quickly to a broad view of the whole dataset.
        // then we zoom slowly to the start point and go through the journey.
        // if it fails we laoded the map centred on Uluru, so at least we see Aus.

        view.goTo(results.extent).then(function (results) {


            let query = geojsonLayer.createQuery();

            query.outFields = ["name", "id", "latitude", "longitude"];
            geojsonLayer.queryFeatures(query)
                .then(function (results) {
                    flyview = view;
                    flyfeatures = results.features;
                    const fragment = document.createDocumentFragment();
                    fly();
                });

        });

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


    // basemap
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


function fly() {
    // don't keep flying if you're at the end... or maybe make it loop.. or go back
    if (leg > flyfeatures.length) {
        return;
    }
    var lat = parseFloat(flyfeatures[leg].attributes.latitude);
    var lng = parseFloat(flyfeatures[leg].attributes.longitude);


    flyview.goTo(
        {
            center: [lng, lat],//[graphics[0].attributes.latitude, graphics[0].attributes.longitude],
            zoom: 13,
            tilt: 75
        },
        {
            speedFactor: 0.1,
            easing: "ease-in-out"
        }
    ).then((resolvedVal) => { //only once we get there should we recursively call to go to the next one
        leg = leg + 1;
        fly();

    }).catch((error) => {
        console.error(error);
    })
        .catch(function (error) {
            if (error.name != "AbortError") {
                console.error(error);
            }
        });
}


function testthis() {

    fly();
}