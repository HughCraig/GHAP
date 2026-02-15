$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $("#csrfToken").val(),
        },
    });

    // Init datatable.
    $("#datasetsTable").DataTable({
        orderClasses: false,
        paging: true,
        searching: true,
        info: false,
        retrieve: true,
        responsive: true,
        order: [[0, "asc"]],
        pageLength: 25,
        language: {
        search: "Filter list:"
        },
        columnDefs: [
            { responsivePriority: 1, targets: 0 }, // most important
            { responsivePriority: 2, targets: 6 }  // second priority
        ]
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
