import React, { Fragment } from "react";
import Dialogue from "../../../../ui/dialogue/dialogue";
import LoadingProgressBar from "../../../../ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../ui/alerts/simple-alerts/danger-alert";
import { serviceContainer } from "../../../../../lib/containers/core-container";
import KingdomPassivesAjax from "../../ajax/kingdom-passives-ajax";

export default class TrainPassive extends React.Component<any, any> {
    private kingdomPassiveAjax: KingdomPassivesAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: "",
        };

        this.kingdomPassiveAjax = serviceContainer().fetch(KingdomPassivesAjax);
    }

    trainSkill() {
        this.setState(
            {
                loading: true,
            },
            () => {
                this.kingdomPassiveAjax.trainPassiveSkill(
                    this,
                    this.props.character_id,
                    this.props.skill.id,
                );
            },
        );
    }

    cancelTrainingSkill() {
        this.setState(
            {
                loading: true,
            },
            () => {
                this.kingdomPassiveAjax.stopTrainingPassiveSkill(
                    this,
                    this.props.character_id,
                    this.props.skill.id,
                );
            },
        );
    }

    isMaxed() {
        return this.props.skill.current_level === this.props.skill.max_level;
    }

    isTraining() {
        return this.props.skill.started_at !== null;
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.props.skill.name}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_disabled:
                        this.isMaxed() ||
                        this.props.skill.is_locked ||
                        this.state.loading ||
                        this.state.is_dead,
                    secondary_button_label: this.isTraining()
                        ? "Stop Training"
                        : "Train",
                    handle_action: this.isTraining()
                        ? this.cancelTrainingSkill.bind(this)
                        : this.trainSkill.bind(this),
                }}
            >
                <p className="mt-4 mb-4">
                    {this.props.skill.passive_skill.description}
                </p>

                {this.props.is_dead ? (
                    <p className="mb-4 text-red-700 dark:text-red-500">
                        No no child! You dead! You ain't training nothing, till
                        you head to the Game tab and click revive.
                    </p>
                ) : null}

                {this.props.skill.is_locked &&
                this.props.skill.passive_skill.unlocks_at_level !== null ? (
                    <p className="mb-4 text-orange-700 dark:text-orange-400">
                        This skill requires it's parent to be trained to be able
                        to train this skill. This skill will unlock at (parent)
                        level: {this.props.skill.passive_skill.unlocks_at_level}
                    </p>
                ) : null}

                {this.props.skill.quest_name !== null &&
                !this.props.skill.is_quest_complete ? (
                    <p className="mb-4 text-orange-700 dark:text-orange-400">
                        You must complete the quest: "
                        {this.props.skill.quest_name}" before you can unlock
                        this passive.
                    </p>
                ) : null}

                {this.isMaxed() ? (
                    <p className="mb-4 text-green-600 dark:text-green-500">
                        This skill has been maxed out and cannot be trained any
                        higher.
                    </p>
                ) : (
                    <Fragment>
                        <dl>
                            <dt>Level</dt>
                            <dd>
                                {this.props.skill.current_level} /{" "}
                                {this.props.skill.max_level}
                            </dd>
                            <dt>Hours till next level:</dt>
                            <dd>{this.props.skill.hours_to_next}</dd>
                        </dl>

                        <p className="mt-4 mb-4">
                            <strong>Caution:</strong> Canceling this skill
                            before it is done training, will result in you
                            having to start the progress all over again.{" "}
                            <strong>
                                We do not take into account, time elapsed when
                                canceling
                            </strong>
                            .
                        </p>
                    </Fragment>
                )}

                {this.state.loading ? <LoadingProgressBar /> : null}

                {this.state.error_message !== "" ? (
                    <DangerAlert additional_css={"mt-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
            </Dialogue>
        );
    }
}
