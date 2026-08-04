import CraftableItemDefinition from '../../definitions/craftable-item-definition';

export default interface UseCraftItemApiParams {
  characterId: number;
  selectedItem: CraftableItemDefinition | null;
}
