$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    //Select option for role by current DB value
    $("#selectRole option").each(function () {
        if ($(this).val() === $(this).parent().data("role")) {
            $(this).attr("selected", "selected");
        }
    });

    // Reset user password button
    $("#reset_user_password").on("click", function () {
        $.ajax({
            type: "POST",
            url: url + "/resetUserPassword",
            data: {
                id: userId,
                password: $("#password").val(),
                password_confirmation: $("#password-confirm").val(),
            },
            success: function (result) {
                $("#password").val("");
                $("#password-confirm").val("");
                $(".alert.alert-success").css("display", "block");
                $(".alert.alert-success").text("Password reset successfully");
                $(".invalid-feedback").css("display", "none");
            },
            error: function (xhr, textStatus, errorThrown) {
                try {
                    $(".invalid-feedback strong").text(xhr.responseText); 
                    $(".invalid-feedback").css("display", "block");
                } catch (e) {
                    alert(xhr.responseText); 
                }
            },
        });
    });
});
