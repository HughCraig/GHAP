$(document).ready( function () {
    // Init datatable.
    $("#collectionsTable").dataTable({
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
