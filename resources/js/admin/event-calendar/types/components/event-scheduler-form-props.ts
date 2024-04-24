import Raid from "../../../../game/components/ui/scheduler/deffinitions/raid";
import EventForm from "../deffinitions/components/event-form";
import { ProcessedEvent } from "@aldabil/react-scheduler/types";

export default interface EventSchedulerFormProps {
    event_data: ProcessedEvent | undefined;

    update_parent: (formData: EventForm) => void;

    raids: Raid[] | [];

    event_types: string[] | [];
}
