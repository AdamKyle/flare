import BaseEquippedItemDetails from '../../../../../api-definitions/items/base-equipped-item-details';
import { BaseItemDetails } from '../../../../../api-definitions/items/base-item-details';

export interface AncestralItemSkillData {
  increase_amount: number;
  name: string;
}

export interface ClassSpecialities {
  amount: number;
  name: string;
}

export interface MapReduction {
  name: string;
  reduction_amount: number;
}

export interface CharacterBoonDefinition {
  increases_all_stats: IncreaseAllStats[] | [];
}

export interface IncreaseAllStats {
  increase_amount: number;
  item_details: BaseItemDetails;
}

export default interface CharacterStatBreakDownDefinition {
  base_value: number;
  modded_value: number;
  boon_details: CharacterBoonDefinition;
  ancestral_item_skill_data: AncestralItemSkillData[] | [];
  items_equipped: BaseEquippedItemDetails[] | [];
  class_specialties: ClassSpecialities[] | [];
  map_reduction: MapReduction | null;
}
