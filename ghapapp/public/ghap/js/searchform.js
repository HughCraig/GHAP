$(document).ready(function () {

    //LGA Autocomplete.
    $("#lga").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(lgas, request.term);
            response(results.slice(0, 20)); //return only 20 results
        }
    });

    //parish autocomplete.
    $("#parish, [name='parish']").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(parishes, request.term);
            response(results.slice(0, 17)); //return only 20 results
        }
    });
    $("#addparish, [name='parish']").autocomplete("option", "appendTo", ".eventInsForm");

    //feature_term autocomplete.
    $("#feature_term, [name='feature_term']").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(feature_terms, request.term);
            response(results.slice(0, 15)); //return only 20 results
        }
    });
    $("#addfeatureterm, [name='feature_term']").autocomplete("option", "appendTo", ".eventInsForm");

    //Bootstrap tooltips.
    $('[data-toggle="tooltip"]').tooltip();

    // Datepickers.
    $('#datefrom').datepicker({
        format: 'yyyy-mm-dd',
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false
    });
    $('#dateto').datepicker({
        format: 'yyyy-mm-dd',
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false
    });
});
