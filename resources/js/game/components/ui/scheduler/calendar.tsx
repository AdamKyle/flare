import React from "react";
import { Scheduler } from "@aldabil/react-scheduler";
import SchedulerProps from "./types/scheduler-props";

export default class Calendar extends React.Component<SchedulerProps, any> {

    constructor(props: SchedulerProps) {
        super(props);
    }

    render() {

        if (typeof this.props.customEditor === 'undefined') {
            return (
                <Scheduler view={this.props.view}
                           events={this.props.events}
                           viewerExtraComponent={this.props.viewerExtraComponent}
                           editable={false}
                />
            )
        }

        return (
            <Scheduler view={this.props.view}
                       events={this.props.events}
                       customEditor={this.props.customEditor}
                       viewerExtraComponent={this.props.viewerExtraComponent}
                       onDelete={this.props.onDelete}
            />
        )
    }
}
