$(document).ready(function () {
    // Set up the CSRF token in the ajax header.
    const token = $('input[name="csrf-token"]').attr("value");
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    // Init datatable.
    $("#textsTable").DataTable({
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
            { targets: 5, orderable: false, searchable: false }, // existing rule
            { responsivePriority: 1, targets: 0 },               // most important
            { responsivePriority: 2, targets: 1 }                // second priority
        ]
        });

    // Init datatable.
    $("#datasettable").DataTable({
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
            { targets: 4, orderable: false, searchable: false }, // existing rule
            { responsivePriority: 1, targets: 0 },               // most important
            { responsivePriority: 2, targets: 1 }                // second priority
        ]
    });

    // Handle click event of the text delete buttons.
    $('button[name="delete_text_button"]').on("click", function () {
        if (confirm("Are you sure you want to delete this text?")) {
            const id = this.id.split("_")[3]; //id will be delete_collection_button_##, we jst want the number
            const row_id = "#row_id_" + id; //get the id of the row to be deleted, id will be row_id_##
            $.ajax({
                type: "POST",
                url: deletetexturl,
                data: {
                    id: id,
                },
                success: function (result) {
                    $(row_id).remove();
                    //jQuery datatable updating
                    $("#textsTable").DataTable().row(row_id).remove().draw();
                },
                error: function (xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                },
            });
        }
    });

    $("#downloadtextcontent").on("click", function () {
        $.ajax({
            type: "POST",
            url: ajaxgettextcontent,
            data: {
                id: textID,
            },
            success: function (result) {
                const contentWithLineBreaks = result.content.replace(
                    /<br\s*\/?>/gi,
                    "\n"
                );

                const blob = new Blob([contentWithLineBreaks], {
                    type: "text/plain",
                });

                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = "textcontent.txt";

                link.click();

                URL.revokeObjectURL(link.href);
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            },
        });
    });
});
