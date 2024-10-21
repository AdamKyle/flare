import { QueueStatus } from "../enums/queue-status";

export default interface Unit {
    unit_name: string;
    secondary_status: QueueStatus;
    amount_to_recruit: number;
    queue_id: number;
}
