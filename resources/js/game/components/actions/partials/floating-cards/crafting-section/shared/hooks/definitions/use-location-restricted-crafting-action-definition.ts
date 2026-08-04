export default interface UseLocationRestrictedCraftingActionDefinition {
  locationRestrictionWarning: string | null;
  clearLocationRestrictionWarning: () => void;
}
