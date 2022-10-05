$(document).ready( function () {
    // Init datatable.
    $("#dataitemtable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[ 0, "asc" ]],
        aoColumnDefs: [{ "aTargets": [ 13,16,17 ], "bSortable": false, "bSearchable": false }],
        "pageLength": 25
    });

    //LGA autocomplete.
    $( "#addlga, [name='lga']" ).autocomplete({
        source: function(request, response) {
            var results = $.ui.autocomplete.filter(lgas, request.term);
            response(results.slice(0, 20)); //return only 20 results
        }
    });
    $( "#addlga, [name='lga']" ).autocomplete( "option", "appendTo", ".eventInsForm" );

    //feature_term autocomplete.
    $( "#addfeatureterm, [name='feature_term']" ).autocomplete({
        source: function(request, response) {
            var results = $.ui.autocomplete.filter(feature_terms, request.term);
            response(results.slice(0, 20)); //return only 20 results
        }
    });
    $( "#addfeatureterm, [name='feature_term']" ).autocomplete( "option", "appendTo", ".eventInsForm" );

    // Datepickers.
    $('[name="editdatestartdiv"]').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
    $('[name="editdateenddiv"]').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
});
