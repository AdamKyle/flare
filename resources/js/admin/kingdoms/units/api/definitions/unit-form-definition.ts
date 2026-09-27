export default interface UnitFormDefinition {
  id: number;
  name: string;
  description: string;
  attack: number;
  defence: number;
  can_heal: boolean | null;
  heal_percentage: number | null;
  siege_weapon: boolean | null;
  is_airship: boolean | null;
  attacker: boolean | null;
  defender: boolean | null;
  can_not_be_healed: boolean | null;
  is_settler: boolean | null;
  is_special: boolean | null;
  reduces_morale_by: number | null;
  wood_cost: number | null;
  clay_cost: number | null;
  stone_cost: number | null;
  iron_cost: number | null;
  steel_cost: number | null;
  required_population: number | null;
  time_to_recruit: number;
}
