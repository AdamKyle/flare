import DataTableBaseData from "../../../lib/ui/types/tables/data-table-base-data";
import BuildingDetails from "../buildings/deffinitions/building-details";
import UnitMovementDetails from "../queues/deffinitions/unit-movement-details";
import BuildingInQueueDetails from "./building-in-queue-details";
import CurrentUnitDetails from "./current-unit-details";
import UnitDetails from "./unit-details";

export default interface KingdomDetails extends DataTableBaseData {
    is_capital: boolean;

    building_cost_reduction: number;

    building_queue: BuildingInQueueDetails[] | [];

    buildings: BuildingDetails[] | [];

    units: UnitDetails[] | [];

    unitsInMovement: UnitMovementDetails[] | [];

    building_time_reduction: number;

    can_access_bank: boolean;

    can_access_smelter: boolean;

    can_access_resource_request: boolean;

    character_id: number;

    color: string;

    current_clay: number;

    current_iron: number;

    current_morale: number;

    current_population: number;

    current_stone: number;

    current_steel: number;

    current_units: CurrentUnitDetails[] | [];

    current_wood: number;

    smelting_time_left: number;

    smelting_amount: number;

    defence_bonus: number;

    game_map_id: number;

    game_map_name: string;

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

    max_steel: number;

    name: string;

    passive_defence: number;

    population_cost_reduction: number;

    treasury: number;

    treasury_defence: number;

    unit_cost_reduction: number;

    smelting_time_reduction: number;

    unit_movement: [];

    unit_queue: [];

    unit_time_reduction: number;

    walls_defence: number;

    x_position: number;

    y_position: number;

    is_protected: boolean;

    protected_days_left: number;

    is_under_attack: boolean;

    smelting_completed_at: string;

    item_resistance_bonus: number;

    can_access_capital_city: boolean;
}
