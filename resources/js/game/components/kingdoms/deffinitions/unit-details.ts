import BuildingDetails from "../buildings/deffinitions/building-details";
import DataTableBaseData from "../../../lib/ui/types/tables/data-table-base-data";

export default interface UnitDetails extends DataTableBaseData {
    attack: number;

    attacker: boolean;

    can_heal: boolean;

    clay_cost: number;

    cost_per_unit: number;

    current_amount: number | null;

    defence: number;

    defender: boolean;

    description: string;

    heal_percentage: number | null;

    id: number;

    iron_cost: number;

    max_amount: number;

    name: string;

    recruited_from: BuildingDetails;

    required_population: number;

    siege_weapon: boolean;

    stone_cost: number;

    time_to_recruit: number;

    travel_time: number;

    wood_cost: number;

    required_building_level: number;

    additional_pop_cost: number;

    steel_cost: number;

    is_special: boolean;
}
