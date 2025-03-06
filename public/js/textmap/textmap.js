$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});


(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const urltoload = urlParams.get("load");

    let currentMapType = "cluster";
    let currentViewMode = "view";
    let currentSelectedPlaceUID = null;
    let currentSelectedPlaceLatitude = null;
    let currentSelectedPlaceLongitude = null;

    let geojsonLayer = null;
    let graphicsLayer = null;
    let featureMap = new Map();

    let isAddNewPlace = false;
    let selectedRange = null;
    let newPlace = {};

    //todo make sure to clear graphics layer when needed

    document
        .getElementById("closePopupButton")
        .addEventListener("click", function () {
            closeEditPopup();
            currentSelectedPlaceUID = null;
        });

    document
        .getElementById("cancelButton")
        .addEventListener("click", function () {
            closeEditPopup();
            currentSelectedPlaceUID = null;
        });

    //Change background of the span, scroll to it
    function highlightPlaceInText(id) {
        restoreAllSpanColors();
        const textContent = document.getElementById("textcontent");
        const spanToHighlight = textContent.querySelector(
            `span[data-uid="${id}"]`
        );

        if (spanToHighlight) {
            // Change the background color
            spanToHighlight.style.backgroundColor = "#286090";

            // Scroll into view if needed
            spanToHighlight.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
        }
    }

    function restoreAllSpanColors() {
        const textContent = document.getElementById("textcontent");
        const allSpans = textContent.querySelectorAll("span[data-uid]");

        allSpans.forEach((span) => {
            span.style.backgroundColor = "orange";
        });
    }

    function refreshGeoJSONLayer(GeoJSONLayer, view, clusterConfig) {
        loadConfig(urltoload).then((config) => {
            const blob = new Blob([JSON.stringify(config.data)], {
                type: "application/json",
            });
            const newurl = URL.createObjectURL(blob);
            // Remove the existing layer from the map
            view.map.remove(geojsonLayer);

            geojsonLayer = new GeoJSONLayer({
                url: newurl,
                copyright:
                    "Check copyright and permissions of this dataset at http://tlcmap.org/ghap.",
                featureReduction: clusterConfig,
                popupTemplate: loadPopUpTemplate(config),
                renderer: loadRenderer(config),
                popupEnabled: config.popupEnabled,
                outFields: ["*"],
            });

            // Add the refreshed layer back to the map
            view.map.add(geojsonLayer);
        });
    }

    function removePopupElements() {
        // Remove pop up elements
        document
            .querySelectorAll(".esri-popup__main-container.esri-widget")
            .forEach(function (element) {
                element.remove();
            });
        document
            .querySelectorAll(".esri-popup__pointer")
            .forEach(function (element) {
                element.remove();
            });

        restoreAllSpanColors();
    }

    function closeEditPopup() {
        document.getElementById("editPopup").style.display = "none";
        geojsonLayer.definitionExpression = null;
        currentSelectedPlaceUID = null;
        graphicsLayer.removeAll();
        isAddNewPlace = false;
        selectedRange = null;
        newPlace = {};
    }

    function attachSpanClickEvents(view, span) {
        span.addEventListener("click", function () {
            restoreAllSpanColors();
            const targetId = span.getAttribute("data-uid");
            currentSelectedPlaceUID = targetId;

            if (currentViewMode === "edit") {
                removePopupElements();

                const spanRect = span.getBoundingClientRect();
                let currentFeature = featureMap.get(targetId);

                showEditPlacePopup(
                    spanRect,
                    currentFeature.latitude,
                    currentFeature.longitude,
                    currentFeature.name
                );

                currentSelectedPlaceLatitude = currentFeature.latitude;
                currentSelectedPlaceLongitude = currentFeature.longitude;

                isAddNewPlace = false;
                selectedRange = null;
                newPlace = {};

                //Hide all other points , only show the selected pin
                geojsonLayer.definitionExpression = `id = '${targetId}'`;
            }

            //Mufeng not showing popup after change
            mapShowPopup(view ,targetId);
        });
    }

    function mapShowPopup(view , targetId) {
        geojsonLayer
            .queryFeatures({
                where: `id = '${targetId}'`,
                returnGeometry: true,
                outFields: ["*"],
            })
            .then(function (result) {
                if (result.features.length > 0) {
                    const feature = result.features[0];
                    view.goTo({
                        target: feature.geometry,
                        zoom: view.zoom,
                    }).then(() => {
                        if (currentViewMode === "view") {
                            view.popup.open({
                                location: feature.geometry,
                                features: [feature],
                                title: feature.attributes.name,
                            });
                        }
                    });
                }
            });
    }

    //Show edit popup
    function showEditPlacePopup(
        rangeRect,
        latitudeInput,
        longitudeInput,
        placeName
    ) {
        const editPopup = document.getElementById("editPopup");
        editPopup.style.display = "block";

        const popupRect = editPopup.getBoundingClientRect();

        // Calculate space above and below the span
        const spaceBelow = window.innerHeight - rangeRect.bottom;
        const spaceAbove = rangeRect.top;

        // Decide whether to place the popup above or below
        if (spaceBelow < popupRect.height && spaceAbove > popupRect.height) {
            // Place popup above the span
            const popupY =
                rangeRect.top + window.scrollY - popupRect.height - 10;
            editPopup.style.top = `${popupY}px`;

            const arrowOffset =
                rangeRect.left + rangeRect.width / 2 - popupRect.left;

            // Set the arrow's left position dynamically
            editPopup.style.setProperty("--arrow-left", `${arrowOffset}px`);
            editPopup.classList.add("arrow-up");
            editPopup.classList.remove("arrow-down");
        } else {
            // Place popup below the span
            const popupY = rangeRect.bottom + window.scrollY + 10;
            editPopup.style.top = `${popupY}px`;

            const arrowOffset =
                rangeRect.left + rangeRect.width / 2 - popupRect.left;

            editPopup.style.setProperty("--arrow-left", `${arrowOffset}px`);
            editPopup.classList.add("arrow-down");
            editPopup.classList.remove("arrow-up");
        }

        document.getElementById("latitudeInput").style.backgroundColor =
            "white";
        document.getElementById("longitudeInput").style.backgroundColor =
            "white";
        document.getElementById("changeAllPlace").style.display = "block";

        document.getElementById("latitudeInput").value = latitudeInput;
        document.getElementById("longitudeInput").value = longitudeInput;

        document.getElementById("applyAllCheckboxText").innerText =
            "Apply to all linked '" + placeName + "' places in this Text";

        document.getElementById("applyAllCheckbox").checked = false;
    }

    require([
        "esri/Map",
        "esri/layers/GeoJSONLayer",
        "esri/layers/GraphicsLayer",
        "esri/Graphic",
        "esri/views/MapView",
        "esri/widgets/Expand",
        "esri/widgets/BasemapGallery",
    ], function (
        Map,
        GeoJSONLayer,
        GraphicsLayer,
        Graphic,
        MapView,
        Expand,
        BasemapGallery
    ) {
        loadConfig(urltoload)
            .then((config) => {
                config.data.features.forEach((feature) => {
                    featureMap.set(feature.properties.id, feature.properties);
                });

                const clusterConfig = {
                    type: "cluster",
                    clusterRadius: "100px",
                    // {cluster_count} is an aggregate field containing
                    // the number of features comprised by the cluster
                    popupTemplate: {
                        title: config.clusterPopupTitle,
                        content:
                            "{cluster_count} places in this cluster. Zoom in or click Browse Features.",
                        fieldInfos: [
                            {
                                fieldName: "cluster_count",
                                format: {
                                    places: 0,
                                    digitSeparator: true,
                                },
                            },
                        ],
                    },
                    clusterMinSize: "24px",
                    clusterMaxSize: "60px",
                    labelingInfo: [
                        {
                            deconflictionStrategy: "none",
                            labelExpressionInfo: {
                                expression:
                                    "Text($feature.cluster_count, '#,###')",
                            },
                            symbol: {
                                type: "text",
                                color: config.clusterFontColor,
                                font: {
                                    weight: "bold",
                                    family: "Noto Sans",
                                    size: "12px",
                                },
                            },
                            labelPlacement: "center-center",
                        },
                    ],
                };

                if (config.clusterColor) {
                    clusterConfig.symbol = {
                        type: "simple-marker",
                        style: "circle",
                        color: config.clusterColor,
                        outline: {
                            color: "white",
                        },
                    };
                }

                if (config.textContent && config.textcontexts) {
                    let textContent = config.textContent;
                    let textContexts = config.textcontexts;
                    let markedText = ""; // This will hold the final marked text
                    let lastIndex = 0; // To track the last position processed

                    textContexts.sort((a, b) => a.start_index - b.start_index);

                    textContexts.forEach((context) => {
                        let startIndex = context.start_index;
                        let endIndex = context.end_index;
                        let dataItemUid = context.dataitem_uid;

                        markedText += textContent.slice(lastIndex, startIndex);

                        markedText +=
                            '<span style="background-color: orange; padding:3px; cursor:pointer" ' +
                            'data-uid="' + dataItemUid + '" ' +
                            'data-related="' + context.linked_dataitem_uid + '" ' +
                            'id="' + dataItemUid + '">' +
                            textContent.slice(startIndex, endIndex) +
                            "</span>";

                        lastIndex = endIndex;
                    });

                    markedText += textContent.slice(lastIndex);
                    document.getElementById("textcontent").innerHTML =
                        markedText;
                }else if(config.textContent){
                    document.getElementById("textcontent").innerHTML = config.textContent
                }

                // Pass the updated data with id for each feature to layer
                const blob = new Blob([JSON.stringify(config.data)], {
                    type: "application/json",
                });
                const newurl = URL.createObjectURL(blob);

                geojsonLayer = new GeoJSONLayer({
                    url: newurl,
                    spatialReference: { wkid: 4326 },
                    copyright:
                        "Check copyright and permissions of this dataset at http://tlcmap.org/ghap.",
                    featureReduction: clusterConfig,
                    popupTemplate: loadPopUpTemplate(config),
                    renderer: loadRenderer(config),
                    popupEnabled: config.popupEnabled,
                    outFields: ["*"],
                });

                graphicsLayer = new GraphicsLayer({
                    title: "graphicsLayer",
                });

                let map = new Map({
                    basemap: config.basemap,
                    ground: "world-elevation",
                    layers: [geojsonLayer, graphicsLayer],
                });

                let view = new MapView({
                    container: "viewDiv",
                    center: [131.034742, -25.345113],
                    zoom: 3,
                    map: map,
                });

                geojsonLayer.queryExtent().then(function (results) {
                    // go to the extent of the results satisfying the query
                    view.goTo(results.extent);
                });

                //Switch view mode button behavior
                document
                    .getElementById("switchviewmode")
                    .addEventListener("click", () => {
                        restoreAllSpanColors();

                        const switchButton =
                            document.getElementById("switchviewmode");
                        removePopupElements();
                        if (currentViewMode === "view") {
                            currentViewMode = "edit";
                            document.getElementById(
                                "text"
                            ).style.backgroundColor = "#d7e6fc";
                            switchButton.innerText = "Switch to view mode";
                            currentSelectedPlaceUID = null;
                        } else {
                            currentViewMode = "view";
                            document.getElementById(
                                "text"
                            ).style.backgroundColor = "white";
                            switchButton.innerText = "Switch to edit mode";
                            document.getElementById("editPopup").style.display =
                                "none";

                            geojsonLayer.definitionExpression = null;
                            graphicsLayer.removeAll();
                        }
                    });

                document
                    .getElementById("backtolayer")
                    .addEventListener("click", () => {
                        window.location.href = home_url + '/myprofile/mydatasets/' + config.datasetID;
                    });

                document
                    .querySelectorAll("span[data-uid]")
                    .forEach(function (span) {
                        attachSpanClickEvents(view, span);
                    });

                // Delete button behavior
                document
                    .getElementById("deleteButton")
                    .addEventListener("click", function () {
                        if (
                            currentViewMode == "edit" &&
                            currentSelectedPlaceUID != null
                        ) {
                            let selectedFeatureName = featureMap.get(
                                currentSelectedPlaceUID
                            ).name;
                            let deleteFeatureUIDs = [];

                            if (
                                document.getElementById("applyAllCheckbox")
                                    .checked
                            ) {
                                featureMap.forEach((value, key) => {
                                    if (value.name === selectedFeatureName) {
                                        deleteFeatureUIDs.push(key);
                                    }
                                });
                            } else {
                                deleteFeatureUIDs.push(currentSelectedPlaceUID);
                            }

                            deleteFeatureUIDs.forEach((uid) => {
                                $.ajax({
                                    type: "POST",
                                    url: ajaxdeletedataitem,
                                    data: {
                                        uid: uid,
                                        ds_id: config.datasetID,
                                    },
                                    success: function () {
                                        // Refresh map
                                        refreshGeoJSONLayer(
                                            GeoJSONLayer,
                                            view,
                                            clusterConfig
                                        );

                                        removePopupElements();

                                        //Hide the edit popup
                                        closeEditPopup();

                                        // Remove the span of the selected place
                                        const spanToDelete =
                                            document.querySelector(
                                                `span[data-uid="${uid}"]`
                                            );
                                        if (spanToDelete) {
                                            const parent =
                                                spanToDelete.parentNode;
                                            parent.replaceChild(
                                                document.createTextNode(
                                                    spanToDelete.textContent
                                                ),
                                                spanToDelete
                                            );
                                        }

                                        // Remove the feature from the featureMap
                                        featureMap.delete(uid);
                                    },
                                    error: function (response) {
                                        alert("Error deleting data:", response);
                                    },
                                });
                            });
                        }
                    });

                // Save change button hebavior
                document
                    .getElementById("saveButton")
                    .addEventListener("click", function () {
                        if (
                            currentViewMode == "edit" &&
                            (currentSelectedPlaceUID != null || isAddNewPlace)
                        ) {
                            const latitude =
                                document.getElementById("latitudeInput").value;
                            const longitude =
                                document.getElementById("longitudeInput").value;

                            // Verify that latitude and longitude are valid
                            if (
                                isNaN(latitude) ||
                                isNaN(longitude) ||
                                latitude < -90 ||
                                latitude > 90 ||
                                longitude < -180 ||
                                longitude > 180
                            ) {
                                alert(
                                    "Invalid latitude or longitude values. Please check your input."
                                );
                                return;
                            }

                            if (
                                isAddNewPlace &&
                                newPlace.title &&
                                newPlace.start_index &&
                                newPlace.end_index
                            ) {
                                //Add new place
                                //Add place first
                                $.ajax({
                                    type: "POST",
                                    url: ajaxadddataitem,
                                    data: {
                                        ds_id: newPlace.dataset_id,
                                        title: newPlace.title,
                                        recordtype: "Text",
                                        latitude: latitude,
                                        longitude: longitude,
                                        description : "\"" + newPlace.sentence + "\"",
                                        extendedData: JSON.stringify(
                                            {
                                                offset : newPlace.start_index,
                                            }
                                        ),
                                    },

                                    success: function (result) {
                                        //New add text context
                                        $.ajax({
                                            type: "POST",
                                            url: ajaxaddtextcontent,
                                            data: {
                                                dataitem_uid:
                                                    result.dataitem.uid,
                                                text_id: config.textID,
                                                start_index:
                                                    newPlace.start_index,
                                                end_index: newPlace.end_index,
                                            },
                            
                                            success: function () {
                                                
                                                // Wrap selected text with <span> in the DOM
                                                const span =
                                                    document.createElement(
                                                        "span"
                                                    );
                                                span.style.backgroundColor =
                                                    "orange";
                                                span.style.padding = "3px";
                                                span.style.cursor = "pointer";
                                                span.id = result.dataitem.uid;
                                                span.setAttribute(
                                                    "data-uid",
                                                    result.dataitem.uid
                                                );
                                                span.setAttribute(
                                                    "data-related",
                                                    result.dataitem.linked_dataitem_uid
                                                );

                                                span.innerText = newPlace.title;

                                                selectedRange.deleteContents();
                                                selectedRange.insertNode(span);
                                                window
                                                    .getSelection()
                                                    .removeAllRanges();

                                                attachSpanClickEvents(
                                                    view,
                                                    span
                                                );

                                                //Push to featureMap
                                                featureMap.set(
                                                    result.dataitem.uid,
                                                    {
                                                        id: result.dataitem.uid,
                                                        name: newPlace.title,
                                                        latitude: latitude,
                                                        longitude: longitude,
                                                    }
                                                );

                                                closeEditPopup();
                                                refreshGeoJSONLayer(
                                                    GeoJSONLayer,
                                                    view,
                                                    clusterConfig
                                                );
                                                removePopupElements();
                                            },
                                            error: function (response) {
                                                alert(
                                                    "Error adding text context:",
                                                    response
                                                );
                                            },
                                        });
                                    },
                                    error: function (response) {
                                        alert("Error adding place:", response);
                                    },
                                });
                            } else if (currentSelectedPlaceUID != null) {
                                //Modify existing place
                                //Get selected features
                                let selectedFeatureName = featureMap.get(
                                    currentSelectedPlaceUID
                                ).name;
                                let editedFeatureUIDs = [];
                                if (
                                    document.getElementById("applyAllCheckbox")
                                        .checked
                                ) {
                                    featureMap.forEach((value, key) => {
                                        if (
                                            value.name === selectedFeatureName
                                        ) {
                                            editedFeatureUIDs.push(key);
                                        }
                                    });
                                } else {
                                    editedFeatureUIDs.push(
                                        currentSelectedPlaceUID
                                    );
                                }

                                editedFeatureUIDs.forEach((uid) => {
                                    $.ajax({
                                        type: "POST",
                                        url: ajaxedittextplacecoordinates,
                                        data: {
                                            uid: uid,
                                            latitude: latitude,
                                            longitude: longitude,
                                        },
                                        success: function (result) {
                                            closeEditPopup();

                                            refreshGeoJSONLayer(
                                                GeoJSONLayer,
                                                view,
                                                clusterConfig
                                            );

                                            removePopupElements();

                                            let updated_linked_dataitem_uid = result.linked_dataitem_uid;
                                            //select span , update data-related
                                            const spanToUpdate = document.querySelector(
                                                `span[data-uid="${uid}"]`
                                            );
                                            if (spanToUpdate) {
                                                console.log("spanToUpdate", updated_linked_dataitem_uid);
                                                spanToUpdate.setAttribute(
                                                    "data-related",
                                                    updated_linked_dataitem_uid
                                                );
                                            }

                                            //Update new coordinates in the featureMap
                                            featureMap.get(uid).latitude =
                                                latitude;
                                            featureMap.get(uid).longitude =
                                                longitude;
                                        },
                                        error: function (response) {
                                            alert(
                                                "Error editing place:",
                                                response
                                            );
                                        },
                                    });
                                });
                            }
                        }
                    });

                document
                    .getElementById("unsetButton")
                    .addEventListener("click", function () {
                        if (
                            currentViewMode == "edit" &&
                            currentSelectedPlaceUID != null
                        ) {
                            document.getElementById("latitudeInput").value =
                                currentSelectedPlaceLatitude;
                            document.getElementById("longitudeInput").value =
                                currentSelectedPlaceLongitude;
                            document.getElementById(
                                "latitudeInput"
                            ).style.backgroundColor = "white";
                            document.getElementById(
                                "longitudeInput"
                            ).style.backgroundColor = "white";
                            graphicsLayer.removeAll();
                        }
                    });

                // Click on map behavior
                view.on("click", (event) => {
                    if (
                        currentViewMode == "edit" &&
                        (currentSelectedPlaceUID != null || isAddNewPlace) &&
                        document.getElementById("editPopup").style.display ==
                            "block"
                    ) {
                        event.stopPropagation();

                        graphicsLayer.removeAll();

                        document.getElementById("latitudeInput").value =
                            event.mapPoint.latitude.toFixed(6);
                        document.getElementById("longitudeInput").value =
                            event.mapPoint.longitude.toFixed(6);

                        document.getElementById(
                            "latitudeInput"
                        ).style.backgroundColor = "lightyellow";
                        document.getElementById(
                            "longitudeInput"
                        ).style.backgroundColor = "lightyellow";

                        // Remove the previous div if it exists
                        const pointGraphic = new Graphic({
                            geometry: {
                                type: "point",
                                longitude: event.mapPoint.longitude,
                                latitude: event.mapPoint.latitude,
                            },
                            symbol: {
                                type: "simple-marker",
                                color: "white",
                                outline: { color: "blue", width: 1 },
                            },
                        });

                        // Add the point graphic to the graphics layer
                        graphicsLayer.add(pointGraphic);
                    }
                });

                view.popup.watch("selectedFeature", (selectedFeature) => {
                    // if (currentViewMode == "view"){
                        if (selectedFeature && selectedFeature.attributes) {
                            const attributes = selectedFeature.attributes;
                            if (attributes.id) {
                                highlightPlaceInText(attributes.id);
                            }
                        }
                    //}
                });

                //Add place behaviour// Mufeng, after something need to rebind again.
                document
                    .getElementById("textcontent")
                    .addEventListener("mouseup", () => {
                        if (
                            currentViewMode == "edit" &&
                            currentSelectedPlaceUID == null &&
                            (document.getElementById("editPopup").style
                                .display == "" ||
                                document.getElementById("editPopup").style
                                    .display == null ||
                                document.getElementById("editPopup").style
                                    .display == "none")
                        ) {
                            const selection = window.getSelection();
                            const selectedText = selection.toString();

                            if (selectedText.length > 0) {
                                let range = selection.getRangeAt(0); // Get the range of the selection

                                //Check if the selection include existing place
                                const spansInRange = Array.from(
                                    range
                                        .cloneContents()
                                        .querySelectorAll("span[data-uid]")
                                );
                                if (spansInRange.length > 0) {
                                    alert(
                                        "Selection includes an existing place. Please select a different area."
                                    );
                                    return;
                                }

                                // Ensure selection includes only whole words, allowing full stops and hyphens
                                const wholeWordRegex = /^\b[\w\s.-]+\b$/;
                                if (!wholeWordRegex.test(selectedText)) {
                                    alert(
                                        "Your select should not include partial words."
                                    );
                                    return;
                                }

                                // Get the text content before the selection
                                let textBeforeSelection = "";
                                const beforeRange = document.createRange();
                                beforeRange.setStart(
                                    document.getElementById("textcontent"),
                                    0
                                );
                                beforeRange.setEnd(
                                    range.startContainer,
                                    range.startOffset
                                );
                                textBeforeSelection = beforeRange.toString();

                                // Count occurrences of the selected text in the text before the selection
                                const occurrencesBefore = (
                                    textBeforeSelection.match(
                                        new RegExp(selectedText, "g")
                                    ) || []
                                ).length;

                                // Locate the selected occurrence in config.textContent
                                const textContent = config.textContent;
                                let currentOccurrence = 0;
                                let startIndex = -1;
                                let endIndex = -1;

                                for (let i = 0; i < textContent.length; i++) {
                                    if (
                                        textContent.substr(
                                            i,
                                            selectedText.length
                                        ) === selectedText
                                    ) {
                                        currentOccurrence++;
                                        if (
                                            currentOccurrence ===
                                            occurrencesBefore + 1
                                        ) {
                                            startIndex = i;
                                            endIndex = i + selectedText.length;
                                            break;
                                        }
                                    }
                                }

                                if (startIndex == -1 || endIndex == -1) {
                                    alert(
                                        "Could not find the exact occurrence in the original text content."
                                    );
                                    return;
                                }

                                const sentenceRegex = /[^.!?]*[.!?]/g; // Regex to capture sentences
                                let sentenceContainingSelection = "";
                
                                let match;
                                while ((match = sentenceRegex.exec(textContent)) !== null) {
                                    if (
                                        match.index <= startIndex &&
                                        startIndex < match.index + match[0].length
                                    ) {
                                        sentenceContainingSelection = match[0].trim();
                                        break;
                                    }
                                }
                
                                const rangeRect = range.getBoundingClientRect();
                                showEditPlacePopup(
                                    rangeRect,
                                    null,
                                    null,
                                    selectedText
                                );

                                isAddNewPlace = true;
                                selectedRange = range;
                                newPlace = {
                                    title: selectedText,
                                    start_index: startIndex,
                                    end_index: endIndex,
                                    dataset_id: config.datasetID,
                                    sentence : sentenceContainingSelection
                                };
                            }
                        }
                    });

                //Refresh map behavior
                document
                    .getElementById("refreshMapButton")
                    .addEventListener("click", function () {
                        if (
                            currentViewMode == "edit" &&
                            currentSelectedPlaceUID != null &&
                            document.getElementById("editPopup").style
                                .display == "block"
                        ) {
                            const latitude = parseFloat(
                                document.getElementById("latitudeInput").value
                            );
                            const longitude = parseFloat(
                                document.getElementById("longitudeInput").value
                            );

                            // Verify that latitude and longitude are valid
                            if (
                                isNaN(latitude) ||
                                isNaN(longitude) ||
                                latitude < -90 ||
                                latitude > 90 ||
                                longitude < -180 ||
                                longitude > 180
                            ) {
                                alert(
                                    "Invalid latitude or longitude values. Please check your input."
                                );
                                return;
                            }

                            graphicsLayer.removeAll();

                            const pointGraphic = new Graphic({
                                geometry: {
                                    type: "point",
                                    longitude: longitude,
                                    latitude: latitude,
                                },
                                symbol: {
                                    type: "simple-marker",
                                    color: "white",
                                    outline: { color: "blue", width: 1 },
                                },
                            });

                            // Add the point graphic to the graphics layer
                            graphicsLayer.add(pointGraphic);

                            view.goTo({
                                center: [longitude, latitude],
                                zoom: 11,
                            });
                        }
                    });

                // Remove highlight when popup is closed
                document.addEventListener("click", function (event) {
                    // Check if the clicked element has the specified class
                    if (
                        event.target.classList.contains("esri-popup__icon") &&
                        event.target.classList.contains("esri-icon-close")
                    ) {
                        // Call the restoreAllSpanColors function
                        restoreAllSpanColors();
                    }
                });

                //Info block
                if (config.infoDisplay != "disabled") {
                    const infoDivExpand = new Expand();
                    loadInfoBlock(config, infoDivExpand, view);
                }

                //Basemap gallery block
                if (config.basemapGallery) {
                    let basemapGallery = new BasemapGallery();
                    let bgExpand = new Expand();
                    loadBaseMapGallery(basemapGallery, bgExpand, view);
                }

                // Add switch map style button
                var switchMapType = document.createElement("div");
                switchMapType.className =
                    "esri-icon-globe esri-widget--button esri-widget esri-interactive";
                switchMapType.setAttribute("tabindex", "0");
                switchMapType.setAttribute("data-html", "true");
                switchMapType.setAttribute("data-animation", "true");
                switchMapType.setAttribute("data-toggle", "tooltip");
                switchMapType.setAttribute("data-placement", "top");
                switchMapType.setAttribute("title", "Switch Map type");

                switchMapType.addEventListener("click", () => {
                    if (currentMapType === "cluster") {
                        currentMapType = "feature";
                        geojsonLayer.featureReduction = null;
                    } else {
                        currentMapType = "cluster";
                        geojsonLayer.featureReduction = clusterConfig;
                    }
                });
                view.ui.add(switchMapType, "top-right");

                //Event for "goto" parameter
                const urlParams = new URLSearchParams(window.location.search);
                const gotoId = urlParams.get("goto");

                if (gotoId) {
                    // Highlight the corresponding text span
                    highlightPlaceInText(gotoId);

                    // Show the popup on the map for the corresponding feature
                    mapShowPopup(view , gotoId);
                }
            })
            .catch((err) => console.error(err));
    });
})();
