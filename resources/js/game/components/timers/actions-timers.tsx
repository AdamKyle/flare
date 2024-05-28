import clsx from "clsx";
import React from "react";
import { serviceContainer } from "../../lib/containers/core-container";
import TimerProgressBar from "../ui/progress-bars/timer-progress-bar";
import ActionTimerListeners from "./event-listeners/action-timer-listeners";
import ActionsTimerProps from "./types/actions-timer-props";
import ActionsTimerState from "./types/actions-timer-state";

export default class ActionsTimers extends React.Component<
    ActionsTimerProps,
    ActionsTimerState
> {
    private actionTimerListeners?: ActionTimerListeners;

    constructor(props: ActionsTimerProps) {
        super(props);

        this.state = {
            attack_time_out: 0,
            crafting_time_out: 0,
        };

        this.actionTimerListeners =
            serviceContainer().fetch(ActionTimerListeners);

        this.actionTimerListeners.initialize(this, this.props.user_id);

        this.actionTimerListeners.register();
    }

    componentDidMount(): void {
        if (this.actionTimerListeners) {
            this.actionTimerListeners.listen();
        }
    }

    updateAttackTimer(timeLeft: number) {
        this.setState({
            attack_time_out: timeLeft,
        });
    }

    updateCraftingTimer(timeLeft: number) {
        this.setState({
            crafting_time_out: timeLeft,
        });
    }

    render() {
        return (
            <div className="relative top-[24px]">
                <div
                    className={clsx("grid gap-2", {
                        "grid-cols-2":
                            this.state.attack_time_out !== 0 &&
                            this.state.crafting_time_out !== 0,
                    })}
                >
                    <div>
                        <TimerProgressBar
                            time_remaining={this.state.attack_time_out}
                            time_out_label={"Attack Timeout"}
                            update_time_remaining={this.updateAttackTimer.bind(
                                this,
                            )}
                        />
                    </div>
                    <div>
                        <TimerProgressBar
                            time_remaining={this.state.crafting_time_out}
                            time_out_label={"Crafting Timeout"}
                            update_time_remaining={this.updateCraftingTimer.bind(
                                this,
                            )}
                        />
                    </div>
                </div>
            </div>
        );
    }
}
