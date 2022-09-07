$(document).ready(function () {
    try {
        $.getJSON(urltoload + '?metadata=only', function (data) {

            $('#infoDiv').append('<h3><a href="' + data.metadata.ghap_url.replace(/json$/, "") + '" target="_blank">' + data.metadata.name + '</h3></p>');
            $('#infoDiv').append('<p>' + data.metadata.description + '</p>');
            if (data.metadata.warning) {
                $('#infoDiv').append('<div class="warning-message"><strong>Warning</strong><br />' + data.metadata.warning + '</div>');
            }
            let linksHtml = '<p><a href="/guides/views/" target="_blank">Help</a> | <a href="/guides/views/#shareview" target="_blank">Share</a>';
            if (data.metadata.linkback) {
                linksHtml += ` | <a href="${data.metadata.linkback}" target="_blank">Linkback</a>`;
            }
            linksHtml += '</p>';
            $('#infoDiv').append(linksHtml);
            console.log("Metadata done " + data);
        });
    } catch {
        console.log("Unable to fetch layer metadata.");
    }
});
