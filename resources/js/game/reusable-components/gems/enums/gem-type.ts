export enum GemType {
  FIRE = 0,
  ICE = 1,
  WATER = 2,
}

export const GEM_TYPE_LABELS: Record<GemType, string> = {
  [GemType.FIRE]: 'Fire',
  [GemType.ICE]: 'Ice',
  [GemType.WATER]: 'Water',
};

const GEM_TYPE_VALUES: readonly GemType[] = [
  GemType.FIRE,
  GemType.ICE,
  GemType.WATER,
];

export const isGemType = (value: number): value is GemType =>
  GEM_TYPE_VALUES.some((candidate) => candidate === value);

export const gemTypeLabel = (value: number): string =>
  isGemType(value) ? GEM_TYPE_LABELS[value] : String(value);
