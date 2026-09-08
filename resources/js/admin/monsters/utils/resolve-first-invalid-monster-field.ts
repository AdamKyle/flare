import MonsterFormErrorsDefinition from '../definitions/monster-form-errors-definition';
import MonsterFormStateDefinition from '../definitions/monster-form-state-definition';

export const MONSTER_STEP_FIELD_IDS: ReadonlyArray<
  ReadonlyArray<{ field: keyof MonsterFormStateDefinition; id: string }>
> = [
  [
    { field: 'name', id: 'monster-name' },
    { field: 'damage_stat', id: 'monster-damage-stat' },
    { field: 'game_map_id', id: 'monster-game-map' },
    { field: 'max_level', id: 'monster-max-level' },
    { field: 'xp', id: 'monster-xp' },
    { field: 'gold', id: 'monster-gold' },
    { field: 'health_range', id: 'monster-health-range' },
    { field: 'attack_range', id: 'monster-attack-range' },
    { field: 'drop_check', id: 'monster-drop-check' },
    { field: 'only_for_location_type', id: 'monster-location-type' },
  ],
  [
    { field: 'str', id: 'monster-str' },
    { field: 'dur', id: 'monster-dur' },
    { field: 'dex', id: 'monster-dex' },
    { field: 'chr', id: 'monster-chr' },
    { field: 'int', id: 'monster-int' },
    { field: 'agi', id: 'monster-agi' },
    { field: 'focus', id: 'monster-focus' },
    { field: 'ac', id: 'monster-ac' },
    { field: 'accuracy', id: 'monster-accuracy' },
    { field: 'dodge', id: 'monster-dodge' },
    { field: 'criticality', id: 'monster-criticality' },
    { field: 'ambush_chance', id: 'monster-ambush-chance' },
    { field: 'ambush_resistance', id: 'monster-ambush-resistance' },
    { field: 'counter_chance', id: 'monster-counter-chance' },
    { field: 'counter_resistance', id: 'monster-counter-resistance' },
  ],
  [
    { field: 'max_spell_damage', id: 'monster-max-spell-damage' },
    { field: 'casting_accuracy', id: 'monster-casting-accuracy' },
    { field: 'spell_evasion', id: 'monster-spell-evasion' },
    { field: 'max_affix_damage', id: 'monster-max-affix-damage' },
    { field: 'affix_resistance', id: 'monster-affix-resistance' },
    { field: 'healing_percentage', id: 'monster-healing-percentage' },
    { field: 'entrancing_chance', id: 'monster-entrancing-chance' },
    { field: 'devouring_light_chance', id: 'monster-devouring-light-chance' },
    {
      field: 'devouring_darkness_chance',
      id: 'monster-devouring-darkness-chance',
    },
    {
      field: 'life_stealing_resistance',
      id: 'monster-life-stealing-resistance',
    },
  ],
  [
    { field: 'quest_item_drop_chance', id: 'monster-quest-item-drop-chance' },
    { field: 'celestial_type', id: 'monster-celestial-type' },
    { field: 'gold_cost', id: 'monster-gold-cost' },
    { field: 'gold_dust_cost', id: 'monster-gold-dust-cost' },
    { field: 'shards', id: 'monster-shards' },
  ],
  [
    { field: 'fire_atonement', id: 'monster-fire-atonement' },
    { field: 'ice_atonement', id: 'monster-ice-atonement' },
    { field: 'water_atonement', id: 'monster-water-atonement' },
    { field: 'is_raid_boss', id: 'monster-is-raid-boss' },
  ],
];

export const resolveFirstInvalidMonsterField = (
  errors: MonsterFormErrorsDefinition,
  stepIndex: number
): string | null =>
  MONSTER_STEP_FIELD_IDS[stepIndex]?.find(({ field }) => field in errors)?.id ??
  null;
