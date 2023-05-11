export default interface EventForm {
    selected_event_type: number | null;
    event_description: string;
    selected_raid: number | null;
    selected_start_date: Date;
    selected_end_date: Date | null;
}
