function showLoadingWheel(loadText) {
    document.getElementsByClassName("loading-text")[0].innerText = loadText;
    document.getElementById("loadingWheel").style.display = "block";
}

function hideLoadingWheel() {
    window.onbeforeunload = null;
    document.getElementById("loadingWheel").style.display = "none";
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

    // Rebuild the dropdown
    if (australiaOption) {
        select.add(australiaOption);
        australiaOption.selected = true; // Ensure Australia is selected by default
    }
    if (globalOption) select.add(globalOption); // Add Global second
    otherOptions.forEach((option) => select.add(option)); // Add the rest alphabetically
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
        console.log(places); // Mufeng remove in production
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

    $("#parse_text_submit").on("click", function () {
        // Start the timer Mufeng Remove this
        let startTime = performance.now();

        window.onbeforeunload = function () {
            return "Your results will not be saved if you close or navigate away.";
        };

        showLoadingWheel("Geoparsing places...");
        var selectedMethod = $("#parsing_method").val();
        var formData = new FormData();

        formData.append("id", textId);
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
                renderDataItems(result.data.place_names);
                hideLoadingWheel();

                // End the timer and calculate the elapsed time in seconds
                let endTime = performance.now();
                let durationInSeconds = (endTime - startTime) / 1000;

                console.log(`Execution Time: ${durationInSeconds} seconds`);
                console.log(places.length + " places found");

                if (document.getElementById("saveautomatically").checked) {
                    showLoadingWheel("Adding places to layer...");
                    const selectPlaces = getSelectedPlaces();
                    if (selectPlaces.length === 0) {
                        alert("No place find");
                        return false;
                    }

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
        showLoadingWheel("Adding places to layer...");
        const selectPlaces = getSelectedPlaces();
        if (selectPlaces.length === 0) {
            alert("Please select at least one place to add to the layer.");
            return false;
        }

        let isValid = validateAddLayerRequestData(msgBanner);

        if (isValid) {
            let layerFormData = getAddLayerRequestData();

            layerFormData.append("from_text_id", textId);

            addLayersAndPlacesInfo(selectPlaces, layerFormData);
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#newLayerModal .scrollable").scrollTop(0);
            hideLoadingWheel();
        }
    });

    function addLayersAndPlacesInfo(selectPlaces, layerFormData) {
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
                const new_layer_id = result.dataset_id;

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
                        textConextFormData.append("text_id", textId);
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

                        // Redirect to the new layer page
                        window.location.href =
                            "/myprofile/mydatasets/" + new_layer_id;
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

        formData.append("dsn", textTitle);
        formData.append("recordtype", "Text");
        formData.append("allowanps", 0);

        formData.append("public", 1); //public by default
        formData.append("description", "Layer created from text: " + textTitle);

        formData.append("from_text_id", textId);

        formData.append("redirect", 'false');

        return formData;
    }
});
