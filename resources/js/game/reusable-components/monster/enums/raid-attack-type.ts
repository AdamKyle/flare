export enum RaidAttackType {
  PHYSICAL_ATTACK = 0,
  MAGICAL_ICE_ATTACK = 1,
  DELUSIONAL_MEMORIES_ATTACK = 2,
  BANSHEE_SCREAM_ATTACK = 3,
  ENRAGED_HATE = 4,
}

export const RAID_ATTACK_TYPE_LABELS: Record<RaidAttackType, string> = {
  [RaidAttackType.PHYSICAL_ATTACK]: 'Physical Attack',
  [RaidAttackType.MAGICAL_ICE_ATTACK]: 'Magical Ice Attack',
  [RaidAttackType.DELUSIONAL_MEMORIES_ATTACK]: 'Delusional Memories Attack',
  [RaidAttackType.BANSHEE_SCREAM_ATTACK]: 'Banshee Scream Attack',
  [RaidAttackType.ENRAGED_HATE]: 'Enraged Hate',
};

export const isRaidAttackType = (
  value: string | number
): value is RaidAttackType =>
  typeof value === 'number' &&
  Object.values(RaidAttackType).some((attackType) => attackType === value);
