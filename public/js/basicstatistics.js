$(document).ready(function () {
    console.log(layer_name);
    $("#download-csv").click(function () {
        var csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Statistic,Value,Unit\n";

        statistics.forEach(function (stat) {
            if (typeof stat.value === "object" && stat.value !== null) {
                Object.entries(stat.value).forEach(([key, val]) => {
                    var row = `"${stat.name} - ${key}","${val}","${
                        stat.unit || ""
                    }"`;
                    csvContent += row + "\n";
                });
            } else {
                var row = `"${stat.name}","${stat.value}","${stat.unit || ""}"`;
                csvContent += row + "\n";
            }
        });

        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "statistics " + layer_name + ".csv");
        document.body.appendChild(link);

        link.click();
        document.body.removeChild(link);
    });
});
