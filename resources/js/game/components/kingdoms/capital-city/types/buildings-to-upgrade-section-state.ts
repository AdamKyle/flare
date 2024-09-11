import Kingdom from "../deffinitions/kingdom";
import { BuildingQueue } from "../../deffinitions/building-queue";

export default interface BuildingsToUpgradeSectionState {
    loading: boolean;
    processing_request: boolean;
    success_message: string | null;
    error_message: string | null;
    building_data: Kingdom[];
    filtered_building_data: Kingdom[];
    open_kingdom_ids: Set<number>;
    sort_direction: "asc" | "desc";
    search_query: string;
    building_queue: BuildingQueue[];
    currentPage: number;
    itemsPerPage: number;
}
