var shapetype = "bbox";

function showLoadingWheel(loadText) {
    document.getElementsByClassName("loading-text")[0].innerText = loadText;
    document.getElementById("loadingWheel").style.display = "block";
}

function hideLoadingWheel() {
    document.getElementById("loadingWheel").style.display = "none";
}

function scrollToTopFunction() {
    window.scrollTo({ top: 0, behavior: "smooth" });
}

/**
 * Download dataitems as csv.
 * Used for bbox scan of non-search results.
 * Data comming from frontend
 *
 * @param {*} dataitems
 * @param {*} filename
 */
function downloadCsv(dataitems, filename = "places.csv") {
    if (!dataitems || dataitems.length === 0) {
        alert("No places available for download.");
        return;
    }

    let colheads = new Set();
    let excludeColumns = [
        "uid",
        "datasource_id",
        "geom",
        "geog",
        "image_path",
        "kml_style_url",
        "dataset_order",
        "geom_date",
        "dataset",
    ];

    dataitems.forEach((item) => {
        Object.keys(item).forEach((key) => {
            if (!excludeColumns.includes(key)) {
                colheads.add(key);
            }
        });
    });

    colheads = Array.from(colheads);

    let csvContent = colheads.join(",") + "\n";
    dataitems.forEach((item) => {
        let row = colheads
            .map((col) => {
                if (col === "recordtype_id") {
                    return recordTypeMap[item[col]] || item[col];
                } else if (item.hasOwnProperty(col)) {
                    return item[col];
                } else if (
                    item.extended_data &&
                    item.extended_data.hasOwnProperty(col)
                ) {
                    return item.extended_data[col];
                } else {
                    return "";
                }
            })
            .join(",");
        csvContent += row + "\n";
    });

    // Encode the CSV content
    const encodedUri =
        "data:text/csv;charset=utf-8," + encodeURIComponent(csvContent);

    // Create a link and trigger download
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function removeOptionByText(selectElement, text) {
    selectElement
        .find("option")
        .filter(function () {
            return $(this).text() === text;
        })
        .remove();
}

function getDatasources() {
    var datasources = [];
    if ($("#searchausgaz").is(":checked")) {
        datasources.push("2");
    }
    if ($("#searchncg").is(":checked")) {
        datasources.push("3");
    }
    if ($("#searchpublicdatasets").is(":checked")) {
        datasources.push("1"); 
        datasources.push("4");
    }
    return datasources;
}

/**
 * Download dataitems as kml.
 * Used for bbox scan of non-search results.
 * Data comming from frontend
 *
 * @param {*} dataitems
 * @param {*} filename
 * @returns
 */
function downloaKML(dataitems, filename = "places.kml") {
    if (!dataitems || dataitems.length === 0) {
        alert("No places available for download.");
        return;
    }

    let excludeColumns = [
        "uid",
        "datasource_id",
        "geom",
        "geog",
        "image_path",
        "kml_style_url",
        "dataset_order",
        "geom_date",
        "dataset",
    ];

    let kmlContent =
        '<?xml version="1.0" encoding="UTF-8"?>\n' +
        '<kml xmlns="http://www.opengis.net/kml/2.2">\n' +
        "  <Document>\n";

    dataitems.forEach((item, index) => {
        const color = generateKMLColorFromStr(index.toString());
        let description = ``;

        Object.entries(item).forEach(([key, value]) => {
            if (
                !excludeColumns.includes(key) &&
                key !== "latitude" &&
                key !== "longitude"
            ) {
                if (key === "recordtype_id") {
                    value = recordTypeMap[value] || value;
                    key = "recordtype";
                }
                description += `${
                    key.charAt(0).toUpperCase() + key.slice(1)
                }: ${value}<br>`;
            }
        });

        kmlContent +=
            `    <Style id="item${index}Style">\n` +
            `      <IconStyle>\n` +
            `        <color>${color}</color>\n` +
            `        <scale>1.1</scale>\n` +
            "      </IconStyle>\n" +
            "    </Style>\n";

        kmlContent +=
            "    <Placemark>\n" +
            `      <name><![CDATA[${
                item.title || "Unnamed Place"
            }]]></name>\n` +
            `      <description><![CDATA[${description}]]></description>\n` +
            "      <Point>\n" +
            `        <coordinates>${item.longitude},${item.latitude}</coordinates>\n` +
            "      </Point>\n" +
            "    </Placemark>\n";
    });

    kmlContent += "  </Document>\n</kml>";

    // Create a link and trigger download
    const encodedUri =
        "data:application/vnd.google-earth.kml+xml;charset=utf-8," +
        encodeURIComponent(kmlContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Changes the shape type for the map selection and updates the UI accordingly.
 *
 * @param string type The new shape type (polygon, bbox, or circle).
 */

function changeShapeType(type) {
    //string: polygon bbox or circle
    $("#" + shapetype + "div").addClass("hidden"); //hide the currently showing div
    $("#" + type + "div").removeClass("hidden"); //show the new div
    shapetype = type; //set the global var
    $("#mapselector").val(type + "option"); //change what is selected on the select box
}

/**
 * Retrieves the number of places to be displayed based on user selection.
 *
 * @return int The number of places to be displayed, or a large number if "All" is selected.
 */
function getNumPlaces() {
    var data = null;

    var selectElement = document.querySelector(".num-places");
    if (selectElement) {
        var selectedValue = selectElement.value;
        if (selectedValue === "ALL") {
            data = 9999999;
        } else {
            data = parseInt(selectedValue, 10);
        }
    }

    return data;
}

/**
 * Gathers search form data, validates it, and formats it for use in the search process.
 *
 * @param array names Optional array of names for bulk search.
 * @param object tlcMap The map instance used for search operations.
 * @return object An object containing the formatted search form data.
 */
function getSearchFormData(names, tlcMap, viewBbox) {
    //Checking that date inputs match the proper format
    var datefrom = document.getElementById("datefrom");
    var dateto = document.getElementById("dateto");
    datefrom.classList.remove("is-invalid");
    dateto.classList.remove("is-invalid");
    if (datefrom.value && !dateMatchesRegex(datefrom.value)) {
        datefrom.classList.add("is-invalid");
        return alert('"Date From" field is NOT in a valid format!');
    }
    if (dateto.value && !dateMatchesRegex(dateto.value)) {
        dateto.classList.add("is-invalid");
        return alert('"Date To" field is NOT in a valid format!');
    }

    tlcMap.getSketchCoordinates();

    //put the lat/long limits into the bbox parameter
    var bbox = getBbox();
    if (bbox) {
        document.getElementById("bbox").setAttribute("value", bbox);
    } else {
        $("#bbox").val(null);
    }

    var polygon = getPolygon();
    if (polygon) {
        document.getElementById("polygon").setAttribute("value", polygon);
    } else {
        $("#polygon").val(null);
    }

    const input = {
        limit: getNumPlaces(),
        viewBbox: viewBbox,
        recordtype: $("#recordtype").val() || null,
        searchlayers: $("#selected-layers").val() || null,
        lga: $("#lga").val() || null,
        state: $("#state").val() || null,
        parish: $("#parish").val() || null,
        from: $("#from").val() || null,
        to: $("#to").val() || null,
        format: null,
        searchdescription: $("#searchdescription").is(":checked") ? "on" : null,
        download: null,
        bbox: $("#bbox").val() || null,
        polygon: $("#polygon").val() || null,
        chunks: $("#chunks").val() || null,
        dataitemid: $("#dataitemid").val() || null,
        id: $("#dataitemuid").val() || null, // uid
        feature_term: $("#feature_term").val() || null,
        extended_data: $("#extended_data").val() || null,
        source: $("#source").val() || null,
        searchpublicdatasets: $("#searchpublicdatasets").is(":checked")
            ? "on"
            : null,
        searchgeocoder: $("#searchpublicdatasets").is(":checked") ? "on" : null,
        searchausgaz: $("#searchausgaz").is(":checked") ? "on" : null,
        searchncg: $("#searchncg").is(":checked") ? "on" : null,

        sort: $("#sort").val() || null,
        direction: $("#direction").val() || null,
        subquery: $("#subquery").val() || null,
        datefrom: $("#datefrom").val() || null,
        dateto: $("#dateto").val() || null,

        containsnames: $("#containsnames").val() || null,
        locationbias: $("#locationbias").val() || null,

        // Trove style parameters
        name: $("#exactq").val() ? $("#exactq").val() : $("#name").val(),
        fuzzyname: $("#q").val() ? $("#q").val() : $("#fuzzyname").val(),
        paging: $("#n").val() || null,
        lga: $("#l-lga").val() ? $("#l-lga").val() : $("#lga").val(),
        state: $("#l-place").val() ? $("#l-place").val() : $("#state").val(),
        from: $("#s").val() ? $("#s").val() : $("#from").val(),
        to: $("#e").val() ? $("#e").val() : $("#to").val(),
    };

    if (getQueryParam("uid")) {
        input["uid"] = getQueryParam("uid");
    }
    if (getQueryParam("id")) {
        input["id"] = getQueryParam("id");
    }

    //change the input depending on form settings
    var selectBox = document.getElementById("input-select-box"); //the select box to choose between name/id
    var inputName = selectBox.options[selectBox.selectedIndex].value; //the value selected for search type (containsname fuzzyname name id)
    if (!names) {
        //if we did NOT bulk file search
        document.getElementById("input").setAttribute("name", inputName); //change input name to the selectbox type
        var trimmed_input = document.getElementById("input").value.trim();
        trimmed_input = trimmed_input.replace(/\s+/, " "); //replace all instances of single or multiple space with a single space. eg "nobbys     beach" becomes "nobbys beach"
        document.getElementById("input").value = trimmed_input;
        input[inputName] = trimmed_input;
    }

    //if we were redirected from the AJAX success with a bulk file of names to search
    else {
        //if no errors, choose between fuzzynames, containsnames or names but skip for id
        if (names.length > 1500)
            return alert(
                "File length was too long! Try using a shorter file (<1500 characters)"
            );
        if (inputName == "id") inputName = "containsname";
        //containsname fuzzyname or name, turned into a plural to make the bulk search parameter active
        document.getElementById(inputName + "s").hidden = false;
        document.getElementById(inputName + "s").value = names;
        input[inputName + "s"] = names;
    }

    return input;
}

/**
 * Updates the URL parameters based on the provided data object.
 *
 * @param object data An object containing key-value pairs to be set as URL parameters.
 */
function updateUrlParameters(data) {
    const url = new URL(window.location);
    const baseUrl = `${url.origin}${url.pathname}`; // Get the base URL without parameters

    const newUrl = new URL(baseUrl);

    if (data) {
        Object.keys(data).forEach((key) => {
            if (
                data[key] !== null &&
                data[key] !== "" &&
                data[key] !== undefined &&
                key !== "viewBbox" &&
                key !== "limit"
            ) {
                newUrl.searchParams.set(key, data[key]);
            }
        });
    }

    window.history.replaceState({}, "", newUrl);
}

/**
 * Retrieves the URL parameters as a query string.
 *
 * @return string A query string containing the current URL parameters.
 */
function getUrlParameters() {
    return new URLSearchParams(window.location.search).toString();
}

/**
 * Retrieves the value of a specific query parameter from the URL.
 *
 * @param string param The name of the query parameter to retrieve.
 * @return string|null The value of the query parameter, or null if not found.
 */
function getQueryParam(param) {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    return urlParams.get(param);
}

/**
 * Updates or deletes a specific query parameter in the URL.
 *
 * @param string param The name of the query parameter to update.
 * @param string|null value The value to set for the query parameter, or null to delete it.
 */
function updateParameter(param, value) {
    const url = new URL(window.location);

    if (value === null || value === undefined) {
        url.searchParams.delete(param);
    } else {
        url.searchParams.set(param, value);
    }

    window.history.pushState({}, "", url);
}

/**
 * Generates the download link for a specific format using the current URL parameters.
 *
 * @param string format The format for the download link (e.g., "kml", "csv").
 * @return string The generated download link.
 */
function updateDownloadLink(format) {
    const urlParams = getUrlParameters();
    return `${baseUrl}?${urlParams}&download=on&format=${format}`;
}

/**
 * Generates the web service feed link for a specific format using the current URL parameters.
 *
 * @param string format The format for the web service feed link (e.g., "kml", "csv").
 * @return string The generated web service feed link.
 */
function updateWsFeedLink(format) {
    const urlParams = getUrlParameters();
    return `${baseUrl}?${urlParams}&format=${format}`;
}

/**
 * Generates the view map link for a specific type and format using the current URL parameters.
 *
 * @param string type The type of the view map (e.g., "3d", "cluster").
 * @param string format The format for the view map link (e.g., "json").
 * @return string The generated view map link.
 */
function updateViewMapLink(type, format) {
    const urlParams = getUrlParameters();
    return `${viewsRootUrl}/${type}.html?load=${encodeURIComponent(
        `${baseUrl}places?${urlParams}&format=${format}`
    )}`;
}

/**
 * Binds the download links to the respective click events, updating the href attribute dynamically.
 */
function bindDownloadLinks(tlcMap) {
    // Unbind all click events first
    $("#downloadKml").off("click");
    $("#downloadCsv").off("click");
    $("#downloadGeoJson").off("click");
    // $("#downloadRoCrate").off("click");

    if (tlcMap.isSearchOn) {
        $("#downloadKml").click(function () {
            $(this).attr("href", updateDownloadLink("kml"));
        });

        $("#downloadCsv").click(function () {
            $(this).attr("href", updateDownloadLink("csv"));
        });

        $("#downloadGeoJson").click(function () {
            $(this).attr("href", updateDownloadLink("json"));
        });

        // $("#downloadRoCrate").click(function () {
        //     $(this).attr("href", updateDownloadLink("rocrate"));
        // });
    } else {
        $("#downloadCsv").click(function () {
            downloadCsv(tlcMap.bboxDataitems);
        });

        $("#downloadKml").click(function () {
            downloaKML(tlcMap.bboxDataitems);
        });
    }
}

/**
 * Binds the web service feed links to the respective click events, updating the href attribute dynamically.
 */
function bindFeedLinks() {
    $("#wsFeedKml").click(function () {
        $(this).attr("href", updateWsFeedLink("kml"));
    });

    $("#wsFeedCsv").click(function () {
        $(this).attr("href", updateWsFeedLink("csv"));
    });

    $("#wsFeedGeoJson").click(function () {
        $(this).attr("href", updateWsFeedLink("json"));
    });
}

/**
 * Binds the view map links to the respective click events, opening the generated links in a new window.
 */
function bindViewLinks() {
    $("#view3d").click(function (e) {
        e.preventDefault();
        window.open(updateViewMapLink("3d", "json"));
    });

    $("#viewCluster").click(function (e) {
        e.preventDefault();
        window.open(updateViewMapLink("cluster", "json"));
    });

    $("#viewJourney").click(function (e) {
        e.preventDefault();
        window.open(updateViewMapLink("journey", "json&line=route"));
    });

    $("#viewWerekata").click(function (e) {
        e.preventDefault();
        window.open(updateViewMapLink("werekata", "json"));
    });
}

function setListViewDisplayInfo(pointsInMap, totalPoints, tlcMap) {
    totalPoints = Math.max(totalPoints, pointsInMap);
    if (totalPoints == null || totalPoints == undefined) {
        totalPoints = 0;
    }

    $("#display_info").text(
        `Displaying ${pointsInMap} from a total of ${totalPoints}`
    );

    if (tlcMap.isSearchOn) {
        $("#save_search_count").val(tlcMap.dataitems.length);
        $(".shown_in_search").show();
    } else {
        $(".shown_in_search").hide();
    }
}

/**
 * Continues the search form submission process, sending data via AJAX and updating the map with the results.
 *
 * @param object tlcMap The map instance.
 * @param array|null names Optional array of names for bulk search.
 * @param object|null defaultLocation Optional default location to zoom to.
 * @param bool isUserSearch. True is user initiated search, false if it trigger by bounding box change
 * @param string bbox. Bounding box coordinates, if triggered by bounding box change after search

 * 
 */
function continueSearchForm(
    tlcMap,
    names = null,
    defaultLocation = null,
    isUserSearch,
    viewBbox
) {
    const data = getSearchFormData(names, tlcMap, viewBbox);
    updateUrlParameters(data);
    showLoadingWheel("loading places...");

    $.ajax({
        type: "POST",
        url: ajaxsearchdataitems,
        data: data,
        success: function (response) {
            if (isUserSearch && response.count <= 0) {
                hideLoadingWheel();
                alert("No places found");
                return;
            }

            if (isUserSearch) {
                //If it is the new search (Not triggered by boundind box change after change
                tlcMap.removeAllPlacesFromFeatureLayer();
            }

            tlcMap.ignoreExtentChange = true;
            tlcMap.isSearchOn = true;
            tlcMap.totalSearchCount = response.count;

            if (!Array.isArray(response.dataitems)) {
                response.dataitems = Object.values(response.dataitems);
            }
            tlcMap.dataitems = response.dataitems;

            tlcMap.addPointsToMap(tlcMap.dataitems, viewBbox);
            tlcMap.renderDataItems(tlcMap.dataitems);

            tlcMap.graphicsLayer.removeAll();

            if (defaultLocation) {
                tlcMap.zoomTo(defaultLocation[1], defaultLocation[0]);
                updateParameter("goto", defaultLocation.join(","));
            }

            if (isUserSearch) {
                if (
                    $(".typeFilter-map").is(":checked") ||
                    $(".typeFilter-cluster").is(":checked")
                ) {
                    window.scrollTo({
                        top: document.body.scrollHeight,
                        behavior: "smooth",
                    });
                }
            }
            hideLoadingWheel();
            bindDownloadLinks(tlcMap);
        },
        error: function (xhr, textStatus, errorThrown) {
            alert(
                "Your search has timed out on the server. Please refine your query."
            );
            hideLoadingWheel();
            console.log(xhr.responseText);
        },
    });
}

/**
 * Retrieves the bounding box coordinates from the form inputs.
 *
 * @return string|null A string representing the bounding box coordinates, or null if not available.
 */
function getBbox() {
    var minlong = document.getElementById("minlong").value;
    var minlat = document.getElementById("minlat").value;
    var maxlong = document.getElementById("maxlong").value;
    var maxlat = document.getElementById("maxlat").value;

    if (minlat && maxlat && minlong && maxlong) {
        return "" + minlong + "," + minlat + "," + maxlong + "," + maxlat;
    }
    return null;
}

/**
 * Retrieves the polygon coordinates from the form input.
 *
 * @return string|null A string representing the polygon coordinates, or null if not available.
 */
function getPolygon() {
    var polygon = document.getElementById("polygoninput").value;
    return polygon; //returns null if not present
}

/**
 * Retrieves the circle parameters from the form inputs.
 *
 * @return string|null A string representing the circle parameters, or null if not available.
 */
function getCircle() {
    var circlelong = document.getElementById("circlelong").value;
    var circlelat = document.getElementById("circlelat").value;
    var circlerad = document.getElementById("circlerad").value;
    if (circlelong && circlelat && circlerad)
        return "" + circlelong + "," + circlelat + "," + circlerad;
}

/**
 * Checks if a search is currently active based on the presence of specific URL parameters.
 *
 * @return boolean True if a search is active, false otherwise.
 */
function isSearchOn() {
    if (
        getQueryParam("searchausgaz") ||
        getQueryParam("searchncg") ||
        getQueryParam("searchpublicdatasets") ||
        getQueryParam("recordtype") ||
        getQueryParam("lga") ||
        getQueryParam("state") ||
        getQueryParam("parish") ||
        getQueryParam("from") ||
        getQueryParam("to") ||
        getQueryParam("datefrom") ||
        getQueryParam("dateto") ||
        getQueryParam("format") ||
        getQueryParam("download") ||
        getQueryParam("bbox") ||
        getQueryParam("polygon") ||
        getQueryParam("extended_data") ||
        getQueryParam("uid") ||
        getQueryParam("id") ||
        getQueryParam("gotoid")
    ) {
        return true;
    }

    return false;
}

/**
 * Presets the search form based on URL parameters and previously saved values.
 * This function is called when the search form needs to be populated with values from a previous search.
 */
function presetSearchForm() {
    const urlParams = new URLSearchParams(window.location.search);

    // Set the input field with the search query if it exists
    if (urlParams.has("containsname")) {
        $("#input").val(urlParams.get("containsname"));
        $("#input-select-box").val("containsname");
    } else if (urlParams.has("fuzzyname")) {
        $("#input").val(urlParams.get("fuzzyname"));
        $("#input-select-box").val("fuzzyname");
    } else if (urlParams.has("name")) {
        $("#input").val(urlParams.get("name"));
        $("#input-select-box").val("name");
    } else if (urlParams.has("id")) {
        $("#input").val(urlParams.get("id"));
        $("#input-select-box").val("id");
    }

    //Check box
    if (urlParams.has("searchpublicdatasets")) {
        $("#searchpublicdatasets").prop(
            "checked",
            urlParams.get("searchpublicdatasets") === "on"
        );
    }
    if (urlParams.has("searchausgaz")) {
        $("#searchausgaz").prop(
            "checked",
            urlParams.get("searchausgaz") === "on"
        );
    }
    if (urlParams.has("searchncg")) {
        $("#searchncg").prop("checked", urlParams.get("searchncg") === "on");
    }
    if (urlParams.has("searchdescription")) {
        $("#searchdescription").prop(
            "checked",
            urlParams.get("searchdescription") === "on"
        );
    }

    if (urlParams.has("gotoid")) {
        $("#dataitemuid").val(urlParams.get("gotoid"));
    }

    if (urlParams.get("recordtype")) {
        $("#recordtype").val(urlParams.get("recordtype") || "");
        $("#filter-Place-Type").show();
        removeOptionByText($("#filterType"), "Place-Type");
    }

    if (urlParams.get("searchlayers")) {
        const layerIds = urlParams.get("searchlayers").split(",");
        const layerNames = layers
            .filter((layer) => layerIds.includes(layer.id.toString()))
            .map((layer) => layer.name)
            .join("; ");

        // Ensure the value ends with a semicolon
        $("#searchlayers").val(layerNames ? `${layerNames};` : "");
        $("#selected-layers").val(layerIds.join(","));
        $("#filter-Layers").show();
        removeOptionByText($("#filterType"), "Layers");
    }

    if (urlParams.get("extended_data")) {
        $("#extended_data").val(urlParams.get("extended_data") || "");
        $("#filter-Extended-Data").show();
        removeOptionByText($("#filterType"), "Extended-Data");
    }

    //Auto complete fields
    if (urlParams.get("lga")) {
        $("#lga").val(urlParams.get("lga") || "");
        $("#filter-LGA").show();
        removeOptionByText($("#filterType"), "LGA");
    }

    if (urlParams.get("state")) {
        $("#state").val(urlParams.get("state") || "");
        $("#filter-State-Territory").show();
        removeOptionByText($("#filterType"), "State-Territory");
    }

    if (urlParams.get("parish")) {
        $("#parish").val(urlParams.get("parish") || "");
        $("#filter-Parish").show();
        removeOptionByText($("#filterType"), "Parish");
    }
    if (urlParams.get("feature_term")) {
        $("#feature_term").val(urlParams.get("feature_term") || "");
        $("#filter-Feature").show();
        removeOptionByText($("#filterType"), "Feature");
    }

    if (urlParams.get("from")) {
        $("#from").val(urlParams.get("from") || "");
        $("#filter-From-ID").show();
        removeOptionByText($("#filterType"), "From-ID");
    }

    if (urlParams.get("to")) {
        $("#to").val(urlParams.get("to") || "");
        $("#filter-To-ID").show();
        removeOptionByText($("#filterType"), "To-ID");
    }

    if (urlParams.get("datefrom")) {
        $("#datefrom").val(urlParams.get("datefrom") || "");
        $("#filter-Date-From").show();
        removeOptionByText($("#filterType"), "Date-From");
    }

    if (urlParams.get("dateto")) {
        $("#dateto").val(urlParams.get("dateto") || "");
        $("#filter-Date-To").show();
        removeOptionByText($("#filterType"), "Date-To");
    }

    //Drawing
    if (urlParams.get("bbox")) {
        changeShapeType("bbox");
        const bboxValues = urlParams.get("bbox").split(",");
        if (bboxValues.length === 4) {
            $("#minlong").val(bboxValues[0]);
            $("#minlat").val(bboxValues[1]);
            $("#maxlong").val(bboxValues[2]);
            $("#maxlat").val(bboxValues[3]);
        }
    } else if (urlParams.get("polygon")) {
        changeShapeType("polygon");
        $("#polygoninput").val(urlParams.get("polygon") || "");
    }

    // Set the number of places dropdown
    const numPlaces = urlParams.get("numPlaces") || "200";
    $(".num-places").val(numPlaces);
}

/**
 *
 * @param bool isUserSearch. True is user initiated search, false if it trigger by bounding box change
 * @param string bbox. Bounding box coordinates, if triggered by bounding box change after search
 *x
 */
function searchActions(tlcMap, isUserSearch, viewBbox) {
    var bulkfileinput = document.getElementById("bulkfileinput");
    var CSRF_TOKEN = $("input[name=_token]").val();

    if (bulkfileinput.value.length) {
        var myFormData = new FormData();
        myFormData.append("file", bulkfileinput.files[0]);
        myFormData.append("_token", CSRF_TOKEN);

        $.ajax({
            url: bulkfileparser,
            type: "POST",
            dataType: "json",
            contentType: false,
            processData: false,
            data: myFormData,
            success: function (result) {
                continueSearchForm(
                    tlcMap,
                    result.names,
                    null,
                    isUserSearch,
                    viewBbox
                );
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            },
        });
    } else {
        continueSearchForm(tlcMap, null, null, isUserSearch, viewBbox);
    }
}

$(function () {
    $("#input").trigger("focus");
    $("#input").on("keyup", function (event) {
        if (event.key === "Enter") {
            $("#searchbutton").click();
        }
    });

    $("#mapselector").change(function () {
        var op = $("#mapselector option:selected").val();
        var type = op.substr(0, op.indexOf("option"));
        changeShapeType(type);
    });

    if ($(".typeFilter-list").is(":checked")) {
        $(".map-view").hide();
        $(".list-view").show();
    } else if ($(".typeFilter-map").is(":checked")) {
        $(".list-view").hide();
        $(".map-view").show();
    } else if ($(".typeFilter-cluster").is(":checked")) {
        $(".list-view").hide();
        $(".map-view").show();
    }
});

$(document).ready(async function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $("#csrfToken").val(),
        },
    });

    var addModalMapPicker = new MapPicker($("#addModal .map-picker"));
    addModalMapPicker.init();

    const tlcMap = new TLCMap(addModalMapPicker);
    await tlcMap.initializeMap();

    // Expose tlcMap to global scope
    window.tlcMap = tlcMap;

    var defaultLocation = null;
    if (getQueryParam("goto")) {
        let coordinates = getQueryParam("goto").split(",");
        if (coordinates.length == 2) {
            defaultLocation = coordinates;
        }
    }

    if (isSearchOn()) {
        presetSearchForm();
        continueSearchForm(tlcMap, null, defaultLocation, true, null);
        tlcMap.isSearchOn = true;
    } else {
        tlcMap.ignoreExtentChange = false;

        if (defaultLocation) {
            tlcMap.zoomTo(defaultLocation[1], defaultLocation[0]);
            updateParameter("goto", defaultLocation.join(","));
        } else {
            tlcMap.gotoUserLocation();
        }
    }

    bindDownloadLinks(tlcMap);
    bindFeedLinks();
    bindViewLinks();

    document.getElementById("addFilter").addEventListener("click", function () {
        var filterTypeSelect = document.getElementById("filterType");

        var filterElement = document.getElementById(
            "filter-" + filterTypeSelect.value
        );

        if (filterElement) {
            filterElement.style.display = "flex";
            filterTypeSelect.options[filterTypeSelect.selectedIndex].remove();
            filterTypeSelect.selectedIndex = 0;
        }
    });

    document
        .querySelectorAll(".remove-filter-button")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                var filterRow = this.closest(".row.align-items-center.my-auto");
                var filterType = filterRow.id.replace("filter-", "");
                filterRow.style.display = "none";

                // Clear values
                filterRow
                    .querySelectorAll("input, select")
                    .forEach(function (input) {
                        input.value = "";
                    });

                // Add the option back to the select
                var filterTypeSelect = document.getElementById("filterType");
                var newOption = document.createElement("option");
                newOption.value = filterType;
                newOption.text = filterType.replace(/-/g, " ");
                filterTypeSelect.appendChild(newOption);
            });
        });

    document.getElementById("mapdraw").addEventListener("click", function () {
        if (shapetype == "bbox") {
            var minlong = $("#minlong").val();
            var minlat = $("#minlat").val();
            var maxlong = $("#maxlong").val();
            var maxlat = $("#maxlat").val();

            if (
                minlong != null &&
                maxlong != null &&
                minlat != null &&
                maxlat != null &&
                minlong != "" &&
                maxlong != "" &&
                minlat != "" &&
                maxlat != ""
            ) {
                var rings = [
                    [parseFloat(minlong), parseFloat(minlat)],
                    [parseFloat(minlong), parseFloat(maxlat)],
                    [parseFloat(maxlong), parseFloat(maxlat)],
                    [parseFloat(maxlong), parseFloat(minlat)],
                ];

                tlcMap.drawPolygon(rings);
            } else {
                alert("Please enter all the coordinates");
            }
        } else if (shapetype == "polygon") {
            var polystr = $("#polygoninput"); //"0 0, 0 100, 100 100, 100 0, 0 0"
            if (!polystr.val()) return alert("polygon input box is empty");

            var pointstrarr = polystr.val().split(","); //["0 0", "0 100", "100 100", "100 0", "0 0"]
            var rings = [];

            for (var i = 0; i < pointstrarr.length; i++) {
                var point = pointstrarr[i].trim().split(" ");
                rings.push([parseFloat(point[0]), parseFloat(point[1])]);
            }
            tlcMap.drawPolygon(rings);
        }
    });

    if (tlcMap.isSearchOn && $("#polygoninput").val()) {
        var pointstrarr = $("#polygoninput").val().split(","); //["0 0", "0 100", "100 100", "100 0", "0 0"]
        var rings = [];

        for (var i = 0; i < pointstrarr.length; i++) {
            var point = pointstrarr[i].trim().split(" ");
            rings.push([parseFloat(point[0]), parseFloat(point[1])]);
        }
        tlcMap.drawPolygon(rings);
    }

    var viewParam = getQueryParam("view");
    if (viewParam == "map") {
        $(".typeFilter-map").prop("checked", true);
        $(".list-view").hide();
        $(".map-view").show();
        tlcMap.switchMapType("3d");
    } else if (viewParam == "cluster") {
        $(".typeFilter-cluster").prop("checked", true);
        $(".list-view").hide();
        $(".map-view").show();
        tlcMap.switchMapType("cluster");
    } else if (viewParam == "list") {
        $(".typeFilter-list").prop("checked", true);
        $(".map-view").hide();
        $(".list-view").show();
    } else {
        $(".typeFilter-map").prop("checked", true);
        $(".list-view").hide();
        $(".map-view").show();
        tlcMap.switchMapType("3d");
        updateParameter("view", "map");
    }

    $('input[name="typeFilter"]').on("change", function () {
        if ($(".typeFilter-list").is(":checked")) {
            $(".map-view").hide();
            $("#advancedaccordion").collapse("hide");
            updateParameter("view", "list");
            $(".list-view").show();
        } else if ($(".typeFilter-map").is(":checked")) {
            $(".list-view").hide();
            $(".map-view").show();
            updateParameter("view", "map");
            tlcMap.switchMapType("3d");
        } else if ($(".typeFilter-cluster").is(":checked")) {
            $(".list-view").hide();
            $(".map-view").show();
            updateParameter("view", "cluster");
            tlcMap.switchMapType("cluster");
        }
    });

    $("#searchpublicdatasets, #searchausgaz, #searchncg").change(function () {
        tlcMap.removeAllPlacesFromFeatureLayer();
        tlcMap.refreshMapPins();
    });

    // Refresh map pins when number of places change
    $(".num-places").change(function () {
        tlcMap.refreshMapPins();
    });

    $("#resetbutton").click(function (e) {
        tlcMap.isSearchOn = false;
        tlcMap.ignoreExtentChange = false;
        tlcMap.dataitems = null;
        tlcMap.graphicsLayer.removeAll();
        tlcMap.removeAllPlacesFromFeatureLayer();

        updateUrlParameters(null);

        $("#input").val("");
        $("#searchdescription").prop("checked", false);
        $("#recordtype").val("");

        $("#searchlayers").val("");
        $("#selected-layers").val("");

        $("#extended_data").val("");
        $("#lga").val("");
        $("#state").val("");
        $("#parish").val("");
        $("#from").val("");
        $("#to").val("");
        $("#datefrom").val("");
        $("#dateto").val("");

        $("#bbox").val("");
        $("#polygon").val("");
        $("#chunks").val("");
        $("#dataitemid").val("");
        $("#dataitemuid").val("");
        $("#feature_term").val("");

        $("#source").val("");
        $("#searchpublicdatasets").prop("checked", true);
        $("#searchausgaz").prop("checked", true);
        $("#searchncg").prop("checked", true);
        $("#sort").val("");
        $("#direction").val("");
        $("#subquery").val("");

        $("#containsnames").val("");
        $("#locationbias").val("");
        $("#exactq").val("");
        $("#q").val("");
        $("#encoding").val("");
        $("#n").val("");
        $("#l-lga").val("");
        $("#l-place").val("");
        $("#s").val("");
        $("#e").val("");

        $("#input-select-box").val("containsname");

        $(".row.align-items-center.my-auto").each(function () {
            if ($(this).css("display") === "flex") {
                $(this).find(".remove-filter-button").click();
            }
        });

        // Reset bounding box and polygon coordinates
        changeShapeType("bbox");
        $("#minlong").val("");
        $("#minlat").val("");
        $("#maxlong").val("");
        $("#maxlat").val("");
        $("#polygoninput").val("");
        bindDownloadLinks(tlcMap);

        tlcMap.gotoUserLocation();
    });

    //Upload kml search behaviour
    $("#polygonkml_search").click(async function (e) {
        e.preventDefault();
        console.log("polygonkml_search");

        const fileInput = document.getElementById("polygonkml");
        const file = fileInput.files[0];

        if (!file) {
            alert("Please upload a KML file.");
            return;
        } else if (!file.name.endsWith(".kml")) {
            alert("File must be a kml file.");
            return;
        }

        try {
            // Read the file content
            const text = await file.text();

            // Parse the KML
            const parser = new DOMParser();
            const kmlDoc = parser.parseFromString(text, "application/xml");

            // Check for parsing errors
            if (kmlDoc.getElementsByTagName("parsererror").length > 0) {
                alert("The uploaded file is not a valid KML.");
                return;
            }

            // Find the Polygon tag
            const polygons = kmlDoc.getElementsByTagName("Polygon");
            if (polygons.length === 0) {
                alert("The KML file must contain at least one Polygon.");
                return;
            }

            // Extract coordinates from the first Polygon
            const coordinatesTag =
                polygons[0].getElementsByTagName("coordinates")[0];
            if (!coordinatesTag) {
                alert("The Polygon tag does not contain coordinates.");
                return;
            }

            const coordinates = coordinatesTag.textContent.trim();
            const latLongCoords = coordinates
                .split(" ")
                .map((coord) => coord.split(",").slice(0, 2))
                .filter((coord) => coord.length === 2);

            let kmlout = "";
            for (let i = 0; i < latLongCoords.length; i++) {
                kmlout += latLongCoords[i][0] + " " + latLongCoords[i][1];
                if (i < latLongCoords.length - 1) {
                    kmlout += ", ";
                }
            }

            const input = {
                limit: 5000,
                polygon: kmlout,
            };
            updateUrlParameters(input);
            window.location.reload();
        } catch (error) {
            alert("An error occurred while processing the KML file.");
        }
    });

    $("#searchbutton").click(function (e) {
        updateUrlParameters(null);
        $("#dataitemuid").val("");
        $("#dataitemid").val("");
        searchActions(tlcMap, true, null);
    });

    $(document).on("click", ".popup-text.new-place-new", function (e) {
        if (!isLoggedIn) {
            window.location.href = baseUrl + "login";
            return;
        }

        $("#addlatitude").val("");
        $("#addlongitude").val("");
        addModalMapPicker.clearMarkers();

        this.ignoreExtentChange = true;

        $("#addModal")
            .modal("show")
            .on("shown.bs.modal", () => {
                this.ignoreExtentChange = false; // Reset after modal fully shown
            })
            .on("hide.bs.modal", () => {
                this.ignoreExtentChange = true; // Set to true when modal starts hiding
            })
            .on("hidden.bs.modal", () => {
                setTimeout(() => {
                    this.ignoreExtentChange = false; // Reset to false after modal is fully hidden
                }, 500);
            });
    });

    $(document).on("click", ".popup-text.new-place-current", function (e) {
        if (!isLoggedIn) {
            window.location.href = baseUrl + "login";
            return;
        }

        if (
            tlcMap.selectedFeature &&
            tlcMap.selectedFeature.uid &&
            tlcMap.selectedFeature.latitude &&
            tlcMap.selectedFeature.longitude
        ) {
            $("#addlatitude").val(tlcMap.selectedFeature.latitude);
            $("#addlongitude").val(tlcMap.selectedFeature.longitude);
            $("#related_place_uid").val(tlcMap.selectedFeature.uid);
            addModalMapPicker.createMarkerAt(
                [
                    parseFloat(tlcMap.selectedFeature.longitude),
                    parseFloat(tlcMap.selectedFeature.latitude),
                ],
                true,
                true
            );
        } else {
            $("#addlatitude").val("");
            $("#addlongitude").val("");
            this.addModalMapPicker.clearMarkers();
        }

        this.ignoreExtentChange = true;

        $("#addModal")
            .modal("show")
            .on("shown.bs.modal", () => {
                this.ignoreExtentChange = false; // Reset after modal fully shown
            })
            .on("hide.bs.modal", () => {
                this.ignoreExtentChange = true; // Set to true when modal starts hiding
            })
            .on("hidden.bs.modal", () => {
                setTimeout(() => {
                    this.ignoreExtentChange = false; // Reset to false after modal is fully hidden
                }, 500);
            });
    });

    $("#add_layer_button_submit").on("click", function () {
        let isValid = validateAddLayerRequestData(msgBanner);

        if (isValid) {
            $.ajax({
                type: "POST",
                url: "/myprofile/mydatasets/newdataset/create", //'User\UserController@createNewDataset'
                data: getAddLayerRequestData(),
                contentType: false,
                processData: false,
                headers: {
                    Accept: "application/json",
                },
                success: function (result) {
                    const new_layer_id = result.dataset_id;
                    const new_layer_name = $("#layername").val();

                    const new_layer_option = new Option(
                        new_layer_name,
                        new_layer_id,
                        true,
                        true
                    );
                    $("#chooseLayer")
                        .append(new_layer_option)
                        .trigger("change");

                    removeExistingSelections();
                    const currentModal =
                        document.querySelector("#newLayerModal");
                    $(currentModal).modal("hide");

                    setTimeout(() => {
                        const newModal = document.querySelector("#addModal");
                        $(newModal).modal("show");
                    }, 500);
                },
                error: function (xhr) {
                    alert("Create layer failed");
                },
            });
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#newLayerModal .scrollable").scrollTop(0);
        }
    });
});
