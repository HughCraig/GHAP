/**
 * Downloads cluster data as a CSV file.
 * 
 * This function takes a structured data object where each key represents a cluster ID
 * and its value is an array of place objects belonging to that cluster. 
 *
 * @param {Object} data - The cluster data to be downloaded. Expected to be an object where
 * each key is a cluster ID and the value is an array of objects representing places.
 * @param {string} filename - The name of the file to be downloaded, including the .csv extension.
 * @param {Array} headers - An array of strings representing the column headers for the CSV file.
 */
function downloadClusterDataAsCSV(data, filename, headers) {
    if (!data || Object.keys(data).length === 0) {
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n";

    Object.entries(data).forEach(([clusterId, clusterData]) => {
        const places = clusterData.records ? clusterData.records : clusterData;    
        places.forEach((place) => {
            let row = headers.map(header => {
                if(header === "Cluster ID") {
                    return parseInt(clusterId) + 1;
                } else if (typeof place[header] === 'string') {
                    // Replace quotes with double quotes for CSV formatting
                    return `"${place[header].replace(/"/g, '""')}"`;
                } else {
                    return place[header];
                }
            }).join(",");
            csvContent += row + "\n";
        });
    });

    // Create a link and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Downloads statistical data as a CSV file.
 * 
 * This function is designed to handle simple key-value data representing statistical information.
 * The data is expected to be an object where keys represent the statistic names and values represent
 * 
 * @param {Object} data - The statistical data to be downloaded. An object where keys represent the
 * statistic names and values represent the statistic values.
 * @param {string} filename - The name of the file to be downloaded, including the .csv extension.
 */

function downStatisticsDataAsCSV(data, filename) {
    if (!data || Object.keys(data).length === 0) {
        alert("No data available for download.");
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,Statistic,Value,Unit\n";

    Object.entries(data).forEach(([statistic, value]) => {
        const unit = statistic.includes("Area") ? "km²" : "km";
        csvContent += `"${statistic}","${value}","${unit}"\n`;
    });

    // Create a link and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}