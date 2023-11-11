import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import OrangeProgressBar from "../../../components/ui/progress-bars/orange-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import EventGoalsTabState from "./types/event-goals-tab-state";

export default class EventGoalsTab extends React.Component<
    {},
    EventGoalsTabState
> {
    constructor(props: {}) {
        super(props);

        this.state = {
            loading: true,
            eventGoal: null,
        };
    }

    componentDidMount(): void {
        new Ajax().setRoute("global-event-goals").doAjaxCall(
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
    }

    buildProgressBars() {
        const progressBars: any = [];

        if (this.state.eventGoal === null) {
            return progressBars;
        }

        let current = this.state.eventGoal.reward_every;
        let phase = 1;
        console.log(this.state.eventGoal);
        while (current <= this.state.eventGoal.max_kills) {
            const value = Math.min(current, this.state.eventGoal.total_kills);

            progressBars.push(
                <div className="mb-4">
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
                </div>
                <div className="max-h-[200px] overflow-y-scroll px-2 relative top-[-10px]">
                    {this.buildProgressBars()}
                </div>
            </div>
        );
    }
}
