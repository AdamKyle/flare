import React from "react";
import { Scheduler } from "@aldabil/react-scheduler";
import SchedulerProps from "./types/scheduler-props";

export default class Calendar extends React.Component<SchedulerProps, {}> {

    constructor(props: SchedulerProps) {
        super(props);
    }

    render() {

        if (!this.props.can_edit) {
            return (
                <Scheduler view={this.props.view}
                           events={this.props.events}
                           viewerExtraComponent={this.props.viewerExtraComponent}
                           customEditor={this.props.customEditor}
                           editable={false}
                           deletable={false}
                           disableViewNavigator={true}
                           draggable={false}
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
