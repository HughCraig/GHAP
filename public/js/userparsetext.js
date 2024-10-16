$(document).ready(function () {
  
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('#csrfToken').val(),
        },
    });
    
    $("#parse_text_submit").on("click", function () {
       
        $.ajax({
            type: "POST",
            url: parsetexturl,
            data: {
                id: textId,
            },
            success: function (result) {
               console.log(result);
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            },
        });

    });
});
