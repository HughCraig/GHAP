/**
 * The loadConfig function fetches and processes a configuration Object from the geojson url or directly from a provided data..
 * It provides default configurations for various elements and overrides them with values
 * from the fetched url or data if they exist.
 * This function handles settings for display options, pop-up features, base map styles,
 * and other individual configurable elements.
 *
 * For each feature in 'features', the function will generate a pop-up template with a title and content,
 * which can include a custom content string, a default table of properties, and custom links.
 *
 * @param {string} urltoload - The URL to load the configuration file from.
 * @param {object} data - Directly provided geoJson data. 
 * @return {Promise<object>} A promise that resolves to the final configuration object.
 */
function loadConfig(urltoload , data = null) {
    const defaultBlockedFields = new Set(["tlcMapUniqueId"]);

    let config = {
        infoDisplay: "default",
        logo: logo_url,
        logoLink: "https://www.tlcmap.org",
        titleText: null,
        titleLink: null,
        titleLinkTarget: "_blank",
        content: null,
        basemapGallery: true,
        basemap: "hybrid",
        color: "orange", //Default color orange
        clusterColor: null,
        clusterFontColor:'#004a5d', //Default color dark blue
        popupEnabled: true, //Enable pop up by default
        popupTitle: null,
        popupContent: null,
        popupAllowedFields: null,
        popupBlockedFields: defaultBlockedFields,
        popupFieldLabels: null,
        popupLinks: null,
        textContent: null,
        textcontexts: null,
        datasetID: null,
        textID: null,
    };

    if (data) {
        return Promise.resolve(processGeoJsonData(data, config));
    } else if (urltoload ) {
        return new Promise((resolve, reject) => {
            fetch(urltoload)
                .then((response) => response.json())
                .then((data) => {
                    resolve(processGeoJsonData(data, config));
                })
                .catch((err) => {
                    console.error(err);
                    config.titleText = "Error: Unable to load GeoJSON data from the specified URL";
                    config["data"] = { features: [] };
                    resolve(config);
                });
        });
    } else {
        return Promise.resolve(config);
    }

    // Function to process geojson data
    function processGeoJsonData(data, config) {
         //global configurations
         if (data.hasOwnProperty("display")) {
            let display = data.display;

            //Info block
            if (display.hasOwnProperty("info")) {
                let info = display.info;

                if (info.hasOwnProperty("display")) {
                    // Info block configurations
                    switch (info.display) {
                        case "enabled":
                            config["infoDisplay"] = "default";
                            break;
                        case "disabled":
                            config["infoDisplay"] = "disabled";
                            break;
                        case "hidden":
                            config["infoDisplay"] = "hidden";
                            break;
                    }
                }

                //logo
                if (
                    info.hasOwnProperty("logo") &&
                    typeof info.logo === "string"
                ) {
                    config["logo"] = info.logo;
                    config["logoLink"] = null; //Remove logo link if custom logo is provided
                }

                //title
                if (info.hasOwnProperty("title")) {
                    if (typeof info.title === "string") {
                        config["titleText"] = info.title;
                    } else if (typeof info.title === "object") {
                        config["titleText"] = info.title.hasOwnProperty(
                            "text"
                        )
                            ? info.title.text
                            : null;
                        config["titleLink"] = info.title.hasOwnProperty(
                            "link"
                        )
                            ? info.title.link
                            : null;
                        if (info.title.target) {
                            config["titleLinkTarget"] =
                                info.title.target;
                        }
                    }
                }

                //content
                if (info.hasOwnProperty("content")) {
                    config["content"] = purifyContent(info.content);
                }
            }

            //base map gallery
            if (display.hasOwnProperty("basemapGallery")) {
                config["basemapGallery"] =
                    typeof display.basemapGallery === "boolean"
                        ? display.basemapGallery
                        : true;
            }

            //base map
            if (
                display.hasOwnProperty("basemap") &&
                typeof display.basemap === "string"
            ) {
                config["basemap"] = getMapStyle(display.basemap);
            }

            //Color
            if (display.hasOwnProperty("color")) {
                config["color"] = display.color;
            }

            //Cluster color
            if (display.hasOwnProperty("clusterColor")) {
                config["clusterColor"] = display.clusterColor;
            }

            //Cluster font color
            if (display.hasOwnProperty("clusterFontColor")) {
                config["clusterFontColor"] = display.clusterFontColor;
            }

            // Popup template
            if (display.hasOwnProperty("popup")) {
                //disable pop up
                if (display.popup === false) {
                    config["popupEnabled"] = false;
                }

                // Custom title
                if (display.popup.title) {
                    config["popupTitle"] = display.popup.title;
                }

                // Custom content
                if (display.popup.content) {
                    config["popupContent"] = display.popup.content;
                }

                // popup allowed fields
                if (
                    display.popup.allowedFields &&
                    Array.isArray(display.popup.allowedFields)
                ) {
                    if (
                        display.popup.allowedFields !== 1 &&
                        display.popup.allowedFields[0] !== "*"
                    ) {
                        // case for ["*"]
                        config["popupAllowedFields"] = new Set(
                            display.popup.allowedFields
                        );
                    }
                }

                // popup blocked fields
                if (
                    display.popup.blockedFields &&
                    Array.isArray(display.popup.blockedFields)
                ) {
                    display.popup.blockedFields.forEach((field) => {
                        defaultBlockedFields.add(field);
                    });
                }

                // popup field labels
                if (display.popup.fieldLabels) {
                    config["popupFieldLabels"] = new Map(
                        Object.entries(display.popup.fieldLabels)
                    );
                }

                // popup links
                if (
                    display.popup.links &&
                    Array.isArray(display.popup.links)
                ) {
                    config["popupLinks"] = display.popup.links;
                }
            }
        }

        if (data.hasOwnProperty("textcontent")) {
            config["textContent"] = data.textcontent;
        }

        if (data.hasOwnProperty("textcontexts")) {
            config["textcontexts"] = data.textcontexts;
        }

        if (data.hasOwnProperty("dataset_id")) {
            config["datasetID"] = data.dataset_id;
        }

        if (data.hasOwnProperty("textID")) {
            config["textID"] = data.textID;
        }

        //Pop up template for indivisual feature configurations
        let popupTemplateMap = new Map();
        if (data.features) {
            data.features.forEach((feature, index) => {
                if (!feature.properties) {
                    feature.properties = {};
                }

                feature.properties.tlcMapUniqueId = index; //Add id to properties.
                const id = index; //Use id (order) as distinct key

                //Load global configurtation first
                let { title, content } = buildDefaultPopup(
                    feature,
                    config
                );

                //Individual feature configurations will override global configurations
                if (feature.display && feature.display.popup) {
                    const popUp = feature.display.popup;

                    // Custom title . default: name.
                    if (popUp.title) {
                        const matches = popUp.title.match(/{(.*?)}/g);
                        const variablesExist = matches
                            ? matches.every((match) =>
                                  feature.properties.hasOwnProperty(
                                      match.slice(1, -1)
                                  )
                              )
                            : true;

                        // If all variables exist, use the custom title, otherwise use the name
                        if (variablesExist) {
                            title = popUp.title.replace(
                                /{(.*?)}/g,
                                (_, key) => feature.properties[key]
                            );
                        }
                    }

                    // Custom content. Could be interpolation or static. for interpolation , must match all variables in properties , otherwise use default null
                    if (popUp.content) {
                        const matches = popUp.content.match(/{(.*?)}/g);
                        const variablesExist = matches
                            ? matches.every((match) =>
                                  feature.properties.hasOwnProperty(
                                      match.slice(1, -1)
                                  )
                              )
                            : true;

                        if (variablesExist) {
                            let res = purifyContent(
                                popUp.content.replace(
                                    /{(.*?)}/g,
                                    (_, key) => feature.properties[key]
                                )
                            );

                            if (res && res != "") {
                                content.customContent = res; // Purify the content to prevent XSS
                            }
                        }
                    }

                    //Default table content

                    //Field labels
                    let fieldLabels = popUp.hasOwnProperty(
                        "fieldLabels"
                    )
                        ? new Map(Object.entries(popUp.fieldLabels))
                        : config.popupFieldLabels;

                    //Allowed fields
                    let allowedFields = null;
                    let allowAllFields = false;
                    if (
                        popUp.hasOwnProperty("allowedFields") &&
                        Array.isArray(popUp.allowedFields)
                    ) {
                        if (
                            popUp.allowedFields.length === 1 &&
                            popUp.allowedFields[0] === "*"
                        ) {
                            allowAllFields = true;
                        } else {
                            allowedFields = new Set(
                                popUp.allowedFields
                            );
                        }
                    }

                    allowedFields = allowAllFields
                        ? null
                        : allowedFields ?? config.popupAllowedFields;

                    //Blocked fields
                    let blockedFields =
                        popUp.hasOwnProperty("blockedFields") &&
                        Array.isArray(popUp.blockedFields)
                            ? new Set(popUp.blockedFields)
                            : config.popupBlockedFields;
                    blockedFields.add("tlcMapUniqueId"); //Always block this field

                    content.defaultTable = buildPopupContentTable(
                        feature,
                        fieldLabels,
                        allowedFields,
                        blockedFields
                    );

                }

                let finalContent = "";
                if (content.customContent) {
                    finalContent += content.customContent;
                }
                if (content.defaultTable) {
                    finalContent += content.defaultTable;
                }
                if (content.customLink) {
                    finalContent += content.customLink;
                }

                popupTemplateMap.set(id, {
                    title,
                    content: finalContent,
                });
            });
        }
        config["popupTemplateMap"] = popupTemplateMap;
        config["data"] = data;

        return config; 
    }
}

/**
 * Function to purify and sanitize HTML content.
 *
 * This function is designed to allow different attributes for different tags.
 * Specifically, it allows:
 * - the <p>, <strong>, <em>, <ul>, <ol>, <li>, <br> <table> <tr> <td> <th> <tbody> <thead> <tfoot> tags with no additional attributes.
 * - the <a> tag with 'href' and 'target' attributes.
 * - the <div> tag with 'class' attribute.
 * Invalid tags and attributes will be removed.
 *
 * @param {string} content - The HTML content string that needs to be sanitized.
 *
 * @returns {string} Sanitized HTML content.
 */
function purifyContent(content) {
    // Use DOMPurify to purify the tags first.
    content = DOMPurify.sanitize(content, {
        ALLOWED_TAGS: ["p", "a", "strong", "em", "ul", "ol", "li", "div", "br", "table", "tr", "td", "th", "tbody", "thead", "tfoot", 'img'],
        ALLOWED_ATTR: ["href", "target", "class", 'src', 'alt'],
    });

    if (content == null || content === "") {
        return content;
    }

    let parser = new DOMParser();
    let doc = parser.parseFromString(content, "text/html");

    // Define a function to recursively check and clean nodes
    function checkNode(node) {
        // Define the allowed attributes for each tag
        const allowedAttributes = {
            a: ["href", "target"],
            div: ["class"],
            p: [],
            strong: [],
            em: [],
            ul: [],
            ol: [],
            li: [],
            br: [],
            table: [],
            tr: [],
            td: [],
            th: [],
            tbody: [],
            thead: [],
            tfoot: [],
            img: ['src', 'alt']
        };

        // Get the current node name
        const nodeName = node.nodeName.toLowerCase();

        // If the current node is one of the specified tags
        if (allowedAttributes.hasOwnProperty(nodeName)) {
            // Get the list of allowed attributes for this tag
            let attributes = allowedAttributes[nodeName];

            // Get all attributes of the current node
            const nodeAttributes = node.attributes;

            for (let i = nodeAttributes.length - 1; i >= 0; i--) {
                // If the attribute is not in the list of allowed attributes, remove it
                if (!attributes.includes(nodeAttributes[i].name)) {
                    node.removeAttribute(nodeAttributes[i].name);
                }
            }
        }

        // Recursively check all child nodes
        for (let i = 0; i < node.childNodes.length; i++) {
            checkNode(node.childNodes[i]);
        }
    }

    checkNode(doc.documentElement);

    return doc.documentElement.innerHTML;
}

/**
 * Build popup title and content from global configurations
 *
 * For point : default title name is : {name} -> {title} -> {placename} -> "Route"
 * For line: defai;t title name is: {name} -> {title} -> "Route"
 * Content have three parts : customContent , defaultTable , customLink
 * @param {Object} feature feature object in geojson
 * @param {Object} config config object
 * @returns an object contains the title and content for pop up template
 */
function buildDefaultPopup(feature, config) {
    let title = "";

    if (feature.geometry.type === "Point") {
        title =
            feature.properties.name ||
            feature.properties.title ||
            feature.properties.placename ||
            "Place";
    } else if (feature.geometry.type === "LineString") {
        title = feature.properties.name || feature.properties.title || "Route";
    }

    let content = {};

    if (config.popupTitle) {
        const matches = config.popupTitle.match(/{(.*?)}/g);
        const variablesExist = matches
            ? matches.every((match) =>
                  feature.properties.hasOwnProperty(match.slice(1, -1))
              )
            : true;

        // If all variables exist, use the custom title, otherwise use the name
        if (variablesExist) {
            title = config.popupTitle.replace(
                /{(.*?)}/g,
                (_, key) => feature.properties[key]
            );
        }
    }

    if (config.popupContent) {
        const matches = config.popupContent.match(/{(.*?)}/g);
        const variablesExist = matches
            ? matches.every((match) =>
                  feature.properties.hasOwnProperty(match.slice(1, -1))
              )
            : true;

        if (variablesExist) {
            let res = purifyContent(
                config.popupContent.replace(
                    /{(.*?)}/g,
                    (_, key) => feature.properties[key]
                )
            );

            if (res && res != "") {
                content.customContent = res; // Purify the content to prevent XSS
            }
        }
    }

    //Default table content
    content.defaultTable = buildPopupContentTable(
        feature,
        config.popupFieldLabels,
        config.popupAllowedFields,
        config.popupBlockedFields
    );

    if (config.popupLinks) {
        let links = [];
        config.popupLinks.forEach((link) => {
            if (link.link && link.text) {
                let dummyElement = document.createElement("div");
                dummyElement.innerText = link.text;
                let safeText = dummyElement.innerHTML;

                links.push(
                    `<a href="${link.link}" target="${
                        link.target ? link.target : "_blank"
                    }">${safeText}</a>`
                );
            }
        });
        content.customLink = `<div style="margin-top: 1rem;">${links.join(
            " | "
        )}</div>`;
    }

    return { title, content };
}

/**
 * Build defaultTable for popup content
 * purifyContent() will be applied to table values, if all elements are restricted, this row
 * will not show
 *
 * @param {Object} feature  GeoJSON feature for each pin
 * @param {Map} fieldLabels Custom field label display
 * @param {Set} allowedFields White list for fields to display
 * @param {Set} blockedFields Black list for fields to display
 * @param {Array} links Links to display
 * @returns an HTML element of the constructed pop up table content
 */
function buildPopupContentTable(
    feature,
    fieldLabels,
    allowedFields,
    blockedFields
) {
    if (allowedFields && allowedFields.size === 0) {
        return null;
    }
    const properties = feature.properties;
    let urlPattern =
        /^(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})$/gi;
    let urlRegex = new RegExp(urlPattern);

    let res = "<div><table class='esri-widget__table'>";

    // Add the extra highlighted row as the first row
    res += `
        <tr style="background-color: #FFD580; font-weight: bold;">
            <td colspan="2">
                Know more about this or other places? 
                <a href="https://tlcmap.org/login" target="_blank" style="font-weight: 900">
                    Contribute…
                </a>
            </td> 
        </tr>
    `;

    if (feature.display && feature.display.source && feature.display.source.Layer) {
        res += `
            <tr>
                <td>Layer</td>
                <td>
                    <a href="${feature.display.source.Layer.url}" target="_blank" style="color:#0000EE">
                        ${feature.display.source.Layer.name}
                    </a>
                </td>
            </tr>
        `;
    }

    for (let key in properties) {
        if (allowedFields && !allowedFields.has(key)) {
            continue;
        }

        if (blockedFields && blockedFields.has(key)) {
            continue;
        }

        if (!properties[key] && properties[key] !== 0) {
            continue;
        }

        let value = properties[key];

        // If the value matches URL pattern, convert it into hyperlink.
        if (typeof value === "string" && value.match(urlRegex)) {
            value = `<a href="${value}" target="_blank">${value}</a>`;
        } else {
            value = purifyContent(value);
        }

        if (value == null || value === "") {
            continue;
        }

        const label =
            fieldLabels && fieldLabels.has(key) ? fieldLabels.get(key) : key;

        res += `<tr><th class="esri-feature-fields__field-header">${label}</th>
  <td class="esri-feature-fields__field-data">${value}</td></tr>`;
    }

    if (feature.display && feature.display.source && feature.display.source.TLCMapID) {
        res += `
            <tr>
                <td>TLCMap ID</td>
                <td>
                    <a href="${feature.display.source.TLCMapID.url}" target="_blank" style="color:#0000EE">
                        ${feature.display.source.TLCMapID.id}
                    </a>
                </td>
            </tr>
        `;
    }

    res += "</table></div>";
    return res;
}

/**
 * Validates the given map style against a set of predefined valid styles.
 * If the provided mapStyle is valid, it is returned; otherwise, "hybrid" is returned as a default value.
 *
 * @param {string} mapStyle - The map style to validate.
 * @returns {string} - The validated map style or "hybrid" if the provided style is not valid.
 */
function getMapStyle(mapStyle) {
    const validMapStyles = new Set([
        "satellite",
        "hybrid",
        "oceans",
        "osm",
        "terrain",
        "dark-gray-vector",
        "gray-vector",
        "streets-vector",
        "streets-night-vector",
        "streets-navigation-vector",
        "topo-vector",
        "streets-relief-vector",
        "topo-vector",
        "streets-vector",
        "dark-gray-vector",
        "gray-vector",
    ]);

    return validMapStyles.has(mapStyle) ? mapStyle : "hybrid";
}
