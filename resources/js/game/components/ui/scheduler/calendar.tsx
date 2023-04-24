import React from "react";
import { Scheduler } from "@aldabil/react-scheduler";
import {SchedulerHelpers} from "@aldabil/react-scheduler/types";
import EventSchedulerEditor from "../../../admin/event-calendar/components/event-scheduler-editor";

export default class Calendar extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            events: [{
                event_id: 1,
                title: "Raid event",
                start: new Date(new Date(new Date().setHours(9)).setMinutes(0)),
                end: new Date(new Date(new Date().setHours(10)).setMinutes(0)),
            }],
        }

    }

    render() {
        console.log(this.props);
        return (
            <Scheduler view={this.props.view} events={this.props.events} customEditor={this.props.customEditor} />
        )
    }
}
