import BaseEquippedItemDetails from '../../../../../../api-definitions/items/base-equipped-item-details';
import {
  AncestralItemSkillData,
  CharacterBoonDefinition,
  ClassSpecialities,
  MapReduction,
} from '../../../character-stat-types/api-definitions/character-stat-break-down-definition';

export interface AttackAttachedAffix {
  affix_name: string;
  base_damage_mod: number;
}

export interface ClassBonusDetails {
  name: string;
  amount: number;
}

export interface EffectingSkills {
  name: string;
  increase_amount: number;
}

export interface MasteryBreakdown {
  position: string;
  name: string;
  amount: number;
}

export interface CharacterAttackTypeBreakDownDefinition {
  damage_stat_name: string;
  damage_stat_amount: number;
  non_equipped_damage_amount: number;
  non_equipped_percentage_of_stat_used: number;
  spell_damage_stat_amount_to_use: number;
  percentage_of_stat_used: number;
  total_damage_for_type: number;
  base_damage: number;
  base_healing: number;
  items_equipped: BaseEquippedItemDetails[] | [];
  class_bonus_details: ClassBonusDetails | null;
  boon_details: CharacterBoonDefinition | null;
  class_specialties: ClassSpecialities[] | null;
  ancestral_item_skill_data: AncestralItemSkillData[] | [];
  skills_effecting_damage: EffectingSkills[] | null;
  skill_affecting_healing: EffectingSkills[] | null;
  skills_effecting_ac: EffectingSkills[] | null;
  masteries: MasteryBreakdown[] | [];
  map_reduction: MapReduction | null;
  stat_amount: number;
  base_ac: number;
  ac_from_items: number;
  spell_evasion: number;
  affix_damage_reduction: number;
  healing_reduction: number;
}

export default interface CharacterAttackBreakDownDefinition {
  regular: CharacterAttackTypeBreakDownDefinition;
  voided: CharacterAttackTypeBreakDownDefinition;
}
