import { BuildingQueue } from "../../deffinitions/building-queue";
import KingdomWithBuildings from "../deffinitions/kingdom-with-buildings";

export default interface BuildingsToUpgradeSectionState {
    loading: boolean;
    processing_request: boolean;
    success_message: string | null;
    error_message: string | null;
    building_data: KingdomWithBuildings[];
    filtered_building_data: KingdomWithBuildings[];
    open_kingdom_ids: Set<number>;
    sort_direction: "asc" | "desc";
    search_query: string;
    building_queue: BuildingQueue[];
    currentPage: number;
    itemsPerPage: number;
}
