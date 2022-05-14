/**
 * Formats a number with comma seperated values.
 *
 * @param number
 * @return number
 * @type [{number: string}]
 */
export const removeCommas = (number: string): number => {
    return parseInt(number.replace(/,/g, ''));
}

/**
 * Turns a number into a comma serpeated string.
 *
 * @param number
 * @return string
 * @type [{number: number | null | undefined}]
 */
export const formatNumber = (number: number | string | null | undefined): string => {

    if (number === null) {
        return '0';
    }

    if (typeof number === 'undefined') {
        return '0';
    }

    if (typeof number === 'string') {
        return parseInt(number).toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    return number.toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Takes floats and makes them into percentages.
 *
 * @param number
 * @return number
 * @type [{number: number | null | undefined}]
 */
export const percent = (number: number | null | undefined): number => {
    if (number === null) {
        return 0.0;
    }

    if (typeof number === 'undefined') {
        return 0.0;
    }

    return parseInt((number * 100).toFixed(2));
}
