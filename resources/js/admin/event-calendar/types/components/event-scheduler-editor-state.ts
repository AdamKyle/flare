import EventForm from "../deffinitions/components/event-form";

export default interface EventSchedulerEditorState {
    form_data: EventForm | null;

    error_message: string | null;

    is_saving: boolean;
}
