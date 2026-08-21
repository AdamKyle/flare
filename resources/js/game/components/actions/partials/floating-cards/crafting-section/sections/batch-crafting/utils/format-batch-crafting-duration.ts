const pad = (value: number): string => value.toString().padStart(2, '0');

const trimTrailingZeros = (value: string): string => {
  if (!value.includes('.')) {
    return value;
  }

  return value.replace(/0+$/, '').replace(/\.$/, '');
};

export const formatBatchCraftingChartTime = (
  elapsedSeconds: number
): string => {
  const safeSeconds = Math.max(0, elapsedSeconds);

  if (safeSeconds < 1) {
    return `${trimTrailingZeros(safeSeconds.toFixed(2))}s`;
  }

  if (safeSeconds < 10) {
    return `${trimTrailingZeros(safeSeconds.toFixed(1))}s`;
  }

  if (safeSeconds < 60) {
    return `${Math.round(safeSeconds)}s`;
  }

  const wholeSeconds = Math.floor(safeSeconds);
  const minutes = Math.floor(wholeSeconds / 60);
  const seconds = wholeSeconds % 60;

  return `${minutes}:${pad(seconds)}`;
};

export const formatBatchCraftingTimer = (elapsedSeconds: number): string => {
  const safeSeconds = Math.max(0, Math.floor(elapsedSeconds));
  const minutes = Math.floor(safeSeconds / 60);
  const seconds = safeSeconds % 60;

  return `${pad(minutes)}:${pad(seconds)}`;
};
