import Event from "../deffinitions/event";
import {View} from "@aldabil/react-scheduler/components/nav/Navigation";
import {FieldProps, ProcessedEvent, SchedulerHelpers} from "@aldabil/react-scheduler/types";

export default interface SchedulerProps {
    events: Event[]|[];
    customEditor?: (scheduler: SchedulerHelpers) => JSX.Element;
    viewerExtraComponent?: (fields: FieldProps[]|[], event: ProcessedEvent) => JSX.Element;
    onDelete?: (eventId: number) => Promise<string | number | void>
    view: View;
}

type RaidsForSelection = {
    id: number;
    name: string;
}
