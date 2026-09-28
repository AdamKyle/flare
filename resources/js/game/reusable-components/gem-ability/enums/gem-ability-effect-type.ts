export enum GemAbilityEffectType {
  BONUS_DAMAGE = 'bonus_damage',
  WEAPON_DAMAGE_MOD = 'weapon_damage_mod',
  SPELL_DAMAGE_MOD = 'spell_damage_mod',
  HEALING_MOD = 'healing_mod',
  DEFENCE_MOD = 'defence_mod',
}

export const GEM_ABILITY_EFFECT_TYPE_LABELS: Record<
  GemAbilityEffectType,
  string
> = {
  [GemAbilityEffectType.BONUS_DAMAGE]: 'Bonus Damage',
  [GemAbilityEffectType.WEAPON_DAMAGE_MOD]: 'Weapon Damage Modifier',
  [GemAbilityEffectType.SPELL_DAMAGE_MOD]: 'Spell Damage Modifier',
  [GemAbilityEffectType.HEALING_MOD]: 'Healing Modifier',
  [GemAbilityEffectType.DEFENCE_MOD]: 'Defence Modifier',
};

const GEM_ABILITY_EFFECT_TYPE_VALUES: readonly GemAbilityEffectType[] =
  Object.values(GemAbilityEffectType);

export const isGemAbilityEffectType = (
  value: string
): value is GemAbilityEffectType =>
  GEM_ABILITY_EFFECT_TYPE_VALUES.some((candidate) => candidate === value);

export const gemAbilityEffectTypeLabel = (value: string): string =>
  isGemAbilityEffectType(value) ? GEM_ABILITY_EFFECT_TYPE_LABELS[value] : value;
