import Raid from "./raid";
import {ProcessedEvent} from "@aldabil/react-scheduler/types";

export default interface Event extends ProcessedEvent {
    description: string;
    event_id: number;
    id: number;
    event_type: number;
    raid: Raid;
    raid_id: number;
    title: string;
    start: Date;
    end: Date;
    currently_running: boolean;
}
