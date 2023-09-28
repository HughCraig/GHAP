$(document).ready(function () {

    //Layers Autocomplete.
    var selectedLayers = [];

    function split( val ) {
        return val.split( /;\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

    //layers autocomplete.
    $("#searchlayers").autocomplete({
        minLength: 0,
        source: function (request, response) {
            // Use only the last term for matching
            var term = extractLast(request.term);
            var results = $.ui.autocomplete.filter(layers.map(layer => layer.name), term);
            response(results.slice(0, 15)); // return only 15 results
        },
        focus: function () {
            // prevent value inserted on focus
            return false;
        },
        select: function (event, ui) {
            var terms = split(this.value);
            // Remove the current input
            terms.pop();
            // Add the selected layer
            terms.push(ui.item.value);
            // Add placeholder for the next layer
            terms.push("");
            this.value = terms.join(";");
            
            // Find the selected layer by name and add its id to selectedLayers
            var selectedLayer = layers.find(layer => layer.name === ui.item.value);
            if (selectedLayer) {
                selectedLayers.push(selectedLayer.id);
            }

            $("#selected-layers").val(selectedLayers.join(","));
            return false;
        }
    });
    $("#searchlayers").on('input', function() {
        var currentLayerNames = split(this.value).filter(name => name.trim().length > 0);
        selectedLayers = layers.filter(layer => currentLayerNames.includes(layer.name)).map(layer => layer.id);
        $("#selected-layers").val(selectedLayers.join(","));
    });

    //feature_term autocomplete.
    $("#feature_term").autocomplete({
        minLength: 0,
        source: function (request, response) {
            // Use only the last term for matching
            var term = extractLast(request.term);
            var results = $.ui.autocomplete.filter(feature_terms, term);
            response(results.slice(0, 15)); // return only 15 results
        },
        focus: function () {
            // prevent value inserted on focus
            return false;
        },
        select: function (event, ui) {
            var terms = split(this.value);
            terms.pop();
            terms.push(ui.item.value);
            terms.push("");
            this.value = terms.join(";");
            
            return false;
        }
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
    if ($('#helpVideoModal').length > 0 && (show_help_video_first_landing === '1')) {
        // Show help video at the first time visit.
        const helpVideoPlayed = Cookies.get('helpVideoPlayed');
        if (!helpVideoPlayed) {
            // Set the cookie expires after 100 years, as never expires.
            Cookies.set('helpVideoPlayed', '1', {expires: 365 * 100});
            $('#helpVideoModal').modal('show');
        }
    }
});
