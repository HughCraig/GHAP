$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    var responseData = null;

    //searchlayer Autocomplete.
    $("#searchlayer").autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(
                layers.map((layer) => layer.name),
                request.term
            );
            response(results.slice(0, 20)); // return only 15 results
        },
        focus: function () {
            // prevent value inserted on focus
            return false;
        },
        select: function (event, ui) {
            var selectedLayer = layers.find(
                (layer) => layer.name === ui.item.value
            );
            $("#selected-layer-id").val(selectedLayer.id);
        },
    });

    // Closeness analysis submit 
    $("#closeness_analysis").click(function (e) {
        e.preventDefault();

        var id = $("#ds_id").val();
        var targetDatasetId = $("#selected-layer-id").val();

        if (!targetDatasetId || targetDatasetId == "") {
            alert("Please select a target layer");
            return;
        }

        var data = {
            dataset_id: id,
            targetDatasetId: targetDatasetId,
        };

        var mapviewUrl = viewsRootUrl + "/journey.html?load=" + encodeURIComponent(currentUrl + '/json?targetLayer=' + targetDatasetId);

        $.ajax({
            type: "POST",
            url: ajaxclosenessanalysis,
            data: data,
            success: function (response) {

                responseData = response;

                // Clear existing content
                $(".result-output").show();
                $(".result-table").empty();

                var table =
                    '<table class="table"><thead><tr><th>Statistic</th><th>Value</th><th>Unit</th></tr></thead><tbody>';

                // Append rows to the table for each statistic
                for (var key in response) {
                    table +=
                        "<tr><td>" +
                        key +
                        "</td><td>" +
                        response[key] +
                        "</td><td>" +
                        (key.includes("Area") ? "km²" : "km") +
                        "</td></tr>";
                }

                table += "</tbody></table>";

                // Append the table to the result-table div
                $(".result-table").append(table);

                document.getElementById('mapViewButton').onclick = function() {
                    window.open(mapviewUrl);
                };

            },
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.responseText);
            },
        });
    });

    //csv download
    $("#downloadCsvButton").click(function() {
        if (!responseData) {
            alert("No data available for download.");
            return;
        }
        
        // Start CSV string and add header
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Statistic,Value,Unit\n"; 

        // Iterate through the response data and add each row
        Object.keys(responseData).forEach(key => {
            const value = responseData[key];
            const unit = key.includes("Area") ? "km²" : "km";
            csvContent += `"${key}","${value}","${unit}"\n`;
        });

        // Create a link and trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "closeness_analysis.csv");
        document.body.appendChild(link); // Required for FF

        link.click(); 
        document.body.removeChild(link); 
    });
});
