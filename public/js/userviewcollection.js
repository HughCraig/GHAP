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

    $('#mark_multilayer_as_unfeatured').on('click', function() {
        $.ajax({
            type: 'POST',
            url: ajaxmarkmultilayerasfeatured,
            data: {
                collection_id: collection_id,
                is_featured: false
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            }
        });
    });
    $('#mark_multilayer_as_featured').on('click', function() {
        $.ajax({
            type: 'POST',
            url: ajaxmarkmultilayerasfeatured,
            data: {
                collection_id: collection_id,
                is_featured: true
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            }
        });
    });
});
