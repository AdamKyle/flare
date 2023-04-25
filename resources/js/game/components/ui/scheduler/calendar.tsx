import React from "react";
import { Scheduler } from "@aldabil/react-scheduler";

export default class Calendar extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Scheduler view={this.props.view}
                       events={this.props.events}
                       customEditor={this.props.customEditor}
            />
        )
    }
}
