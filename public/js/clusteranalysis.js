$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    var clusteringResponseData = null;

    function toggleInputs(method) {
        if (method === "kmeans") {
            $(".dbscan-input").hide();
            $(".kmeans-input").show();
        } else {
            // DBScan
            $(".dbscan-input").show();
            $(".kmeans-input").hide();
        }
    }

    $("#downloadCsvButton").click(function () {
        if (
            !clusteringResponseData ||
            Object.keys(clusteringResponseData).length === 0
        ) {
            alert("No data available for download.");
            return;
        }

        // Start CSV string and add header
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Cluster ID,Place ID,Place Name,Latitude,Longitude\n"; // Add your columns here


        Object.entries(clusteringResponseData).forEach(
            ([clusterId, places]) => {
                places.forEach((place) => {
                    let row = [
                        parseInt(clusterId) + 1, 
                        place.id, // Place ID
                        `"${place.title.replace(/"/g, '""')}"`, 
                        place.latitude, // Latitude
                        place.longitude, // Longitude
                    ].join(",");
                    csvContent += row + "\n";
                });
            }
        );

        // Create a link and trigger download
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "clustering_results.csv");
        document.body.appendChild(link); // Required for FF

        link.click(); // This will download the file
        document.body.removeChild(link); // Clean up
    });

    function getClusterResultTable(response) {
        var clusterSummaryTable = "<h2>Cluster Summary</h2>";

        clusterSummaryTable +=
            '<table class="table"><thead><tr><th>Cluster Number</th><th>Total Places</th></tr></thead><tbody>';

        var resultTable = "<h2>Cluster Detail</h2>"; // Added heading

        resultTable +=
            '<table class="table"><thead><tr><th>Cluster Number</th><th>Place ID</th><th>Place Name</th><th>Latitude</th><th>Longitude</th></tr></thead><tbody>';

        // Use Object.entries to iterate over the response, which works for both objects and arrays
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
                    place.latitude +
                    "</td>" +
                    "<td>" +
                    place.longitude +
                    "</td>" +
                    "</tr>";
            });
        });

        clusterSummaryTable += "</tbody></table>";
        resultTable += "</tbody></table>";

        return clusterSummaryTable + resultTable;
    }

    $("#clusteringMethod").change(function () {
        toggleInputs($(this).val());
    });

    $("#backButton").click(function () {
        $(".result-table").empty(); 
        $(".result-output").hide(); 
        $(".user-input").show(); 
    });

    $("#cluster_analysis").click(function (e) {
        e.preventDefault();

        var id = $("#ds_id").val();
        var clusteringMethod = $("#clusteringMethod").val();
        var url = clusteringMethod === "dbscan" ? ajaxdbscan : ajaxkmeans;
        var mapviewUrl = "";

        var data = {
            id: id,
            method: clusteringMethod,
        };

        if (clusteringMethod === "dbscan") {
            data.distance = $("#distance").val();
            if(!data.distance  || data.distance < 0) {
                alert("Please enter a valid distance value.");
                return;
            }

            data.minPoints = $("#minPoints").val();
            mapviewUrl =
                viewsRootUrl +
                "/collection-cluster.html?load=" +
                encodeURIComponent(
                    currentUrl +
                        "/dbscan/json?distance=" +
                        data.distance +
                        "&minPoints=" +
                        data.minPoints
                );
        } else {
            // KMeans
            data.numClusters = $("#numClusters").val();
            if(!data.numClusters || data.numClusters < 0) {
                alert("Please enter a valid number of clusters.");
                return;
            }

            data.withinRadius = $("#withinRadius").val() || null;
            mapviewUrl =
                viewsRootUrl +
                "/collection-cluster.html?load=" +
                encodeURIComponent(
                    currentUrl +
                        "/kmeans/json?numClusters=" +
                        data.numClusters +
                        "&withinRadius=" +
                        data.withinRadius
                );
        }

        $.ajax({
            type: "POST",
            url: url,
            data: data,
            success: function (response) {
                clusteringResponseData = response;
                $(".user-input").hide();
                var resultTable = getClusterResultTable(response);
                $(".result-table").html(resultTable);
                $(".result-output").show();
                document.getElementById("mapViewButton").onclick = function () {
                    window.open(mapviewUrl);
                };
            },
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.responseText);
            },
        });
    });

    // Initial input toggle based on the default selected method
    toggleInputs($("#clusteringMethod").val());
});
