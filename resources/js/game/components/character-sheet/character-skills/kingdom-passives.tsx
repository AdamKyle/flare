import React, { Fragment } from "react";
import { DateTime } from "luxon";
import ComponentLoading from "../../ui/loading/component-loading";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
import KingdomPassiveTree from "./skill-tree/kingdom-passive-tree";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import KingdomPassivesAjax from "./ajax/kingdom-passives-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import KingdomPassiveSkillsEventDefinition from "./event-listeners/kingdom-passive-skills-event-definition";
import KingdomPassiveSkillsEvent from "./event-listeners/kingdom-passive-skills-event";

export default class KingdomPassives extends React.Component<any, any> {
    private kingdomPassiveTreeAjax: KingdomPassivesAjax;

    private kingdomPassiveSkillEvent: KingdomPassiveSkillsEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            kingdom_passives: [],
            success_message: null,
            error_message: null,
            skill_in_training: null,
        };

        this.kingdomPassiveTreeAjax =
            serviceContainer().fetch(KingdomPassivesAjax);

        this.kingdomPassiveSkillEvent =
            serviceContainer().fetch<KingdomPassiveSkillsEventDefinition>(
                KingdomPassiveSkillsEvent,
            );

        this.kingdomPassiveSkillEvent.initialize(this, this.props.user_id);
        this.kingdomPassiveSkillEvent.register();
    }

    componentDidMount() {
        this.props.manage_inventory_visibility();

        this.kingdomPassiveTreeAjax.fetchPassiveTree(
            this,
            this.props.character_id,
        );

        this.kingdomPassiveSkillEvent.listen();
    }

    componentWillUnmount() {
        this.props.manage_inventory_visibility();
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

    updatePassives(passives: any, passiveInTraining: any) {
        this.setState({
            kingdom_passives: passives,
            skill_in_training: passiveInTraining,
        });
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

    render() {
        return (
            <Fragment>
                {this.state.loading ? (
                    <div className={"relative p-10"}>
                        <ComponentLoading />
                    </div>
                ) : (
                    <div className="max-w-[75%] mr-auto ml-auto">
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

                        {this.state.error_message !== null ? (
                            <div className="mb-4">
                                <DangerAlert>
                                    {this.state.error_message}
                                </DangerAlert>
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
                                    Automation is running. You cannot manage
                                    your passive skills.
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
                        />
                    </div>
                )}
            </Fragment>
        );
    }
}
