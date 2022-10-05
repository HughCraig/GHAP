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
        "pageLength": 25,
        aoColumnDefs: [{ "aTargets": [ 13 ], "bSortable": false, "bSearchable": false }]
    });
});
