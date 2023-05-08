import Event from "../../components/ui/scheduler/deffinitions/event";

export default interface CalendarState {

    events: Event[]|[];

    loading: boolean;
}
