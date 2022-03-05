import React, {Fragment} from "react";
import ProgressBarProps from "../../../lib/ui/types/progress-bars/progress-bar-props";

export default class ProgressBar extends React.Component<ProgressBarProps, any> {

    constructor(props: ProgressBarProps) {
        super(props);
    }

    getTimeLeft(): number {
        return 100 / 2;
    }

    render() {
        return (
            <Fragment>
                <div className="flex justify-between mb-1">
                    <span className="text-base font-medium text-gray-800 dark:text-white">
                        Timeout
                        <i className="ml-2 fas fa-spinner fa-pulse"></i>
                    </span>
                    <span className="text-sm font-medium text-gray-800 dark:text-white">45 seconds left</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div className="bg-fuchsia-600 h-2.5 rounded-full" style={{width: "45%"}}></div>
                </div>
            </Fragment>
        )
    }
}
