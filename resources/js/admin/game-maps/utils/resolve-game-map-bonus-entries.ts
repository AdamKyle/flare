import { convertStoredBonusToPercentage } from './convert-stored-bonus-to-percentage';
import { GameMapDetailDefinition } from '../api/definitions/game-map-editor-definition';

export interface GameMapBonusEntry {
  label: string;
  percentage: number;
}

/**
 * Resolve the Game Map's nonzero display bonuses. A stored zero/null bonus
 * communicates nothing to an Admin reader, so it is omitted rather than
 * shown as `0%`; both the standalone show screen and the SidePeek call this
 * one resolver so they can never disagree about which bonuses are factual.
 *
 * @param  gameMap  Game Map detail to resolve display bonuses for.
 * @return  Nonzero bonus entries, in the canonical display order.
 */
export const resolveGameMapBonusEntries = (
  gameMap: GameMapDetailDefinition
): GameMapBonusEntry[] => {
  const entries: { label: string; value: number | null }[] = [
    { label: 'XP Bonus', value: gameMap.xp_bonus },
    { label: 'Skill XP Bonus', value: gameMap.skill_training_bonus },
    { label: 'Drop Chance Bonus', value: gameMap.drop_chance_bonus },
    { label: 'Enemy Stat Increase', value: gameMap.enemy_stat_bonus },
    {
      label: 'Character Damage Deduction',
      value: gameMap.character_attack_reduction,
    },
  ];

  return entries
    .map(({ label, value }) => ({
      label,
      percentage: convertStoredBonusToPercentage(value ?? 0),
    }))
    .filter((entry) => entry.percentage !== 0);
};
