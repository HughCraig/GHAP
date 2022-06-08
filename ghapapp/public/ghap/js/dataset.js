/* Get CSRF token for POST and add it to the AJAX header */
var token = $('input[name="csrf-token"]').attr('value');
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

/*
 *  DELETING DATA SET
 */

/* Delete data SET button */
$("main").on('click','[name="delete_dataset_button"]', function() { 
    if(confirm('Are you sure you want to delete this dataset?')) 
    {
        var id = this.id.split("_")[3]; //id will be delete_dataitem_button_##, we jst want the number
        var row_id = '#row_id_'+id; //get the id of the row to be deleted, id will be row_id_##
        $.ajax({
            type: 'POST',
            url: ajaxdeletedataset,
            data: {
                id: id
            },
            success: function(result) {
                $(row_id).remove();
                //jQuery datatable updating
                $('#datasettable').DataTable().row( row_id ).remove().draw();
            },
            error: function(xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            }
        }); 
    }
 });


 /*
 *  SHOW JOIN DATASET CONTROLS
 */
$("main").on('click','#show_join_controls_button', function() { 
    $('#join_controls').removeClass('hideme');
    $('#join_link_input').focus();
});

/*
 *  HIDE JOIN DATASET CONTROLS
 */
$("main").on('click','#hide_join_controls_button', function() { 
    $('#join_controls').addClass('hideme');
});

/*
 *  JOIN DATASET
 */
$("main").on('click','#join_link_button', function() { 
    var sharelink = $('#join_link_input').val();
    $.ajax({
        type: 'POST',
        url: ajaxjoindataset,
        data: {
             sharelink: sharelink
        },
        success: function(result) {
            //Show some kind of success message
            $("#notification_box").addClass("notification-success");
            $("#notification_message").text('Successfully joined dataset!');
            setTimeout(function(){
                $("#notification_box").removeClass("notification-success");
              },4000);

            //Some magic to make it appear in the datasets
            var newrow = $('#datasettable').DataTable().row.add([
                '<a href="' + result.url + '/' + result.dataset.id + '">' + result.dataset.name + '</a>',
                result.count,
                result.owner,
                result.dsrole,
                'PRIVATE',
                result.dataset.created_at,
                result.dataset.updated_at,
                '<button name="leave_dataset_button" id="leave_dataset_button_' + result.dataset.id + '">Leave</button>',
                ''
            ]).draw().node();

            //Green fade in for row
            $( newrow ).css( 'background-color', '#AAFFAA' ).animate( {'background-color': 'inherit'} , 5000);

            //hide the join dataset control
            $('#join_controls').addClass('hideme');
        },
        error: function(xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        }
    }); 
});

/*
 *  LEAVE DATASET
 */
$("main").on('click','[name="leave_dataset_button"]', function() { 
    var parent_row = jQuery(this).parent().parent();
    if(confirm('Are you sure you want to leave this dataset?')) 
    {
        var id = this.id.split("_")[3]; //id will be leave_dataset_button_##, we just want the number
        $.ajax({
            type: 'POST',
            url: ajaxleavedataset,
            data: {
                id: id
            },
            success: function(result) {
                //Show some kind of success message
                $("#notification_box").addClass("notification-success");
                $("#notification_message").text('Successfully left dataset!');
                setTimeout(function(){
                    $("#notification_box").removeClass("notification-success");
                },4000);

                //Some magic to make it disappear in the datasets
                $('#datasettable').DataTable().row( parent_row ).remove().draw();
            },
            error: function(xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            }
        }); 
    }
});