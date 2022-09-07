/**
 * Class to generate collection legend.
 */
class CollectionLegend {

    /**
     * Class constructor.
     */
    constructor() {
        this.items = [];
    }

    /**
     * Add a legend item.
     *
     * @param {string} name
     *   The item name.
     * @param {string} color
     *   The HEX color code of the item.
     */
    addItem(name, color) {
        this.items.push({
            name: name,
            color: color
        });
    }

    /**
     * Render the legend HTML to the given container.
     *
     * @param {jQuery} container
     *   The container element.
     */
    render(container) {
        let html = `<div class="collection-legend">`;
        // Use the reverse order to keep it consistent with the LayerList widget.
        for (let i = this.items.length - 1; i >= 0; i--) {
            html += `<div class="collection-legend-item">`;
            html += `<span class="collection-legend-color" style="background-color:${this.items[i].color}"></span>`;
            html += `<span class="collection-legend-name">${CollectionUtility.sanitize(this.items[i].name)}</span>`;
            html += `</div>`;
        }
        html += `</div>`;
        container.append(html);
    }
}
