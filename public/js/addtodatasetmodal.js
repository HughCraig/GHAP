$(document).ready(function () {
    handleRecordTypeChange();

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

    // Set mobility information setting
    // Options for mobility place record
    $("#addrecordtype").on("change", function () {
        const defaultRouteOption = document.querySelector(
            'input[name="routeOption"]:checked'
        );
        handleRecordTypeChange(defaultRouteOption);
    });

    function handleRecordTypeChange(event, defaultRouteOption) {
        const selectedRecordType = $("#addrecordtype").val();
        if (selectedRecordType === "Mobility") {
            $(".add-quantity-group").show();
            $(".add-route-info-group").show();
            if (!defaultRouteOption) {
                defaultRouteOption = $(
                    'input[name="routeOption"]:checked'
                ).val();
            }
            $('input[name="routeOption"]:checked').trigger(
                "change",
                defaultRouteOption
            );
        } else {
            $(".add-quantity-group").hide();
            $(".add-route-info-group").hide();
        }
    }

    // Options for route setting
    $('input[name="routeOption"]').on(
        "change",
        function (event, selectedOption) {
            selectedOption = selectedOption || $(this).val();
            if (selectedOption === "new") {
                $(".route-title-row").show();
                $(".route-description-row").show();
                $(".route-existing-row").hide();
                $("#addRouteTitle").prop("required", true);
            } else if (selectedOption === "existing") {
                $(".route-title-row").hide();
                $(".route-description-row").hide();
                $(".route-existing-row").show();
                $("#addRouteId").prop("required", true);
                $("#addRouteId").prop("readonly", true);
                $("#addStopIdx").prop("required", true);
                updateStopIdxOptions();
            } else {
                $(".route-title-row").hide();
                $(".route-description-row").hide();
                $(".route-existing-row").hide();
            }
        }
    );

    // updateStopIdxOptions();
    // After select route id, update available stop_idx
    $("#addRouteId").on("change", function () {
        updateStopIdxOptions();
    });

    function updateStopIdxOptions() {
        const routeId = $("#addRouteId").val();
        $("#addStopIdx").empty();
        $("#addStopIdx").append(
            $("<option>", {
                value: "append",
                text: "Append",
            })
        );
        if (routeId) {
            $.ajax({
                url: updateStopIndicesUrl.replace("{routeId}", routeId),
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                type: "GET",
                success: function (data) {
                    const stopIndices = Object.values(data).sort(function (
                        a,
                        b
                    ) {
                        return a - b;
                    });
                    $.each(stopIndices, function (index, stopIdx) {
                        $("#addStopIdx").append(
                            $("<option>", {
                                value: stopIdx,
                                text: stopIdx,
                            })
                        );
                    });
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert("Error retrieving stop indices. " + errorThrown);
                },
            });
        }
    }
});
