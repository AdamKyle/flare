import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import OrangeProgressBar from "../../../components/ui/progress-bars/orange-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import EventGoalsTabState from "./types/event-goals-tab-state";
import EventGoal from "./definitions/event-goal";

export default class EventGoalsTab extends React.Component<
    any,
    EventGoalsTabState
> {
    private eventGoalsUpdate: any;

    constructor(props: {}) {
        super(props);

        this.state = {
            loading: true,
            eventGoal: null,
        };

        // @ts-ignore
        this.eventGoalsUpdate = Echo.join("update-event-goal-progress");
    }

    componentDidMount(): void {
        new Ajax().setRoute("global-event-goals/" + this.props.character_id).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    eventGoal: result.data.event_goals,
                });
            },
            (error: AxiosError) => {
                console.error(error);
            }
        );

        this.eventGoalsUpdate.listen(
            "Game.Events.Events.UpdateEventGoalProgress",
            (event: { eventGoalData: { event_goals: EventGoal } }) => {
                this.setState({
                    eventGoal: event.eventGoalData.event_goals,
                });
            }
        );
    }

    buildProgressBars() {
        const progressBars: any = [];

        if (this.state.eventGoal === null) {
            return progressBars;
        }

        let current = this.state.eventGoal.reward_every;
        let phase = 1;

        while (current <= this.state.eventGoal.max_kills) {
            const value = Math.min(current, this.state.eventGoal.total_kills);

            progressBars.push(
                <div className="mb-4 relative top-[-15px]">
                    <OrangeProgressBar
                        key={current}
                        primary_label={"Phase: " + phase}
                        secondary_label={
                            formatNumber(value) + "/" + formatNumber(current)
                        }
                        percentage_filled={value / current}
                        height_override_class="h-2"
                        text_override_class="text-md"
                    />
                </div>
            );

            current += this.state.eventGoal.reward_every;
            phase++;
        }

        return progressBars;
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.eventGoal === null) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <div className="relative top-[-30px]">
                    <OrangeProgressBar
                        primary_label={"Event Goal - Creature Kill"}
                        secondary_label={
                            formatNumber(this.state.eventGoal.total_kills) +
                            "/" +
                            formatNumber(this.state.eventGoal.max_kills)
                        }
                        percentage_filled={
                            this.state.eventGoal?.total_kills /
                            this.state.eventGoal?.max_kills
                        }
                        height_override_class="h-3"
                        text_override_class="text-md"
                    />
                </div>
                <div className="my-4 relative top-2">
                    <p className="my-2">
                        Each phase is an objective then when completed will
                        reward all players who participated with a piece of new
                        gear in relation to the event goal.
                    </p>
                    <p className="my-2">
                        Goals are created such that the more players, the faster
                        you get the reward.
                    </p>
                    <dl className="my-2">
                        <dt>Gear Set Name</dt>
                        <dd>Corrupted Ice</dd>
                        <dt>With Legendary Unique Attached?</dt>
                        <dd>Yes</dd>
                        <dt>With Mythic Attached?</dt>
                        <dd>No</dd>
                    </dl>
                    <p className="my-2 font-bold">
                        <span className="text-orange-500 dark:text-orange-300">
                            Amount of kills required to gain phase reward:
                        </span>{" "}
                        {formatNumber(
                            this.state.eventGoal.kills_needed_for_reward
                        )}
                    </p>
                    <p className="my-2 font-bold">
                        <span className="text-orange-500 dark:text-orange-300">
                            Your Current Kills For Event Goal:
                        </span>{" "}
                        {formatNumber(
                            this.state.eventGoal.current_kills
                        )}
                    </p>
                </div>
                <div className="max-h-[200px] overflow-y-scroll px-2 relative top-[-10px]">
                    {this.buildProgressBars()}
                </div>
            </div>
        );
    }
}
