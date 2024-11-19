function showLoadingWheel(loadText) {
    document.getElementsByClassName("loading-text")[0].innerText = loadText;
    document.getElementById("loadingWheel").style.display = "block";
}

function hideLoadingWheel() {
    document.getElementById("loadingWheel").style.display = "none";
}

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
        } else if (selectedMethod === "dictionary") {
            // Show dictionary file input and enable the other selects
            $("#dictionary_file_input").show();
            $("#geocoding_method").prop("disabled", false);
            $("#geocoding_bias").prop("disabled", false);
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
                                ${
                                    place.text_position
                                        ? `<dd>${JSON.stringify(
                                              place.text_position
                                          )}</dd>`
                                        : ""
                                }
                              
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

        formData.append("datasource_id", 4);

        let extendedData = {
            context: context.replace(/<br>/g, "").trim(),
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
            let formData = getAddLayerRequestData();

            formData.append("from_text_id", textId);

            $.ajax({
                type: "POST",
                url: "/myprofile/mydatasets/newdataset/create", //'User\UserController@createNewDataset'
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    Accept: "application/json",
                },
                success: function (result) {
                    const new_layer_id = result.dataset_id;

                    //After layer is created, add the selected places to the layer
                    selectPlaces.forEach((place) => {
                        let placeFormData = getAddPlaceFormData(
                            place,
                            new_layer_id,
                            place.context
                        );

                        $.ajax({
                            type: "POST",
                            url: ajaxadddataitem,
                            data: placeFormData,
                            contentType: false,
                            processData: false,
                            success: function (result) {
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
                                textConextFormData.append(
                                    "line_word_end_index",
                                    -1
                                );
                                $.ajax({
                                    type: "POST",
                                    url: ajaxaddtextcontent,
                                    data: textConextFormData,
                                    contentType: false,
                                    processData: false,
                                    success: function (result) {
                                        // Redirect to the new layer page
                                        window.location.href =
                                            "/myprofile/mydatasets/" +
                                            new_layer_id;
                                    },
                                    error: function (xhr) {
                                        alert(xhr.responseText); //error message with error info
                                    },
                                });
                            },
                            error: function (xhr) {
                                alert(xhr.responseText); //error message with error info
                            },
                        });
                    });
                },
                error: function (xhr) {
                    alert("Create layer failed");
                    hideLoadingWheel();
                },
            });
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#newLayerModal .scrollable").scrollTop(0);
            hideLoadingWheel();
        }
    });
});
