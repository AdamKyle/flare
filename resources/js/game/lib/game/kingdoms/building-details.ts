import DataTableBaseData from "../../ui/types/tables/data-table-base-data";

export default interface BuildingDetails extends DataTableBaseData {

    base_clay_cost: number;

    base_iron_cost: number;

    base_population: number;

    base_stone_cost: number;

    base_wood_cost: number;

    clay_cost: number;

    clay_increase: number;

    current_defence: number;

    current_durability: number;

    description: string;

    future_clay_increase: number;

    future_defence_increase: number;

    future_durability_increase: number;

    future_iron_increase: number;

    future_population_increase: number;

    future_stone_increase: number;

    future_wood_increase: number;

    id: number;

    iron_cost: number;

    iron_increase: number;

    is_church: boolean;

    is_farm: boolean;

    is_locked: boolean;

    is_maxed: boolean;

    is_resource_building: boolean;

    is_wall: boolean;

    kingdom_id: number;

    level: number;

    max_defence: number;

    max_durability: number;

    max_level: number;

    morale_decrease: number;

    morale_increase: number;

    name: string;

    passive_skill_name: string|null;

    population_increase: number;

    population_required: number;

    raw_required_population: number;

    raw_time_increase: number;

    raw_time_to_build: number;

    rebuild_time: number;

    stone_cost: number;

    stone_increase: number;

    time_increase: number;

    trains_units: boolean;

    upgrade_cost: number;

    wood_cost: number;

    wood_increase: number;
}
