/**
 * Read a dataset attribute and return it as a positive finite integer, or `null`
 * when the attribute is missing or does not represent a positive integer.
 */
export const readPositiveDatasetInteger = (
  element: HTMLElement,
  key: string
): number | null => {
  const rawValue = element.dataset[key];

  if (rawValue === undefined) {
    return null;
  }

  const parsedValue = Number(rawValue);

  if (
    !Number.isFinite(parsedValue) ||
    !Number.isInteger(parsedValue) ||
    parsedValue <= 0
  ) {
    return null;
  }

  return parsedValue;
};
