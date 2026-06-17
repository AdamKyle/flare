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
            time_left_label: "0:00:00",
            initial_time: 0,
        };

        this.interval = null;
    }

    componentDidMount() {
        this.initializeTimer();
    }

    componentWillUnmount() {
        clearInterval(this.interval);
    }

    componentDidUpdate(
        prevProps: Readonly<TimerProgressBarProps>,
        prevState: Readonly<TimerProgressBarState>,
        snapshot?: any,
    ) {
        if (
            prevProps.time_remaining != this.props.time_remaining ||
            prevProps.timer_started_at != this.props.timer_started_at ||
            prevProps.completed_at_timestamp !=
                this.props.completed_at_timestamp ||
            prevProps.timer_duration != this.props.timer_duration
        ) {
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
        const timeRemaining = this.getEffectiveTimeRemaining();
        const duration = this.props.timer_duration ?? this.props.time_remaining;

        this.setState(
            {
                time_left: timeRemaining,
                percentage_left:
                    timeRemaining > 0 && duration > 0
                        ? timeRemaining / duration
                        : 0.0,
                time_left_label: this.getTimeLabel(timeRemaining),
                initial_time: timeRemaining,
            },
            () => {
                if (timeRemaining > 0 && this.state.time_left > 0) {
                    this.interval = setInterval(() => {
                        let newTime: number;

                        if (
                            typeof this.props.completed_at_timestamp !==
                                "undefined" ||
                            typeof this.props.timer_started_at !== "undefined"
                        ) {
                            newTime = this.getEffectiveTimeRemaining();
                        } else {
                            newTime = Math.max(0, this.state.time_left - 1);
                        }

                        if (newTime <= 0) {
                            this.setState({
                                time_left: 0,
                                percentage_left: 0,
                                time_left_label: "0:00:00",
                            });

                            if (
                                typeof this.props.update_time_remaining !==
                                "undefined"
                            ) {
                                this.props.update_time_remaining(0);
                            }

                            clearInterval(this.interval);
                        } else {
                            const duration =
                                this.props.timer_duration ??
                                this.props.time_remaining;

                            this.setState({
                                time_left: newTime,
                                percentage_left:
                                    duration > 0 ? newTime / duration : 0,
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

    getEffectiveTimeRemaining(): number {
        if (typeof this.props.completed_at_timestamp !== "undefined") {
            return Math.max(
                0,
                Math.ceil(
                    (this.props.completed_at_timestamp - Date.now()) / 1000,
                ),
            );
        }

        if (typeof this.props.timer_started_at === "undefined") {
            return this.props.time_remaining;
        }

        const elapsedSeconds = Math.floor(
            (Date.now() - this.props.timer_started_at) / 1000,
        );

        return Math.max(0, this.props.time_remaining - elapsedSeconds);
    }

    getTimeLabel(newTime: number): string {
        const time = Math.max(0, Math.floor(newTime));
        const hours = Math.floor(time / 3600);
        const minutes = Math.floor((time % 3600) / 60);
        const seconds = time % 60;

        return (
            hours +
            ":" +
            minutes.toString().padStart(2, "0") +
            ":" +
            seconds.toString().padStart(2, "0")
        );
    }

    render() {
        if (
            (this.state.percentage_left <= 0 && this.state.time_left <= 0) ||
            this.getEffectiveTimeRemaining() <= 0
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
                        {this.state.time_left_label} left
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
