export enum GemAbilityType {
  ACTIVE = 'active',
  PASSIVE = 'passive',
}

export const GEM_ABILITY_TYPE_LABELS: Record<GemAbilityType, string> = {
  [GemAbilityType.ACTIVE]: 'Active',
  [GemAbilityType.PASSIVE]: 'Passive',
};

const GEM_ABILITY_TYPE_VALUES: readonly GemAbilityType[] =
  Object.values(GemAbilityType);

export const isGemAbilityType = (value: string): value is GemAbilityType =>
  GEM_ABILITY_TYPE_VALUES.some((candidate) => candidate === value);

export const gemAbilityTypeLabel = (value: string): string =>
  isGemAbilityType(value) ? GEM_ABILITY_TYPE_LABELS[value] : value;
