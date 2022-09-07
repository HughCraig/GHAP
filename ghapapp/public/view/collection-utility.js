/**
 * Class contains helper functions for collection visualization.
 */
class CollectionUtility {

    /**
     * Create the metadata display for a collection.
     *
     * @param {jQuery} container
     *   The jQuery object of the container.
     * @param {Object} properties
     *   The collection properties.
     */
    static createCollectionMetadataDisplay(container, properties) {
        if (properties.name && properties.url) {
            container.append(`<h3><a href="${properties.url}" target="_blank">${CollectionUtility.sanitize(properties.name)}</h3></p>`);
        }
        if (properties.description) {
            container.append(`<p>${CollectionUtility.sanitize(properties.description)}</p>`);
        }
        if (properties.warning) {
            container.append(`<div class="warning-message"><strong>Warning</strong><br />${CollectionUtility.sanitize(properties.warning)}</div>`);
        }
        container.append('<div class="legend-container"></div>');
        let linksHtml = '<p><a href="/guides/views/" target="_blank">Help</a> | <a href="/guides/views/#shareview" target="_blank">Share</a>';
        if (properties.linkback) {
            linksHtml += ` | <a href="${properties.linkback}" target="_blank">Linkback</a>`;
        }
        linksHtml += '</p>';
        container.append(linksHtml);
    }

    /**
     * Create the info panel element for a layer.
     *
     * @param {Object} layerData
     *   The layer data.
     * @return {HTMLElement}
     *   The HTML element of the info panel.
     */
    static createLayerInfoPanelElement(layerData) {
        const propElement = $('<div></div>').css({
            "padding-left": '13px'
        });
        if (layerData.color) {
            const legend = $('<div class="layer-prop-color"></div>')
                .css({
                    "width": '15px',
                    "height": '15px',
                    "border-radius": '50%',
                    "background-color": layerData.color
                });
            propElement.append(legend);
        }
        if (layerData.description) {
            propElement.append(`<p>${CollectionUtility.sanitize(layerData.description)}</p>`);
        }
        if (layerData.warning) {
            propElement.append(`<p>${CollectionUtility.sanitize(layerData.warning)}</p>`);
        }
        if (layerData.url) {
            propElement.append(`<p><a target="_blank" href="${layerData.url}">View layer details</a></p>`);
        }
        return propElement[0];
    }

    /**
     * Get the min and max time from an array of layers.
     *
     * @param {Array} layers
     *   Array contains the GeoJSONLayer instances.
     * @return {Array}
     *   The start date object and end date object.
     */
    static getLayersTimeExtent(layers) {
        let start = null;
        let end = null;
        for (let i = 0; i < layers.length; i++) {
            const layer = layers[i];
            if (typeof layer.timeInfo.fullTimeExtent.start !== 'undefined') {
                const layerStart = new Date(layer.timeInfo.fullTimeExtent.start);
                if (start === null || layerStart < start) {
                    start = layerStart;
                }
            }
            if (typeof layer.timeInfo.fullTimeExtent.end !== 'undefined') {
                const layerEnd = new Date(layer.timeInfo.fullTimeExtent.end);
                if (end === null || layerEnd > end) {
                    end = layerEnd;
                }
            }
        }
        return [start, end];
    }

    /**
     * Calculate the timeline interval unit based on the start and end time.
     *
     * @param {Date} start
     *   The start time.
     * @param {Date} end
     *   The end time.
     * @return {string}
     *   The interval unit.
     */
    static getTimelineIntervalUnit(start, end) {
        const fulltimespan = Math.abs(start.getTime() / 1000 - end.getTime() / 1000);
        let tunit = "minutes";
        tunit = (fulltimespan > 864000) ? "days" : tunit; //  than 10 days
        tunit = (fulltimespan > 31540000) ? "months" : tunit; //  than a year
        tunit = (fulltimespan > 1577000000) ? "years" : tunit; //  than 50 years
        tunit = (fulltimespan > 31540000000) ? "decades" : tunit; //  than 1000 years
        tunit = (fulltimespan > 315360000000) ? "centuries" : tunit; //  than 10000 years.
        return tunit;
    }

    /**
     * Sanitize the value for HTML output.
     *
     * @param {string} value
     *   The raw value.
     * @return {string}
     *   The sanitized value.
     */
    static sanitize(value) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;',
            "/": '&#x2F;',
        };
        const reg = /[&<>"'/]/ig;
        return value.replace(reg, (match) => (map[match]));
    }
}
