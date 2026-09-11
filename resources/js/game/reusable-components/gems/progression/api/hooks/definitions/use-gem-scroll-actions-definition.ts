export default interface UseGemScrollActionsDefinition {
  actingScrollId: number | null;
  successMessage: string | null;
  mutationError: string | null;
  useScroll: (alchemyBagSlotId: number) => Promise<void>;
  fillMapScroll: (
    characterGameMapGemScrollId: number,
    alchemyBagSlotId: number
  ) => Promise<void>;
  fillLocationScroll: (
    characterGameLocationGemScrollId: number,
    alchemyBagSlotId: number
  ) => Promise<void>;
  removeMapScroll: (characterGameMapGemScrollId: number) => Promise<void>;
  removeLocationScroll: (
    characterGameLocationGemScrollId: number
  ) => Promise<void>;
}
