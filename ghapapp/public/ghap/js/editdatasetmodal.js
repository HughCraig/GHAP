$(document).ready(function () {
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
        'height': '50px',
        'width': '100%',
        'interactive': true,
        'defaultText': 'add a tag',
        'delimiter': [',', ';'],   // Or a string with a single delimiter. Ex: ';'
        'removeWithBackspace': true,
        'minChars': 0,
        'maxChars': 0, // if not provided there is no limit
        'placeholderColor': '#666666',
        'overflow': 'auto'
    });

    //Make it look like the other inputs
    $('#tags_tagsinput').addClass('form-control').addClass('mb-2');

    //prefill the form with the keywords associated with this dataset
    $.each(currentKeywords, function (index, value) {
        $('#tags').addTag(value.keyword);
    });
});
