import { QUEST_TREE_STATE_BORDER_STYLES } from './quest-tree-state-styles';
import { QuestTreeState } from '../enums/quest-tree-state';

export const questCardBaseStyles = (): string =>
  'border-2 w-full flex items-start gap-3 p-4 rounded-lg shadow-md text-left transition-colors';

export const questCardThemeStyles = (): string =>
  'bg-glacier-100 dark:bg-glacier-100 hover:bg-glacier-200 dark:hover:bg-glacier-200';

export const questCardBorderStyles = (state?: QuestTreeState): string =>
  state
    ? QUEST_TREE_STATE_BORDER_STYLES[state]
    : 'border-glacier-700 dark:border-glacier-500';

export const questCardPrimaryTextStyles = (): string => 'text-glacier-900';

export const questCardSecondaryTextStyles = (): string => 'text-glacier-700';

export const questCardFocusRingStyles = (): string =>
  'focus-visible:ring-glacier-500 focus:outline-none focus-visible:ring-2 rounded-sm';
