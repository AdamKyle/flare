import React from "react";
import OrangeProgressBarProps from "../../../lib/ui/types/progress-bars/orange-progress-bar-props";
import clsx from "clsx";

export default class OrangeProgressBar extends React.Component<
    OrangeProgressBarProps,
    {}
> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div
                className={clsx({
                    "relative top-[24px]": this.props.push_down,
                })}
            >
                <div className="flex justify-between mb-1">
                    <span
                        className={
                            "font-medium text-orange-700 dark:text-white text-xs " +
                            this.props.text_override_class
                        }
                    >
                        {this.props.primary_label}
                    </span>
                    <span
                        className={
                            "text-xs font-medium text-orange-700 dark:text-white " +
                            this.props.text_override_class
                        }
                    >
                        {this.props.secondary_label}
                    </span>
                </div>
                <div
                    className={
                        "w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 " +
                        this.props.height_override_class
                    }
                >
                    <div
                        className={
                            "bg-orange-600 h-1.5 rounded-full " +
                            this.props.height_override_class
                        }
                        style={{ width: this.props.percentage_filled + "%" }}
                    ></div>
                </div>
            </div>
        );
    }
}
