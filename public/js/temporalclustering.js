$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    var clusteringResponseData = null;

    function getClusterResultTable(response) {
        var clusterSummaryTable = "<h2>Cluster Summary</h2>"; 
        var droppedRecordsCount = response["droppedRecordsCount"];
        clusterSummaryTable +=
            "<h3>Number of records dropped: " + droppedRecordsCount + "</h3>";

        response = response["clusters"];
        clusterSummaryTable +=
            '<table class="table"><thead><tr><th>Cluster Number</th><th>Total Places</th></tr></thead><tbody>';

        var resultTable = "<h2>Cluster Detail</h2>"; // Added heading
        resultTable +=
            '<table class="table"><thead><tr><th>Cluster Number</th><th>Place ID</th><th>Place Name</th><th>Date</th><th>Latitude</th><th>Longitude</th></tr></thead><tbody>';

        Object.entries(response).forEach(([clusterIndex, cluster], index) => {
            // Append to the cluster summary table
            clusterSummaryTable +=
                "<tr>" +
                '<td style="font-weight:bolder">' +
                (parseInt(clusterIndex) + 1) +
                "</td>" +
                "<td>" +
                cluster.length +
                "</td>" +
                "</tr>";

            // Populate the detailed result table
            cluster.forEach((place) => {
                resultTable +=
                    "<tr>" +
                    '<td style="font-weight:bolder">' +
                    (parseInt(clusterIndex) + 1) +
                    "</td>" +
                    "<td>" +
                    place.id +
                    "</td>" +
                    "<td>" +
                    place.title +
                    "</td>" +
                    "<td>" +
                    place.datestart +
                    "</td>" +
                    "<td>" +
                    place.latitude +
                    "</td>" +
                    "<td>" +
                    place.longitude +
                    "</td>" +
                    "</tr>";
            });
        });

        // Close the tables
        clusterSummaryTable += "</tbody></table>";
        resultTable += "</tbody></table>";

        // Combine the summary and detailed tables for the final output
        return clusterSummaryTable + resultTable;
    }

    function downloadClusterDataAsCSV() {
        if (!clusteringResponseData) {
            alert("No clustering data available to download.");
            return;
        }

        let csvContent =
            "data:text/csv;charset=utf-8,Cluster Number,Place ID,Place Name,Date,Latitude,Longitude\n";

        Object.entries(clusteringResponseData.clusters).forEach(
            ([clusterIndex, cluster]) => {
                cluster.forEach((place) => {
                    const row = [
                        parseInt(clusterIndex) + 1,
                        place.id,
                        `"${place.title.replace(/"/g, '""')}"`, 
                        place.datestart,
                        place.latitude,
                        place.longitude,
                    ].join(",");
                    csvContent += row + "\n";
                });
            }
        );

        // Create and trigger the download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "temporal_clustering.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    $("#downloadCsvButton").click(function () {
        downloadClusterDataAsCSV();
    });

    $("#backButton").click(function () {
        $(".result-table").empty(); 
        $(".result-output").hide(); 
        $(".user-input").show(); 
    });

    $("#temporal_cluster").click(function (e) {
        e.preventDefault();

        var id = $("#ds_id").val();
        var yearsInterval = parseFloat($("#yearsInterval").val()) || 0;
        var daysInterval = parseFloat($("#daysInterval").val()) || 0;
        var totalInterval = yearsInterval + daysInterval / 366;
        var mapviewUrl =
            viewsRootUrl +
            "/collection-cluster.html?load=" +
            encodeURIComponent(
                currentUrl +
                    "/json?year=" +
                    yearsInterval +
                    "&day=" +
                    daysInterval
            );

        $.ajax({
            type: "POST",
            url: ajaxtemporalclustering,
            data: {
                id: id,
                totalInterval: totalInterval,
            },
            success: function (response) {
                clusteringResponseData = response; 
                $(".user-input").hide();
                var resultTable = getClusterResultTable(response);
                $(".result-table").html(resultTable);
                $(".result-output").show();
                // Update the map view button's onclick to include the global data
                document.getElementById("mapViewButton").onclick = function () {
                    window.open(mapviewUrl);
                };
            },
            error: function (xhr) {
                console.error("An error occurred:", xhr.responseText);
            },
        });
    });
});
