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

export default class Skills extends React.Component<SkillsProps, any> {
    constructor(props: SkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            show_train_skill: false,
            skill: null,
            stopping: false,
            success_message: null,
        };
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
                new Ajax()
                    .setRoute(
                        "skill/cancel-train/" +
                            this.props.character_id +
                            "/" +
                            row.id,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    stopping: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_skills(
                                        result.data.skills,
                                    );
                                },
                            );
                        },
                        (error: AxiosError) => {},
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

                <div
                    className={"max-w-[390px] md:max-w-full overflow-y-hidden"}
                >
                    <Table
                        columns={this.buildColumns()}
                        data={this.props.trainable_skills}
                        dark_table={this.props.dark_table}
                    />
                </div>

                {this.state.show_skill_details && this.state.skill !== null ? (
                    <SkillInformation
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
