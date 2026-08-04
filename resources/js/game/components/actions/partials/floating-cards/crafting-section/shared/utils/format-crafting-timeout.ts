const formatTimeUnit = (
  amount: number,
  singular: string,
  plural: string
): string => {
  return `${amount} ${amount === 1 ? singular : plural}`;
};

export const formatCraftingTimeout = (remainingSeconds: number): string => {
  const minutes = Math.floor(remainingSeconds / 60);
  const seconds = remainingSeconds % 60;

  if (minutes === 0) {
    return formatTimeUnit(seconds, 'second', 'seconds');
  }

  if (seconds === 0) {
    return formatTimeUnit(minutes, 'minute', 'minutes');
  }

  return `${formatTimeUnit(minutes, 'minute', 'minutes')} ${formatTimeUnit(
    seconds,
    'second',
    'seconds'
  )}`;
};
