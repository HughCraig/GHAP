var urlParams = new URLSearchParams(window.location.search);
var urltoload = urlParams.get("load");
var baselayer = "hybrid";
if (urlParams.has("base")) {
    baselayer = urlParams.get("base"); //maybe sanitise this?? Or allow only a reliable subset.
    //I think there are too many options in the ESRI base layers and some 3rd party ones don't work.
    // eg: just hybrid (default), satellite, street, terrain, dark.
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

    function addatt() {

    }

    // The function used for the PopupTemplate
    function getInfo(feature) {
        try {
            var graphic, attributes, content;
            graphic = feature.graphic;
            attributes = graphic.attributes;


            title = attributes["name"];//(attributes["name"])? attributes["name"] : "TLCMap Place";
            content = "<table id='tlcmproperties'>";


            if (attributes["description"]) {
                content = content + "<tr><td>Description</td><td>" + attributes["description"] + "</td></tr>";
            }

            var specialatts = ["OBJECTID", "id", "title", "name", "description", "udatestart", "udateend", "layer", "TLCMapLinkBack", "TLCMapDataset"]; // for ignoring in loop that displays all the data incl. extended data
            var specialdisplay = {
                "placename": "Place Name",
                "StartDate": "Date Start",
                "EndDate": "Date End",
                "datestart": "Date Start",
                "dateend": "Date End",
                "latitude": "Latitude",
                "longitude": "Longitude",
                "state": "State",
                "lga": "LGA",
                "feature_term": "Feature Term",
                "original_data_source": "Source",
                "linkback": "Link Back",
                "type": "Type"

            }; // match keys to more human friendly display labels


            //console.log(attributes);

            // Add all the cannonical attributes with nice labels, if they exist
            for (display in specialdisplay) {
                if (!attributes[display] && attributes[display] !== 0) {
                    continue;
                }
                disval = attributes[display];
                if (disval.startsWith("http")) {
                    disval = "<a href='" + disval + "'>" + disval + "</a>";
                }
                content = content + "<tr><td>" + specialdisplay[display] + "</td><td>" + disval + "</td></tr>";

            }


            for (const key in attributes) {

                // skip display of special core atts
                if (specialdisplay[key]) {
                    continue;
                }


                if (!attributes[key] && attributes[key] !== 0) {
                    continue;
                } // ignore null or empty, but allow value of 0.


                // skip things that are to be ignored
                if (specialatts.includes(key)) {
                    continue;
                } // don't display things to ignore or handled sepera

                var val = attributes[key].toString();

                if (val.startsWith("http")) {
                    val = "<a href='" + val + "'>" + val + "</a>";
                }


                content = content + "<tr><td>" + key + "</td><td>" + val + "</td></tr>";

            }


            content = content + "</table>";

            content = content + "<p><a href='" + attributes["TLCMapLinkBack"] + "'>TLCMap Record: " + attributes["id"] + "</a> | ";
            content = content + "<a href='" + attributes["TLCMapDataset"] + "'>TLCMap Layer</a></p>";


            return content;
        } catch (err) {
            return console.log("Error: " + key + " could getinfo " + err + " " + content);
        }
    }

    var geojsonLayer = new GeoJSONLayer({
        url: url,
        title: "{title}",
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
