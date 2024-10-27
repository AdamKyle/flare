import { QueueStatus } from "../enums/queue-status";
import Unit from "./unit";

export interface KingdomWithUnitRequests {
    kingdom_id: number;
    kingdom_name: string;
    map_name: string;
    unit_requests: Unit[];
    status: QueueStatus;
    total_time: number;
}
