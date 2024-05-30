var shapetype = "bbox";

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
 * Selects a specified number of random data items from an array using the Fisher-Yates shuffle algorithm.
 *
 * @param array dataitems An array of data items.
 * @param int num The number of data items to select.
 * @return array An array containing the selected random data items.
 */
function selectRandomDataitems(dataitems, num) {
    if (!Array.isArray(dataitems) || num <= 0) {
        return [];
    }

    if (num >= dataitems.length) {
        return dataitems;
    }

    let items = dataitems.slice();

    // Shuffle the array using the Fisher-Yates algorithm and select the first num elements
    for (let i = 0; i < num; i++) {
        // Pick a random index between current index i and the end of the array
        let j = Math.floor(Math.random() * (items.length - i) + i);

        // Swap elements at indices i and j
        [items[i], items[j]] = [items[j], items[i]];
    }

    // Return the first num elements of the shuffled array
    return items.slice(0, num);
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
function getSearchFormData(names, tlcMap) {
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

    // Validate ANPS ID.
    if (
        $("#input-select-box").val() === "anps_id" &&
        !/^\d+$/.test($("#input").val())
    ) {
        $("#input").addClass("is-invalid");
        return alert("ANPS ID should be a number");
    }

    tlcMap.getSketchCoordinates();

    //put the lat/long limits into the bbox parameter
    var bbox = getBbox();
    if (bbox) document.getElementById("bbox").setAttribute("value", bbox);

    var polygon = getPolygon();
    if (polygon)
        document.getElementById("polygon").setAttribute("value", polygon);

    const input = {
        recordtype: $("#recordtype").val() || null,
        searchlayers: $("#selected-layers").val() || null,
        lga: $("#lga").val() || null,
        state: $("#state").val() || null,
        parish: $("#parish").val() || null,
        from: $("#from").val() || null,
        to: $("#to").val() || null,
        format: $("#format").val() || null,
        searchdescription: $("#searchdescription").is(":checked") ? "on" : null,
        download: $("#download").is(":checked") ? "on" : null,
        bbox: $("#bbox").val() || null,
        polygon: $("#polygon").val() || null,
        chunks: $("#chunks").val() || null,
        dataitemid: $("#dataitemid").val() || null,
        feature_term: $("#feature_term").val() || null,
        extended_data: $("#extended_data").val() || null,
        source: $("#source").val() || null,
        searchpublicdatasets: $("#searchpublicdatasets").is(":checked")
            ? "on"
            : null,
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
        format: $("#encoding").val()
            ? $("#encoding").val()
            : $("#format").val(),
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
    var selectBox = document.getElementById("input-select-box"); //the select box to choose between name/anps_id
    var inputName = selectBox.options[selectBox.selectedIndex].value; //the value selected for search type (containsname fuzzyname name anps_id)
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
        //if no errors, choose between fuzzynames, containsnames or names but skip for anps_id
        if (names.length > 1500)
            return alert(
                "File length was too long! Try using a shorter file (<1500 characters)"
            );
        if (inputName == "anps_id") inputName = "containsname";
        //containsname fuzzyname or name, turned into a plural to make the bulk search parameter active
        document.getElementById(inputName + "s").hidden = false;
        document.getElementById(inputName + "s").value = names;
        input[inputName + "s"] = trimmed_input;
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
                data[key] !== undefined
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
    return `${baseUrl}places?${urlParams}&download=on&format=${format}`;
}

/**
 * Generates the web service feed link for a specific format using the current URL parameters.
 *
 * @param string format The format for the web service feed link (e.g., "kml", "csv").
 * @return string The generated web service feed link.
 */
function updateWsFeedLink(format) {
    const urlParams = getUrlParameters();
    return `${baseUrl}places?${urlParams}&format=${format}`;
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
function bindDownloadLinks() {
    $("#downloadKml").click(function () {
        $(this).attr("href", updateDownloadLink("kml"));
    });

    $("#downloadCsv").click(function () {
        $(this).attr("href", updateDownloadLink("csv"));
    });

    $("#downloadGeoJson").click(function () {
        $(this).attr("href", updateDownloadLink("json"));
    });

    $("#downloadRoCrate").click(function () {
        $(this).attr("href", updateDownloadLink("rocrate"));
    });
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

    $("#viewTemporalEarth").click(function (e) {
        e.preventDefault();
        window.open(
            `${viewsTemporalEarthUrl}?file=${encodeURIComponent(
                `${baseUrl}places?${urlParams}&format=kml`
            )}`
        );
    });
}

/**
 * Continues the search form submission process, sending data via AJAX and updating the map with the results.
 *
 * @param array|null names Optional array of names for bulk search.
 * @param object tlcMap The map instance used for search operations.
 */
function continueSearchForm(tlcMap, names = null, defaultLocation = null) {
    const data = getSearchFormData(names, tlcMap);
    updateUrlParameters(data);

    $.ajax({
        type: "POST",
        url: ajaxsearchdataitems,
        data: data,
        success: function (response) {
            if (response.dataitems.length > 0) {
                tlcMap.ignoreExtentChange = true;
                tlcMap.isSearchOn = true;

                tlcMap.dataitems = response.dataitems;
                dataitemsInMap = selectRandomDataitems(
                    tlcMap.dataitems,
                    getNumPlaces()
                );

                tlcMap.addPointsToMap(dataitemsInMap);
                tlcMap.renderDataItems(dataitemsInMap);

                if (defaultLocation) {
                    tlcMap.zoomTo(defaultLocation[1], defaultLocation[0]);
                    updateParameter("goto", defaultLocation.join(","));
                }

                //Hide advanded search
                $("#advancedaccordion").collapse("hide");
            } else {
                alert("No pLaces found");
            }
        },
        error: function (xhr, textStatus, errorThrown) {
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
        getQueryParam("id")
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
    } else if (urlParams.has("anps_id")) {
        $("#input").val(urlParams.get("anps_id"));
        $("#input-select-box").val("anps_id");
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
    if (urlParams.has("download")) {
        $("#download").prop("checked", urlParams.get("download") === "on");
    }

    //Dropdowns
    $("#recordtype").val(urlParams.get("recordtype") || "");
    $("#state").val(urlParams.get("state") || "");
    $("#format").val(urlParams.get("format") || "");

    //Auto complete fields
    $("#lga").val(urlParams.get("lga") || "");
    $("#parish").val(urlParams.get("parish") || "");
    $("#feature_term").val(urlParams.get("feature_term") || "");

    if (urlParams.get("searchlayers")) {
        const layerIds = urlParams.get("searchlayers").split(",");
        const layerNames = layers
            .filter((layer) => layerIds.includes(layer.id.toString()))
            .map((layer) => layer.name)
            .join("; ");

        // Ensure the value ends with a semicolon
        $("#searchlayers").val(layerNames ? `${layerNames};` : "");
        $("#selected-layers").val(layerIds.join(","));
    }

    //Text
    $("#from").val(urlParams.get("from") || "");
    $("#to").val(urlParams.get("to") || "");
    $("#extended_data").val(urlParams.get("extended_data") || "");

    //Date
    $("#datefrom").val(urlParams.get("datefrom") || "");
    $("#dateto").val(urlParams.get("dateto") || "");

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

    //Single place search.. redirect from /places/id
    if (getQueryParam("gotoid")) {
        const input =  {
            id : getQueryParam("gotoid")
        }
        $.ajax({
            type: "POST",
            url: ajaxsearchdataitems,
            data: input,
            success: function (response) {
                if (response.dataitems.length > 0) {
                    tlcMap.ignoreExtentChange = true;
                    tlcMap.isSearchOn = true;
    
                    tlcMap.dataitems = response.dataitems;
    
                    tlcMap.addPointsToMap(tlcMap.dataitems);
                    tlcMap.renderDataItems(tlcMap.dataitems);
        
                    //Hide advanded search
                    $("#advancedaccordion").collapse("hide");
                } else {
                    alert("No pLaces found");
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
            },
        });

    } else {
        var defaultLocation = null;
        if (getQueryParam("goto")) {
            let coordinates = getQueryParam("goto").split(",");
            if (coordinates.length == 2) {
                defaultLocation = coordinates;
            }
        }

        if (isSearchOn()) {
            presetSearchForm();
            continueSearchForm(tlcMap, null, defaultLocation);
        } else {
            tlcMap.ignoreExtentChange = false;

            if (defaultLocation) {
                tlcMap.zoomTo(defaultLocation[1], defaultLocation[0]);
                updateParameter("goto", defaultLocation.join(","));
            } else {
                tlcMap.gotoUserLocation();
            }
        }
    }

    bindDownloadLinks();
    bindFeedLinks();
    bindViewLinks();

    $('input[name="typeFilter"]').on("change", function () {
        if ($(".typeFilter-list").is(":checked")) {
            $(".map-view").hide();

            if (tlcMap.isSearchOn) {
                const displayPlaces = Math.min(
                    getNumPlaces(),
                    tlcMap.dataitems.length
                );

                $("#display_info").text(
                    `Displaying ${displayPlaces} from a total of ${tlcMap.dataitems.length}`
                );
                $("#save_search_count").val(tlcMap.dataitems.length);
                $("#list-buttons").show();
                $("#display_info").show();
                $("#list-save-search").show();

                //Hide advanded search
            } else {
                $("#display_info").hide();
                $("#list-buttons").hide();
                $("#list-save-search").hide();
            }

            $("#advancedaccordion").collapse("hide");
            $(".list-view").show();
        } else if ($(".typeFilter-map").is(":checked")) {
            $(".list-view").hide();
            $(".map-view").show();
        }
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

        updateUrlParameters(null);
        $("#display_info").text(` `);

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
        $("#format").val("");
        $("#download").prop("checked", false);

        $("#bbox").val("");
        $("#polygon").val("");
        $("#chunks").val("");
        $("#dataitemid").val("");
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
        document.getElementById("input").placeholder = "Enter place name";

        // Reset bounding box and polygon coordinates
        changeShapeType("bbox");
        $("#minlong").val("");
        $("#minlat").val("");
        $("#maxlong").val("");
        $("#maxlat").val("");
        $("#polygoninput").val("");

        $("#display_info").hide();
        $("#list-buttons").hide();
        $("#list-save-search").hide();

        tlcMap.gotoUserLocation();
    });

    $("#searchbutton").click(function (e) {
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
                    continueSearchForm(tlcMap, result.names);
                },
                error: function (xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                },
            });
        } else {
            continueSearchForm(tlcMap);
        }
    });
});
