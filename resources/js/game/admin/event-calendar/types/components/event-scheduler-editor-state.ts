import {SchedulerHelpers} from "@aldabil/react-scheduler/types";
import Raid from "../../../../components/ui/scheduler/deffinitions/raid";
import EventForm from "../../deffinitions/components/event-form";

export default interface EventSchedulerEditorState {

    form_data: EventForm | {};

    error_message: string | null;

    is_saving: boolean;

}
