export enum GemAbilityType {
  ACTIVE = 'active',
  PASSIVE = 'passive',
}

export const GEM_ABILITY_TYPE_LABELS: Record<GemAbilityType, string> = {
  [GemAbilityType.ACTIVE]: 'Active',
  [GemAbilityType.PASSIVE]: 'Passive',
};

const GEM_ABILITY_TYPE_HELP: Record<GemAbilityType, string> = {
  [GemAbilityType.ACTIVE]:
    'Active Gem abilities can trigger during eligible combat actions and use their listed proc chance.',
  [GemAbilityType.PASSIVE]:
    'Passive Gem abilities contribute their effect continuously while the Gem is equipped in an active item.',
};

const GEM_ABILITY_TYPE_VALUES: readonly GemAbilityType[] =
  Object.values(GemAbilityType);

export const isGemAbilityType = (value: string): value is GemAbilityType =>
  GEM_ABILITY_TYPE_VALUES.some((candidate) => candidate === value);

export const gemAbilityTypeLabel = (value: string): string =>
  isGemAbilityType(value) ? GEM_ABILITY_TYPE_LABELS[value] : value;

export const gemAbilityTypeHelp = (value: string): string =>
  isGemAbilityType(value)
    ? GEM_ABILITY_TYPE_HELP[value]
    : `Gem ability type: ${value}.`;
