class Validation {
    /**
     * Validate an TLCMap date string.
     *
     * - The string could be a valid date yyyy-mm-dd, dd/mm/yyyy. Month and day could be single digit, or have the
     *   leading 0.
     * - Could be year only, or yyyy-mm
     * - Allow any number of years, eg: yyyy, or yyy, and allow negative years for BC dates eg: -yyyy)
     *
     * @param {string} value
     *   The input value.
     * @returns {boolean}
     */
    static date(value) {
        // Matches dates like '1862-00-00'.
        const regex1 = new RegExp("[1-9]+-00-00");

        // Matches dates like '00/00/1862'.
        const regex2 = new RegExp("00/00/[1-9]+");

        // Matches dates in the 'yyyy-mm' format (e.g., '2023-09').
        const regex3 = new RegExp("^([0-9]{4})-(0[1-9]|1[012])$");

        // Matches dates and optional times in an ISO 8601-like format. Supports BC/BCE years.
        const regex4 = new RegExp(
            "^(-?[0-9]*[1-9]+0*)(-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])(T(0?[0-9]|1[0-9]|2[0-3])(:(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])(:(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])([.][0-9]+)?)?)?)?)?$"
        );

        // Matches dates in the 'DD/MM/YYYY' format with optional time in 'HH:MM' format.  Supports BC/BCE years.

        const regex5 =
            /^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(-?[0-9]*[1-9]+0*)( ((0?[0-9]|1[0-9]|2[0-3]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])))?$/;

        return (
            regex1.test(value) ||
            regex2.test(value) ||
            regex3.test(value) ||
            regex4.test(value) ||
            regex5.test(value)
        );
    }

    /**
     * Validate a latitude string.
     *
     * @param {string} value
     *   The input value.
     * @returns {boolean}
     */
    static latitude(value) {
        const regex = /^[-+]?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?)$/;
        return regex.test(value);
    }

    /**
     * Validate a longitude string.
     *
     * @param {string} value
     *   The input value.
     * @returns {boolean}
     */
    static longitude(value) {
        const regex = /^[-+]?((?:1[0-7]|[1-9])?\d(?:\.\d+)?|180(?:\.0+)?)$/;
        return regex.test(value);
    }

    /**
     * Validate a URL string.
     *
     * @param value
     * @returns {boolean}
     */
    static url(value) {
        const regex =
            /^(?:\w+:)?\/\/([^\s.]+\.\S{2})\S*(?:\/[^\s]*)?(?:\?[^#\s]*)?(?:#[^\s]*)?$/;
        return regex.test(value);
    }

    /**
     * Validate a natural number string.
     *
     * @param {string} value
     *   The input value.
     * @returns {boolean}
     */
    static naturalNumber(value) {
        const regex = /^(?:0|[1-9]\d*)$/;
        return regex.test(value);
    }

    /**
     * Validate the stop number of route.
     *
     * @param {string} value
     *   The input value.
     * @returns {boolean}
     */
    static isValidStopIdx(value) {
        return (
            Validation.naturalNumber(value) || value.toLowerCase() === "append"
        );
    }
}
