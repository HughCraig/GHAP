$(document).ready(function () {
    // Datepickers.
    $("#temporalfromdiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });
    $("#temporaltodiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });

    //Initiate jQuery tagsInput function AND Adjust the settings for the tags field
    $("#tags").tagsInput({
        height: "50px",
        width: "100%",
        interactive: true,
        defaultText: "add a tag",
        delimiter: [",", ";"], // Or a string with a single delimiter. Ex: ';'
        removeWithBackspace: true,
        minChars: 0,
        maxChars: 0, // if not provided there is no limit
        placeholderColor: "#666666",
        overflow: "auto",
    });

    //Make it look like the other inputs
    $("#tags_tagsinput").addClass("form-control").addClass("mb-2");

    $(".add_layer_button_back").on("click", function () {
        const currentModal = document.querySelector("#newLayerModal");
        $(currentModal).modal("hide");

        setTimeout(() => {
            const newModal = document.querySelector("#addModal");
            $(newModal).modal("show");
        }, 500); // Delay to allow the current modal to fully hide
    });

    // Create message banner for add dataitem modal.
    const msgBanner = new MessageBanner($("#newLayerModal .message-banner"));
    msgBanner.hide();

    /**
     * Validate the input for adding a data item.
     *
     * @param {MessageBanner} msgBanner
     *  The message banner to display errors.
     *
     */
    const validateAddLayerRequestData = function (msgBanner) {
        // Validate the input.
        let isValid = true;
        msgBanner.clear();

        if ($("#layername").val() === "") {
            isValid = false;
            msgBanner.error("Layer Title must be filled");
        }

        if (tinymce.get("description").getContent() === "") {
            isValid = false;
            msgBanner.error("Description must be filled");
        }

        if ($("#latitudefrom").val() !== "") {
            if (!Validation.latitude($("#latitudefrom").val())) {
                isValid = false;
                msgBanner.error("From Latitude must be valid from -90 to 90");
            }
        }

        if ($("#latitudeto").val() !== "") {
            if (!Validation.latitude($("#latitudeto").val())) {
                isValid = false;
                msgBanner.error("To Latitude must be valid from -90 to 90");
            }
        }

        if ($("#longitudefrom").val() !== "") {
            if (!Validation.longitude($("#longitudefrom").val())) {
                isValid = false;
                msgBanner.error(
                    "From Longitude must be valid from -180 to 180"
                );
            }
        }

        if ($("#longitudeto").val() !== "") {
            if (!Validation.longitude($("#longitudeto").val())) {
                isValid = false;
                msgBanner.error("To Longitude must be valid from -180 to 180");
            }
        }

        if (
            $("#temporalfrom").val() !== "" &&
            !Validation.date($("#temporalfrom").val())
        ) {
            isValid = false;
            msgBanner.error("Date Start must be in valid format");
        }
        if (
            $("#temporalto").val() !== "" &&
            !Validation.date($("#temporalto").val())
        ) {
            isValid = false;
            msgBanner.error("Date End must be in valid format");
        }

        if (
            $("#created").val() !== "" &&
            !Validation.date($("#created").val())
        ) {
            isValid = false;
            msgBanner.error("Date created must be in valid format");
        }

        var file = $("#datasetAddImage")[0].files[0];
        if (file && file.size > max_upload_image_size) {
            isValid = false;
            msgBanner.error(
                "The image size should be less than " +
                    Math.floor(max_upload_image_size / (1024 * 1024)) +
                    " MB"
            );
        }

        return isValid;
    };

    function removeExistingSelections() {
        $("#layername").val(null).trigger("change");
        $("#tags").val(null).trigger("change");
        $("#creator").val(null).trigger("change");
        $("#publisher").val(null).trigger("change");
        $("#contact").val(null).trigger("change");
        $("#doi").val(null).trigger("change");
        $("#source_url").val(null).trigger("change");
        $("#linkback").val(null).trigger("change");
        $("#language").val(null).trigger("change");
        $("#latitudefrom").val(null).trigger("change");
        $("#longitudefrom").val(null).trigger("change");
        $("#latitudeto").val(null).trigger("change");
        $("#longitudeto").val(null).trigger("change");
        $("#license").val(null).trigger("change");
        $("#temporalfrom").val(null).trigger("change");
        $("#temporalto").val(null).trigger("change");
        $("#created").val(null).trigger("change");
        tinymce.get("description").setContent("");
        tinymce.get("citation").setContent("");
        tinymce.get("warning").setContent("");
        tinymce.get("rights").setContent("");
        $("#datasetAddImage").val(null).trigger("change");

    }
    /**
     * Get the form data for creating the first layer.
     *
     * @return {FormData} - FormData object with layer details.
     */
    function getAddLayerRequestData() {
        const formData = new FormData();
        formData.append("dsn", $("#layername").val());
        formData.append("tags", $("#tags").val());
        formData.append(
            "recordtype",
            $("#layerrecordtype").children("option:selected").val()
        );

        formData.append(
            "public",
            $("#public").children("option:selected").val()
        );

        formData.append(
            "allowanps",
            $("#allowanps").children("option:selected").val()
        );

        formData.append("creator", $("#creator").val());
        formData.append("publisher", $("#publisher").val());

        formData.append("contact", $("#contact").val());

        formData.append("doi", $("#doi").val());

        formData.append("source_url", $("#source_url").val());
        formData.append("linkback", $("#linkback").val()); // Private layer
        formData.append("language", $("#language").val());

        formData.append("latitudefrom", $("#latitudefrom").val());
        formData.append("longitudefrom", $("#longitudefrom").val());
        formData.append("latitudeto", $("#latitudeto").val());
        formData.append("longitudeto", $("#longitudeto").val());

        formData.append("license", $("#license").val());
        if ($("#datasetAddImage").length && $("#datasetAddImage")[0].files[0]) {
            formData.append("image", $("#datasetAddImage")[0].files[0]);
        }
        formData.append("temporalfrom", $("#temporalfrom").val());
        formData.append("temporalto", $("#temporalto").val());

        formData.append("created", $("#created").val());
        formData.append("description", tinymce.get("description").getContent());
        formData.append("citation", tinymce.get("citation").getContent());
        formData.append("warning", tinymce.get("warning").getContent());
        formData.append("rights", tinymce.get("rights").getContent());
        formData.append("redirect", false);

        return formData;
    }

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
                    const isPublic = result.is_public; 

                    const new_layer_option = new Option(
                        new_layer_name,
                        new_layer_id,
                        true,
                        true
                    );
                    $(new_layer_option).attr("data-public", isPublic);
                    $("#chooseLayer").append(new_layer_option).trigger("change");

                    removeExistingSelections();
                    const currentModal = document.querySelector("#newLayerModal");
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
