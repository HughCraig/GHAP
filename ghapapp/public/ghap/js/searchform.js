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

    // Change the advance search button icon on expand/collapse.
    $('#advancedaccordion').on('show.bs.collapse', function () {
        $('#advancedSearchButton').find('i.fa')
            .removeClass('fa-chevron-down')
            .addClass('fa-chevron-up');
    });
    $('#advancedaccordion').on('hide.bs.collapse', function () {
        $('#advancedSearchButton').find('i.fa')
            .removeClass('fa-chevron-up')
            .addClass('fa-chevron-down');
    });

    // Expand the advanced search tab if it's specified in the URL fragment
    if (window.location.hash === '#advancedsearch') {
        $('#advancedaccordion').collapse('show');
        $('#advancedSearchButton').find('i.fa')
            .removeClass('fa-chevron-down')
            .addClass('fa-chevron-up');
    }

    // Check whether the help video is loaded.
    if ($('#helpVideoModal').length > 0) {
        // Show help video at the first time visit.
        const helpVideoPlayed = Cookies.get('helpVideoPlayed');
        if (!helpVideoPlayed) {
            // Set the cookie expires after 100 years, as never expires.
            Cookies.set('helpVideoPlayed', '1', {expires: 365 * 100});
            $('#helpVideoModal').modal('show');
        }

        // Pause the help video when the modal is closed.
        $('#helpVideoModal').on('hidden.bs.modal', function () {
            $('#helpVideoModal').find('iframe')[0].contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*')
        });
    }
});
