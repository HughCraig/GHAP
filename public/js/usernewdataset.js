$(document).ready( function () {
    // Datepickers.
    $('#temporalfromdiv').datepicker({
        format: 'yyyy-mm-dd',
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false
    });
    $('#temporaltodiv').datepicker({
        format: 'yyyy-mm-dd',
        todayBtn: true,
        forceParse: false,
        keyboardNavigation: false
    });

    //Initiate jQuery tagsInput function AND Adjust the settings for the tags field
    $('#tags').tagsInput({
        'height':'50px',
        'width':'100%',
        'interactive':true,
        'defaultText':'add a tag',
        'delimiter': [',',';'],   // Or a string with a single delimiter. Ex: ';'
        'removeWithBackspace' : true,
        'minChars' : 0,
        'maxChars' : 0, // if not provided there is no limit
        'placeholderColor' : '#666666',
        'overflow' : 'auto'
    });

    //Make it look like the other inputs
    $('#tags_tagsinput').addClass('form-control').addClass('mb-2');

    $('#addDatasetSaveButton').on('click', function () {
        var file = $('#datasetAddImage')[0].files[0];
        if (file && file.size > max_upload_image_size) {
            alert('The image size should be less than ' + Math.floor(max_upload_image_size / (1024 * 1024)) + ' MB');
            return false;
        }
    });
});
