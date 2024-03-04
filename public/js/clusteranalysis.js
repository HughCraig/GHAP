$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('#csrfToken').val(),
        },
    });

    var clusteringResponseData = null;
    var clusteringMethod = null;

    var distance = null;
    var minPoint = 0;

    var numClusters = 1;
    var withinRadius = null;

    // Function to toggle input fields based on the selected clustering method
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

    $("#cluster-download-csv").click(function () {
        const headers = ["Cluster ID", "id", "title", "latitude", "longitude"];
        downloadClusterDataAsCSV(
            clusteringResponseData,
            "clustering_results.csv",
            headers
        );
    });

    $("#cluster-download-json").click(function () {
        if (clusteringMethod === "dbscan") {
            if (!distance || distance < 0) {
                alert("Please enter a valid distance value.");
                return;
            }
            var href =
                currentUrl +
                "/dbscan/json/download?distance=" +
                distance +
                "&minPoints=" +
                minPoint;
            window.location.href = href;
        } else if (clusteringMethod === "kmeans") {
            if (!numClusters || numClusters < 0) {
                alert("Please enter a valid number of clusters.");
                return;
            }
            var href =
                currentUrl +
                "/kmeans/json/download?numClusters=" +
                numClusters +
                "&withinRadius=" +
                withinRadius;
            window.location.href = href;
        }
    });

    // Function to generate the result table based on response data
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
        clusteringMethod = $("#clusteringMethod").val();
        var url = clusteringMethod === "dbscan" ? ajaxdbscan : ajaxkmeans;

        var data = {
            id: id,
            method: clusteringMethod,
        };

        if (clusteringMethod === "dbscan") {
            data.distance = $("#distance").val();
            if (!data.distance || data.distance < 0) {
                alert("Please enter a valid distance value.");
                return;
            }
            distance = data.distance;

            data.minPoints = $("#minPoints").val();
            minPoint = data.minPoints;
            mapSourceUrl = encodeURIComponent(
                currentUrl +
                    "/dbscan/json?distance=" +
                    data.distance +
                    "&minPoints=" +
                    data.minPoints
            );
        } else {
            // KMeans
            data.numClusters = $("#numClusters").val();
            if (!data.numClusters || data.numClusters < 0) {
                alert("Please enter a valid number of clusters.");
                return;
            }
            numClusters = data.numClusters;

            data.withinRadius = $("#withinRadius").val() || null;
            withinRadius = data.withinRadius;
            mapSourceUrl = encodeURIComponent(
                currentUrl +
                    "/kmeans/json?numClusters=" +
                    data.numClusters +
                    "&withinRadius=" +
                    data.withinRadius
            );
        }

        var threeDMapviewUrl =
            viewsRootUrl + "/collection-3d.html?load=" + mapSourceUrl;

        var clusterMapviewUrl =
            viewsRootUrl + "/collection-cluster.html?load=" + mapSourceUrl;

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
                document.getElementById("collection-3d-map").onclick =
                    function () {
                        window.open(threeDMapviewUrl);
                    };
                document.getElementById("collection-cluster-map").onclick =
                    function () {
                        window.open(clusterMapviewUrl);
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
