import DataTableBaseData from "../../ui/types/tables/data-table-base-data";
import BuildingDetails from "./building-details";

export default interface UnitDetails extends DataTableBaseData {

    attack: number;

    attacker: boolean;

    can_heal: boolean;

    can_recruit_more: null;

    clay_cost: number;

    cost_per_unit: number;

    current_amount: number|null;

    defence: number;

    defender: boolean;

    description: string;

    heal_percentage: null;

    id: number;

    iron_cost: number;

    kd_max: number|null;

    max_amount: number|null;

    name: string;

    recruited_from: BuildingDetails;

    required_population: number;

    siege_weapon: boolean;

    stone_cost: number;

    time_to_recruit: number;

    travel_time: number;

    wood_cost: number;

    required_building_level: number;


}
