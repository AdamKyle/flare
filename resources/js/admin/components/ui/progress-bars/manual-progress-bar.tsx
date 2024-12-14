import React, { Fragment } from "react";
import ManualProgressBarProps from "../../../lib/ui/types/progress-bars/manual-progress-bar-props";

export default class ManualProgressBar extends React.Component<
    ManualProgressBarProps,
    any
> {
    constructor(props: ManualProgressBarProps) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                <div className="flex justify-between mb-1">
                    <span className="text-base font-medium text-gray-800 dark:text-white">
                        {this.props.label}
                        {this.props.show_loading_icon ? (
                            <i className="ml-2 fas fa-spinner fa-pulse"></i>
                        ) : null}
                    </span>
                    <span className="text-sm font-medium text-gray-800 dark:text-white">
                        {this.props.secondary_label}
                    </span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div
                        className="h-2.5 rounded-full bg-blue-600 dark:bg-blue-500"
                        style={{
                            width: this.props.percentage_left * 100 + "%",
                        }}
                    ></div>
                </div>
            </Fragment>
        );
    }
}
