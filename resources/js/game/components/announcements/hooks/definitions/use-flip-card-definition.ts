export default interface UseFlipCardDefinition {
  flippedCardKey: string | null;
  handleToggleCard: (cardKey: string) => void;
}
