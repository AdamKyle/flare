import React, { Fragment } from "react";
import { AxiosError, AxiosResponse } from "axios";
import clsx from "clsx";
import SkillsProps from "./types/tables/skills-props";
import Ajax from "../../../lib/ajax/ajax";
import SkillType from "./deffinitions/skill-type";
import { formatNumber } from "../../../lib/game/format-number";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import Table from "../../ui/data-tables/table";
import SkillInformation from "./modals/skill-information";
import TrainSkill from "./modals/train-skill";
import CharacterSkillsAjax from "./ajax/character-skills-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";

export default class Skills extends React.Component<SkillsProps, any> {
    private characterSkillsAjax: CharacterSkillsAjax;

    constructor(props: SkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            show_train_skill: false,
            skill: null,
            stopping: false,
            success_message: null,
        };

        this.characterSkillsAjax =
            serviceContainer().fetch(CharacterSkillsAjax);
    }

    manageTrainSkill(row: any) {
        this.setState({
            show_train_skill: !this.state.show_train_skill,
            skill: row || null,
        });
    }

    stopTraining(row: any) {
        this.setState(
            {
                stopping: true,
            },
            () => {
                this.characterSkillsAjax.stopTrainingSkill(
                    this,
                    this.props.character_id,
                    row.id,
                );
            },
        );
    }

    manageSkillDetails(row?: any) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: row || null,
        });
    }

    setSuccessMessage(message: string) {
        this.setState({
            success_message: message,
        });
    }

    isAnySkillTraining() {
        return (
            this.props.trainable_skills.filter((skill) => skill.is_training)
                .length > 0
        );
    }

    buildColumns() {
        return [
            {
                name: "Name",
                selector: (row: { name: string }) => row.name,
                sortable: true,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                        className="m-auto"
                    >
                        <button
                            onClick={() => this.manageSkillDetails(row)}
                            className={clsx("underline", {
                                "text-orange-600 dark:text-orange-300":
                                    row.is_class_skill,
                            })}
                        >
                            <i
                                className={clsx({
                                    "ra ra-player-pyromaniac":
                                        row.is_class_skill,
                                })}
                            ></i>{" "}
                            {row.name}
                        </button>
                    </span>
                ),
            },
            {
                name: "Level",
                selector: (row: { level: number }) => row.level,
                sortable: true,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.level}/{row.max_level}
                    </span>
                ),
            },
            {
                name: "XP",
                selector: (row: { xp: number }) => row.xp,
                sortable: true,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {formatNumber(row.xp)}/{formatNumber(row.xp_max)}
                    </span>
                ),
            },
            {
                name: "Training?",
                selector: (row: { is_training: boolean }) =>
                    row.is_training ? "Yes" : "No",
                sortable: true,
            },
            {
                name: "Actions",
                selector: (row: any) => "",
                sortable: false,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.is_training ? (
                            <DangerButton
                                button_label={
                                    this.state.stopping ? (
                                        <span>
                                            Stopping{" "}
                                            <i className="fas fa-spinner fa-pulse"></i>
                                        </span>
                                    ) : (
                                        "Stop training"
                                    )
                                }
                                on_click={() => this.stopTraining(row)}
                                disabled={
                                    this.props.is_dead ||
                                    this.state.stopping ||
                                    this.props.is_automation_running
                                }
                            />
                        ) : (
                            <PrimaryButton
                                button_label={"Train"}
                                on_click={() => this.manageTrainSkill(row)}
                                disabled={
                                    this.props.is_dead ||
                                    this.isAnySkillTraining() ||
                                    this.props.is_automation_running
                                }
                            />
                        )}
                    </span>
                ),
            },
        ];
    }

    renderMobileTrainableSkills(): JSX.Element {
        const skills = this.props.trainable_skills.map(
            (trainable_skill: any, index: number) => {
                return (
                    <div key={trainable_skill.id}>
                        <div className="p-4">
                            <div className="flex justify-between items-center mb-2">
                                <span className="font-semibold w-24">
                                    Name:
                                </span>
                                <span>
                                    <button
                                        className="underline text-orange-600 dark:text-orange-300 cursor-pointer"
                                        onClick={() =>
                                            this.manageSkillDetails(
                                                trainable_skill,
                                            )
                                        }
                                    >
                                        <i className="ra ra-player-pyromaniac"></i>{" "}
                                        {trainable_skill.name}
                                    </button>
                                </span>
                            </div>

                            <div className="flex justify-between items-center mb-2">
                                <span className="font-semibold w-24">
                                    Level:
                                </span>
                                <span>
                                    {trainable_skill.level}/
                                    {trainable_skill.max_level}
                                </span>
                            </div>

                            <div className="flex justify-between items-center mb-2">
                                <span className="font-semibold w-24">XP:</span>
                                <span>
                                    {trainable_skill.xp}/
                                    {trainable_skill.xp_max}
                                </span>
                            </div>

                            <div className="flex justify-between items-center mb-2">
                                <span className="font-semibold w-24">
                                    Training?
                                </span>
                                <span>
                                    {trainable_skill.is_training ? "Yes" : "No"}
                                </span>
                            </div>

                            <div className="flex justify-between items-center">
                                <span className="font-semibold w-24">
                                    Actions:
                                </span>
                                <span>
                                    {trainable_skill.is_training ? (
                                        <DangerButton
                                            button_label={
                                                this.state.stopping ? (
                                                    <span>
                                                        Stopping{" "}
                                                        <i className="fas fa-spinner fa-pulse"></i>
                                                    </span>
                                                ) : (
                                                    "Stop training"
                                                )
                                            }
                                            on_click={() =>
                                                this.stopTraining(
                                                    trainable_skill,
                                                )
                                            }
                                            disabled={
                                                this.props.is_dead ||
                                                this.state.stopping ||
                                                this.props.is_automation_running
                                            }
                                        />
                                    ) : (
                                        <PrimaryButton
                                            button_label={"Train"}
                                            on_click={() =>
                                                this.manageTrainSkill(
                                                    trainable_skill,
                                                )
                                            }
                                            disabled={
                                                this.props.is_dead ||
                                                this.isAnySkillTraining() ||
                                                this.props.is_automation_running
                                            }
                                        />
                                    )}
                                </span>
                            </div>
                        </div>
                        {index < this.props.trainable_skills.length - 1 && (
                            <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                        )}
                    </div>
                );
            },
        );

        return <div className="space-y-4">{skills}</div>;
    }

    render() {
        return (
            <Fragment>
                {this.props.is_automation_running ? (
                    <div className="mb-4">
                        <WarningAlert>
                            Automation is running. You cannot train or stop
                            training skills.
                        </WarningAlert>
                    </div>
                ) : null}

                <div className="mb-4">
                    <InfoAlert>
                        This section will not update in real time.
                    </InfoAlert>
                </div>

                <div className={"max-w-full"}>
                    <div>
                        <div className={"hidden md:block"}>
                            <Table
                                columns={this.buildColumns()}
                                data={this.props.trainable_skills}
                                dark_table={this.props.dark_table}
                            />
                        </div>
                        <div className={"block md:hidden"}>
                            {this.renderMobileTrainableSkills()}
                        </div>
                    </div>
                </div>

                {this.state.show_skill_details && this.state.skill !== null ? (
                    <SkillInformation
                        is_trainable={true}
                        skill={this.state.skill}
                        manage_modal={this.manageSkillDetails.bind(this)}
                        is_open={this.state.show_skill_details}
                    />
                ) : null}

                {this.state.show_train_skill && this.state.skill !== null ? (
                    <TrainSkill
                        is_open={this.state.show_train_skill}
                        manage_modal={this.manageTrainSkill.bind(this)}
                        skill={this.state.skill}
                        set_success_message={this.setSuccessMessage.bind(this)}
                        update_skills={this.props.update_skills}
                        character_id={this.props.character_id}
                    />
                ) : null}
            </Fragment>
        );
    }
}
