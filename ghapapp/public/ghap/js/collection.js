$(document).ready(function () {

    // Set up the CSRF token in the ajax header.
    const token = $('input[name="csrf-token"]').attr('value');
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Handle click event of the collection delete buttons.
    $('button[name="delete_collection_button"]').on('click', function () {
        if (confirm('Are you sure you want to delete this collection?')) {
            const id = this.id.split("_")[3]; //id will be delete_collection_button_##, we jst want the number
            const row_id = '#row_id_' + id; //get the id of the row to be deleted, id will be row_id_##
            $.ajax({
                type: 'POST',
                url: deleteCollectionService,
                data: {
                    id: id
                },
                success: function(result) {
                    $(row_id).remove();
                    //jQuery datatable updating
                    $('#collectionsTable').DataTable().row( row_id ).remove().draw();
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                }
            });
        }
    });

    // Handle click event of the collection dataset remove buttons.
    $('button[name="remove_dataset_button"]').on('click', function () {
        if (confirm('Are you sure you want to remove this dataset from this collection?')) {
            const datasetID = this.id.split("_")[3]; //id will be remove_dataset_button_##, we jst want the number
            const row_id = '#row_id_' + datasetID; //get the id of the row to be deleted, id will be row_id_##
            const collectionID = $(this).data('collection-id');
            $.ajax({
                type: 'POST',
                url: removeCollectionDatasetService,
                data: {
                    id: collectionID,
                    datasetID: datasetID
                },
                success: function(result) {
                    $(row_id).remove();
                    //jQuery datatable updating
                    $('#collectionsTable').DataTable().row( row_id ).remove().draw();
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                }
            });
        }
    });
});
