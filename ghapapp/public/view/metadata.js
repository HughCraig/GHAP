$(document).ready(function () {
    try {
        $.getJSON(urltoload + '?metadata=only', function (data) {

            $('#infoDiv').append('<h3><a href="' + data.metadata.ghap_url.replace(/json$/, "") + '" target="_blank">' + data.metadata.name + '</h3></p>');
            $('#infoDiv').append('<p>' + data.metadata.description + '</p>');
            if (data.metadata.warning) {
                $('#infoDiv').append('<p>' + data.metadata.warning + '</p>');
            }
            $('#infoDiv').append('<p><a href="/guides/views/" target="_blank">Help</a> | <a href="/guides/views/#shareview" target="_blank">Share</a></p></p>');
            console.log("Metadata done " + data);
        });
    } catch {
        console.log("Unable to fetch layer metadata.");
    }
});