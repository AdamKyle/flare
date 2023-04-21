import DataTableBaseData from "../../ui/types/tables/data-table-base-data";

export default interface BuildingInQueueDetails {
    building_id: number;

    character_id: number;

    completed_at: string;

    created_at: string;

    id: number;

    kingdom_id: number;

    paid_amount: number;

    paid_with_gold: boolean;

    started_at: string;

    to_level: number;

    updated_at: string;
}
