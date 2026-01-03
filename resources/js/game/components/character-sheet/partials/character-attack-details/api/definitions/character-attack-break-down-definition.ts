import { BaseItemDetails } from '../../../../../../api-definitions/items/base-item-details';
import {
  AncestralItemSkillData,
  CharacterBoonDefinition,
  ClassSpecialities,
} from '../../../character-stat-types/api-definitions/character-stat-break-down-definition';

export interface AttackAttachedAffix {
  affix_name: string;
  base_damage_mod: number;
}

export interface CharacterAttackEquippedItemDetails {
  item_base_stat: number | string;
  item_details: BaseItemDetails;
  total_stat_increase: number;
  attached_affixes: AttackAttachedAffix[] | [];
}

export interface ClassBonusDetails {
  name: string;
  amount: number;
}

export interface SkillEffectingDamage {
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
  damage_stat_amount: string;
  non_equipped_damage_amount: number;
  non_equipped_percentage_of_stat_used: number;
  spell_damage_stat_amount_to_use: number;
  percentage_of_stat_used: number;
  total_damage_for_type: string;
  base_damage: string;
  items_equipped: CharacterAttackEquippedItemDetails[] | [];
  class_bonus_details: ClassBonusDetails | null;
  boon_details: CharacterBoonDefinition | null;
  class_specialties: ClassSpecialities[] | null;
  ancestral_item_skill_data: AncestralItemSkillData[] | [];
  skills_effecting_damage: SkillEffectingDamage[] | null;
  masteries: MasteryBreakdown[] | [];
}

export default interface CharacterAttackBreakDownDefinition {
  regular: CharacterAttackTypeBreakDownDefinition;
  voided: CharacterAttackTypeBreakDownDefinition;
}
