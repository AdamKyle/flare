export const healthBarPercentage = (
  currentHealth: number,
  maxHealth: number
): number => {
  if (currentHealth <= 0) {
    return 0;
  }

  return parseInt(((currentHealth / maxHealth) * 100).toFixed(0));
};
