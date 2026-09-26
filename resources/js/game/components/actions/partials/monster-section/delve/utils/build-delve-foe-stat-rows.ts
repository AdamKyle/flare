import { DelveCurrentFoeStatsDefinition } from '../api/definitions/delve-current-foe-definition';
import DelveFoeStatRowDefinition from '../types/delve-foe-stat-row-definition';

const DELVE_FOE_STAT_LABELS: Record<
  keyof DelveCurrentFoeStatsDefinition,
  string
> = {
  str: 'Strength',
  dur: 'Durability',
  dex: 'Dexterity',
  chr: 'Charisma',
  int: 'Intelligence',
  agi: 'Agility',
  focus: 'Focus',
  ac: 'Armour Class',
  health_range: 'Health Range',
  attack_range: 'Attack Range',
  max_spell_damage: 'Max Spell Damage',
  healing_percentage: 'Healing Percentage',
  max_level: 'Max Level',
  xp: 'XP',
  gold: 'Gold',
};

const isStatKey = (key: string): key is keyof DelveCurrentFoeStatsDefinition =>
  Object.prototype.hasOwnProperty.call(DELVE_FOE_STAT_LABELS, key);

/**
 * The backend sends `[]` (not `{}`) when no foe stats exist yet, so arrays
 * produce no rows.
 */
export const buildDelveFoeStatRows = (
  stats: DelveCurrentFoeStatsDefinition | []
): DelveFoeStatRowDefinition[] => {
  if (Array.isArray(stats)) {
    return [];
  }

  return Object.entries(stats)
    .filter(
      (
        entry
      ): entry is [keyof DelveCurrentFoeStatsDefinition, string | number] =>
        isStatKey(entry[0]) && entry[1] !== null && entry[1] !== undefined
    )
    .map(([key, value]) => ({
      key,
      label: DELVE_FOE_STAT_LABELS[key],
      value: String(value),
    }));
};
