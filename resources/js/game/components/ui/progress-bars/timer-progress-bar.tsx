import React, { Fragment } from "react";
import TimerProgressBarProps from "../../../lib/ui/types/progress-bars/timer-progress-bar-props";
import TimerProgressBarState from "../../../lib/ui/types/progress-bars/timer-progress-bar-state";
import clsx from "clsx";

export default class TimerProgressBar extends React.Component<
    TimerProgressBarProps,
    TimerProgressBarState
> {
    private interval: any;

    constructor(props: TimerProgressBarProps) {
        super(props);

        this.state = {
            time_left: 0,
            percentage_left: 0,
            label: "seconds",
            time_left_label: 0,
            initial_time: 0,
        };

        this.interval = null;
    }

    componentDidMount() {
        this.initializeTimer();
    }

    componentDidUpdate(
        prevProps: Readonly<TimerProgressBarProps>,
        prevState: Readonly<TimerProgressBarState>,
        snapshot?: any,
    ) {
        if (prevProps.time_remaining != this.props.time_remaining) {
            clearInterval(this.interval);

            this.initializeTimer();
        }

        if (this.state.time_left < 0) {
            clearInterval(this.interval);

            this.setState({
                time_left: 0,
            });
        }
    }

    initializeTimer() {
        this.setState(
            {
                time_left: this.props.time_remaining,
                percentage_left: this.props.time_remaining > 0 ? 1.0 : 0.0,
                label: this.getLabel(),
                time_left_label: this.getTimeLabel(this.props.time_remaining),
                initial_time: this.props.time_remaining,
            },
            () => {
                if (this.props.time_remaining > 0 && this.state.time_left > 0) {
                    this.interval = setInterval(() => {
                        let newTime = this.state.time_left - 1;

                        if (newTime <= 0) {
                            this.setState({
                                time_left: 0,
                                percentage_left: 0,
                                label: "seconds",
                                time_left_label: 0,
                            });

                            if (
                                typeof this.props.update_time_remaining !==
                                "undefined"
                            ) {
                                this.props.update_time_remaining(0);
                            }

                            clearInterval(this.interval);
                        } else {
                            this.setState({
                                time_left: newTime,
                                percentage_left:
                                    newTime / this.props.time_remaining,
                                label: this.getLabel(newTime),
                                time_left_label: this.getTimeLabel(newTime),
                            });
                        }
                    }, 1000);
                } else {
                    clearInterval(this.interval);
                }
            },
        );
    }

    getLabel(newTime?: number): string {
        let label = "seconds";
        let time = this.props.time_remaining;

        if (newTime) {
            time = newTime;
        }

        if (time / 3600 >= 1) {
            label = "hour(s)";
        } else if (time / 60 >= 1) {
            label = "minute(s)";
        }

        return label;
    }

    getTimeLabel(newTime: number): number {
        let timeLeftLabel = newTime;

        if (newTime / 3600 >= 1) {
            timeLeftLabel = parseInt((newTime / 3600).toFixed(0));
        } else if (newTime / 60 >= 1) {
            timeLeftLabel = parseInt((newTime / 60).toFixed(0));
        }

        return timeLeftLabel;
    }

    render() {
        if (
            (this.state.percentage_left <= 0 && this.state.time_left <= 0) ||
            this.props.time_remaining === 0
        ) {
            return null;
        }

        return (
            <Fragment>
                <div
                    className={clsx(
                        {
                            "flex justify-between mb-1 ":
                                !this.props.useSmallTimer,
                        },
                        {
                            "flex md:justify-between mb-1":
                                this.props.useSmallTimer,
                        },
                        typeof this.props.additional_css !== "undefined"
                            ? this.props.additional_css
                            : "",
                    )}
                >
                    <span className="text-base font-medium text-gray-800 dark:text-white mr-4 md:mr-0">
                        {this.props.time_out_label}
                    </span>
                    <span className="text-sm font-medium text-gray-800 dark:text-white mt-[3px]">
                        {this.state.time_left_label} {this.state.label} left
                    </span>
                </div>
                <div
                    className={clsx(
                        "bg-gray-200 rounded-full h-1.5 dark:bg-gray-700",
                        { "w-full": !this.props.useSmallTimer },
                        { "w-1/2": this.props.useSmallTimer },
                    )}
                >
                    <div
                        className={
                            "h-1.5 rounded-full " +
                            (this.state.percentage_left >= 0.75
                                ? "bg-fuchsia-600 dark:bg-fuchsia-700"
                                : this.state.percentage_left < 0.75 &&
                                    this.state.percentage_left >= 0.5
                                  ? "bg-fuchsia-500 dark:bg-fuchsia-600"
                                  : this.state.percentage_left >= 0.25 &&
                                      this.state.percentage_left < 0.5
                                    ? "bg-fuchsia-400 dark:bg-fuchsia-500"
                                    : this.state.percentage_left >= 0.0 &&
                                        this.state.percentage_left < 0.25
                                      ? "bg-fuchsia-300 dark:bg-fuchsia-400"
                                      : "")
                        }
                        style={{
                            width: this.state.percentage_left * 100 + "%",
                        }}
                    ></div>
                </div>
            </Fragment>
        );
    }
}
