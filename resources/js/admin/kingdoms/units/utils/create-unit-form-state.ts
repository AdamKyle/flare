import UnitFormDefinition from '../api/definitions/unit-form-definition';
import UnitFormStateDefinition from '../definitions/unit-form-state-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

export const createUnitFormState = (
  unit: UnitFormDefinition | null
): UnitFormStateDefinition => {
  if (!unit) {
    return {
      name: '',
      description: '',
      attack: '0',
      defence: '0',
      can_heal: false,
      heal_percentage: '',
      is_settler: false,
      reduces_morale_by: '',
      attacker: false,
      defender: false,
      siege_weapon: false,
      is_airship: false,
      is_special: false,
      can_not_be_healed: false,
      time_to_recruit: '',
      wood_cost: '0',
      stone_cost: '0',
      clay_cost: '0',
      iron_cost: '0',
      steel_cost: '0',
      required_population: '0',
    };
  }

  return {
    name: unit.name,
    description: unit.description,
    attack: String(unit.attack),
    defence: String(unit.defence),
    can_heal: unit.can_heal ?? false,
    heal_percentage: toStringValue(unit.heal_percentage),
    is_settler: unit.is_settler ?? false,
    reduces_morale_by: toStringValue(unit.reduces_morale_by),
    attacker: unit.attacker ?? false,
    defender: unit.defender ?? false,
    siege_weapon: unit.siege_weapon ?? false,
    is_airship: unit.is_airship ?? false,
    is_special: unit.is_special ?? false,
    can_not_be_healed: unit.can_not_be_healed ?? false,
    time_to_recruit: String(unit.time_to_recruit),
    wood_cost: toStringValue(unit.wood_cost),
    stone_cost: toStringValue(unit.stone_cost),
    clay_cost: toStringValue(unit.clay_cost),
    iron_cost: toStringValue(unit.iron_cost),
    steel_cost: toStringValue(unit.steel_cost),
    required_population: toStringValue(unit.required_population),
  };
};
