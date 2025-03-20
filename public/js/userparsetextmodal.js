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

function getParseTimeEstimate(textContentSize) {
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

function storeTimeUsed(timeUsed , textContentSize) {
    $.ajax({
        type: "POST",
        url: ajaxstoreparsetime,
        data: {
            text_size: textContentSize,
            parse_time: timeUsed,
        },
        success: function (result) {
        },
        error: function (xhr, textStatus, errorThrown) {
            console.error("Error:", xhr.responseText);
        },
    });
}

function stripHtmlUsingDOM(html) {
    let doc = new DOMParser().parseFromString(html, "text/html");
    return (doc.body.textContent || "").trim(); // ✅ Removes HTML + trims spaces
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

    // Select All button
    $("#select_all").on("click", function () {
        $(".place-checkbox").prop("checked", true); // Check all checkboxes
    });

    // Select None button
    $("#select_none").on("click", function () {
        $(".place-checkbox").prop("checked", false); // Uncheck all checkboxes
    });

    $("#add_to_new_layer").on("click", function () {
        let selectedPlaces = getSelectedPlaces();

        let res = [];

        selectedPlaces.forEach((place) => {
            if (
                !place.temp_lat ||
                !place.temp_lon ||
                place.temp_lat === "" ||
                place.temp_lon === ""
            ) {
                return;
            }

            let addPlace = {
                title: place.name,
                longitude: place.temp_lon,
                latitude: place.temp_lat,
                description: place.context,
                type: "Text",
            };

            if (place.text_position) {
                Object.keys(place.text_position).forEach((key) => {
                    addPlace[key] = place.text_position[key];
                });
            }

            res.push(addPlace);
        });

        window.contributesourcedata = res;
        window.fromTextID = $("#parsetextID").val();

        $("#sourceadded").text(res.length + " places added to layer from source");
        $("#userparsetextmodal").modal("hide");
    });

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

    $("#parse_text_submit").on("click", async function () {
        let textContentSize = document.getElementById("parsetextSize").value;
        const parseTime = await getParseTimeEstimate(textContentSize);
       
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

        formData.append("id", $("#parsetextID").val());
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
                places.forEach((places) => {
                    places.context = stripHtmlUsingDOM(places.context);
                });
                renderDataItems(result.data.place_names);
                $('#parse_text_submit').text('Resubmit for parsing');

                hideLoadingWheel();

                // End the timer and calculate the elapsed time in seconds
                let endTime = performance.now();
                let durationInSeconds = (endTime - startTime) / 1000;
                storeTimeUsed(durationInSeconds , textContentSize);

            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
                hideLoadingWheel();
            },
        });
    });
});
