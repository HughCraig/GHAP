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

        var specialatts = ["OBJECTID", "__OBJECTID", "id", "title", "name", "description", "udatestart", "udateend", "layer", "TLCMapLinkBack", "TLCMapDataset"]; // for ignoring in loop that displays all the data incl. extended data
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

