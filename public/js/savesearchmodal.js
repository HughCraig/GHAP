$(document).ready(function () {
    /* Get CSRF token for POST and add it to the AJAX header */
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    const msgBanner = new MessageBanner($("#saveSearchModal .message-banner"));
    msgBanner.hide();

    // Datepickers for Temporal Coverage.
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

    // Subject keywords
    $("#save_search_tags").tagsInput({
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
    $("#tags_tagsinput").addClass("form-control").addClass("mb-2");

    function getAddSavedSearchFormValues() {
        const searchquery = $("#save_search_query").val();
        const count = $("#save_search_count").val();

        const name = $("#save_search_name").val();
        const description = tinymce.get("save_search_description").getContent();
        const recordType = $("#save_search_recordtype").val();
        const tags = $("#save_search_tags").val();
        const warning = tinymce.get("save_search_warning").getContent();

        const latitudefrom = $("#save_search_latitudefrom").val();
        const longitudefrom = $("#save_search_longitudefrom").val();
        const latitudeto = $("#save_search_latitudeto").val();
        const longitudeto = $("#save_search_longitudeto").val();

        const temporalfrom = $("#save_search_temporalfrom").val();
        const temporalto = $("#save_search_temporalto").val();

        return {
            searchquery: searchquery,
            count: count,
            name: name !== "" ? name : null,
            description: description !== "" ? description : null,
            recordtype: recordType ? recordType : null,
            tags: tags ? tags : null,
            warning: warning ? warning : null,
            latitudefrom: latitudefrom ? latitudefrom : null,
            longitudefrom: longitudefrom ? longitudefrom : null,
            latitudeto: latitudeto ? latitudeto : null,
            longitudeto: longitudeto ? longitudeto : null,
            temporalfrom: temporalfrom ? temporalfrom : null,
            temporalto: temporalto ? temporalto : null,
        };
    }

    function validateAddSavedSearchFormValues(inputs) {
        let isValid = true;
        msgBanner.clear();

        if (!inputs.name) {
            isValid = false;
            msgBanner.error("Search name must be filled");
        }

        if (!inputs.description) {
            isValid = false;
            msgBanner.error("Search description must be filled");
        }

        if (inputs.latitudefrom && !Validation.latitude(inputs.latitudefrom)) {
            isValid = false;
            msgBanner.error("Latitude from must be valid from -90 to 90");
        }
        if (
            inputs.longitudefrom &&
            !Validation.longitude(inputs.longitudefrom)
        ) {
            isValid = false;
            msgBanner.error("Longitude from must be valid from -180 to 180");
        }
        if (inputs.latitudeto && !Validation.latitude(inputs.latitudeto)) {
            isValid = false;
            msgBanner.error("Latitude to must be valid from -90 to 90");
        }
        if (inputs.longitudeto && !Validation.longitude(inputs.longitudeto)) {
            isValid = false;
            msgBanner.error("Longitude to must be valid from -180 to 180");
        }

        if (inputs.temporalfrom && !Validation.date(inputs.temporalfrom)) {
            isValid = false;
            msgBanner.error("Temporal from must be valid date");
        }

        if (inputs.temporalto && !Validation.date(inputs.temporalto)) {
            isValid = false;
            msgBanner.error("Temporal to must be valid date");
        }

        return isValid;
    }

    /* Use AJAX to get values from form */
    $("#save_search_button").click(function () {
        const inputs = getAddSavedSearchFormValues();
        let isValid = validateAddSavedSearchFormValues(inputs);

        if (isValid) {
            $.ajax({
                type: "POST",
                url: ajaxsavesearch,
                data: inputs,
                success: function (data) {
                    $("#saveSearchModalButton").hide();
                    $("#save_search_name").hide();
                    $("#save_search_message").show();
                    $("#saveSearchModal").modal("hide");
                },
                error: function (xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                },
            });
        } else {
            // Display and scroll to the message banner.
            msgBanner.show();
            $("#saveSearchModal").scrollTop(0);
        }
    });
});
