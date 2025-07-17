$(document).ready(function () {
    window.glycerineUrl = null;

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
});
