$(document).ready(function () {
    // Datepickers.
    $("#temporalfromdiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });
    $("#temporaltodiv").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false,
    });

    //Initiate jQuery tagsInput function AND Adjust the settings for the tags field
    $("#tags").tagsInput({
        height: "50px",
        width: "100%",
        interactive: true,
        defaultText: "add a tag",
        delimiter: [",", ";"], // Or a string with a single delimiter. Ex: ';'
        removeWithBackspace: true,
        minChars: 0,
        maxChars: 0, // if not provided there is no limit
        placeholderColor: "#666666",
        overflow: "auto",
    });

    //Make it look like the other inputs
    $("#tags_tagsinput").addClass("form-control").addClass("mb-2");

    $("#addTextSaveButton").on("click", function () {

        var upload_image = $("#textAddImage")[0].files[0];
        if (upload_image && upload_image.size > max_upload_image_size) {
            alert(
                "The image size should be less than " +
                    Math.floor(max_upload_image_size / (1024 * 1024)) +
                    " MB"
            );
            return false;
        }

        var upload_text_file = $("#textAddFile")[0].files[0];
        if (!upload_text_file) {
            alert("Please upload the text file.");
            return false;
        }
        
        if (upload_text_file.size === 0) {
            alert('The uploaded text file is empty. Please upload a non-empty file.');
            return false;
        }
        if (upload_text_file.size > text_max_upload_file_size) {
            alert(
                "We are currently limiting individual uploads to " +
                    Math.floor(text_max_upload_file_size / (1024 * 1024)) +
                    " MB , in order to conserve system resources and ensure availability. Please consider breaking you text into sections."
            );
            return false;
        }
        var fileExtension = upload_text_file.name.split('.').pop().toLowerCase();
        if (!allowed_text_file_types.includes(fileExtension)) {
            alert('Please upload a valid text file (allowed formats: ' + allowed_text_file_types.join(', ') + ').');
            return false;
        }

    });
});
