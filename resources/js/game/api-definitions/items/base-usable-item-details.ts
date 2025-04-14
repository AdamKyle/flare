type ModifiableKeys =
  | 'kingdom_damage'
  | 'increase_skill_bonus_by'
  | 'increase_skill_training_bonus_by'
  | 'base_healing_mod'
  | 'base_ac_mod'
  | 'base_damage_mod'
  | 'stat_increase'
  | 'holy_level';

export type BaseUsableItemDetails = {
  [K in ModifiableKeys]?: number | null;
};
