export const progressFillClass = (progress: number): string => {
  if (progress > 66) {
    return 'bg-emerald-700';
  }

  if (progress > 33) {
    return 'bg-emerald-500';
  }

  return 'bg-emerald-300 dark:bg-emerald-400';
};
