import { QueueStatus } from "../enums/queue-status";
import BuildingInQueue from "./building-in-queue";

export default interface KingddomBuildingQueue {
    kingdom_id: number;
    kingdom_name: string;
    map_name: string;
    status: QueueStatus;
    building_queue: BuildingInQueue[];
    total_time: number;
    queue_id: number;
}
