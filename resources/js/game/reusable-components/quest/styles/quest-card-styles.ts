import { QUEST_TREE_STATE_BORDER_STYLES } from './quest-tree-state-styles';
import { QuestTreeState } from '../enums/quest-tree-state';

/**
 * Canonical Quest-domain card visual language: the same density/shadow/focus
 * quality established by the inventory Item card, using the `glacier`
 * palette instead of Item's `marigold` palette so the two domains stay
 * visually distinct while feeling like the same design system.
 */
export const questCardBaseStyles = (): string =>
  'border-2 w-full flex items-start gap-3 p-4 rounded-lg shadow-md text-left transition-colors';

/**
 * Quest card Glacier surface: an intentionally light card background even
 * in dark mode, matching the established high-contrast
 * light-card-on-dark-page treatment already used by the inventory Quest
 * Item cards. Border is owned separately by `questCardBorderStyles` so a
 * card's structural Quest state can override it without disturbing the
 * Quest domain's Glacier surface identity.
 */
export const questCardThemeStyles = (): string =>
  'bg-glacier-100 dark:bg-glacier-100 hover:bg-glacier-200 dark:hover:bg-glacier-200';

/**
 * Resolve a Quest card's border: the canonical Quest-domain Glacier border
 * when no structural Quest state is supplied (e.g. relationship cards with
 * no player-preview state), or the shared state border language when the
 * card does carry a `QuestTreeState`.
 *
 * @param  state  Optional structural Quest state to border by.
 * @return  Border Tailwind class string.
 */
export const questCardBorderStyles = (state?: QuestTreeState): string =>
  state
    ? QUEST_TREE_STATE_BORDER_STYLES[state]
    : 'border-glacier-700 dark:border-glacier-500';

/**
 * Primary Quest card text color.
 */
export const questCardPrimaryTextStyles = (): string => 'text-glacier-900';

/**
 * Secondary Quest card text color, for supporting metadata.
 */
export const questCardSecondaryTextStyles = (): string => 'text-glacier-700';

/**
 * Focus-visible ring treatment for interactive Quest controls (e.g. the
 * Quest title button) nested inside a Quest card.
 */
export const questCardFocusRingStyles = (): string =>
  'focus-visible:ring-glacier-500 focus:outline-none focus-visible:ring-2 rounded-sm';
