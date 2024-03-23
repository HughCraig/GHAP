/**
 * Downloads cluster data as a CSV file.
 * 
 * This function takes a structured data object where each key represents a cluster ID
 * and its value is an array of place objects belonging to that cluster. 
 *
 * @param {Object} data - The cluster data to be downloaded. Expected to be an object where
 * each key is a cluster ID and the value is an array of objects representing places.
 * @param {string} filename - The name of the file to be downloaded, including the .csv extension.
 */
function downloadClusterDataAsCSV(data, filename) {
    if (!data || Object.keys(data).length === 0) {
        return;
    }

    let csvContent = "";

    // Extract headers dynamically from the first element of the data
    const firstClusterKey = Object.keys(data)[0];
    const firstRecord = data[firstClusterKey].records ? data[firstClusterKey].records[0] : data[firstClusterKey][0];
    const headers = Object.keys(firstRecord);
    csvContent += headers.join(",") + "\n";

    Object.entries(data).forEach(([clusterId, clusterData]) => {
        const places = clusterData.records ? clusterData.records : clusterData;    
        places.forEach((place) => {
            let row = headers.map(header => {
                if (typeof place[header] === 'string') {
                    // Replace quotes with double quotes for CSV formatting
                    return `"${place[header].replaceAll(/"/g, '""')}"`;
                } else {
                    return place[header];
                }
            }).join(",");
            csvContent += row + "\n";
        });
    });

    const encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);

    // Create a link and trigger download
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", replaceWithUnderscores(filename));
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Downloads cluster data as a KML file.
 *
 * This function takes a structured data object where each key represents a cluster ID
 * and its value is an array of place objects belonging to that cluster. Each place object
 * must have latitude and longitude properties.
 *
 * @param {Object} data - The cluster data to be downloaded. Expected to be an object where
 * each key is a cluster ID and the value is an array of objects representing places.
 * @param {string} filename - The name of the file to be downloaded, including the .kml extension.
 */
function downloadClusterDataAsKML(data, filename) {
    if (!data || Object.keys(data).length === 0) {
        return;
    }

    let kmlContent =
        '<?xml version="1.0" encoding="UTF-8"?>\n' +
        '<kml xmlns="http://www.opengis.net/kml/2.2">\n' +
        "  <Document>\n";

    Object.entries(data).forEach(([index, clusterData]) => {
        const color = generateKMLColorFromStr(index);
        const places = clusterData.records ? clusterData.records : clusterData;
        places.forEach((place) => {
            let clusterId = parseInt(index) + 1;
            let description = `Cluster ID: ${clusterId}<br>`;
            let startDate = place.datestart || '';
        
            Object.entries(place).forEach(([key, value]) => {
                if (
                    key !== "latitude" &&
                    key !== "longitude" &&
                    key !== "datestart" && 
                    value !== "Geom_date"
                ) {
                    description += `${
                        key.charAt(0).toUpperCase() + key.slice(1)
                    }: ${value}<br>`;
                }
            });
        
            if (startDate) {
                description += `Date: ${startDate}<br>`; // Add 'Date' to the description
            }
        
            kmlContent +=
                `    <Style id="cluster${clusterId}Style">\n` +
                `      <IconStyle>\n` +
                `        <color>${color}</color>\n` +
                `        <scale>1.1</scale>\n` +
                "      </IconStyle>\n" +
                "    </Style>\n";
        
            kmlContent +=
                "    <Placemark>\n" +
                `      <name><![CDATA[${place.title || "Unnamed Place"}]]></name>\n` +
                `      <description><![CDATA[${description}]]></description>\n` +
                "      <Point>\n" +
                `        <coordinates>${place.longitude},${place.latitude}</coordinates>\n` +
                "      </Point>\n" +
                "    </Placemark>\n";
        });
    });

    kmlContent += "  </Document>\n</kml>";

    // Create a link and trigger download
    const encodedUri = encodeURI(
        `data:application/vnd.google-earth.kml+xml,${kmlContent}`
    );
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", replaceWithUnderscores(filename) + '.kml');
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
    link.setAttribute("download", replaceWithUnderscores(filename));
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Generates a color for a given string input. The generated color is intended for use in KML files.
 * KML color format is aabbggrr, where aa is the opacity, and bb, gg, rr are blue, green, and red color values in hexadecimal format.
 *
 * @param {string} str - The input string from which to generate a color.
 * @returns {string} A KML-compatible color string.
 */
function generateKMLColorFromStr(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const character = str.charCodeAt(i);
        hash = (hash << 5) - hash + character;
        hash &= hash; // Convert to 32bit integer
    }

    const color = (hash & 0x00ffffff).toString(16).toUpperCase();
    const paddedColor = "00000".substring(0, 6 - color.length) + color;

    const kmlColor = `ff${paddedColor.substring(4, 6)}${paddedColor.substring(
        2,
        4
    )}${paddedColor.substring(0, 2)}`;

    return kmlColor;
}

//Replace non-alphanumeric characters with underscores
function replaceWithUnderscores(str) {
    return str.replace(/[^a-zA-Z0-9]/g, '_');
}