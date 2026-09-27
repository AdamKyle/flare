import BuildingRequestDefinition from '../api/definitions/building-request-definition';
import BuildingFormStateDefinition from '../definitions/building-form-state-definition';

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const buildBuildingRequestPayload = (
  state: BuildingFormStateDefinition
): BuildingRequestDefinition => ({
  name: state.name.trim(),
  description: state.description,
  max_level: Number(state.max_level),
  base_durability: Number(state.base_durability),
  base_defence: Number(state.base_defence),
  required_population: Number(state.required_population),
  is_walls: state.is_walls,
  is_church: state.is_church,
  is_farm: state.is_farm,
  is_resource_building: state.is_resource_building,
  trains_units: state.trains_units,
  is_locked: state.is_locked,
  is_special: state.is_special,
  wood_cost: Number(state.wood_cost),
  clay_cost: Number(state.clay_cost),
  stone_cost: Number(state.stone_cost),
  iron_cost: Number(state.iron_cost),
  steel_cost: toNumberOrNull(state.steel_cost),
  increase_population_amount: Number(state.increase_population_amount),
  increase_morale_amount: Number(state.increase_morale_amount),
  decrease_morale_amount: Number(state.decrease_morale_amount),
  increase_wood_amount: Number(state.increase_wood_amount),
  increase_clay_amount: Number(state.increase_clay_amount),
  increase_stone_amount: Number(state.increase_stone_amount),
  increase_iron_amount: Number(state.increase_iron_amount),
  increase_durability_amount: Number(state.increase_durability_amount),
  increase_defence_amount: Number(state.increase_defence_amount),
  time_to_build: Number(state.time_to_build),
  time_increase_amount: Number(state.time_increase_amount),
  units_per_level: state.trains_units
    ? toNumberOrNull(state.units_per_level)
    : null,
  only_at_level: state.trains_units
    ? toNumberOrNull(state.only_at_level)
    : null,
  passive_skill_id: state.passive_skill_id,
  level_required: toNumberOrNull(state.level_required),
  unit_ids: state.trains_units ? state.unit_ids : [],
});
