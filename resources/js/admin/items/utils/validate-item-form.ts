import ItemFormErrorsDefinition from '../definitions/item-form-errors-definition';
import ItemFormStateDefinition from '../definitions/item-form-state-definition';

interface NumericFieldRule {
  field: keyof ItemFormStateDefinition;
  label: string;
  integer?: boolean;
  min?: number;
}

const STEP_1_NUMERIC_FIELDS: NumericFieldRule[] = [
  { field: 'cost', label: 'Gold Cost', integer: true, min: 0 },
  { field: 'gold_dust_cost', label: 'Gold Dust Cost', integer: true, min: 0 },
  { field: 'shards_cost', label: 'Shards Cost', integer: true, min: 0 },
  {
    field: 'copper_coin_cost',
    label: 'Copper Coin Cost',
    integer: true,
    min: 0,
  },
  { field: 'gold_bars_cost', label: 'Gold Bars Cost', integer: true, min: 0 },
];

const STEP_2_NUMERIC_FIELDS: NumericFieldRule[] = [
  { field: 'base_damage', label: 'Base Damage', integer: true, min: 0 },
  { field: 'base_ac', label: 'Base AC', integer: true, min: 0 },
  { field: 'base_healing', label: 'Base Healing', integer: true, min: 0 },
  { field: 'base_damage_mod', label: 'Base Damage Modifier' },
  { field: 'base_ac_mod', label: 'Base AC Modifier' },
  { field: 'base_healing_mod', label: 'Base Healing Modifier' },
  { field: 'str_mod', label: 'Strength Modifier' },
  { field: 'dur_mod', label: 'Durability Modifier' },
  { field: 'dex_mod', label: 'Dexterity Modifier' },
  { field: 'chr_mod', label: 'Charisma Modifier' },
  { field: 'int_mod', label: 'Intelligence Modifier' },
  { field: 'agi_mod', label: 'Agility Modifier' },
  { field: 'focus_mod', label: 'Focus Modifier' },
  { field: 'ambush_chance', label: 'Ambush Chance', min: 0 },
  { field: 'ambush_resistance', label: 'Ambush Resistance', min: 0 },
  { field: 'counter_chance', label: 'Counter Chance', min: 0 },
  { field: 'counter_resistance', label: 'Counter Resistance', min: 0 },
];

const STEP_3_NUMERIC_FIELDS: NumericFieldRule[] = [
  { field: 'skill_bonus', label: 'Skill Bonus' },
  { field: 'skill_training_bonus', label: 'Skill Training Bonus' },
  { field: 'fight_time_out_mod_bonus', label: 'Fight Timeout Modifier Bonus' },
  { field: 'move_time_out_mod_bonus', label: 'Move Timeout Modifier Bonus' },
  { field: 'xp_bonus', label: 'XP Bonus' },
  { field: 'resurrection_chance', label: 'Resurrection Chance', min: 0 },
  { field: 'spell_evasion', label: 'Spell Evasion', min: 0 },
  { field: 'artifact_annulment', label: 'Artifact Annulment', min: 0 },
  { field: 'healing_reduction', label: 'Healing Reduction', min: 0 },
  {
    field: 'affix_damage_reduction',
    label: 'Affix Damage Reduction',
    min: 0,
  },
  { field: 'devouring_light', label: 'Devouring Light', min: 0 },
  { field: 'devouring_darkness', label: 'Devouring Darkness', min: 0 },
];

const STEP_4_NUMERIC_FIELDS: NumericFieldRule[] = [
  {
    field: 'skill_level_required',
    label: 'Minimum Crafting Level',
    integer: true,
    min: 0,
  },
  {
    field: 'skill_level_trivial',
    label: 'Trivial Crafting Level',
    integer: true,
    min: 0,
  },
];

const STEP_5_NUMERIC_FIELDS: NumericFieldRule[] = [
  { field: 'lasts_for', label: 'Lasts For', integer: true, min: 0 },
  { field: 'increase_stat_by', label: 'Increase Stat By' },
  { field: 'kingdom_damage', label: 'Kingdom Damage', min: 0 },
  { field: 'increase_skill_bonus_by', label: 'Increase Skill Bonus By' },
  {
    field: 'increase_skill_training_bonus_by',
    label: 'Increase Skill Training Bonus By',
  },
  { field: 'holy_level', label: 'Holy Level', integer: true, min: 0 },
];

/**
 * Validate one optional numeric text field against its finite/integer/minimum
 * constraints, writing a specific error message into `errors` when invalid.
 * An empty string is treated as "not provided" and is always valid.
 */
const validateNumericField = (
  state: ItemFormStateDefinition,
  rule: NumericFieldRule,
  errors: ItemFormErrorsDefinition
): void => {
  const rawValue = state[rule.field];

  if (typeof rawValue !== 'string' || rawValue.trim() === '') {
    return;
  }

  const parsedValue = Number(rawValue);

  if (!Number.isFinite(parsedValue)) {
    errors[rule.field] = `Enter a valid number for ${rule.label}.`;

    return;
  }

  if (rule.integer && !Number.isInteger(parsedValue)) {
    errors[rule.field] = `Enter a whole number for ${rule.label}.`;

    return;
  }

  if (rule.min !== undefined && parsedValue < rule.min) {
    errors[rule.field] = `${rule.label} must be ${rule.min} or greater.`;
  }
};

/**
 * Validate every numeric field in the given rule set, merging any resulting
 * errors into a fresh errors object.
 */
const validateNumericFields = (
  state: ItemFormStateDefinition,
  rules: NumericFieldRule[]
): ItemFormErrorsDefinition => {
  const errors: ItemFormErrorsDefinition = {};

  for (const rule of rules) {
    validateNumericField(state, rule, errors);
  }

  return errors;
};

/**
 * Validate the Basic/catalog step: required name, type, description, and
 * finite non-negative catalog costs.
 */
const validateBasicStep = (
  state: ItemFormStateDefinition
): ItemFormErrorsDefinition => {
  const errors: ItemFormErrorsDefinition = validateNumericFields(
    state,
    STEP_1_NUMERIC_FIELDS
  );

  if (!state.name.trim()) {
    errors.name = 'Enter an Item name.';
  }

  if (!state.type.trim()) {
    errors.type = 'Select an Item type.';
  }

  if (!state.description.trim()) {
    errors.description = 'Enter an Item description.';
  }

  return errors;
};

/**
 * Validate one Item form wizard step and return the resulting field errors.
 *
 * @param stepIndex 1-indexed wizard step being validated.
 * @param state Current Item form state.
 * @return Field errors found for the given step; empty when the step is valid.
 */
export const validateItemFormStep = (
  stepIndex: number,
  state: ItemFormStateDefinition
): ItemFormErrorsDefinition => {
  switch (stepIndex) {
    case 1:
      return validateBasicStep(state);
    case 2:
      return validateNumericFields(state, STEP_2_NUMERIC_FIELDS);
    case 3:
      return validateNumericFields(state, STEP_3_NUMERIC_FIELDS);
    case 4:
      return validateNumericFields(state, STEP_4_NUMERIC_FIELDS);
    case 5:
      return validateNumericFields(state, STEP_5_NUMERIC_FIELDS);
    default:
      return {};
  }
};

/**
 * Validate every Item form wizard step and return the combined field errors.
 * Used as a final guard immediately before submission so no step's fields
 * can be skipped regardless of which step the wizard is currently showing.
 *
 * @param state Current Item form state.
 * @return Combined field errors across every step; empty when the form is valid.
 */
export const validateAllItemFormSteps = (
  state: ItemFormStateDefinition
): ItemFormErrorsDefinition => ({
  ...validateBasicStep(state),
  ...validateNumericFields(state, STEP_2_NUMERIC_FIELDS),
  ...validateNumericFields(state, STEP_3_NUMERIC_FIELDS),
  ...validateNumericFields(state, STEP_4_NUMERIC_FIELDS),
  ...validateNumericFields(state, STEP_5_NUMERIC_FIELDS),
});
