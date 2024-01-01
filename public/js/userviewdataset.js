$(document).ready(function () {
    //LGA autocomplete.
    $("#editLga").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(lgas, request.term);
            response(results.slice(0, 20)); //return only 20 results
        },
    });
    $("#editLga").autocomplete("option", "appendTo", ".eventInsForm");

    //feature_term autocomplete.
    $("#editFeatureterm").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(feature_terms, request.term);
            response(results.slice(0, 20)); //return only 20 results
        },
    });
    $("#editFeatureterm").autocomplete("option", "appendTo", ".eventInsForm");

    // Datepickers.
    $("#editDateStartDiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });
    $("#editDateEndDiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });

    // Map pickers.
    const addModalMapPicker = new MapPicker($("#addModal .map-picker"));
    addModalMapPicker.init();
    const editModalMapPicker = new MapPicker(
        $("#editDataitemModal .map-picker")
    );
    editModalMapPicker.init();

    // Initialise the extended data editors.
    const addModalExtendedDataEditor = new ExtendedDataEditor(
        "#addModal .extended-data-editor"
    );
    addModalExtendedDataEditor.init();
    const editModalExtendedDataEditor = new ExtendedDataEditor(
        "#editDataitemModal .extended-data-editor"
    );
    editModalExtendedDataEditor.init();

    //Change place order
    var isDraggable = false;
    var orderChanged = false;
    function makeDraggable() {
        $(".place-list").sortable({
            update: function () {
                orderChanged = true;
            },
        });
        $(".place-list").disableSelection();
        $(".place-list .row .dragIcon").css("display", "flex");
    }

    function destroyDraggable() {
        $(".place-list").sortable("destroy");
        $(".place-list .row .dragIcon").css("display", "none");
    }

    $("#toggle-drag").click(function () {
        isDraggable = !isDraggable;
        if (isDraggable) {
            makeDraggable();
        } else {
            if (orderChanged) {
                $.ajax({
                    type: "POST",
                    url: ajaxchangedataitemorder,
                    data: {
                        ds_id: dataset_id,
                        newOrder: $(".place-list").sortable("toArray", {
                            attribute: "data-id",
                        }),
                    },
                    success: function () {
                        location.reload();
                    },
                    error: function (xhr) {
                        alert(xhr.responseText);
                    },
                });
            }
            destroyDraggable();
        }
        $(this).text(isDraggable ? "Save Order" : "Change Order");
    });

    // Handle dataitem delete.
    $(".delete-dataitem-button").on("click", function () {
        const dataitemID = $(this).data("itemId");
        const datasetID = $(this).data("setId");
        if (dataitemID && datasetID) {
            $("#deleteConfirmModal #deleteConfirmButton").data(
                "itemId",
                dataitemID
            );
            $("#deleteConfirmModal #deleteConfirmButton").data(
                "setId",
                datasetID
            );
            $("#deleteConfirmModal").modal("show");
        }
    });

    // When delete confirmed.
    $("#deleteConfirmModal #deleteConfirmButton").on("click", function () {
        const dataitemID = $(this).data("itemId");
        const datasetID = $(this).data("setId");
        if (dataitemID && datasetID) {
            $(this).prop("disabled", "disabled");
            // Delete the dataitem.
            $.ajax({
                type: "POST",
                url: ajaxdeletedataitem,
                data: {
                    id: dataitemID,
                    ds_id: datasetID,
                },
                success: function (result) {
                    $(this).removeProp("disabled");
                    $("#deleteConfirmModal").modal("hide");
                    // Unset IDs.
                    $(this).data("itemId", "");
                    $(this).data("setId", "");
                    location.reload();
                },
                error: function (xhr, textStatus, errorThrown) {
                    $(this).removeProp("disabled");
                    $("#deleteConfirmModal").modal("hide");
                    alert(xhr.responseText); //error message with error info
                },
            });
        }
    });

    /**
     * Set the values of controls in the dataitem editing form.
     *
     * @param {Array} dataitem
     *   The object data of the dataitem.
     */
    const setEditDataitemFormValues = function (dataitem) {
        if (dataitem.title) {
            $("#editTitle").val(dataitem.title);
        }
        if (dataitem.placename) {
            $("#editPlacename").val(dataitem.placename);
        }
        if (dataitem.latitude) {
            $("#editLatitude").val(dataitem.latitude);
        }
        if (dataitem.longitude) {
            $("#editLongitude").val(dataitem.longitude);
        }
        if (dataitem.recordtype_id && dataitem.recordtype) {
            $("#editRecordtype").val(dataitem.recordtype.type);
        }
        if (dataitem.description) {
            tinymce.get("editDescription").setContent(dataitem.description);
        }
        if (dataitem.quantity) {
            $("#editQuantity").val(dataitem.quantity);
        }
        if (dataitem.feature_term) {
            $("#editFeatureterm").val(dataitem.feature_term);
        }
        if (dataitem.state) {
            $("#editState").val(dataitem.state);
        }
        if (dataitem.datestart) {
            $("#editDatestart").val(dataitem.datestart);
        }
        if (dataitem.dateend) {
            $("#editDateend").val(dataitem.dateend);
        }
        if (dataitem.lga) {
            $("#editLga").val(dataitem.lga);
        }
        if (dataitem.external_url) {
            $("#editExternalurl").val(dataitem.external_url);
        }
        if (dataitem.source) {
            tinymce.get("editSource").setContent(dataitem.source);
        }
        if (dataitem.extendedData) {
            const extendedDataEditor = new ExtendedDataEditor(
                "#editDataitemModal .extended-data-editor"
            );
            extendedDataEditor.setData(dataitem.extendedData);
        }
        // Handle Image Display and Label
        if (dataitem.image_path) {
            $("#editImagePreview").attr(
                "src",
                "/storage/images/" + dataitem.image_path
            );
            $("#editImageContainer").show();
        } else {
            $("#editImageContainer").hide();
        }
    };

    /**
     * Get the data to send to the dataitem edit service.
     *
     * @returns {*}
     *   The request data.
     */
    const getEditDataitemRequestData = function () {
        const formData = new FormData();
        formData.append("id", $("#editDataitemModal").data("itemId"));
        formData.append("ds_id", $("#editDataitemModal").data("setId"));
        formData.append("title", $("#editTitle").val());
        formData.append("placename", $("#editPlacename").val());
        formData.append("recordtype", $("#editRecordtype").val());
        formData.append("latitude", $("#editLatitude").val());
        formData.append("longitude", $("#editLongitude").val());
        formData.append(
            "description",
            tinymce.get("editDescription").getContent()
        );
        formData.append("quantity", $("#editQuantity").val());
        formData.append("datestart", $("#editDatestart").val());
        formData.append("dateend", $("#editDateend").val());
        formData.append("state", $("#editState").val());
        formData.append(
            "featureterm",
            $("#editFeatureterm").val().toLowerCase()
        );
        formData.append("lga", $("#editLga").val().toUpperCase());
        formData.append("url", $("#editExternalurl").val());
        formData.append("source", tinymce.get("editSource").getContent());
        formData.append(
            "extendedData",
            JSON.stringify(
                new ExtendedDataEditor(
                    "#editDataitemModal .extended-data-editor"
                ).getData()
            )
        );

        // Handle the image file upload
        if ($("#editImage").length && $("#editImage")[0].files[0]) {
            formData.append("image", $("#editImage")[0].files[0]);
        }

        return formData;
    };

    /**
     * Clear all values in the dataitem editing form.
     */
    const clearEditDataitemFormValues = function () {
        $("#editTitle").val("");
        $("#editPlacename").val("");
        $("#editLatitude").val("");
        $("#editLongitude").val("");
        $("#editRecordtype").val("");
        tinymce.get("editDescription").setContent("");
        $("#editQuantity").val(null);
        $("#editFeatureterm").val("");
        $("#editState").val("");
        $("#editDateStartDiv").datepicker("setDate", null);
        $("#editDateEndDiv").datepicker("setDate", null);
        $("#editLga").val("");
        $("#editExternalurl").val("");
        tinymce.get("editSource").setContent("");
        const extendedDataEditor = new ExtendedDataEditor(
            "#editDataitemModal .extended-data-editor"
        );
        extendedDataEditor.setData(null);
    };

    // Handle dataitem edit.
    $(".edit-dataitem-button").on("click", function () {
        const dataitemID = $(this).data("itemId");
        const datasetID = $(this).data("setId");
        $.ajax({
            type: "GET",
            url: ajaxviewdataitem,
            data: {
                id: dataitemID,
                dataset_id: datasetID,
            },
            success: function (result) {
                setEditDataitemFormValues(result);
                $("#editDataitemModal").data("itemId", dataitemID);
                $("#editDataitemModal").data("setId", datasetID);
                $("#editDataitemModal").modal("show");
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            },
        });
    });

    // Create the message banner for edit modal.
    const msgBanner = new MessageBanner(
        $("#editDataitemModal .message-banner")
    );
    msgBanner.hide();

    // Unset all control values when the modal is hidden.
    $("#editDataitemModal").on("hidden.bs.modal", function () {
        clearEditDataitemFormValues();
        msgBanner.clear();
        msgBanner.hide();
        $("#editDataitemModal").data("itemId", "");
        $("#editDataitemModal").data("setId", "");
    });

    // Refresh the map when the modal is shown.
    $("#editDataitemModal").on("shown.bs.modal", function () {
        editModalMapPicker.refresh();
    });

    // Handle record edit when the save button is clicked.
    $("#editDataitemSaveButton").on("click", function () {
        // Validate the input.
        let isValid = true;
        msgBanner.clear();
        if ($("#editTitle").val() === "") {
            isValid = false;
            msgBanner.error("Title must be filled");
        }
        if ($("#editLatitude").val() === "") {
            isValid = false;
            msgBanner.error("Latitude must be filled");
        } else if (!Validation.latitude($("#editLatitude").val())) {
            isValid = false;
            msgBanner.error("Latitude must be valid from -90 to 90");
        }
        if ($("#editLongitude").val() === "") {
            isValid = false;
            msgBanner.error("Longitude must be filled");
        } else if (!Validation.longitude($("#editLongitude").val())) {
            isValid = false;
            msgBanner.error("Longitude must be valid from -180 to 180");
        }
        if (!Validation.naturalNumber($("#editQuantity").val())) {
            isValid = false;
            msgBanner.error(
                "Quantity must be an integer greater or equal to 0"
            );
        }
        if (
            $("#editDatestart").val() !== "" &&
            !Validation.date($("#editDatestart").val())
        ) {
            isValid = false;
            msgBanner.error("Date Start must be in valid format");
        }
        if (
            $("#editDateend").val() !== "" &&
            !Validation.date($("#editDateend").val())
        ) {
            isValid = false;
            msgBanner.error("Date End must be in valid format");
        }
        if (
            $("#editExternalurl").val() !== "" &&
            !Validation.url($("#editExternalurl").val())
        ) {
            isValid = false;
            msgBanner.error("Linkback must be in valid URL format");
        }
        var file = $("#editImage")[0].files[0];
        if (file && file.size > max_upload_image_size) {
            isValid = false;
            msgBanner.error(
                "The image size should be less than " +
                    Math.floor(max_upload_image_size / (1024 * 1024)) +
                    " MB"
            );
        }

        if (isValid) {
            var saveButton = $(this);
            saveButton.prop("disabled", true);
            // Save the dataitem.
            $.ajax({
                type: "POST",
                url: ajaxeditdataitem,
                data: getEditDataitemRequestData(),
                contentType: false,
                processData: false,
                success: function (result) {
                    saveButton.prop("disabled", true);
                    $("#editDataitemModal").modal("hide");
                    location.reload();
                },
                error: function (xhr, textStatus, errorThrown) {
                    saveButton.prop("disabled", false);
                    alert(xhr.responseText); //error message with error info
                },
            });
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#editDataitemModal .scrollable").scrollTop(0);
        }
    });
});
