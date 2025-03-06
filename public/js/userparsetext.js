let progressInterval = null; // Declare a variable for the interval

function showLoadingWheel(loadText, estimatedTimeInSeconds = null) {
    // Hide the progress container if no estimated time is given
    if (estimatedTimeInSeconds === null) {
        document.querySelector(".progress-container").style.display = "none";
    } else {
        // Reset progress bar to 0% if it is not null
        document.querySelector(".progress-bar").style.width = "0%";
        document.querySelector(".progress-container").style.display = "block"; // Show the progress bar

        // Calculate the interval time in milliseconds
        const updateInterval = 100;
        const intervalTime = estimatedTimeInSeconds * 1000;
        const totalSteps = intervalTime / updateInterval;

        let currentProgress = 0;

        // If there's an ongoing progress interval, clear it first
        if (progressInterval !== null) {
            clearInterval(progressInterval);
        }

        // Start the progress
        progressInterval = setInterval(() => {
            currentProgress += 1;

            // Update the progress bar width
            document.querySelector(".progress-bar").style.width =
                (currentProgress / totalSteps) * 100 + "%";

            // Stop the progress when it reaches 100%
            if (currentProgress >= totalSteps) {
                document.querySelector(".progress-bar").style.width = "100%";
                clearInterval(progressInterval); // Stop the interval when done
            }
        }, updateInterval); // Execute every 100ms
    }

    // Show the loading text
    document.getElementsByClassName("loading-text")[0].innerText = loadText;

    // Display the loading wheel container
    document.getElementById("loadingWheel").style.display = "block";
}

function hideLoadingWheel() {
    window.onbeforeunload = null;
    document.getElementById("loadingWheel").style.display = "none";
}

function stripHtmlUsingDOM(html) {
    let doc = new DOMParser().parseFromString(html, 'text/html');
    return (doc.body.textContent || "").trim(); // ✅ Removes HTML + trims spaces
}


document.addEventListener("DOMContentLoaded", function () {
    const select = document.getElementById("geocoding_bias");
    const options = Array.from(select.options);

    // Find "Australia" and "Global" options
    const australiaOption = options.find(
        (option) => option.value === "Australia"
    );
    const globalOption = options.find((option) => option.value === "null");
    const otherOptions = options.filter(
        (option) => option.value !== "Australia" && option.value !== "null"
    );

    // Sort other options alphabetically
    otherOptions.sort((a, b) => a.text.localeCompare(b.text));

    // Clear the existing options
    select.innerHTML = "";

    // Create optgroup for Global
    const globalGroup = document.createElement("optgroup");
    globalGroup.label = "Global";

    if (globalOption) {
        globalGroup.appendChild(globalOption); // Add Global
    }

    // Create optgroup for other options
    const otherGroup = document.createElement("optgroup");
    otherGroup.label = "Other";

    // Add Australia to the other options group
    if (australiaOption) {
        otherOptions.unshift(australiaOption); // Add Australia at the beginning of the sorted list
    }

    otherOptions.forEach((option) => {
        otherGroup.appendChild(option); // Add remaining options alphabetically
    });

    // Add the optgroups to the select
    select.appendChild(globalGroup);
    select.appendChild(otherGroup);

    // Set Australia as selected by default
    if (australiaOption) {
        australiaOption.selected = true;
    }

    // Trigger change event to ensure the selected option is reflected across browsers
    select.dispatchEvent(new Event("change"));
});

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $("#csrfToken").val(),
        },
    });

    let places = [];

    $("#parsing_method").on("change", function () {
        var selectedMethod = $(this).val();

        if (selectedMethod === "dictionary_with_coords") {
            // Show dictionary file input and disable the other selects
            $("#dictionary_file_input").show();
            $("#geocoding_method").prop("disabled", true);
            $("#geocoding_bias").prop("disabled", true);

            // Update instruction text
            $("#dictionary_file_instructions")
                .text(
                    "Please upload a CSV file with the following columns: First column - Place Name, Second column - Latitude, Third column - Longitude."
                )
                .show();
        } else if (selectedMethod === "dictionary") {
            // Show dictionary file input and enable the other selects
            $("#dictionary_file_input").show();
            $("#geocoding_method").prop("disabled", false);
            $("#geocoding_bias").prop("disabled", false);

            // Update instruction text
            $("#dictionary_file_instructions")
                .text(
                    "Please upload a CSV file with the following columns: First column - Place Name."
                )
                .show();
        } else {
            // Hide dictionary file input and enable the other selects
            $("#dictionary_file_input").hide();
            $("#geocoding_method").prop("disabled", false);
            $("#geocoding_bias").prop("disabled", false);
        }
    });

    function renderDataItems(places) {
        const listView = $(".place-list");
        listView.empty();

        places.forEach((place, index) => {
            var html = `<div class="row">`;

            html += `<div class="col col-xl-1 d-flex justify-content-center">
                        <input type="checkbox" class="place-checkbox" data-index="${index}" checked>
                    </div>`;

            //Main info
            html += `
                    <div class="col col-xl-3">
                        <div class="sresultmain">
                            <h4>
                                <button type="button" class="btn btn-primary btn-sm">C</button>
                                <a>${place.name}</a>
                            </h4>
                            <dl>
                                ${
                                    place.name
                                        ? `<dt>Placename</dt><dd>${place.name}</dd>`
                                        : ""
                                }
                            </dl>
                        </div>
                    </div>`;

            //Details
            html += `<div class="col col-xl-4">
                        <div>
                            <h4>Details</h4>
                            <dl>
                                ${
                                    place.temp_lat
                                        ? `<dt>Latitude</dt><dd>${place.temp_lat}</dd>`
                                        : ""
                                }
                                ${
                                    place.temp_lon
                                        ? `<dt>Longitude</dt><dd>${place.temp_lon}</dd>`
                                        : ""
                                }
                            </dl>
                        </div>
                    </div>
                    `;

            //Description
            html += `<div class="col col-xl-4">
                        <h4>Description</h4>
                        <div>
                            <dl>
                                ${'"' + place.context + '"'}
                              
                            </dl>
                        </div>
                    </div>
                    `;

            html += `</div>`;
            listView.append(html);
        });

        $("#parse_result").show();
    }

    // Function to gather selected places when needed
    function getSelectedPlaces() {
        let selectedPlaces = [];

        $(".place-checkbox:checked").each(function () {
            var index = $(this).data("index");
            selectedPlaces.push(places[index]);
        });

        return selectedPlaces;
    }

    function getAddPlaceFormData(place, layer_id, context) {
        const formData = new FormData();
        formData.append("ds_id", layer_id);
        formData.append("title", place["name"]);
        formData.append("placename", place["name"]);

        formData.append("recordtype", "Text");
        formData.append("latitude", place["temp_lat"]);
        formData.append("longitude", place["temp_lon"]);

        formData.append("description", '"' + context + '"');
        formData.append("datasource_id", 4);

        let extendedData = {
            ...place["text_position"],
        };

        formData.append("extendedData", JSON.stringify(extendedData));

        return formData;
    }

    function getParseTimeEstimate() {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "GET",
                url: ajaxgetparsetimeestimate,
                data: {
                    text_size: textContentSize,
                },
                success: function (result) {
                    resolve(result); // Resolve the promise with the result
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error("Error:", xhr.responseText);
                    reject(xhr.responseText); // Reject the promise with the error message
                },
            });
        });
    }

    function storeTimeUsed(timeUsed) {
        console.log(textContentSize);
        $.ajax({
            type: "POST",
            url: ajaxstoreparsetime,
            data: {
                text_size: textContentSize,
                parse_time: timeUsed,
            },
            success: function (result) {
                console.log(`Execution Time: ${timeUsed} seconds`);
            },
            error: function (xhr, textStatus, errorThrown) {
                console.error("Error:", xhr.responseText);
            },
        });
    }

    // Select All button
    $("#select_all").on("click", function () {
        $(".place-checkbox").prop("checked", true); // Check all checkboxes
    });

    // Select None button
    $("#select_none").on("click", function () {
        $(".place-checkbox").prop("checked", false); // Uncheck all checkboxes
    });

    $("#add_to_new_layer").on("click", function () {
        $("#newLayerModal").modal("show");

        // Remove all existing options except 'Text'
        $("#layerrecordtype").html('<option label="Text">Text</option>');
    });

    $("#parse_text_submit").on("click", async function () {
        const parseTime = await getParseTimeEstimate();

        // Start the timer
        let startTime = performance.now();

        window.onbeforeunload = function () {
            return "Your results will not be saved if you close or navigate away.";
        };

        showLoadingWheel(
            "Geoparsing places. Do not close this browser window.",
            parseTime.estimated_time
        );
        var selectedMethod = $("#parsing_method").val();
        var formData = new FormData();

        formData.append("id", text["id"]);
        formData.append("method", selectedMethod);

        if (
            selectedMethod === "dictionary" ||
            selectedMethod === "dictionary_with_coords"
        ) {
            var dictionaryFile = $("#dictionary")[0].files[0];

            if (!dictionaryFile) {
                alert("Please upload a CSV file for the dictionary method.");
                hideLoadingWheel();
                return false;
            }

            // Check file extension
            var fileName = dictionaryFile.name.toLowerCase();
            if (!fileName.endsWith(".csv")) {
                alert("The uploaded file must be a csv file.");
                hideLoadingWheel();
                return false;
            }

            formData.append("dictionary", dictionaryFile);
        }

        if (selectedMethod !== "dictionary_with_coords") {
            formData.append("geocoding_method", $("#geocoding_method").val());
            formData.append("geocoding_bias", $("#geocoding_bias").val());
        }

        $.ajax({
            type: "POST",
            url: parsetexturl,
            data: formData,
            processData: false,
            contentType: false,
            success: function (result) {
                places = result.data.place_names;
                places.forEach(places => {
                    places.context = stripHtmlUsingDOM(places.context);
                });
                renderDataItems(result.data.place_names);
                hideLoadingWheel();

                // End the timer and calculate the elapsed time in seconds
                let endTime = performance.now();
                let durationInSeconds = (endTime - startTime) / 1000;
                storeTimeUsed(durationInSeconds);

                if (document.getElementById("saveautomatically").checked || places.length === 0) {
                    showLoadingWheel("Adding places to layer...", null);
                    const selectPlaces = getSelectedPlaces();
                  
                    let layerFormData = getDefaultLayerRequestData();
                    addLayersAndPlacesInfo(selectPlaces, layerFormData);
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
                hideLoadingWheel();
            },
        });
    });

    $("#add_layer_button_submit").on("click", function () {
        showLoadingWheel("Adding places to layer...", null);
        const selectPlaces = getSelectedPlaces();
        if (selectPlaces.length === 0) {
            alert("Please select at least one place to add to the layer.");
            return false;
        }

        let isValid = validateAddLayerRequestData(msgBanner);

        if (isValid) {
            let layerFormData = getAddLayerRequestData();

            layerFormData.append("from_text_id", text["id"]);

            addLayersAndPlacesInfo(selectPlaces, layerFormData);
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#newLayerModal .scrollable").scrollTop(0);
            hideLoadingWheel();
        }
    });

    function addLayersAndPlacesInfo(selectPlaces, layerFormData) {
        var new_layer_id;
        //create dataset
        $.ajax({
            type: "POST",
            url: "/myprofile/mydatasets/newdataset/create", //'User\UserController@createNewDataset'
            data: layerFormData,
            contentType: false,
            processData: false,
            headers: {
                Accept: "application/json",
            },
            success: function (result) {
                new_layer_id = result.dataset_id;

                const ajaxPromises = []; // Array to hold all AJAX promises

                // After layer is created, add the selected places to the layer
                selectPlaces.forEach((place) => {
                    if (place["temp_lat"] == "" || place["temp_lon"] == "") {
                        return;
                    }

                    let placeFormData = getAddPlaceFormData(
                        place,
                        new_layer_id,
                        place.context
                    );

                    // Create dataitem
                    const placePromise = $.ajax({
                        type: "POST",
                        url: ajaxadddataitem,
                        data: placeFormData,
                        contentType: false,
                        processData: false,
                    }).then((result) => {
                        var new_dataitem_uid = result.dataitem.uid;
                        const textConextFormData = new FormData();
                        textConextFormData.append(
                            "dataitem_uid",
                            new_dataitem_uid
                        );
                        textConextFormData.append("text_id", text["id"]);
                        textConextFormData.append(
                            "start_index",
                            place.text_position.offset
                        );
                        textConextFormData.append(
                            "end_index",
                            parseInt(place.text_position.offset, 10) +
                                place.name.length
                        );
                        textConextFormData.append(
                            "sentence_start_index",
                            place.text_position.sentence_start_index
                        );
                        textConextFormData.append(
                            "sentence_end_index",
                            place.text_position.sentence_end_index
                        );
                        textConextFormData.append(
                            "line_index",
                            place.text_position.line
                        );
                        textConextFormData.append(
                            "line_word_start_index",
                            place.text_position.word
                        );
                        textConextFormData.append("line_word_end_index", -1);

                        // Create text content
                        return $.ajax({
                            type: "POST",
                            url: ajaxaddtextcontent,
                            data: textConextFormData,
                            contentType: false,
                            processData: false,
                        });
                    });

                    // Add this promise to the list
                    ajaxPromises.push(placePromise);
                });

                // Wait for all AJAX requests to finish before redirecting
                $.when
                    .apply($, ajaxPromises)
                    .done(() => {
                        window.onbeforeunload = null;

                        // Redirect to the text map view page
                        window.location.href =
                            "/myprofile/mydatasets/" +
                            new_layer_id +
                            "/textmap?load=" +
                            encodeURIComponent(
                                appurl + "/layers/" + new_layer_id + "/json"
                            ) +
                            "?textmap=true";
                    })
                    .fail((xhr) => {
                        alert("An error occurred: " + xhr.responseText);
                    });
            },
            error: function (xhr) {
                alert("Create layer failed");
                hideLoadingWheel();
            },
        });
    }

    function getDefaultLayerRequestData() {
        const formData = new FormData();

        appendIfNotNull(formData, "dsn", text["name"]); // dataset name
        appendIfNotNull(formData, "description", text["description"]); // dataset description
        appendIfNotNull(formData, "tags", ""); // tags (empty string if null)
        appendIfNotNull(formData, "recordtype", "Text");
        appendIfNotNull(formData, "allowanps", 0);
        appendIfNotNull(formData, "public", 1); // public by default
        appendIfNotNull(formData, "from_text_id", text["id"]);
        appendIfNotNull(formData, "redirect", "false");

        appendIfNotNull(formData, "temporalfrom", text["temporal_from"]);
        appendIfNotNull(formData, "temporalto", text["temporal_to"]);
        appendIfNotNull(formData, "creator", text["creator"]);
        appendIfNotNull(formData, "publisher", text["publisher"]);
        appendIfNotNull(formData, "contact", text["contact"]);
        appendIfNotNull(formData, "citation", text["citation"]);
        appendIfNotNull(formData, "doi", text["doi"]);
        appendIfNotNull(formData, "source_url", text["source_url"]);
        appendIfNotNull(formData, "linkback", text["linkback"]);
        appendIfNotNull(formData, "language", text["language"]);
        appendIfNotNull(formData, "license", text["license"]);
        appendIfNotNull(formData, "rights", text["rights"]);
        appendIfNotNull(formData, "warning", text["warning"]);
        appendIfNotNull(formData, "created", text["created"]);

        return formData;
    }

    function appendIfNotNull(formData, key, value) {
        if (value != null) {
            formData.append(key, value);
        } else {
            formData.append(key, ""); // Append empty string for null or undefined values
        }
    }
});
