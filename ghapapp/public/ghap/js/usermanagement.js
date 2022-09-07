$(document).ready( function () {
    $("#usertable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[ 0, "asc" ]],
        aoColumnDefs: [
        ],
        "pageLength": 25
    });
});
