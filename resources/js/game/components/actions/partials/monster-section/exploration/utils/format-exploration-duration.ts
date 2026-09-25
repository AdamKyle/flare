const pad = (value: number): string => value.toString().padStart(2, '0');

/**
 * Formats a whole number of elapsed seconds as `H:MM:SS` (or `MM:SS` under
 * an hour), matching the server-computed `duration` on the Exploration
 * output panel.
 */
export const formatExplorationDuration = (totalSeconds: number): string => {
  const safeSeconds = Math.max(0, Math.floor(totalSeconds));
  const hours = Math.floor(safeSeconds / 3600);
  const minutes = Math.floor((safeSeconds % 3600) / 60);
  const seconds = safeSeconds % 60;

  if (hours > 0) {
    return `${hours}:${pad(minutes)}:${pad(seconds)}`;
  }

  return `${pad(minutes)}:${pad(seconds)}`;
};
