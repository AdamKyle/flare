import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import SkillHelpModal from "./skill-help-modal";

export default class TrainSkill extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            selected_value: 0.0,
            error_message: null,
            loading: false,
            show_help: false,
        };
    }

    setSkillToTrain(data: any) {
        this.setState({
            selected_value: data.value,
            error_message: null,
        });
    }

    trainSkill() {
        if (this.state.selected_value === 0.0) {
            return this.setState({
                error_message: "You must select a % of XP tp sacrifice.",
            });
        }

        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute("skill/train/" + this.props.character_id)
                    .setParameters({
                        skill_id: this.props.skill.id,
                        xp_percentage: this.state.selected_value,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                },
                                () => {
                                    this.props.set_success_message(
                                        result.data.message,
                                    );
                                    this.props.update_skills(
                                        result.data.skills,
                                    );
                                    this.props.manage_modal();
                                },
                            );
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    buildItems() {
        return [
            {
                label: "10%",
                value: 0.1,
            },
            {
                label: "20%",
                value: 0.2,
            },
            {
                label: "30%",
                value: 0.3,
            },
            {
                label: "40%",
                value: 0.4,
            },
            {
                label: "50%",
                value: 0.5,
            },
            {
                label: "60%",
                value: 0.6,
            },
            {
                label: "70%",
                value: 0.7,
            },
            {
                label: "80%",
                value: 0.8,
            },
            {
                label: "90%",
                value: 0.9,
            },
            {
                label: "100%",
                value: 1.0,
            },
        ];
    }

    defaultItem() {
        if (this.state.selected_value === 0.0) {
            return {
                label: "Please Select",
                value: 0.0,
            };
        }

        return {
            label: this.state.selected_value * 100 + "%",
            value: this.state.selected_value,
        };
    }

    manageHelpDialogue() {
        this.setState({
            show_help: !this.state.show_help,
        });
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.props.skill.name}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_label: "Train Skill",
                    secondary_button_disabled:
                        this.state.loading || this.state.error_message !== null,
                    handle_action: this.trainSkill.bind(this),
                }}
            >
                {this.props.skill.is_locked ? (
                    <DangerAlert additional_css={"mb-4 mt-4"}>
                        This skill is locked. You will need to complete a quest
                        to unlock it.
                    </DangerAlert>
                ) : null}

                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"mb-4 mt-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                <p className="mb-4">{this.props.skill.description}</p>

                <dl>
                    <dt>Current Level</dt>
                    <dd>{this.props.skill.level}</dd>
                    <dt>Max Level</dt>
                    <dd>{this.props.skill.max_level}</dd>
                    <dt className="flex items-center">
                        <span>XP Towards</span>
                        <div>
                            <div className="ml-2">
                                <button
                                    type={"button"}
                                    onClick={() => this.manageHelpDialogue()}
                                    className="text-blue-500 dark:text-blue-300"
                                >
                                    <i className={"fas fa-info-circle"}></i>{" "}
                                    Help
                                </button>
                            </div>
                        </div>
                    </dt>
                    <dd>
                        <Select
                            onChange={this.setSkillToTrain.bind(this)}
                            options={this.buildItems()}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={this.defaultItem()}
                        />
                    </dd>
                    <dt>XP</dt>
                    <dd>
                        {formatNumber(this.props.skill.xp)} /{" "}
                        {formatNumber(this.props.skill.xp_max)}
                    </dd>
                </dl>

                {this.state.loading ? <LoadingProgressBar /> : null}

                {this.state.show_help ? (
                    <SkillHelpModal
                        manage_modal={this.manageHelpDialogue.bind(this)}
                    />
                ) : null}
            </Dialogue>
        );
    }
}
