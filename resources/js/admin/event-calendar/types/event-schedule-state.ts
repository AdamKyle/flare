import Raid from "../../../game/components/ui/scheduler/deffinitions/raid";
import Event from "../../../game/components/ui/scheduler/deffinitions/event";

export default interface EventScheduleState {
    events: Event[]|[];
    raids: Raid[]|[];
    event_types: string[]|[];
    loading: boolean;
    deleting: boolean;
    show_generate_event_modal: boolean;
}

