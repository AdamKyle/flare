/**
 * `StackedCard` child-content presentation ownership. `PADDED` preserves the
 * generic shell's own padding/scroll ownership for form/edit stacks.
 * `FULL_BLEED` hands scroll and horizontal padding ownership to the nested
 * canonical factual detail component so a deep stack of pushed factual
 * detail never nests competing scroll containers or progressively insets.
 */
export enum StackedCardContentMode {
  PADDED = 'padded',
  FULL_BLEED = 'full_bleed',
}
