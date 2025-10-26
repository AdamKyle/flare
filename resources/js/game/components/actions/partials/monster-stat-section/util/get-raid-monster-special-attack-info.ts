import { match } from 'ts-pattern';

import { RaidSpecialAttackTypes } from '../enums/raid-special-attack-types';

export type SpecialAttackTypeInfo = {
  name: string;
  details: string;
};

/**
 * Get the raid monster special attack name
 *
 * @param attackType
 */
export const getRaidMonsterSpecialAttackInfo = (
  attackType: RaidSpecialAttackTypes | null
): SpecialAttackTypeInfo => {
  return match(attackType)
    .with(RaidSpecialAttackTypes.PHYSICAL_ATTACK, () => {
      return {
        name: 'Physical Rage',
        details:
          'Deals 15% of the enemy damage stat as damage towards the player. The player can defend against this high AC.',
      };
    })
    .with(RaidSpecialAttackTypes.MAGICAL_ICE_ATTACK, () => {
      return {
        name: 'Blizzard of despair',
        details:
          'Deals 20% of the enemy damage stat as damage towards the player. The player can defend against this high AC.',
      };
    })
    .with(RaidSpecialAttackTypes.DELUSIONAL_MEMORIES_ATTACK, () => {
      return {
        name: 'Delusional Thrashing',
        details:
          'Deals 22% of the enemy damage stat as damage towards the player. The player can defend against this high AC.',
      };
    })
    .with(RaidSpecialAttackTypes.BANSHEE_SCREAM_ATTACK, () => {
      return {
        name: 'Scream of the banshee',
        details:
          'Deals 25% of the enemy damage stat as damage towards the player. The player can defend against this high AC.',
      };
    })
    .otherwise(() => {
      return {
        name: 'Unknown',
        details: 'Unknown',
      };
    });
};
