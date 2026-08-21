import {
  CraftableItemCraftingType,
  CraftableItemSubtype,
} from '../../api/definitions/craftable-item-query-definition';

export interface CraftTypeOptionDefinition {
  label: string;
  value: CraftableItemCraftingType;
}

export interface ArmourTypeOptionDefinition {
  label: string;
  value: CraftableItemSubtype;
}
