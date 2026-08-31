import MonsterFormDefinition from '../api/definitions/monster-form-definition';
import MonsterFormStateDefinition from '../definitions/monster-form-state-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

const toNumberOrZero = (value: string): number =>
  value.trim() === '' ? 0 : Number(value);

export const createMonsterFormState = (
  monster: MonsterFormDefinition | null
): MonsterFormStateDefinition => {
  if (!monster) {
    return {
      name: '',
      damage_stat: '',
      game_map_id: null,
      max_level: '0',
      xp: '0',
      gold: '0',
      health_range: '',
      attack_range: '',
      drop_check: '0',
      only_for_location_type: null,
      str: '0',
      dur: '0',
      dex: '0',
      chr: '0',
      int: '0',
      agi: '0',
      focus: '0',
      ac: '0',
      accuracy: '',
      dodge: '',
      criticality: '',
      ambush_chance: '',
      ambush_resistance: '',
      counter_chance: '',
      counter_resistance: '',
      can_cast: false,
      max_spell_damage: '',
      casting_accuracy: '',
      spell_evasion: '',
      max_affix_damage: '',
      affix_resistance: '',
      healing_percentage: '',
      entrancing_chance: '',
      devouring_light_chance: '',
      devouring_darkness_chance: '',
      life_stealing_resistance: '',
      quest_item_id: null,
      quest_item_drop_chance: '',
      is_celestial_entity: false,
      celestial_type: '',
      gold_cost: '',
      gold_dust_cost: '',
      shards: '',
      is_raid_monster: false,
      is_raid_boss: false,
      raid_special_attack_type: null,
      fire_atonement: '',
      ice_atonement: '',
      water_atonement: '',
    };
  }

  return {
    name: monster.name,
    damage_stat: monster.damage_stat,
    game_map_id: monster.game_map_id,
    max_level: toStringValue(monster.max_level),
    xp: toStringValue(monster.xp),
    gold: toStringValue(monster.gold),
    health_range: monster.health_range,
    attack_range: monster.attack_range,
    drop_check: toStringValue(monster.drop_check),
    only_for_location_type: monster.only_for_location_type,
    str: toStringValue(monster.str),
    dur: toStringValue(monster.dur),
    dex: toStringValue(monster.dex),
    chr: toStringValue(monster.chr),
    int: toStringValue(monster.int),
    agi: toStringValue(monster.agi),
    focus: toStringValue(monster.focus),
    ac: toStringValue(monster.ac),
    accuracy: toStringValue(monster.accuracy),
    dodge: toStringValue(monster.dodge),
    criticality: toStringValue(monster.criticality),
    ambush_chance: toStringValue(monster.ambush_chance),
    ambush_resistance: toStringValue(monster.ambush_resistance),
    counter_chance: toStringValue(monster.counter_chance),
    counter_resistance: toStringValue(monster.counter_resistance),
    can_cast: monster.can_cast,
    max_spell_damage: toStringValue(monster.max_spell_damage),
    casting_accuracy: toStringValue(monster.casting_accuracy),
    spell_evasion: toStringValue(monster.spell_evasion),
    max_affix_damage: toStringValue(monster.max_affix_damage),
    affix_resistance: toStringValue(monster.affix_resistance),
    healing_percentage: toStringValue(monster.healing_percentage),
    entrancing_chance: toStringValue(monster.entrancing_chance),
    devouring_light_chance: toStringValue(monster.devouring_light_chance),
    devouring_darkness_chance: toStringValue(monster.devouring_darkness_chance),
    life_stealing_resistance: toStringValue(monster.life_stealing_resistance),
    quest_item_id: monster.quest_item_id,
    quest_item_drop_chance: toStringValue(monster.quest_item_drop_chance),
    is_celestial_entity: monster.is_celestial_entity,
    celestial_type: toStringValue(monster.celestial_type),
    gold_cost: toStringValue(monster.gold_cost),
    gold_dust_cost: toStringValue(monster.gold_dust_cost),
    shards: toStringValue(monster.shards),
    is_raid_monster: monster.is_raid_monster,
    is_raid_boss: monster.is_raid_boss,
    raid_special_attack_type: monster.raid_special_attack_type,
    fire_atonement: toStringValue(monster.fire_atonement),
    ice_atonement: toStringValue(monster.ice_atonement),
    water_atonement: toStringValue(monster.water_atonement),
  };
};

export const buildMonsterRequestPayload = (
  state: MonsterFormStateDefinition
): Record<string, unknown> => ({
  name: state.name,
  damage_stat: state.damage_stat,
  game_map_id: state.game_map_id,
  max_level: toNumberOrZero(state.max_level),
  xp: toNumberOrZero(state.xp),
  gold: toNumberOrZero(state.gold),
  health_range: state.health_range,
  attack_range: state.attack_range,
  drop_check: toNumberOrZero(state.drop_check),
  only_for_location_type: state.only_for_location_type,
  str: toNumberOrZero(state.str),
  dur: toNumberOrZero(state.dur),
  dex: toNumberOrZero(state.dex),
  chr: toNumberOrZero(state.chr),
  int: toNumberOrZero(state.int),
  agi: toNumberOrZero(state.agi),
  focus: toNumberOrZero(state.focus),
  ac: toNumberOrZero(state.ac),
  accuracy: toNumberOrNull(state.accuracy),
  dodge: toNumberOrNull(state.dodge),
  criticality: toNumberOrNull(state.criticality),
  ambush_chance: toNumberOrNull(state.ambush_chance),
  ambush_resistance: toNumberOrNull(state.ambush_resistance),
  counter_chance: toNumberOrNull(state.counter_chance),
  counter_resistance: toNumberOrNull(state.counter_resistance),
  can_cast: state.can_cast,
  max_spell_damage: toNumberOrNull(state.max_spell_damage),
  casting_accuracy: toNumberOrNull(state.casting_accuracy),
  spell_evasion: toNumberOrNull(state.spell_evasion),
  max_affix_damage: toNumberOrNull(state.max_affix_damage),
  affix_resistance: toNumberOrNull(state.affix_resistance),
  healing_percentage: toNumberOrNull(state.healing_percentage),
  entrancing_chance: toNumberOrNull(state.entrancing_chance),
  devouring_light_chance: toNumberOrNull(state.devouring_light_chance),
  devouring_darkness_chance: toNumberOrNull(state.devouring_darkness_chance),
  life_stealing_resistance: toNumberOrNull(state.life_stealing_resistance),
  quest_item_id: state.quest_item_id,
  quest_item_drop_chance: toNumberOrNull(state.quest_item_drop_chance),
  is_celestial_entity: state.is_celestial_entity,
  celestial_type: toNumberOrNull(state.celestial_type),
  gold_cost: toNumberOrNull(state.gold_cost),
  gold_dust_cost: toNumberOrNull(state.gold_dust_cost),
  shards: toNumberOrNull(state.shards),
  is_raid_monster: state.is_raid_monster,
  is_raid_boss: state.is_raid_boss,
  raid_special_attack_type: state.raid_special_attack_type,
  fire_atonement: toNumberOrNull(state.fire_atonement),
  ice_atonement: toNumberOrNull(state.ice_atonement),
  water_atonement: toNumberOrNull(state.water_atonement),
});

type MonsterFormErrors = Partial<
  Record<keyof MonsterFormStateDefinition, string>
>;

/**
 * The exact Monster form state keys whose value is a numeric string
 * validated by `validateIntegerFields`. Kept narrow (rather than accepting
 * every `keyof MonsterFormStateDefinition`) so `state[field]` is already
 * typed as `string` without a type assertion.
 */
type MonsterIntegerStringField =
  | 'max_level'
  | 'xp'
  | 'gold'
  | 'str'
  | 'dur'
  | 'dex'
  | 'chr'
  | 'int'
  | 'agi'
  | 'focus'
  | 'ac'
  | 'max_spell_damage'
  | 'max_affix_damage'
  | 'celestial_type'
  | 'gold_cost'
  | 'gold_dust_cost'
  | 'shards';

/**
 * The exact Monster form state keys whose value is a numeric (decimal
 * allowed) string validated by `validateNumberFields`.
 */
type MonsterNumberStringField =
  | 'drop_check'
  | 'accuracy'
  | 'dodge'
  | 'criticality'
  | 'ambush_chance'
  | 'ambush_resistance'
  | 'counter_chance'
  | 'counter_resistance'
  | 'casting_accuracy'
  | 'spell_evasion'
  | 'affix_resistance'
  | 'healing_percentage'
  | 'entrancing_chance'
  | 'devouring_light_chance'
  | 'devouring_darkness_chance'
  | 'life_stealing_resistance'
  | 'fire_atonement'
  | 'ice_atonement'
  | 'water_atonement';

const NON_NEGATIVE_INTEGER_MESSAGE = 'Enter a whole number of 0 or more.';
const NON_NEGATIVE_NUMBER_MESSAGE = 'Enter a number of 0 or more.';

const isValidNonNegativeIntegerString = (value: string): boolean => {
  if (value.trim() === '') {
    return true;
  }

  const parsed = Number(value);

  return Number.isInteger(parsed) && parsed >= 0;
};

const isValidNonNegativeNumberString = (
  value: string,
  max?: number
): boolean => {
  if (value.trim() === '') {
    return true;
  }

  const parsed = Number(value);

  if (Number.isNaN(parsed) || parsed < 0) {
    return false;
  }

  return max === undefined || parsed <= max;
};

const validateIntegerFields = (
  state: MonsterFormStateDefinition,
  fields: ReadonlyArray<MonsterIntegerStringField>
): MonsterFormErrors => {
  const errors: MonsterFormErrors = {};

  fields.forEach((field) => {
    if (!isValidNonNegativeIntegerString(state[field])) {
      errors[field] = NON_NEGATIVE_INTEGER_MESSAGE;
    }
  });

  return errors;
};

const validateNumberFields = (
  state: MonsterFormStateDefinition,
  fields: ReadonlyArray<MonsterNumberStringField>
): MonsterFormErrors => {
  const errors: MonsterFormErrors = {};

  fields.forEach((field) => {
    if (!isValidNonNegativeNumberString(state[field])) {
      errors[field] = NON_NEGATIVE_NUMBER_MESSAGE;
    }
  });

  return errors;
};

/**
 * Validate the fields belonging to the Identity & Placement step before the
 * wizard advances.
 */
export const validateMonsterIdentityStep = (
  state: MonsterFormStateDefinition
): MonsterFormErrors => {
  const errors: MonsterFormErrors = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Monster name.';
  }

  if (state.damage_stat.trim() === '') {
    errors.damage_stat = 'Select a damage stat.';
  }

  if (state.game_map_id === null) {
    errors.game_map_id = 'Select a Game Map.';
  }

  if (state.health_range.trim() === '') {
    errors.health_range = 'Enter a health range.';
  }

  if (state.attack_range.trim() === '') {
    errors.attack_range = 'Enter an attack range.';
  }

  return {
    ...errors,
    ...validateIntegerFields(state, ['max_level', 'xp', 'gold']),
    ...validateNumberFields(state, ['drop_check']),
  };
};

/**
 * Validate the fields belonging to the Core Combat step (base stats and
 * probabilities) before the wizard advances.
 */
export const validateMonsterCombatStep = (
  state: MonsterFormStateDefinition
): MonsterFormErrors => ({
  ...validateIntegerFields(state, [
    'str',
    'dur',
    'dex',
    'chr',
    'int',
    'agi',
    'focus',
    'ac',
  ]),
  ...validateNumberFields(state, [
    'accuracy',
    'dodge',
    'criticality',
    'ambush_chance',
    'ambush_resistance',
    'counter_chance',
    'counter_resistance',
  ]),
});

/**
 * Validate the fields belonging to the Spells & Affixes step before the
 * wizard advances.
 */
export const validateMonsterSpellStep = (
  state: MonsterFormStateDefinition
): MonsterFormErrors => ({
  ...validateIntegerFields(state, ['max_spell_damage', 'max_affix_damage']),
  ...validateNumberFields(state, [
    'casting_accuracy',
    'spell_evasion',
    'affix_resistance',
    'healing_percentage',
    'entrancing_chance',
    'devouring_light_chance',
    'devouring_darkness_chance',
    'life_stealing_resistance',
  ]),
});

/**
 * Validate the fields belonging to the Quest & Celestial step before the
 * wizard advances.
 */
export const validateMonsterQuestCelestialStep = (
  state: MonsterFormStateDefinition
): MonsterFormErrors => {
  const errors: MonsterFormErrors = {
    ...validateIntegerFields(state, [
      'celestial_type',
      'gold_cost',
      'gold_dust_cost',
      'shards',
    ]),
  };

  if (!isValidNonNegativeNumberString(state.quest_item_drop_chance, 9.9999)) {
    errors.quest_item_drop_chance = 'Enter a chance between 0 and 9.9999.';
  }

  return errors;
};

/**
 * Validate the fields belonging to the Raid & Special Rules step before the
 * wizard advances. The backend remains authoritative for relationship
 * cycles; this only prevents an obviously invalid local value.
 */
export const validateMonsterRaidStep = (
  state: MonsterFormStateDefinition
): MonsterFormErrors => {
  const errors: MonsterFormErrors = validateNumberFields(state, [
    'fire_atonement',
    'ice_atonement',
    'water_atonement',
  ]);

  if (state.is_raid_monster && state.is_raid_boss) {
    errors.is_raid_boss =
      'A Monster cannot be both a raid Monster and a raid boss.';
  }

  return errors;
};

const MONSTER_FORM_STEP_VALIDATORS: ReadonlyArray<
  (state: MonsterFormStateDefinition) => MonsterFormErrors
> = [
  validateMonsterIdentityStep,
  validateMonsterCombatStep,
  validateMonsterSpellStep,
  validateMonsterQuestCelestialStep,
  validateMonsterRaidStep,
];

/**
 * Validate the complete Monster form (every step's fields combined), used
 * before final submit so a field left invalid on an earlier, already-passed
 * step still blocks submission.
 */
export const validateMonsterForm = (
  state: MonsterFormStateDefinition
): MonsterFormErrors => {
  const errors: MonsterFormErrors = {};

  MONSTER_FORM_STEP_VALIDATORS.forEach((validateStep) => {
    Object.assign(errors, validateStep(state));
  });

  return errors;
};
