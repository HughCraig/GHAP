$(document).ready( function () {
    // Init datatable.
    $("#savedsearchestable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[ 0, "asc" ]],
        aoColumnDefs: [
            { "aTargets": [ 4 ], "bSortable": false, "bSearchable": false },
            { "aTargets": [ 1,3,4 ], "bSearchable": false }
        ],
        "pageLength": 25
    });
});
