import { QueueStatus } from "../enums/queue-status";
import Unit from "./unit";

export interface KingdomWithUnitRequests {
    queue_id: number;
    kingdom_id: number;
    kingdom_name: string;
    map_name: string;
    unit_requests: Unit[];
    status: QueueStatus;
    total_time: number;
    time_remaining?: number;
    timer_duration?: number;
    phase_timer_label?: string;
    timer_started_at?: number;
    started_at?: string;
    completed_at?: string;
    completed_at_timestamp?: number;
    queue_ids?: number[];
}
