import ItemStatDefinition from './definitions/item-stat-definition';
import ItemDetails from '../../../api-definitions/items/item-details';

export const getBaseItemStats = (item: ItemDetails): ItemStatDefinition[] => {
  return [
    { label: 'Strength', value: item.str_modifier, isPercent: true },
    { label: 'Durability', value: item.dur_modifier, isPercent: true },
    { label: 'Intelligence', value: item.int_modifier, isPercent: true },
    { label: 'Dexterity', value: item.dex_modifier, isPercent: true },
    { label: 'Charisma', value: item.chr_modifier, isPercent: true },
    { label: 'Agility', value: item.agi_modifier, isPercent: true },
    { label: 'Focus', value: item.focus_modifier, isPercent: true },
  ];
};
