/* Get CSRF token for POST and add it to the AJAX header */
var token = $('input[name="csrf-token"]').attr("value");
$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

/**
 * Calls the above function but also changes the css input box on valid / invalid
 */
function validateUrlAndUpdateCss(e) {
    var caller = e.target || e.srcElement;
    $(caller).removeClass(["forcevalidborder", "forceinvalidborder"]);
    if (caller.value != "") {
        if (isValidURL(caller.value)) $(caller).addClass("forcevalidborder");
        else $(caller).addClass("forceinvalidborder");
    }
}

//Similar to above but for DOI
function validateDoiAndUpdateCss(e) {
    var caller = e.target || e.srcElement;
    $(caller).removeClass(["forcevalidborder", "forceinvalidborder"]);
    if (caller.value != "") {
        if (isValidDOI(caller.value)) $(caller).addClass("forcevalidborder");
        else $(caller).addClass("forceinvalidborder");
    }
}

$("#addexternalurl").on("input", validateUrlAndUpdateCss); //adddataitem modal linkback url

$("[name='external_url']").on("input", validateUrlAndUpdateCss); //data table linkback url

$("[name='source_url']").on("input", validateUrlAndUpdateCss); //adddataitem modal source url

//Edit dataset modal or New dataset form submission
$("#edit_dataset_form, #new_dataset_form").on("submit", function () {
    var temporalfrom = document.getElementById("temporalfrom");
    var temporalto = document.getElementById("temporalto");
    temporalfrom.classList.remove("is-invalid");
    temporalto.classList.remove("is-invalid");
    if (temporalfrom.value && !dateMatchesRegex(temporalfrom.value)) {
        temporalfrom.classList.add("is-invalid");
        alert('Temporal Coverage "From" field is NOT in a valid format!');
        return false;
    }
    if (temporalto.value && !dateMatchesRegex(temporalto.value)) {
        temporalto.classList.add("is-invalid");
        alert('Temporal Coverage "To" field is NOT in a valid format!');
        return false;
    }
});

//edit dataset modal or new dataset DOI validator
$("[name='doi']").on("input", validateDoiAndUpdateCss); //data table linkback url

/*
 *  DELETING DATA ITEMS
 */

/* Delete data item button */
$("main").on("click", '[name="delete_dataitem_button"]', function () {
    var id = this.id.split("_")[3]; //id will be delete_dataitem_button_##, we jst want the number
    var ds_id = $("#ds_id").val(); //the id of the dataset we are deleting from
    var row_id = "#row_id_" + id; //get the id of the row to be deleted, id will be row_id_##
    $.ajax({
        type: "POST",
        url: ajaxdeletedataitem,
        data: {
            id: id,
            ds_id: ds_id,
        },
        success: function (result) {
            $(row_id).remove();
            $("#dsupdatedat").text(result.time);
            $("#dscount").text(result.count);

            //jQuery datatable updating
            $("#dataitemtable").DataTable().row(row_id).remove().draw();
        },
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        },
    });
});

// Create message banner for add dataitem modal.
const msgBanner = new MessageBanner($("#addModal .message-banner"));
msgBanner.hide();

/**
 * Get the data to send to the dataitem add service.
 *
 * @returns {*}
 *   The request data.
 */
const getAddDataitemRequestData = function () {
    const formData = new FormData();

    formData.append("ds_id", $("#ds_id").val());
    formData.append("title", $("#addtitle").val());
    formData.append("placename", $("#addplacename").val());
    formData.append(
        "recordtype",
        $("#addrecordtype").children("option:selected").val()
    );
    formData.append("latitude", $("#addlatitude").val());
    formData.append("longitude", $("#addlongitude").val());
    formData.append("description", tinymce.get("adddescription").getContent());
    formData.append("quantity", $("#addquantity").val());
    formData.append("routeId", $("#addRouteId").val());
    formData.append("routeOriId", $("#addRouteOriId").val());
    formData.append("routeTitle", $("#addRouteTitle").val());
    formData.append("datestart", $("#adddatestart").val());
    formData.append("dateend", $("#adddateend").val());
    formData.append("state", $("#addstate").children("option:selected").val());
    formData.append("featureterm", $("#addfeatureterm").val().toLowerCase());
    formData.append("lga", $("#addlga").val().toUpperCase());
    formData.append("parish", $("#addparish").val());
    formData.append("source", tinymce.get("addsource").getContent());
    formData.append("url", $("#addexternalurl").val());
    formData.append(
        "extendedData",
        JSON.stringify(
            new ExtendedDataEditor("#addModal .extended-data-editor").getData()
        )
    );
    // image file upload
    if ($("#addImage").length && $("#addImage")[0].files[0]) {
        formData.append("image", $("#addImage")[0].files[0]);
    }

    return formData;
};

/*
 *  ADDING DATA ITEMS

    changed to bootstrap modal
 */

/* Add data item was clicked */
$("main").on("click", "#add_dataitem_button_submit", function () {
    // Validate the input.
    let isValid = true;
    msgBanner.clear();
    if ($("#addtitle").val() === "") {
        isValid = false;
        msgBanner.error("Title must be filled");
    }
    if ($("#addlatitude").val() === "") {
        isValid = false;
        msgBanner.error("Latitude must be filled");
    } else if (!Validation.latitude($("#addlatitude").val())) {
        isValid = false;
        msgBanner.error("Latitude must be valid from -90 to 90");
    }
    if ($("#addlongitude").val() === "") {
        isValid = false;
        msgBanner.error("Longitude must be filled");
    } else if (!Validation.longitude($("#addlongitude").val())) {
        isValid = false;
        msgBanner.error("Longitude must be valid from -180 to 180");
    }
    if (
        $("#addquantity").val() !== "" &&
        !Validation.naturalNumber($("#addquantity").val())
    ) {
        isValid = false;
        msgBanner.error("Quantity must be an integer greater or equal to 0");
    }
    var routeIdValue = $("#addRouteId").val();
    if (
        routeIdValue !== "" &&
        routeIdValue !== "0" &&
        !Validation.naturalNumber($("#addRouteId").val())
    ) {
        isValid = false;
        msgBanner.error(
            "GHAP Route ID must be an integer greater or equal to 1"
        );
    }
    if (
        $("#adddatestart").val() !== "" &&
        !Validation.date($("#adddatestart").val())
    ) {
        isValid = false;
        msgBanner.error("Date Start must be in valid format");
    }
    if (
        $("#adddateend").val() !== "" &&
        !Validation.date($("#adddateend").val())
    ) {
        isValid = false;
        msgBanner.error("Date End must be in valid format");
    }
    if (
        $("#addexternalurl").val() !== "" &&
        !Validation.url($("#addexternalurl").val())
    ) {
        isValid = false;
        msgBanner.error("Linkback must be in valid URL format");
    }
    var file = $("#addImage")[0].files[0];
    if (file && file.size > max_upload_image_size) {
        isValid = false;
        msgBanner.error(
            "The image size should be less than " +
                Math.floor(max_upload_image_size / (1024 * 1024)) +
                " MB"
        );
    }
    if (isValid) {
        $.ajax({
            type: "POST",
            url: ajaxadddataitem,
            data: getAddDataitemRequestData(),
            contentType: false,
            processData: false,
            success: function (result) {
                if (
                    result.hasOwnProperty("addRouteWarning") &&
                    result.addRouteWarning !== null
                ) {
                    sessionStorage.setItem(
                        "userViewDSMsgBanner",
                        result.addRouteWarning
                    );
                }
                location.reload();
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
                if (result.hasOwnProperty("eQTY") && result.eQTY === false)
                    document
                        .getElementById("addquantity")
                        .classList.add("is-invalid");
                else
                    document
                        .getElementById("addquantity")
                        .classList.remove("is-invalid");
                if (
                    result.hasOwnProperty("routeId") &&
                    result.eRouteId === false
                )
                    document
                        .getElementById("addRouteId")
                        .classList.add("is-invalid");
                else
                    document
                        .getElementById("addRouteId")
                        .classList.remove("is-invalid");
                if (result.hasOwnProperty("error")) alert(result.error);
                else alert(xhr.responseText); //error message with error info
            },
        });
    }
});

window.onload = function () {
    if (sessionStorage.getItem("userViewDSMsgBanner") !== null) {
        const prevDiv = document.querySelector(`.row.mt-3`);
        prevDiv.insertAdjacentHTML(
            "afterend",
            "<div class='pt-4 pb-4 mb-3' id='userViewDatasetMsg'></div>"
        );
        const userViewDSMsgBanner = new MessageBanner($("#userViewDatasetMsg"));
        userViewDSMsgBanner.hide();
        userViewDSMsgBanner.clear();
        userViewDSMsgBanner.warning(
            sessionStorage.getItem("userViewDSMsgBanner")
        );
        userViewDSMsgBanner.show();
        sessionStorage.removeItem("userViewDSMsgBanner");
    }
    if (sessionStorage.getItem("siblingDataId") !== null) {
        // Create message banner for the editted dataitem
        const dataIdValue = sessionStorage.getItem("siblingDataId");
        const insertPosition = sessionStorage.getItem("insertPosition");
        const diDiv = document.querySelector(`[data-id="${dataIdValue}"]`);
        diDiv.insertAdjacentHTML(
            insertPosition,
            "<div class='pt-4 pb-4 mb-3' id='userViewDataitemMsg'></div>"
        );

        const userViewDIMsgBanner = new MessageBanner(
            $("#userViewDataitemMsg")
        );
        userViewDIMsgBanner.clear();
        userViewDIMsgBanner.warning(
            sessionStorage.getItem("userViewDIMsgBanner")
        );
        userViewDIMsgBanner.show();
        sessionStorage.removeItem("userViewDIMsgBanner");
        sessionStorage.removeItem("siblingDataId");
        sessionStorage.removeItem("insertPosition");
    }
};

/* Show edit controls for this dataitem */
$("main").on("click", '[name="edit_dataitem_button_show"]', function () {
    var id = this.id.split("_")[4]; //id will be edit_dataitem_button_show_##, we jst want the number

    //show submit and cancel buttons
    $("#edit_dataitem_button_" + id).removeClass("hideme");
    $("#edit_dataitem_button_cancel_" + id).removeClass("hideme");

    //hide edit_dataitem_button_show for ALL
    $("[name='edit_dataitem_button_show']").addClass("hideme");

    //enable inputs, add a border to show theyre now editable
    $("#row_id_" + id + " td input").removeAttr("disabled");
    $("#row_id_" + id + " td select").removeAttr("disabled");
    $("#row_id_" + id + " td input").addClass("editingtd");
    $("#row_id_" + id + " td SELECT").addClass("editingtd");

    //for this external_url make it no longer clickable
    $("#row_id_" + id + ' td [name="external_url"]').removeClass(
        "external_url_clickable"
    );
    $("#row_id_" + id + ' td [name="external_url"]').removeClass(
        "forceinvalid"
    );
});

/* Cancel edits for this dataitem */
$("main").on("click", '[name="edit_dataitem_button_cancel"]', function () {
    var id = this.id.split("_")[4]; //id will be edit_dataitem_button_cancel_##, we jst want the number

    //disable inputs, remove border
    $("#row_id_" + id + " td input").attr("disabled", "true");
    $("#row_id_" + id + " td SELECT").attr("disabled", "true");
    $("#row_id_" + id + " td input").attr("class", "inputastd");
    $("#row_id_" + id + " td SELECT").removeClass("editingtd");

    //Return values back to their originals
    $("#row_id_" + id + " td input").each(function () {
        $(this).val($(this).attr("oldvalue")); //set each input's value to its old value
    });
    $("#row_id_" + id + " td select").each(function () {
        $(this).val($(this).attr("oldvalue")); //set each input's value to its old value
    });

    //add clickable back to external_url IF it is a valid url
    var linkback = $("#row_id_" + id + ' td [name="external_url"]');
    if (isValidURL(linkback.val())) linkback.addClass("external_url_clickable");
    else linkback.addClass("forceinvalid");

    //select the old value for feature term
    if ($(this).attr("oldvalue"))
        $(this)
            .find('option[value="' + $(this).attr("oldvalue") + '"]')
            .attr("selected", "selected");

    //hide submit and cancel buttons
    $("#edit_dataitem_button_" + id).addClass("hideme");
    $("#edit_dataitem_button_cancel_" + id).addClass("hideme");

    //show edit_dataitem_button_show for ALL
    $("[name='edit_dataitem_button_show']").removeClass("hideme");
});

/* Submit edits for this dataitem */
// Ivy's note: It seems that it's not used in user view dataset edition.
$("main").on("click", '[name="edit_dataitem_button"]', function () {
    var ds_id = $("#ds_id").val(); //the id of the dataset we are editing
    var id = this.id.split("_")[3]; //id will be edit_dataitem_button_##, we jst want the number

    //get new values for inputs
    var title = $("#row_id_" + id + " td")
        .find("#title")
        .val();
    var placename = $("#row_id_" + id + " td")
        .find("#placename")
        .val();
    var recordtype = $("#row_id_" + id + " td")
        .find("#recordtype")
        .children("option:selected")
        .val();
    var latitude = $("#row_id_" + id + " td")
        .find("#latitude")
        .val();
    var longitude = $("#row_id_" + id + " td")
        .find("#longitude")
        .val();
    var description = $("#row_id_" + id + " td")
        .find("#description")
        .val();
    // var quantity = $("#row_id_" + id + " td")
    //     .find("#quantity")
    //     .val();
    // var routeId = $("#row_id_" + id + " td")
    //     .find("#routeId")
    //     .val();
    // var routeOriId = $("#row_id_" + id + " td")
    //     .find("#quantity")
    //     .val();
    // var routeTitle = $("#row_id_" + id + " td")
    //     .find("#quantity")
    //     .val();
    var datestart = $("#row_id_" + id + " td")
        .find("#datestart")
        .val();
    var dateend = $("#row_id_" + id + " td")
        .find("#dateend")
        .val();
    var state = $("#row_id_" + id + " td")
        .find("#state")
        .children("option:selected")
        .val();
    var featureterm = $("#row_id_" + id + " td")
        .find("#feature_term")
        .val()
        .toLowerCase();
    var lga = $("#row_id_" + id + " td")
        .find("#lga")
        .val()
        .toUpperCase();
    var parish = $("#row_id_" + id + " td")
        .find("#parish")
        .val();
    var source = $("#row_id_" + id + " td")
        .find("#source")
        .val();
    var url = $("#row_id_" + id + " td")
        .find("#external_url")
        .val();

    //disable inputs, remove border
    $("#row_id_" + id + " td input").attr("disabled", "true");
    $("#row_id_" + id + " td input").attr("class", "inputastd");
    $("#row_id_" + id + " td select").attr("disabled", "true");
    $("#row_id_" + id + " td select").removeClass("editingtd");

    //add clickable back to external_url IF it is a valid url
    var linkback = $("#row_id_" + id + ' td [name="external_url"]');
    if (isValidURL(linkback.val())) linkback.addClass("external_url_clickable");
    else linkback.addClass("forceinvalid");

    //hide submit and cancel buttons
    $("#edit_dataitem_button_" + id).addClass("hideme");
    $("#edit_dataitem_button_cancel_" + id).addClass("hideme");

    //show edit_dataitem_button_show for ALL
    $("[name='edit_dataitem_button_show']").removeClass("hideme");

    //AJAX query to update the data in DB
    $.ajax({
        type: "POST",
        url: ajaxeditdataitem,
        data: {
            ds_id: ds_id,
            id: id,
            title: title,
            placename: placename,
            recordtype: recordtype,
            description: description,
            latitude: latitude,
            longitude: longitude,
            quantity: quantity,
            datestart: datestart,
            dateend: dateend,
            state: state,
            featureterm: featureterm,
            lga: lga,
            parish: parish,
            source: source,
            url: url,
        },
        success: function (result) {
            var row_id = "#row_id_" + id; //get the id of the row
            //data in the table should already be updated from before
            //update oldvalues to be equal to the new values
            $("#row_id_" + id + " td input").each(function () {
                $(this).attr("oldvalue", $(this).val()); //set each input's oldvalue to its new value
                $(this).attr("value", $(this).val());
            });

            $("#row_id_" + id + " #updatedat").text(result.time);
            $("#dsupdatedat").text(result.time);

            //update datestart and dateend to correct format if alternate format was entered
            new_start = result.datestart ? result.datestart : datestart;
            new_end = result.dateend ? result.dateend : dateend;

            //Edit the input values to reflect the converted dates
            $("#row_id_" + id + " #datestart")
                .attr({
                    oldvalue: new_start,
                    value: new_start,
                    text: new_start,
                })
                .prop({ value: new_start });
            $("#row_id_" + id + " #dateend")
                .attr({
                    oldvalue: new_end,
                    value: new_end,
                    text: new_end,
                })
                .prop({ value: new_end });

            //Turn the url into a link
            //$('#row_id_'+id+' #external_url').attr({ 'oldvalue': '<a href="'+url+'">'+url, 'value': '<a href="'+url+'">'+url, 'text': '<a href="'+url+'">'+url });

            //jQuery datataables updating search/sort values
            var table = $("#dataitemtable").DataTable();
            var myrow = table.row(row_id).node();
            $("#row_id_" + id + " td:eq(0)").attr({
                "data-order": title,
                "data-search": title,
            });
            $("#row_id_" + id + " td:eq(0)").attr({
                "data-order": placename,
                "data-search": placename,
            }); //we can just use the data from the original request as it hasnt changed
            $("#row_id_" + id + " td:eq(1)").attr({
                "data-order": description,
                "data-search": description,
            });
            $("#row_id_" + id + " td:eq(2)").attr({
                "data-order": latitude,
                "data-search": latitude,
            });
            $("#row_id_" + id + " td:eq(3)").attr({
                "data-order": longitude,
                "data-search": longitude,
            });
            $("#row_id_" + id + " td:eq(4)").attr({
                "data-order": new_start,
                "data-search": new_start,
            });
            $("#row_id_" + id + " td:eq(5)").attr({
                "data-order": new_end,
                "data-search": new_end,
            });
            $("#row_id_" + id + " td:eq(6)").attr({
                "data-order": quantity,
                "data-search": quantity,
            });
            $("#row_id_" + id + " td:eq(7)").attr({
                "data-order": state,
                "data-search": state,
            });
            $("#row_id_" + id + " td:eq(8)").attr({
                "data-order": featureterm,
                "data-search": featureterm,
            });
            $("#row_id_" + id + " td:eq(9)").attr({
                "data-order": lga,
                "data-search": lga,
            });
            $("#row_id_" + id + " td:eq(10)").attr({
                "data-order": parish,
                "data-search": parish,
            });
            $("#row_id_" + id + " td:eq(11)").attr({
                "data-order": source,
                "data-search": source,
            });
            $("#row_id_" + id + " td:eq(12)").attr({
                "data-order": url,
                "data-search": url,
            });
            table.row(row_id).invalidate().draw();
            $(myrow)
                .css("background-color", "yellow")
                .animate({ "background-color": "inherit" }, 5000);
        },
        error: function (xhr, textStatus, errorThrown) {
            //Return values back to their originals
            $("#row_id_" + id + " td input").each(function () {
                $(this).val($(this).attr("oldvalue")); //set each input's value to its old value
            });
            var result = xhr.responseJSON;
            if (result.hasOwnProperty("error")) alert(result.error);
            else alert(xhr.responseText); //error message with error info
        },
    });
});
