import UnitRequestDefinition from '../api/definitions/unit-request-definition';
import UnitFormStateDefinition from '../definitions/unit-form-state-definition';

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const buildUnitRequestPayload = (
  state: UnitFormStateDefinition
): UnitRequestDefinition => ({
  name: state.name.trim(),
  description: state.description,
  attack: Number(state.attack),
  defence: Number(state.defence),
  can_heal: state.can_heal,
  heal_percentage: toNumberOrNull(state.heal_percentage),
  siege_weapon: state.siege_weapon,
  is_airship: state.is_airship,
  attacker: state.attacker,
  defender: state.defender,
  can_not_be_healed: state.can_not_be_healed,
  is_settler: state.is_settler,
  is_special: state.is_special,
  reduces_morale_by: toNumberOrNull(state.reduces_morale_by),
  wood_cost: toNumberOrNull(state.wood_cost),
  clay_cost: toNumberOrNull(state.clay_cost),
  stone_cost: toNumberOrNull(state.stone_cost),
  iron_cost: toNumberOrNull(state.iron_cost),
  steel_cost: toNumberOrNull(state.steel_cost),
  required_population: toNumberOrNull(state.required_population),
  time_to_recruit: Number(state.time_to_recruit),
});
