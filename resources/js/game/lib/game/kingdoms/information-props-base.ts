export default interface InformationPropsBase {
    kingdom_building_time_reduction?: number;

    kingdom_unit_time_reduction?: number;

    kingdom_building_cost_reduction?: number;

    kingdom_iron_cost_reduction: number;

    kingdom_population_cost_reduction: number;

    kingdom_current_population: number;

    unit_cost_reduction?: number

    character_id: number;

    is_in_queue: boolean;
}
