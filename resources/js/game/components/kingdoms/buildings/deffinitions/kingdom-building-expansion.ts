export interface ResourceCosts {
    stone: number;
    wood: number;
    iron: number;
    clay: number;
    steel: number;
    population: number;
}

export default interface KingdomBuildingExpansion {
    game_building_id: number;
    kingdom_id: number;
    expansion_type: number;
    expansion_count: number;
    expansions_left: number;
    minutes_until_next_expansion: number;
    resource_costs: ResourceCosts;
    gold_bars_cost: number;
    resource_increases: number;
}
