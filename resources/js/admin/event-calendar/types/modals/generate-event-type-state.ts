export default interface GenerateExtentTypeState {
    action_in_progress: boolean;

    form_data: {
        selected_event_type: number | null;
        generate_every: string | Date;
        selected_start_date: string | Date;
    };

    error_message: string | null;
}
