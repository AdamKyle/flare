export default interface UnitFormStateDefinition {
  name: string;
  description: string;
  attack: string;
  defence: string;
  can_heal: boolean;
  heal_percentage: string;
  is_settler: boolean;
  reduces_morale_by: string;
  attacker: boolean;
  defender: boolean;
  siege_weapon: boolean;
  is_airship: boolean;
  is_special: boolean;
  can_not_be_healed: boolean;
  time_to_recruit: string;
  wood_cost: string;
  stone_cost: string;
  clay_cost: string;
  iron_cost: string;
  steel_cost: string;
  required_population: string;
}
