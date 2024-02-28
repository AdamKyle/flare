import {SchedulerHelpers} from "@aldabil/react-scheduler/types";
import Raid from "../../../../game/components/ui/scheduler/deffinitions/raid";

export default interface EventSchedulerEditorProps {

    scheduler: SchedulerHelpers;

    is_loading: boolean;

    raids: Raid[]|[];

    event_types: string[]|[]

}
