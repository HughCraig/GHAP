$(document).ready(function () {

    //Layers Autocomplete.
    var layersSelector = $("#searchlayers")[0];
    const layersChoices = new Choices(layersSelector, {
        choices: layers.map(function(layer) {
            return {value: layer.id, label: layer.name};
        }),
        renderChoiceLimit: 40, //The amount of choices to be rendered within the dropdown list ("-1" indicates no limit). 
        maxItemCount: 10, //The amount of items a user can input/select 
        maxItemText: function (maxItemCount) {
            return 'Only ' + maxItemCount + ' layers can be selected.';
        },
        removeItemButton: true, //Whether each item should have a remove button.
        allowHTML: false, //Whether HTML should be rendered in all Choices elements.
        searchFields: ['label'], //Specify which fields should be used when a user is searching
        searchResultLimit: 5, //The maximum amount of search results to show.
        noResultsText: "No layer found", //The text to be displayed when a user's search has returned no results.
        position: 'bottom', // Whether the dropdown should appear above
        itemSelectText: '', //The text that is shown when a user hovers over a selectable choice.
    });

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
    }
});
