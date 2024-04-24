import Event from "../../../../game/components/ui/scheduler/deffinitions/event";

export default interface CalendarState {
    events: Event[] | [];

    loading: boolean;
}
