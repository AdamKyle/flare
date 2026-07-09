import clsx from "clsx";
import React from "react";
import TimerProgressBar from "../ui/progress-bars/timer-progress-bar";
import MapTimerProps from "./types/map-timer-props";

export default class MapTimer extends React.Component<MapTimerProps, {}> {
    constructor(props: MapTimerProps) {
        super(props);
    }

    batchCraftingTimerVisible(): boolean {
        return this.props.batch_crafting_time_out >= 5;
    }

    batchCraftingTimerLabel(): string {
        if (this.props.batch_crafting_experience_mode) {
            return "Crafting For Experience";
        }

        if (this.props.batch_crafting_retry_mode) {
            return "Time To Craft Remaining Items";
        }

        return "Time Till Batch Craft";
    }

    render() {
        const timers = [
            {
                key: "movement-timer",
                time_remaining: this.props.time_left,
                time_out_label: "Movement Timeout",
            },
            {
                key: "automation-timer",
                time_remaining: this.props.automation_time_out,
                time_out_label: this.props.automation_time_out_label,
            },
            {
                key: "celestial-timer",
                time_remaining: this.props.celestial_time_out,
                time_out_label: "Celestial Timeout",
            },
            {
                key: "batch-crafting-timer",
                time_remaining: this.batchCraftingTimerVisible()
                    ? this.props.batch_crafting_time_out
                    : 0,
                time_out_label: this.batchCraftingTimerLabel(),
            },
        ].filter((timer) => timer.time_remaining !== 0);

        return (
            <div
                className={clsx("grid gap-2", {
                    "grid-cols-2": timers.length > 1,
                })}
            >
                {timers.map((timer) => (
                    <div key={timer.key}>
                        <TimerProgressBar
                            time_remaining={timer.time_remaining}
                            time_out_label={timer.time_out_label}
                        />
                    </div>
                ))}
            </div>
        );
    }
}
