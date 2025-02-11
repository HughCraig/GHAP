$(document).ready(function () {
    $("#addDateStartDiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });
    $("#addDateEndDiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });

    $('#addModal').on('hidden.bs.modal', function () {
        $('#related_place_uid').val(null);
    });

    //LGA autocomplete.
    $("#addlga").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(lgas, request.term);
            response(results.slice(0, 20)); //return only 20 results
        },
    });
    $("#addlga").autocomplete("option", "appendTo", ".eventInsForm");

    //feature_term autocomplete.
    $("#addfeatureterm").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(feature_terms, request.term);
            response(results.slice(0, 20)); //return only 20 results
        },
    });
    $("#addfeatureterm").autocomplete("option", "appendTo", ".eventInsForm");

    $("#addNewLayer").on("click", function () {

        tlcMap.ignoreExtentChange = true;
        const currentModal = document.querySelector("#addModal");
        $(currentModal).modal("hide");

        setTimeout(() => {
            const newModal = document.querySelector("#newLayerModal");
            $(newModal).modal("show")
                .on('hide.bs.modal', () => {
                    tlcMap.ignoreExtentChange = true;
                })
                .on('hidden.bs.modal', () => {
                    setTimeout(() => {
                        tlcMap.ignoreExtentChange = false; 
                    }, 500); 
                });

        }, 500); // Delay to allow the current modal to fully hide
    });

    function refreshSelectList() {
        // Clear all existing options.
        $("#chooseLayer").val(null).trigger("change");
        $("#chooseLayer").html('<option value=""></option>');

        $("#chooseLayer").prop("disabled", true);

        if (Array.isArray(userLayers) && userLayers.length > 0) {
            for (let i = 0; i < userLayers.length; i++) {
                if (userLayers[i].name && userLayers[i].id) {
                    const option = new Option(
                        userLayers[i].name,
                        userLayers[i].id,
                        false,
                        false
                    );
                    
                    // Set the data attribute for public status
                    $(option).attr("data-public", userLayers[i].is_public);
                    $("#chooseLayer").append(option).trigger("change");
                }
            }
        } else {
            // If userLayers is empty, set the default option to username's places and select it
            const defaultOption = new Option(
                `${userName}'s places`,
                null,
                true,
                true
            );
            $("#chooseLayer").append(defaultOption).trigger("change");
        }
        $("#chooseLayer").prop("disabled", false);
    }

    /**
     * Get the form data for creating the first layer.
     *
     * @return {FormData} - FormData object with layer details.
     */
    function getAddFirstLayerFormData() {
        const formData = new FormData();
        formData.append("dsn", `${userName}'s places`);
        formData.append("description", `${userName}'s first layer`);
        formData.append("recordtype", "Other");
        formData.append("public", 1); // Public layer
        formData.append("allowanps", 0);
        formData.append("redirect", false);

        return formData;
    }

    /**
     * Add a place to a layer using AJAX.
     *
     * @param {FormData} addPlaceFormData - FormData object with place details.
     */
    function addPlaceToLayer(addPlaceFormData , is_public = true) {
        $.ajax({
            type: "POST",
            url: ajaxadddataitem,
            data: addPlaceFormData,
            contentType: false,
            processData: false,
            success: function (result) {
                var lng = $("#addlongitude").val();
                var lat = $("#addlatitude").val();

                $("#addModal").modal("hide");

                // Zoom to the new place.
                $("#customPlaceDiv").remove();
                if(is_public){
                    tlcMap.isSearchOn = false;
                    tlcMap.ignoreExtentChange = false;
                    tlcMap.dataitems = null;
                    updateUrlParameters(null);
                    tlcMap.zoomTo(parseFloat(lng), parseFloat(lat), 20);
                }
                tlcMap.graphicsLayer.removeAll();
            },
            error: function (xhr) {
                var result = xhr.responseJSON;
                if (result.hasOwnProperty("e1") && result.e1 === false)
                    document
                        .getElementById("adddatestart")
                        .classList.add("is-invalid");
                else
                    document
                        .getElementById("adddatestart")
                        .classList.remove("is-invalid");
                if (result.hasOwnProperty("e2") && result.e2 === false)
                    document
                        .getElementById("adddateend")
                        .classList.add("is-invalid");
                else
                    document
                        .getElementById("adddateend")
                        .classList.remove("is-invalid");
                if (result.hasOwnProperty("error")) alert(result.error);
                else alert(xhr.responseText); //error message with error info
            },
        });
    }

    refreshSelectList();

    const addModalExtendedDataEditor = new ExtendedDataEditor(
        "#addModal .extended-data-editor"
    );
    addModalExtendedDataEditor.init();

    $("main").on("click", "#add_place_button_submit", function () {
        let isValid = validateAddDataRequestData(msgBanner);
        //Validate layer id.
        if ($("#chooseLayer").val() === "") {
            isValid = false;
            msgBanner.error("A layer must be selected");
        }

        let is_dataset_public = $("#chooseLayer option:selected").data("public");

        let addPlaceFormData = getAddDataitemRequestData();
        addPlaceFormData.append("ds_id", $("#chooseLayer").val());

        if (isValid) {
            //If User have no layer, create new layer first.
            if ($("#chooseLayer").val() === "null") {
                const createLayerFormData = getAddFirstLayerFormData();
                $.ajax({
                    type: "POST",
                    url: "/myprofile/mydatasets/newdataset/create", //'User\UserController@createNewDataset'
                    data: createLayerFormData,
                    contentType: false,
                    processData: false,
                    headers: {
                        Accept: "application/json",
                    },
                    success: function (result) {
                        addPlaceFormData.append("ds_id", result.dataset_id);
                        addPlaceToLayer(addPlaceFormData , result.is_public);
                    },
                    error: function (xhr) {
                        alert("Create layer failed");
                    },
                });
            } else {
                addPlaceToLayer(addPlaceFormData , is_dataset_public);
            }
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#addModal .scrollable").scrollTop(0);
        }
    });
});
