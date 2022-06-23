import BuildingDetails from "../building-details";
import KingdomDetails from "../kingdom-details";

export default interface BuildingInformationProps {
    building: BuildingDetails;

    close: (building?: BuildingDetails) => void;

    update_kingdoms: (kingdom: KingdomDetails) => void;

    kingdom_building_time_reduction: number;

    kingdom_building_cost_reduction: number;

    kingdom_iron_cost_reduction: number;

    kingdom_building_pop_cost_reduction: number;

    kingdom_current_population: number;

    character_id: number;

    is_in_queue: boolean;
}
