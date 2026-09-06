/**
 * Compact density base styles for `ReadOnlyItemCard`: matches the
 * density/shadow quality of the canonical Quest/Location relationship
 * cards, while composing with the existing Quest Item Marigold palette
 * functions (`backpackFocusRingStyles`/`backpackBorderStyles`/
 * `backpackButtonBackground`) exactly as the default density does. Owns
 * only compact layout, never Item-color business rules. Deliberately
 * separate from `backpackBaseItemStyles()`, which is left untouched and
 * continues to own the Character inventory's card presentation.
 */
export const readOnlyItemCardCompactBaseStyles = (): string =>
  'border-2 w-full flex items-start gap-3 p-3 rounded-lg shadow-sm text-left transition-colors focus:outline-none focus-visible:ring-2';
