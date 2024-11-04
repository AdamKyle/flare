export const xpbarPercentage = (currentXp: number, maxXp: number): number => {
    return parseInt(((currentXp / maxXp) * 100).toFixed(0));
};
