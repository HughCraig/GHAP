/**
 * Class used for legend color generation.
 */
class LegendColorGenerator {

    /**
     * Class constructor.
     *
     * @param {Array|null} baseColors
     *   The list of base colors. Each color in the array should be a HEX color code start with '#'. If null is set, It
     *   will use the default colors.
     */
    constructor(baseColors = null) {
        // Define the base colors. If the color is not passed in, use the default colors.
        if (baseColors && Array.isArray(baseColors)) {
            this.baseColors = baseColors;
        } else {
            // Color blind safe colors. Ref: https://davidmathlogic.com/colorblind
            this.baseColors = [
                '#E69F00',
                '#56B4E9',
                '#009E73',
                '#F0E442',
                '#0072B2',
                '#D55E00',
                '#CC79A7'
            ];
        }
        // The delta of last generated color.
        this.index = -1;
        // The variation unit to be used for color adjust.
        this.variationUnit = 50;
    }

    /**
     * Generate a color.
     *
     * @return {string}
     *   A HEX color code.
     */
    generate() {
        this.index++;
        const rounds = Math.floor(this.index / this.baseColors.length);
        const delta = this.index % this.baseColors.length;
        let color = this.baseColors[delta];
        if (rounds > 0) {
            color = LegendColorGenerator.adjustColor(color, rounds * this.variationUnit);
        }
        return color;
    }

    /**
     * Reset the generator to start.
     */
    reset() {
        this.index = -1;
    }

    /**
     * Adjust a HEX color by certain amount.
     *
     * @param {string} color
     *   The HEX color code.
     * @param {int} amount
     *   The amount to adjust. Positive integer will lighten the color, and negative integer will darken the color.
     * @return {string}
     *   The altered HEX color code.
     */
    static adjustColor(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => ('0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2));
    }
}
