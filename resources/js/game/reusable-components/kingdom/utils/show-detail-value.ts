export type ShowDetailValue = string | number | boolean | null | undefined;

export const shouldShowDetailValue = (value: ShowDetailValue): boolean => {
  if (value === null || value === undefined || value === false || value === 0) {
    return false;
  }

  if (typeof value !== 'string') {
    return true;
  }

  const normalizedValue = value.trim();

  return (
    normalizedValue !== '' &&
    normalizedValue !== '0' &&
    normalizedValue !== 'N/A'
  );
};

export const isPositiveShowNumber = (value: ShowDetailValue): value is number =>
  typeof value === 'number' && value > 0;
