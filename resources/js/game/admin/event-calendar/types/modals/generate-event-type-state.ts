export default interface GenerateExtentTypeState {
    action_in_progress: boolean;

    form_data: {
        selected_event_type: number | null;
        event_generation_times: number | null;
        generate_every: string | null;
        selected_start_date: Date;
    };

    error_message: string | null;
}
