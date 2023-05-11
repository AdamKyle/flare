import Raid from "../../../components/ui/scheduler/deffinitions/raid";
import Event from "../../../components/ui/scheduler/deffinitions/event";

export default interface EventScheduleState {
    events: Event[]|[];
    raids: Raid[]|[];
    event_types: string[]|[];
    loading: boolean;
    deleting: boolean;
}

