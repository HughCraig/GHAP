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
        "pageLength": 25
    });
});
