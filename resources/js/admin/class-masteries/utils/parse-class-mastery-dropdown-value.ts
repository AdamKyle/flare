export const parseNumberOption = (value: string | number): number | null => {
  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : null;
};
