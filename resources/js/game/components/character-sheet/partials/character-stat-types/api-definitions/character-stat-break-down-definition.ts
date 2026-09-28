import BaseEquippedItemDetails from '../../../../../api-definitions/items/base-equipped-item-details';
import { BaseItemDetails } from '../../../../../api-definitions/items/base-item-details';
import { CharacterGemModifierType } from '../../../../../reusable-components/character-gem/enums/character-gem-modifier-type';

export interface AncestralItemSkillData {
  increase_amount: number;
  name: string;
}

export interface ClassSpecialities {
  amount: number;
  name: string;
}

export interface MapReduction {
  map_name: string;
  reduction_amount: number;
}

export interface CharacterBoonDefinition {
  increases_all_stats: IncreaseAllStats[] | [];
}

export interface IncreaseAllStats {
  increase_amount: number;
  item_details: BaseItemDetails;
}

export interface CharacterGemStatDetailDefinition {
  gem_id: number;
  gem_name: string;
  tier: number;
  item_id: number;
  item_name: string;
  modifier_type: CharacterGemModifierType;
  amount: number;
  ability_name: string | null;
}

export default interface CharacterStatBreakDownDefinition {
  base_value: number;
  modded_value: number;
  boon_details: CharacterBoonDefinition;
  ancestral_item_skill_data: AncestralItemSkillData[] | [];
  items_equipped: BaseEquippedItemDetails[] | [];
  class_specialties: ClassSpecialities[] | [];
  map_reduction: MapReduction | null;
  gem_details: CharacterGemStatDetailDefinition[];
}
