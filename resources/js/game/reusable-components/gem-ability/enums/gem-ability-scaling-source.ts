export enum GemAbilityScalingSource {
  WEAPON_ATTACK = 'weapon_attack',
  SPELL_ATTACK = 'spell_attack',
  DAMAGE_STAT = 'damage_stat',
  DEFENCE = 'defence',
}

export const GEM_ABILITY_SCALING_SOURCE_LABELS: Record<
  GemAbilityScalingSource,
  string
> = {
  [GemAbilityScalingSource.WEAPON_ATTACK]: 'Weapon Attack',
  [GemAbilityScalingSource.SPELL_ATTACK]: 'Spell Attack',
  [GemAbilityScalingSource.DAMAGE_STAT]: 'Damage Stat',
  [GemAbilityScalingSource.DEFENCE]: 'Defence',
};

const GEM_ABILITY_SCALING_SOURCE_VALUES: readonly GemAbilityScalingSource[] =
  Object.values(GemAbilityScalingSource);

export const isGemAbilityScalingSource = (
  value: string
): value is GemAbilityScalingSource =>
  GEM_ABILITY_SCALING_SOURCE_VALUES.some((candidate) => candidate === value);

export const gemAbilityScalingSourceLabel = (value: string): string =>
  isGemAbilityScalingSource(value)
    ? GEM_ABILITY_SCALING_SOURCE_LABELS[value]
    : value;
