import React, { Fragment } from "react";
import clsx from "clsx";

export default class LoadingProgressBar extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                {this.props.show_label ? (
                    <div className="flex justify-between mb-1 mt-5">
                        <span className="text-base font-medium text-gray-800 dark:text-white">
                            {this.props.label}
                        </span>
                        <span className="text-sm font-medium text-gray-800 dark:text-white">
                            {this.props.secondary_label}
                        </span>
                    </div>
                ) : null}

                <div
                    className={clsx(
                        "w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 relative mb-5",
                        {
                            "mt-5": !this.props.show_label,
                        },
                    )}
                >
                    <div className="h-2.5 rounded-full bg-blue-600 dark:bg-blue-500 loading-progress-bar"></div>
                </div>
            </Fragment>
        );
    }
}
