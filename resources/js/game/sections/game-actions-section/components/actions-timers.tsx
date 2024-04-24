import React from "react";
import clsx from "clsx";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import ActionsTimerProps from "./types/actions-timer-props";

export default class ActionsTimers extends React.Component<
    ActionsTimerProps,
    {}
> {
    constructor(props: ActionsTimerProps) {
        super(props);
    }

    render() {
        return (
            <div className="relative top-[24px]">
                <div
                    className={clsx("grid gap-2", {
                        "md:grid-cols-2":
                            this.props.attack_time_out !== 0 &&
                            this.props.crafting_time_out !== 0,
                    })}
                >
                    <div>
                        <TimerProgressBar
                            time_remaining={this.props.attack_time_out}
                            time_out_label={"Attack Timeout"}
                            update_time_remaining={this.props.update_attack_timer.bind(
                                this,
                            )}
                        />
                    </div>
                    <div>
                        <TimerProgressBar
                            time_remaining={this.props.crafting_time_out}
                            time_out_label={"Crafting Timeout"}
                            update_time_remaining={this.props.update_crafting_timer.bind(
                                this,
                            )}
                        />
                    </div>
                </div>
            </div>
        );
    }
}
