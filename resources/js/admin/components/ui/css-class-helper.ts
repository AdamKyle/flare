/**
 * Joins all class names together.
 *
 * @type [{classes: string[]}]
 */
export const classNames = (...classes: string[]): string => {
    return classes.filter(Boolean).join(" ");
};
