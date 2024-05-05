import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import OrangeProgressBar from "../../../components/ui/progress-bars/orange-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import EventGoalsTabState from "./types/event-goals-tab-state";
import EventGoal from "./definitions/event-goal";
import {kebabCase} from "lodash";
export default class EventGoalsTab extends React.Component<
    any,
    EventGoalsTabState
> {
    private eventGoalsUpdate: any;

    private playerEventGoalCurrentAmount: any;

    constructor(props: {}) {
        super(props);

        this.state = {
            loading: true,
            eventGoal: null,
            player_amount: 0,
        };

        // @ts-ignore
        this.eventGoalsUpdate = Echo.join("update-event-goal-progress");

        // @ts-ignore
        this.playerEventGoalCurrentAmount = Echo.private(
            "player-current-event-goal-progression-" + this.props.user_id,
        );
    }

    componentDidMount(): void {
        new Ajax()
            .setRoute("global-event-goals/" + this.props.character_id)
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState(
                        {
                            loading: false,
                            eventGoal: result.data.event_goals,
                        },
                        () => {
                            this.setCurrentAmount();
                        },
                    );
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );

        this.eventGoalsUpdate.listen(
            "Game.Events.Events.UpdateEventGoalProgress",
            (event: { eventGoalData: { event_goals: EventGoal } }) => {
                this.setState({
                    eventGoal: event.eventGoalData.event_goals,
                });
            },
        );

        this.playerEventGoalCurrentAmount.listen(
            "Game.Events.Events.UpdateEventGoalCurrentProgressForCharacter",
            (event: any) => {
                this.setState({
                    player_amount: event.amount,
                });
            },
        );
    }

    buildProgressBars() {
        const progressBars: any = [];

        if (this.state.eventGoal === null) {
            return progressBars;
        }

        let totalDone = 0;
        let maxAmount = 0;

        let current = this.state.eventGoal.reward_every;
        let phase = 1;

        if (
            this.state.eventGoal.max_kills !== null &&
            this.state.eventGoal.total_kills !== null
        ) {
            maxAmount = this.state.eventGoal.max_kills;
            totalDone = this.state.eventGoal.total_kills;
        }

        if (
            this.state.eventGoal.max_crafts !== null &&
            this.state.eventGoal.total_crafts !== null
        ) {
            maxAmount = this.state.eventGoal.max_crafts;
            totalDone = this.state.eventGoal.total_crafts;
        }

        if (
            this.state.eventGoal.max_enchants !== null &&
            this.state.eventGoal.total_enchants !== null
        ) {
            maxAmount = this.state.eventGoal.max_enchants;
            totalDone = this.state.eventGoal.total_enchants;
        }

        while (current <= maxAmount) {
            const value = Math.min(current, totalDone);

            progressBars.push(
                <div className="mb-4 relative top-[-15px]">
                    <OrangeProgressBar
                        key={current}
                        primary_label={"Phase: " + phase}
                        secondary_label={
                            formatNumber(value) + "/" + formatNumber(current)
                        }
                        percentage_filled={(value / current) * 100}
                        height_override_class="h-2"
                        text_override_class="text-md"
                        push_down={true}
                    />
                </div>,
            );

            current += this.state.eventGoal.reward_every;
            phase++;
        }

        return progressBars;
    }

    getEventGoalLabel(): string {
        if (this.state.eventGoal === null) {
            return "ERROR - Missing event goal.";
        }

        let label = "ERROR - undefined type of total for event goal.";

        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            label =
                formatNumber(this.state.eventGoal.total_kills) +
                "/" +
                formatNumber(this.state.eventGoal.max_kills);
        }

        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            label =
                formatNumber(this.state.eventGoal.total_crafts) +
                "/" +
                formatNumber(this.state.eventGoal.max_crafts);
        }

        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            label =
                formatNumber(this.state.eventGoal.total_enchants) +
                "/" +
                formatNumber(this.state.eventGoal.max_enchants);
        }

        return label;
    }

    getOverAllProgress(): number {
        if (this.state.eventGoal === null) {
            return 0;
        }

        let percentageFilled = 0;

        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            percentageFilled =
                (this.state.eventGoal.total_kills /
                    this.state.eventGoal.max_kills) *
                100;
        }

        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            percentageFilled =
                (this.state.eventGoal.total_crafts /
                    this.state.eventGoal.max_crafts) *
                100;
        }

        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            percentageFilled =
                (this.state.eventGoal.total_enchants /
                    this.state.eventGoal.max_enchants) *
                100;
        }

        return percentageFilled > 100 ? 100 : percentageFilled;
    }

    getTitleForProgressBar(): string {
        if (this.state.eventGoal === null) {
            return "Unknown Event Step";
        }

        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            return "Creature Kill";
        }

        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            return "Item Crafting Amount";
        }

        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            return "Enchanting Amount";
        }

        return "Unknown Event Step";
    }

    setCurrentAmount() {
        if (this.state.eventGoal === null) {
            return;
        }

        if (
            this.state.eventGoal.total_kills !== null &&
            this.state.eventGoal.max_kills !== null
        ) {
            this.setState({
                player_amount: this.state.eventGoal.current_kills,
            });
        }

        if (
            this.state.eventGoal.total_crafts !== null &&
            this.state.eventGoal.max_crafts !== null
        ) {
            this.setState({
                player_amount: this.state.eventGoal.current_crafts,
            });
        }

        if (
            this.state.eventGoal.total_enchants !== null &&
            this.state.eventGoal.max_enchants !== null
        ) {
            this.setState({
                player_amount: this.state.eventGoal.current_enchants,
            });
        }

        return;
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
                        primary_label={
                            "Event Goal - " + this.getTitleForProgressBar()
                        }
                        secondary_label={this.getEventGoalLabel()}
                        percentage_filled={this.getOverAllProgress()}
                        height_override_class="h-3"
                        text_override_class="text-md"
                        push_down={true}
                    />
                </div>
                <div className="my-4 relative top-2">
                    <p className="my-2">
                        Each phase is an objective that when completed will
                        reward all players who participated with a piece of new
                        gear in relation to the event goal.
                    </p>
                    <p className="my-2">
                        Goals are created such that the more players, the faster
                        you get the reward.
                    </p>
                    <dl className="my-2">
                        <dt>Gear Set Name</dt>
                        <dd><a href={"/information/" + kebabCase(this.state.eventGoal.reward)} target='_blank'>{
                            this.state.eventGoal.reward
                        } <i
                            className="fas fa-external-link-alt"></i></a></dd>
                        <dt>With Legendary Unique Attached?</dt>
                        <dd>
                            {this.state.eventGoal.should_be_unique
                                ? "Yes"
                                : "No"}
                        </dd>
                        <dt>With Mythic Attached?</dt>
                        <dd>
                            {this.state.eventGoal.should_be_mythic
                                ? "Yes"
                                : "No"}
                        </dd>
                    </dl>
                    <p className="my-2 font-bold">
                        <span className="text-orange-500 dark:text-orange-300">
                            Contribution required for reward:
                        </span>{" "}
                        {formatNumber(
                            this.state.eventGoal.amount_needed_for_reward,
                        )}
                    </p>
                    <p className="my-2 font-bold">
                        <span className="text-orange-500 dark:text-orange-300">
                            Your current contribution:
                        </span>{" "}
                        {formatNumber(this.state.player_amount)}
                    </p>
                </div>
                <div className="max-h-[200px] overflow-y-scroll px-2 relative top-[-10px]">
                    {this.buildProgressBars()}
                </div>
            </div>
        );
    }
}
