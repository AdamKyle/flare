import React from "react";
import DangerAlert from "../../../../../game/components/ui/alerts/simple-alerts/danger-alert";
import TopsErrorStateProps from "../types/tops-error-state-props";

export default class TopsErrorState extends React.Component<TopsErrorStateProps> {
    render() {
        return (
            <div role="alert">
                <DangerAlert additional_css="my-4">
                    {this.props.message}
                </DangerAlert>
            </div>
        );
    }
}
