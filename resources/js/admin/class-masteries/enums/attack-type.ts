export enum AttackType {
  ATTACK = 'attack',
  VOIDED_ATTACK = 'voided_attack',
  CAST = 'cast',
  VOIDED_CAST = 'voided_cast',
  CAST_AND_ATTACK = 'cast_and_attack',
  VOIDED_CAST_AND_ATTACK = 'voided_cast_and_attack',
  ATTACK_AND_CAST = 'attack_and_cast',
  VOIDED_ATTACK_AND_CAST = 'voided_attack_and_cast',
  DEFEND = 'defend',
  VOIDED_DEFEND = 'voided_defend',
  ANY = 'any',
}

export const ATTACK_TYPE_LABELS: Record<AttackType, string> = {
  [AttackType.ATTACK]: 'Attack',
  [AttackType.VOIDED_ATTACK]: 'Voided Attack',
  [AttackType.CAST]: 'Cast',
  [AttackType.VOIDED_CAST]: 'Voided Cast',
  [AttackType.CAST_AND_ATTACK]: 'Cast And Attack',
  [AttackType.VOIDED_CAST_AND_ATTACK]: 'Voided Cast And Attack',
  [AttackType.ATTACK_AND_CAST]: 'Attack And Cast',
  [AttackType.VOIDED_ATTACK_AND_CAST]: 'Voided Attack And Cast',
  [AttackType.DEFEND]: 'Defend',
  [AttackType.VOIDED_DEFEND]: 'Voided Defend',
  [AttackType.ANY]: 'Any',
};

const ATTACK_TYPE_VALUES: readonly AttackType[] = Object.values(AttackType);

export const isAttackType = (value: string): value is AttackType =>
  ATTACK_TYPE_VALUES.some((candidate) => candidate === value);

export const attackTypeLabel = (value: string): string =>
  isAttackType(value) ? ATTACK_TYPE_LABELS[value] : value;
