export const resolveLineChartNumber = (value: unknown): number | null => {
  if (typeof value !== 'number') {
    return null;
  }

  if (!Number.isFinite(value)) {
    return null;
  }

  return value;
};
