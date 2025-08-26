$(document).ready(function () {
    // Init datatable.
    $("#dataitemtable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[0, "asc"]],
        pageLength: 25,
        aoColumnDefs: [
            { aTargets: [13], bSortable: false, bSearchable: false },
        ],
    });

    $("#mark_layer_as_unfeatured").on("click", function () {
        $.ajax({
            type: "POST",
            url: ajaxmarklayerasfeatured,
            data: {
                layer_id: dataset_id,
                is_featured: false,
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            },
        });
    });
    $("#mark_layer_as_featured").on("click", function () {
        $.ajax({
            type: "POST",
            url: ajaxmarklayerasfeatured,
            data: {
                layer_id: dataset_id,
                is_featured: true,
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            },
        });
    });
});
