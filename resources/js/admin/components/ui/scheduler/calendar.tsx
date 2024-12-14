import React from "react";
import { Scheduler } from "@aldabil/react-scheduler";
import SchedulerProps from "./types/scheduler-props";
import { Button } from "@mui/material";

export default class Calendar extends React.Component<SchedulerProps, {}> {
    constructor(props: SchedulerProps) {
        super(props);
    }

    render() {
        if (!this.props.can_edit) {
            return (
                <Scheduler
                    view={this.props.view}
                    events={this.props.events}
                    viewerExtraComponent={this.props.viewerExtraComponent}
                    customEditor={this.props.customEditor}
                    editable={false}
                    deletable={false}
                    disableViewNavigator={true}
                    draggable={false}
                    month={{
                        weekDays: [0, 1, 2, 3, 4, 5, 6],
                        weekStartOn: 6,
                        startHour: 0,
                        endHour: 23,
                        cellRenderer: ({
                            height,
                            start,
                            onClick,
                            ...props
                        }) => {
                            return (
                                <Button
                                    style={{
                                        height: "100%",
                                        cursor: "not-allowed",
                                    }}
                                ></Button>
                            );
                        },
                    }}
                />
            );
        }

        return (
            <Scheduler
                view={this.props.view}
                events={this.props.events}
                customEditor={this.props.customEditor}
                viewerExtraComponent={this.props.viewerExtraComponent}
                onDelete={this.props.onDelete}
            />
        );
    }
}
