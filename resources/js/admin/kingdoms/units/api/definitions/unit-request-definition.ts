export default interface UnitRequestDefinition {
  name: string;
  description: string;
  attack: number;
  defence: number;
  can_heal: boolean;
  heal_percentage: number | null;
  siege_weapon: boolean;
  is_airship: boolean;
  attacker: boolean;
  defender: boolean;
  can_not_be_healed: boolean;
  is_settler: boolean;
  is_special: boolean;
  reduces_morale_by: number | null;
  wood_cost: number | null;
  clay_cost: number | null;
  stone_cost: number | null;
  iron_cost: number | null;
  steel_cost: number | null;
  required_population: number | null;
  time_to_recruit: number;
}
