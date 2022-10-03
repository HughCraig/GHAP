$(document).ready( function () {
    // Init datatable.
    $("#datasetsTable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[ 0, "asc" ]],
        aoColumnDefs: [{ "aTargets": [ 9 ], "bSortable": false, "bSearchable": false }],
        "pageLength": 25
    });
});
