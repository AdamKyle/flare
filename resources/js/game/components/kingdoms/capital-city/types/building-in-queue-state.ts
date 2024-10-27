import BuildingManagementCancelation from "../deffinitions/building-management-cancelation";
import KingddomBuildingQueue from "../deffinitions/kingdom-building-queue";

export default interface BuildingInQueueState {
    loading: boolean;
    success_message: string | null;
    error_message: string | null;
    building_queues: KingddomBuildingQueue[];
    filtered_building_queues: KingddomBuildingQueue[];
    search_query: string;
    open_kingdom_ids: Set<number>;
    view_port: number;
    dark_tables: boolean;
    cancelation_modal: BuildingManagementCancelation;
}
