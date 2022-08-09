/* Get CSRF token for POST and add it to the AJAX header */
var token = $('input[name="csrf-token"]').attr('value');
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

/*
 *  DELETING ALL COLLAB LINKS FOR THIS DS
 */
$("main").on('click', '#delete_share_links_button', function () {
    var id = $('#dsid').text(); //get the blade var for the dataset id
    $.ajax({
        type: 'POST',
        url: '/ajaxdestroysharelinks',
        data: {
            id: id
        },
        success: function (result) {
            //Show some kind of success message
            $("#notification_box").addClass("notification-success");
            $("#notification_message").text('Successfully deleted all share links for this dataset!');
            setTimeout(function () {
                $("#notification_box").removeClass("notification-success");
            }, 4000);
        },
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        }
    });
});

/*
*  Show collab controls
*/
$("main").on('click', '#show_collaborator_button', function () {
    $('#collaborator_controls').removeClass('hideme');
});

/*
*  Hide collab controls
*/
$("main").on('click', '#hide_collaborator_button', function () {
    $('#collaborator_controls').addClass('hideme');
});

/*
 * Generate a share link
 */
$("main").on('click', '#generate_share_link_button', function () {
    var id = $('#dsid').text();
    var dsrole = $('#dsrole_selector').val();
    $.ajax({
        type: 'POST',
        url: '/ajaxgeneratesharelink',
        data: {
            id: id,
            dsrole: dsrole
        },
        success: function (result) {
            //Show some kind of success message
            $("#notification_box").addClass("notification-success");
            $("#notification_message").text('Copied share link to keyboard!');
            setTimeout(function () {
                $("#notification_box").removeClass("notification-success");
            }, 4000);

            //Set vals
            $("#share_link").val(window.location.hostname + '/myprofile/mydatasets/join/' + result.sharelink);
            $("#share_link").select();
            //$("#share_link").setSelectionRange(0, 99999); /*For mobile devices*/
            $("#generate_share_link_button").prop('disabled', true);
            $("#emailcollaboratorbutton").prop('disabled', false);
            document.execCommand("copy"); //copy to clipboard
        },
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        }
    });
});

$("main").on('click', '#emailcollaboratorbutton', function () {
    var email = $("#collaboratoremail").val();
    var sharelink = $("#share_link").val();
    var senderemail = $("#senderemail").val();
    var dsrole = $('#dsrole_selector').val();
    if (!email) $("#collaboratoremail").addClass("border-danger");
    else {
        //send the email
        $.ajax({
            type: 'POST',
            url: '/ajaxemailsharelink',
            data: {
                collaboratoremail: email,
                sharelink: sharelink,
                senderemail: senderemail,
                dsrole: dsrole
            },
            success: function (result) {
                //Show some kind of success message
                $("#notification_box").addClass("notification-success");
                $("#notification_message").text('Successfully emailed the share link!');
                setTimeout(function () {
                    $("#notification_box").removeClass("notification-success");
                }, 4000);
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            }
        });

        $("#emailcollaboratorbutton").prop('disabled', true);
        $("#collaboratoremail").removeClass("border-danger");
    }
});

$("main").on('click', '#collaboratorclosebutton', function () {
    $("#emailcollaboratorbutton").prop('disabled', false);
    $("#generate_share_link_button").prop('disabled', false);
    $("#collaboratoremail").val('');
    $("#collaboratoremail").removeClass("border-danger");
    //$("#share_link").val('');   
});


/*
 *  Show edit controls
 */
$("main").on('click', '[name="edit_collaborator_button"]', function () {
    var parent_row = jQuery(this).parent().parent();
    var select_box = parent_row.find('select');
    var old_text = parent_row.find('input');
    var submit_button = parent_row.find('[name="edit_collaborator_submit_button"]');
    var cancel_button = parent_row.find('[name="edit_collaborator_cancel_button"]');

    select_box.removeAttr('disabled');
    select_box.removeClass('hideme');
    select_box.find('option[value=' + old_text.val() + ']').prop('selected', true);
    old_text.addClass('hideme');
    jQuery(this).addClass('hideme');
    submit_button.removeClass('hideme');
    cancel_button.removeClass('hideme');
});

/*
 *  Hide edit controls
 */
$("main").on('click', '[name="edit_collaborator_cancel_button"]', function () {
    var parent_row = jQuery(this).parent().parent();
    var select_box = parent_row.find('select');
    var old_text = parent_row.find('input');
    var edit_button = parent_row.find('[name="edit_collaborator_button"]');
    var submit_button = parent_row.find('[name="edit_collaborator_submit_button"]');

    select_box.prop('selected', false);
    select_box.attr('disabled', 'disabled');
    select_box.addClass('hideme');
    old_text.removeClass('hideme');
    jQuery(this).addClass('hideme');
    submit_button.addClass('hideme');
    edit_button.removeClass('hideme');
});

/*
 *  Submit edit on collaborator
 */
$("main").on('click', '[name="edit_collaborator_submit_button"]', function () {
    var parent_row = jQuery(this).parent().parent();
    var select_box = parent_row.find('select');
    var old_text = parent_row.find('input');
    var edit_button = parent_row.find('[name="edit_collaborator_button"]');
    var submit_button = jQuery(this);
    var cancel_button = parent_row.find('[name="edit_collaborator_cancel_button"]');
    var collaborator_email = parent_row.find('[name="collaborator_email"]').text();

    //get the new role
    //get the user email (previously)
    //get the dataset id
    var dataset_id = $('#dsid').text();
    var new_dsrole = select_box.val();

    $.ajax({
        type: 'POST',
        url: '/ajaxeditcollaborator',
        data: {
            id: dataset_id,
            dsrole: new_dsrole,
            collaborator_email: collaborator_email
        },
        success: function (result) {
            //Show some kind of success message
            $("#notification_box").addClass("notification-success");
            $("#notification_message").text('Successfully edited role for collaborator!');
            setTimeout(function () {
                $("#notification_box").removeClass("notification-success");
            }, 4000);

            //hide show fields
            select_box.prop('selected', false);
            select_box.attr('disabled', 'disabled');
            select_box.addClass('hideme');
            old_text.removeClass('hideme');
            submit_button.addClass('hideme');
            cancel_button.addClass('hideme');
            edit_button.removeClass('hideme');

            //update data
            old_text.val(result.newdsrole);
            $('#dsupdatedat').text(result.time);

            //flash yellow
            parent_row.css('background-color', 'yellow').animate({'background-color': 'inherit'}, 5000);

        },
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        }
    });
});

/*
 *  Delete collaborator
 */
$("main").on('click', '[name="delete_collaborator_button"]', function () {
    var parent_row = jQuery(this).parent().parent();
    var dataset_id = $('#dsid').text();
    var collaborator_email = parent_row.find('[name="collaborator_email"]').text();

    $.ajax({
        type: 'POST',
        url: '/ajaxdeletecollaborator',
        data: {
            id: dataset_id,
            collaborator_email: collaborator_email
        },
        success: function (result) {
            //Show some kind of success message
            $("#notification_box").addClass("notification-success");
            $("#notification_message").text('Successfully deleted collaborator!');
            setTimeout(function () {
                $("#notification_box").removeClass("notification-success");
            }, 4000);

            //remove row
            $('#collabtable').DataTable().row(parent_row).remove().draw();
        },
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        }
    });
});