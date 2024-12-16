export const healthBarPercentage = (
  currentHealth: number,
  maxHealth: number
): number => {
  return parseInt(((currentHealth / maxHealth) * 100).toFixed(0));
};
