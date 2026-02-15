$(document).ready(function () {
    // Init datatable.
    $("#datasetsTable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        responsive: true,
        aaSorting: [[0, "asc"]],
        aoColumnDefs: [{ aTargets: [8], bSortable: false, bSearchable: false }],
        pageLength: 25,
        oLanguage: {
            sSearch: "Filter list:"
        }
    });

    $("#mark_multilayer_as_unfeatured").on("click", function () {
        $.ajax({
            type: "POST",
            url: ajaxmarkmultilayerasfeatured,
            data: {
                collection_id: collection_id,
                featured_url: null,
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            },
        });
    });
    $(".mark_multilayer_as_featured").on("click", function () {
        const featuredUrl = $(this).data("featured-url");
        $.ajax({
            type: "POST",
            url: ajaxmarkmultilayerasfeatured,
            data: {
                collection_id: collection_id,
                featured_url: featuredUrl,
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
