$(document).ready(function () {
    const msgBanner = new MessageBanner(
        $("#savesearcheditmetadatamodal .message-banner")
    );
    msgBanner.hide();

    // Init datatable.
    $("#savedsearchestable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[0, "asc"]],
        aoColumnDefs: [
            { aTargets: [4], bSortable: false, bSearchable: false },
            { aTargets: [1, 3, 4], bSearchable: false },
        ],
        pageLength: 25,
    });

    $(".openMetaDataModal").on("click", function () {
        var searchId = $(this).data("id");
        const search = searches.find((search) => search.id === searchId);

        if (search) {
            if (search.name) {
                $("#name").text(search.name);
            }

            if (search.description) {
                $("#description").html(search.description);
            }

            if (search.recordtype_id) {
                $("#type").text(recordTypeMap[search.recordtype_id]);
            }

            if (search.warning) {
                $("#warning").html(search.warning);
            }

            if (subjectKeywordMap[search.id]) {
                var keywords = subjectKeywordMap[search.id]
                    .map(function (item) {
                        return item.keyword;
                    })
                    .join(", ");
                $("#subject").html(keywords);
            }

            if (search.temporal_from) {
                $("#temporal_from").text(search.temporal_from);
            }

            if (search.temporal_to) {
                $("#temporal_to").text(search.temporal_to);
            }

            if (search.latitude_from) {
                $("#latitude_from").text(search.latitude_from);
            }
            if (search.latitude_to) {
                $("#latitude_to").text(search.latitude_to);
            }
            if (search.longitude_from) {
                $("#longitude_from").text(search.longitude_from);
            }
            if (search.longitude_to) {
                $("#longitude_to").text(search.longitude_to);
            }

            if (search.query) {
                $("#search_query").empty();
                const params = new URLSearchParams(search.query.slice(1));
                for (const [key, value] of params) {
                    $("#search_query").append(
                        `<tr><td>${key}</td><td>${value}</td></tr>`
                    );
                }
            }

            $("#savesearchmetadatamodal").modal("show");
        } else {
            alert("Error: Search not found");
        }
    });

    $(".openEditMetaDataModal").on("click", function () {
        var searchId = $(this).data("id");
        const search = searches.find((search) => search.id === searchId);

        if (search) {
            $("#searchID").val(searchId);

            if (search.name) {
                $("#editName").val(search.name);
            }

            if (search.description) {
                tinymce.get("editDescription").setContent(search.description);
            }

            if (search.recordtype_id) {
                $("#editSearchType").val(recordTypeMap[search.recordtype_id]);
            }

            if (search.warning) {
                tinymce.get("editSearchWarning").setContent(search.warning);
            }

            if (subjectKeywordMap[search.id]) {
                $("#editSearchTags").tagsInput({
                    height: "50px",
                    width: "100%",
                    interactive: true,
                    defaultText: "add a tag",
                    delimiter: [",", ";"],
                    removeWithBackspace: true,
                    minChars: 0,
                    maxChars: 0,
                    placeholderColor: "#666666",
                    overflow: "auto",
                });

                $("#tags_tagsinput").addClass("form-control").addClass("mb-2");

                $("#editSearchTags").importTags("");
                subjectKeywordMap[search.id].map(function (item) {
                    $("#editSearchTags").addTag(item.keyword);
                });
            }

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

            if (search.temporal_from) {
                $("#editSearchTemporalFrom").val(search.temporal_from);
            }

            if (search.temporal_to) {
                $("#editSearchTemporalTo").val(search.temporal_to);
            }

            if (search.latitude_from) {
                $("#editSearchLatitudeFrom").val(search.latitude_from);
            }
            if (search.latitude_to) {
                $("#editSearchLatitudeTo").val(search.latitude_to);
            }
            if (search.longitude_from) {
                $("#editSearchLongitudeFrom").val(search.longitude_from);
            }
            if (search.longitude_to) {
                $("#editSearchLongitudeTo").val(search.longitude_to);
            }

            $("#savesearcheditmetadatamodal").modal("show");
        } else {
            alert("Error: Search not found");
        }
    });

    function getEditSavedSearchFormValues() {
        const id = $("#searchID").val();
        const name = $("#editName").val();
        const description = tinymce.get("editDescription").getContent();
        const recordType = $("#editSearchType").val();
        const tags = $("#editSearchTags").val();
        const warning = tinymce.get("editSearchWarning").getContent();
        const latitudefrom = $("#editSearchLatitudeFrom").val();
        const longitudefrom = $("#editSearchLongitudeFrom").val();
        const latitudeto = $("#editSearchLatitudeTo").val();
        const longitudeto = $("#editSearchLongitudeTo").val();
        const temporalfrom = $("#editSearchTemporalFrom").val();
        const temporalto = $("#editSearchTemporalTo").val();

        return {
            id: id,
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

    $("#editSaveSearchButton").on("click", function () {
        const inputs = getEditSavedSearchFormValues();
        let isValid = validateAddSavedSearchFormValues(inputs);
        console.log(inputs);

        if (isValid) {
            $.ajax({
                type: "POST",
                url: ajaxeditsearch,
                data: inputs,
                success: function (result) {
                    location.reload();
                },
                error: function (xhr, textStatus, errorThrown) {
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
