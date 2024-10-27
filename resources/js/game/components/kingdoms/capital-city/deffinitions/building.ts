import BuildingPassiveSkill from "./building-passive-skill";
import UnitForBuilding from "./unit-for-building";

export default interface Building {
    id: number;
    kingdom_id: number;
    game_building_id: number;
    name: string;
    description: string;
    level: number;
    current_defence: number;
    current_durability: number;
    max_defence: number;
    max_durability: number;
    population_required: number;
    is_locked: boolean;
    passive_skill_name: string;
    wood_cost: number;
    stone_cost: number;
    clay_cost: number;
    iron_cost: number;
    steel_cost: number;
    population_increase: number;
    rebuild_time: number;
    morale_increase: number;
    morale_decrease: number;
    wood_increase: number;
    clay_increase: number;
    stone_increase: number;
    iron_increase: number;
    max_level: number;
    units_for_building: UnitForBuilding[] | [];
    passive_required_for_building: BuildingPassiveSkill | null;
}
