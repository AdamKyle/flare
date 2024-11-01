import React from "react";
import HealthBarContainerProps from "./types/health-bar-container-props";

export default class HealthBarContainer extends React.Component<HealthBarContainerProps> {
    render() {
        return (
            <div
                className="
                    w-full lg:w-2/3 mx-auto mt-4 flex items-center justify-center
                    gap-x-3 text-lg leading-none
                "
            >
                <div className="w-full lg:w-1/3">{this.props.children}</div>
            </div>
        );
    }
}
