$(document).ready( function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#usertable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        responsive: true,
        aaSorting: [[ 0, "asc" ]],
        aoColumnDefs: [
        ],
        "pageLength": 25
    });

    // Delete user button
    $("main").on('click', '[name="delete_user_button"]', function () {
        if (confirm('Are you sure you want to delete this user? All layers created by this user will be deleted')) {
            var id = this.id.split("_")[3]; //user id
            var row_id = '#row_id_' + id;  
            $.ajax({
                type: 'POST',
                url: url + '/deleteUser',
                data: {
                    id: id
                },
                success: function (result) {
                    $(row_id).remove();
                    $('#usertable').DataTable().row(row_id).remove().draw();
                },
                error: function (xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                }
            });
        }
    });
});
