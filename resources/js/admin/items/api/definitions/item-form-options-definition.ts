import { ItemAlchemyType } from '../../enums/item-alchemy-type';
import { ItemCatalogType } from '../../enums/item-catalog-type';
import { ItemCraftingType } from '../../enums/item-crafting-type';
import { ItemDefaultPosition } from '../../enums/item-default-position';
import { ItemEffectType } from '../../enums/item-effect-type';
import { ItemSkillType } from '../../enums/item-skill-type';
import { ItemSpecialtyType } from '../../enums/item-specialty-type';

export interface ItemEntityOption {
  value: number;
  label: string;
}

export default interface ItemFormOptionsDefinition {
  types: ItemCatalogType[];
  default_positions: ItemDefaultPosition[];
  crafting_types: ItemCraftingType[];
  alchemy_types: ItemAlchemyType[];
  specialty_types: ItemSpecialtyType[];
  effects: ItemEffectType[];
  skill_types: ItemSkillType[];
  item_skills: ItemEntityOption[];
  locations: ItemEntityOption[];
  classes: ItemEntityOption[];
}
