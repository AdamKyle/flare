import React, { Fragment } from "react";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import KingdomPassiveTree from "./skill-tree/kingdom-passive-tree";
import TimerProgressBar from "../../../../../components/ui/progress-bars/timer-progress-bar";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import { DateTime } from "luxon";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import KingdomPassiveRow from "../../../../../lib/game/character-sheet/types/skills/kingdom-passive-row";

interface KingdomPassivesProps {
    read_only?: boolean;
    preloaded_kingdom_passives?: KingdomPassiveRow[];
    character_id: number;
    is_dead: boolean;
    is_automation_running: boolean;
    is_faction_loyalty_automation_running: boolean;
    is_delve_running: boolean;
    active_automation: { name: string } | null;
}

interface KingdomPassivesState {
    loading: boolean;
    kingdom_passives: KingdomPassiveRow[];
    success_message: string | null;
    skill_in_training: KingdomPassiveRow | null;
}

export default class KingdomPassives extends React.Component<
    KingdomPassivesProps,
    KingdomPassivesState
> {
    constructor(props: KingdomPassivesProps) {
        super(props);

        this.state = {
            loading: !props.read_only,
            kingdom_passives: props.read_only
                ? (props.preloaded_kingdom_passives ?? [])
                : [],
            success_message: null,
            skill_in_training: null,
        };
    }

    componentDidMount() {
        if (this.props.read_only) {
            return;
        }

        new Ajax()
            .setRoute("character/kingdom-passives/" + this.props.character_id)
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        kingdom_passives: result.data.kingdom_passives,
                        skill_in_training: result.data.passive_training,
                    });
                },
                (error: AxiosError) => {},
            );
    }

    manageSuccessMessage(message: string) {
        this.setState({
            success_message: message,
        });
    }

    closeSuccessAlert() {
        this.setState({
            success_message: null,
        });
    }

    updatePassives(
        passives: KingdomPassiveRow[],
        passiveInTraining?: KingdomPassiveRow,
    ) {
        this.setState({
            kingdom_passives: passives,
            skill_in_training: passiveInTraining ?? null,
        });
    }

    findSkillInTraining(passive: KingdomPassiveRow): void {
        if (this.updatePassiveTrainingState(passive)) {
            return;
        }

        if (passive.children.length > 0) {
            for (let i = 0; i < passive.children.length; i++) {
                const child = passive.children[i];

                if (child.children.length > 0) {
                    this.findSkillInTraining(child);
                }

                if (this.updatePassiveTrainingState(child)) {
                    return;
                }
            }
        }
    }

    updatePassiveTrainingState(passive: KingdomPassiveRow): boolean {
        if (passive.started_at !== null) {
            this.setState({
                skill_in_training: passive,
            });

            return true;
        } else {
            this.setState({
                skill_in_training: null,
            });

            return false;
        }
    }

    getTimeLeftInSeconds(): number {
        if (this.state.skill_in_training !== null) {
            const start = DateTime.now();
            const end = DateTime.fromISO(
                this.state.skill_in_training.completed_at,
            );

            const diff = end.diff(start, ["seconds"]).toObject();

            if (diff.hasOwnProperty("seconds")) {
                if (typeof diff.seconds !== "undefined") {
                    return Math.round(diff.seconds);
                }
            }

            return 0;
        }

        return 0;
    }

    automationName(): string {
        if (this.props.active_automation !== null) {
            return this.props.active_automation.name;
        }

        if (this.props.is_faction_loyalty_automation_running) {
            return "Faction Loyalty";
        }

        if (this.props.is_delve_running) {
            return "Delve";
        }

        return "Exploration";
    }

    renderReadOnly(): JSX.Element {
        const passives = this.state.kingdom_passives;

        if (passives.length === 0) {
            return (
                <p className="text-sm text-gray-700 dark:text-gray-300">
                    No public kingdom passive progress is available.
                </p>
            );
        }

        return <KingdomPassiveTree passives={passives[0]} read_only={true} />;
    }

    render() {
        if (this.props.read_only) {
            return this.renderReadOnly();
        }

        return (
            <Fragment>
                {this.state.loading ? (
                    <div className={"relative p-10"}>
                        <ComponentLoading />
                    </div>
                ) : (
                    <div>
                        {this.state.success_message !== null ? (
                            <div className="mb-4">
                                <SuccessAlert
                                    close_alert={this.closeSuccessAlert.bind(
                                        this,
                                    )}
                                >
                                    {this.state.success_message}
                                </SuccessAlert>
                            </div>
                        ) : null}

                        <div className="mb-4">
                            <InfoAlert>
                                Click The skill name for additional actions. The
                                timer will show below the tree when a skill is
                                in progress.
                            </InfoAlert>
                        </div>
                        {this.props.is_automation_running ? (
                            <div className="mb-4">
                                <WarningAlert>
                                    {this.automationName()} automation is
                                    running. Passive skill details are read-only
                                    until it is stopped.
                                </WarningAlert>
                            </div>
                        ) : null}

                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        {this.state.skill_in_training != null ? (
                            <div className="my-4">
                                <TimerProgressBar
                                    time_out_label={
                                        "Skill In Training: " +
                                        this.state.skill_in_training.name
                                    }
                                    time_remaining={this.getTimeLeftInSeconds()}
                                />
                            </div>
                        ) : null}
                        <KingdomPassiveTree
                            passives={this.state.kingdom_passives[0]}
                            manage_success_message={this.manageSuccessMessage.bind(
                                this,
                            )}
                            update_passives={this.updatePassives.bind(this)}
                            character_id={this.props.character_id}
                            is_dead={this.props.is_dead}
                            is_automation_running={
                                this.props.is_automation_running
                            }
                            active_automation={this.props.active_automation}
                            skill_in_training={this.state.skill_in_training}
                        />
                    </div>
                )}
            </Fragment>
        );
    }
}
