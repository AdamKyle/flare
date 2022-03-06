/**
 * Formats a number with comma seperated values.
 *
 * @param number
 * @return number
 * @type [{number: string}]
 */
export const formatNumber = (number: string): number => {
    return parseInt(number.replace(/\B(?=(\d{3})+(?!\d))/g, ","));
}
