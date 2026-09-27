export const buildingCardBaseStyles = (): string =>
  'flex w-full items-start gap-3 rounded-lg border-2 border-ferra-300 bg-ferra-100 p-4 text-left text-ferra-900 shadow-md transition-colors dark:border-ferra-700 dark:bg-ferra-950 dark:text-ferra-100';

export const buildingCardInteractiveStyles = (): string =>
  'hover:bg-ferra-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-ferra-400 dark:hover:bg-ferra-900 dark:focus-visible:ring-ferra-500';

export const buildingCardSecondaryTextStyles = (): string =>
  'text-ferra-700 dark:text-ferra-300';
