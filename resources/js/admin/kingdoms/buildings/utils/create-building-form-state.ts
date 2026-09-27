import BuildingFormDefinition from '../api/definitions/building-form-definition';
import BuildingFormStateDefinition from '../definitions/building-form-state-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

export const createBuildingFormState = (
  building: BuildingFormDefinition | null
): BuildingFormStateDefinition => {
  if (!building) {
    return {
      name: '',
      description: '',
      max_level: '',
      required_population: '0',
      base_durability: '0',
      base_defence: '0',
      is_walls: false,
      is_farm: false,
      is_church: false,
      is_resource_building: false,
      is_special: false,
      is_locked: false,
      passive_skill_id: null,
      level_required: '',
      wood_cost: '0',
      clay_cost: '0',
      stone_cost: '0',
      iron_cost: '0',
      steel_cost: '0',
      time_to_build: '0',
      time_increase_amount: '0',
      increase_population_amount: '0',
      increase_morale_amount: '0',
      decrease_morale_amount: '0',
      increase_wood_amount: '0',
      increase_clay_amount: '0',
      increase_stone_amount: '0',
      increase_iron_amount: '0',
      increase_durability_amount: '0',
      increase_defence_amount: '0',
      trains_units: false,
      unit_ids: [],
      units_per_level: '',
      only_at_level: '',
    };
  }

  return {
    name: building.name,
    description: building.description,
    max_level: String(building.max_level),
    required_population: String(building.required_population),
    base_durability: String(building.base_durability),
    base_defence: String(building.base_defence),
    is_walls: building.is_walls,
    is_farm: building.is_farm,
    is_church: building.is_church,
    is_resource_building: building.is_resource_building,
    is_special: building.is_special ?? false,
    is_locked: building.is_locked,
    passive_skill_id: building.passive_skill_id,
    level_required: toStringValue(building.level_required),
    wood_cost: String(building.wood_cost),
    clay_cost: String(building.clay_cost),
    stone_cost: String(building.stone_cost),
    iron_cost: String(building.iron_cost),
    steel_cost: toStringValue(building.steel_cost),
    time_to_build: String(building.time_to_build),
    time_increase_amount: String(building.time_increase_amount),
    increase_population_amount: String(building.increase_population_amount),
    increase_morale_amount: String(building.increase_morale_amount),
    decrease_morale_amount: String(building.decrease_morale_amount),
    increase_wood_amount: String(building.increase_wood_amount),
    increase_clay_amount: String(building.increase_clay_amount),
    increase_stone_amount: String(building.increase_stone_amount),
    increase_iron_amount: String(building.increase_iron_amount),
    increase_durability_amount: String(building.increase_durability_amount),
    increase_defence_amount: String(building.increase_defence_amount),
    trains_units: building.trains_units,
    unit_ids: building.unit_ids,
    units_per_level: toStringValue(building.units_per_level),
    only_at_level: toStringValue(building.only_at_level),
  };
};
