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
        const regex = /^-?\d{1,4}(?:-(0?[1-9]|1[0-2])(?:-(0?[1-9]|[12]\d|3[01]))?)?$/;
        const regex2 = /^(0?[1-9]|[12]\d|3[01])\/(0?[1-9]|1[0-2])\/(-?\d{1,4})$/;
        return regex.test(value) || regex2.test((value));
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
        const regex = /^(?:\w+:)?\/\/([^\s.]+\.\S{2})\S*(?:\/[^\s]*)?(?:\?[^#\s]*)?(?:#[^\s]*)?$/;
        return regex.test(value);
    }

    /**
     * Validate a natural number string.
     * (For "quantity" value)
     *
     * @param {string} value
     *   The input value.
     * @returns {boolean}
     */
    static naturalNumber(value) {
        const regex = /^(?:0|[1-9]\d*)$/;
        return regex.test(value);
    }
}
