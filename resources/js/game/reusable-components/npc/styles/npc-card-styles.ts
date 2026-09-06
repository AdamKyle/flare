/**
 * Canonical Terracotta NPC-domain card visual language: a full-width
 * relationship card using the project's Terracotta palette, distinct from
 * the Location domain's Emerald cards and the Quest domain's Glacier cards.
 */
export const npcCardBaseStyles = (): string =>
  'border-2 w-full flex items-start gap-3 p-3 rounded-lg shadow-sm text-left transition-colors';

export const npcCardThemeStyles = (): string =>
  'border-terracotta-600 dark:border-terracotta-500 bg-terracotta-50 dark:bg-terracotta-950/40 hover:bg-terracotta-100 dark:hover:bg-terracotta-900/40';

export const npcCardIconStyles = (): string =>
  'text-terracotta-700 dark:text-terracotta-300';

export const npcCardPrimaryTextStyles = (): string =>
  'text-terracotta-900 dark:text-terracotta-100';

export const npcCardSecondaryTextStyles = (): string =>
  'text-terracotta-700 dark:text-terracotta-300';

export const npcCardFocusRingStyles = (): string =>
  'focus-visible:ring-terracotta-500 dark:focus-visible:ring-terracotta-300 focus:outline-none focus-visible:ring-2';
