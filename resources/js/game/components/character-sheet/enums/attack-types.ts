export enum AttackTypes {
  WEAPON,
  SPELL_DAMAGE,
  HEALING,
  RING_DAMAGE,
  HEALTH,
  DEFENCE,
}

export const ATTACK_TYPE_NAME_BY_TYPE = {
  [AttackTypes.WEAPON]: 'weapon_damage',
  [AttackTypes.SPELL_DAMAGE]: 'spell_damage',
  [AttackTypes.HEALING]: 'heal_for',
  [AttackTypes.RING_DAMAGE]: 'ring_damage',
  [AttackTypes.HEALTH]: 'health',
  [AttackTypes.DEFENCE]: 'ac',
} as const satisfies Record<AttackTypes, string>;

export const getAttackTypeName = (attackType: AttackTypes): string =>
  ATTACK_TYPE_NAME_BY_TYPE[attackType];
