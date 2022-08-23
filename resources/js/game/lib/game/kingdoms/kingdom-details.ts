import BuildingDetails from "./building-details";
import DataTableBaseData from "../../ui/types/tables/data-table-base-data";
import UnitDetails from "./unit-details";
import BuildingInQueueDetails from "./building-in-queue-details";
import CurrentUnitDetails from "./current-unit-details";

export default interface KingdomDetails extends DataTableBaseData {
    building_cost_reduction: number;

    building_queue: BuildingInQueueDetails[]|[];

    buildings: BuildingDetails[] | [];

    units: UnitDetails[] | [];

    building_time_reduction: number;

    can_access_bank: boolean;

    character_id: number;

    color: string;

    current_clay: number;

    current_iron: number;

    current_morale: number;

    current_population: number;

    current_stone: number;

    current_units: CurrentUnitDetails[] | [];

    current_wood: number;

    defence_bonus: number;

    game_map_id: number;

    gold_bars: number;

    gold_bars_defence: number;

    id: number;

    iron_cost_reduction: number;

    max_clay: number;

    max_iron: number;

    max_morale: number;

    max_population: number;

    max_stone: number;

    max_wood: number;

    name: string;

    passive_defence: number;

    population_cost_reduction: number;

    treasury: number;

    treasury_defence: number;

    unit_cost_reduction: number;

    unit_movement: [];

    unit_queue: [];

    unit_time_reduction: number;

    walls_defence: number;

    x_position: number;

    y_position: number;

    is_protected: boolean;

    protected_days_left: number;

    is_under_attack: boolean;
}
