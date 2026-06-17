import { QueueStatus } from "../enums/queue-status";
import BuildingInQueue from "./building-in-queue";

export default interface KingddomBuildingQueue {
    kingdom_id: number;
    kingdom_name: string;
    map_name: string;
    status: QueueStatus;
    building_queue: BuildingInQueue[];
    total_time: number;
    time_remaining?: number;
    timer_duration?: number;
    phase_timer_label: string;
    queue_id: number;
    timer_started_at?: number;
    started_at?: string;
    completed_at?: string;
    completed_at_timestamp?: number;
}
