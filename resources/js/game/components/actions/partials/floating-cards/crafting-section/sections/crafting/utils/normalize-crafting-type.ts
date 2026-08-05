import CraftableItemDefinition from '../api/definitions/craftable-item-definition';

export const normalizeCraftingType = (
  item: CraftableItemDefinition
): string => {
  if (item.crafting_type === 'armour') {
    return 'armour';
  }

  if (
    item.preview.type === 'spell-damage' ||
    item.preview.type === 'spell-healing'
  ) {
    return 'spell';
  }

  return item.preview.type;
};
