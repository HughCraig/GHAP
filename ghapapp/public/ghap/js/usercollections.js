$(document).ready(function () {
    // Init datatable.
    $("#collectionsTable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[0, "asc"]],
        aoColumnDefs: [
            {"aTargets": [6], "bSortable": false, "bSearchable": false},
        ],
        "pageLength": 25
    });
});
