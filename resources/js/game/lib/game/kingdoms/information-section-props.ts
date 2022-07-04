import UnitDetails from "./unit-details";
import BuildingDetails from "./building-details";

export default interface InformationSectionProps {
    sections: {
        unit_to_view: UnitDetails | null,
        building_to_view: BuildingDetails | null,
    };

    close: () => void;

    cost_reduction: {
        kingdom_building_time_reduction: number,
        kingdom_building_cost_reduction: number,
        kingdom_iron_cost_reduction: number,
        kingdom_population_cost_reduction: number,
        kingdom_current_population: number,
        kingdom_unit_cost_reduction: number,
    };

    queue: {
        is_building_in_queue: boolean;
        is_unit_in_queue: boolean;
    };

    character_id: number;

    kingdom_id: number;

    buildings: BuildingDetails[] | [];
}
