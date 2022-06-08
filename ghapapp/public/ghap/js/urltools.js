function isValidURL(str) {
    return str.match(/https?:\/\/(www\.)?.+\..+/i);
}

function isValidDOI(str) {
    return str.match(/^10.\d{4,}(.\d{1,})*\/[\u0020-\uFFFF]+$/);
}

/* On page load */
$(function() {
    /* for datasets, check all links in the link column, make them clickable if valid or red if invalid  */
    $('[name="external_url"]').each(function(){
        var str = $(this).val() || this.innerText;
        if (!isValidURL(str)) $(this).addClass('forceinvalid');
        else $(this).addClass('external_url_clickable');
    }); 

    /* 
        Clicking one of these but NOT in editing mode 
            Currently uses the td as the click receipient because we have input DISABLED when not in edit mode, which ignores all click events :(
    */
    $('td > input[name="external_url"]').parent().on('click', function() {
        var input = $(this).children()[0];
        if (input) {
            var link = $(input).val();
            if (link && $(input).hasClass("external_url_clickable")) window.open(link);
        }
    }); //dataitem links
    $('span[name="external_url"]').on('click', function() {
        var link = this.innerText;
        if (link && $(this).hasClass("external_url_clickable")) window.open(link);
    }); //search result links

    //Source for the dataset itself
    if (isValidURL($('#source_url').text())) {
        $('#source_url').addClass("external_url_clickable");
        $('#source_url').on("click", function() { window.open($('#source_url').text()); })
    } else {
        $('#source_url').addClass("forceinvalid");
    }

    //Source for the doi
    if (isValidDOI($('#doi').text())) {
        $('#doi').addClass("external_url_clickable");
        $('#doi').on("click", function() { window.open("https://doi.org/" + $('#doi').text()); })
    } else {
        $('#doi').addClass("forceinvalid");
    }
})



/* On edit for dataitems, check all links in the link column, make them clickable if valid or red if invalid */

/* On page load for search results, check all links in the link column, make them clickable if valid or red if invalid */