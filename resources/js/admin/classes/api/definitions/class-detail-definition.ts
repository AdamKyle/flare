import ClassRelatedIdentityDefinition from './class-related-identity-definition';

export interface ClassAttributesDefinition {
  str_mod: number;
  dur_mod: number;
  dex_mod: number;
  chr_mod: number;
  int_mod: number;
  agi_mod: number;
  focus_mod: number;
}

export interface ClassCombatModifiersDefinition {
  accuracy_mod: number;
  dodge_mod: number;
  defense_mod: number;
  looting_mod: number;
}

export interface ClassUnlockRequirementsDefinition {
  primary_required_class: ClassRelatedIdentityDefinition;
  secondary_required_class: ClassRelatedIdentityDefinition;
  primary_required_class_level: number;
  secondary_required_class_level: number;
}

export default interface ClassDetailDefinition {
  id: number;
  name: string;
  description: string | null;
  damage_stat: string;
  to_hit_stat: string;
  attributes: ClassAttributesDefinition;
  combat_modifiers: ClassCombatModifiersDefinition;
  unlock_requirements: ClassUnlockRequirementsDefinition | null;
}
