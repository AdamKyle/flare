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
 * @type [{number: number}]
 */
export const formatNumber = (number: number): string => {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
