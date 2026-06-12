import clsx from "clsx";
import React from "react";
import TimerProgressBar from "../ui/progress-bars/timer-progress-bar";
import MapTimerProps from "./types/map-timer-props";

export default class MapTimer extends React.Component<MapTimerProps, {}> {
    constructor(props: MapTimerProps) {
        super(props);
    }

    render() {
        const activeTimerCount = [
            this.props.time_left,
            this.props.automation_time_out,
            this.props.celestial_time_out,
        ].filter((timeRemaining: number) => timeRemaining !== 0).length;

        return (
            <div
                className={clsx("grid gap-2", {
                    "grid-cols-2": activeTimerCount > 1,
                })}
            >
                <div>
                    <TimerProgressBar
                        key="movement-timer"
                        time_remaining={this.props.time_left}
                        time_out_label={"Movement Timeout"}
                    />
                </div>
                <div>
                    <TimerProgressBar
                        key="automation-timer"
                        time_remaining={this.props.automation_time_out}
                        time_out_label={this.props.automation_time_out_label}
                    />
                </div>
                <div>
                    <TimerProgressBar
                        key="celestial-timer"
                        time_remaining={this.props.celestial_time_out}
                        time_out_label={"Celestial Timeout"}
                    />
                </div>
            </div>
        );
    }
}
