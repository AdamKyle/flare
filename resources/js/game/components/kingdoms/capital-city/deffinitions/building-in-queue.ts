import { QueueStatus } from "../enums/queue-status";

export default interface BuildingInQueue {
    building_name: string;
    secondary_status: QueueStatus;
    building_id: number;
    from_level: number;
    to_level: number;
}
