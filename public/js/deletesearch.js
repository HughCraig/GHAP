/* Get CSRF token for POST and add it to the AJAX header */
var token = $('input[name="csrf-token"]').attr("value");
$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

/* Use AJAX to get values from form */
$("[name='delete_search_button']").click(function () {
    var id = this.id.split("_")[3]; //id will be delete_search_button_##, we jst want the number
    var row_id = "#row_id_" + id; //get the id of the row to be deleted, id will be row_id_##
    $.ajax({
        type: "POST",
        url: ajaxdeletesearch,
        data: {
            delete_id: id,
        },
        success: function (data) {
            $(row_id).remove();
            //jQuery datatable updating
            $("#savedsearchestable").DataTable().row(row_id).remove().draw();
        },
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        },
    });
});
