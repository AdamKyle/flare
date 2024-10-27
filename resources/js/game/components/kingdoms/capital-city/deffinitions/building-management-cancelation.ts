import { CancellationType } from "../enums/cancellation-type";
import BuildingInQueue from "./building-in-queue";

export default interface BuildingManagementCancelation {
    open: boolean;
    building_details: BuildingInQueue | null;
    kingdom_id: number | null;
    queue_id: number | null;
    cancellation_type: CancellationType | null;
}
