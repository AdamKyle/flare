export enum GemEffectPolarity {
  BENEFICIAL = 'beneficial',
  HARMFUL = 'harmful',
}

const HARMFUL_FIELDS = new Set<string>([
  'character_power_reduction',
  'enemy_strength_increase',
  'enemy_healing_increase',
  'enemy_spell_evasion',
  'enemy_affix_resistance',
  'enemy_entrancing_chance',
  'enemy_devouring_light_chance',
  'enemy_devouring_darkness_chance',
  'enemy_ambush_chance',
  'enemy_ambush_resistance',
  'enemy_counter_chance',
  'enemy_counter_resistance',
  'monster_atonement_amount',
  'personal_negative_effect_increase',
]);

/**
 * Resolve whether a Gem effect field is beneficial to the Character or harmful
 * (a Character-negative effect, or one that makes combat more difficult),
 * keyed by the same field names used across the Gem domain data. The numeric
 * value always increases; this only classifies whether that increase helps
 * or hurts the Character.
 */
export const resolveGemEffectPolarity = (field: string): GemEffectPolarity =>
  HARMFUL_FIELDS.has(field)
    ? GemEffectPolarity.HARMFUL
    : GemEffectPolarity.BENEFICIAL;
