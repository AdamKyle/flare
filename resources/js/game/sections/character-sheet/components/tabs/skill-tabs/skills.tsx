import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import SkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/skills-props";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import SkillInformation from "../../modals/skills/skill-information";
import { formatNumber } from "../../../../../lib/game/format-number";
import TrainSkill from "../../modals/skills/train-skill";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";

interface SkillsState {
    show_skill_details: boolean;
    show_train_skill: boolean;
    skill: SkillType | null;
    stopping: boolean;
    success_message: string | null;
}

export default class Skills extends React.Component<SkillsProps, SkillsState> {
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

    manageTrainSkill(row?: SkillType) {
        this.setState({
            show_train_skill: !this.state.show_train_skill,
            skill: row || null,
        });
    }

    stopTraining(row: SkillType) {
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

    manageSkillDetails(row?: SkillType) {
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

    buildColumns(read_only: boolean = false) {
        const columns = [
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
        ];

        if (read_only) {
            return columns;
        }

        columns.push({
            name: "Actions",
            selector: (row: SkillType) => row.id,
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
        });

        return columns;
    }

    renderMobileTrainableSkills(): JSX.Element {
        const skills = this.props.trainable_skills.map(
            (trainable_skill: SkillType, index: number) => {
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

    renderMobileSkillsReadOnly(skills: SkillType[]): JSX.Element {
        const rows = skills.map((skill: SkillType, index: number) => {
            return (
                <div key={skill.id}>
                    <div className="p-4">
                        <div className="flex justify-between items-center mb-2">
                            <span className="font-semibold w-24">Name:</span>
                            <span>
                                <button
                                    className={clsx("underline", {
                                        "text-orange-600 dark:text-orange-300":
                                            skill.is_class_skill,
                                    })}
                                    onClick={() =>
                                        this.manageSkillDetails(skill)
                                    }
                                >
                                    <i
                                        className={clsx({
                                            "ra ra-player-pyromaniac":
                                                skill.is_class_skill,
                                        })}
                                    ></i>{" "}
                                    {skill.name}
                                </button>
                            </span>
                        </div>

                        <div className="flex justify-between items-center mb-2">
                            <span className="font-semibold w-24">Level:</span>
                            <span>
                                {skill.level}/{skill.max_level}
                            </span>
                        </div>

                        <div className="flex justify-between items-center">
                            <span className="font-semibold w-24">XP:</span>
                            <span>
                                {formatNumber(skill.xp)}/
                                {formatNumber(skill.xp_max)}
                            </span>
                        </div>
                    </div>
                    {index < skills.length - 1 && (
                        <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                    )}
                </div>
            );
        });

        return <div className="space-y-4">{rows}</div>;
    }

    renderReadOnly(): JSX.Element {
        const nonClassSkills = this.props.trainable_skills.filter(
            (skill: SkillType) => !skill.is_class_skill,
        );
        const classSkills = this.props.trainable_skills.filter(
            (skill: SkillType) => skill.is_class_skill,
        );

        return (
            <Fragment>
                <div className={"max-w-full"}>
                    <div>
                        <div className={"hidden md:block"}>
                            <Table
                                columns={this.buildColumns(true)}
                                data={nonClassSkills}
                                dark_table={this.props.dark_table}
                            />
                        </div>
                        <div className={"block md:hidden"}>
                            {this.renderMobileSkillsReadOnly(nonClassSkills)}
                        </div>
                    </div>
                </div>

                {classSkills.length > 0 ? (
                    <div className="mt-6">
                        <h3 className="text-lg font-semibold">Class Skills</h3>
                        <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                            Class skills are learned by switching into and using
                            different classes. These bonuses apply while you are
                            using the related class and help define how that
                            class behaves and scales.{" "}
                            <a
                                href="/information/class-skills"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="my-2"
                            >
                                Learn more{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                        <div className="mt-4">
                            <div className={"hidden md:block"}>
                                <Table
                                    columns={this.buildColumns(true)}
                                    data={classSkills}
                                    dark_table={this.props.dark_table}
                                />
                            </div>
                            <div className={"block md:hidden"}>
                                {this.renderMobileSkillsReadOnly(classSkills)}
                            </div>
                        </div>
                    </div>
                ) : null}

                {this.state.show_skill_details && this.state.skill !== null ? (
                    <SkillInformation
                        is_trainable={true}
                        skill={this.state.skill}
                        manage_modal={this.manageSkillDetails.bind(this)}
                        is_open={this.state.show_skill_details}
                        preloaded_skill_details={this.state.skill.details}
                    />
                ) : null}
            </Fragment>
        );
    }

    render() {
        if (this.props.read_only) {
            return this.renderReadOnly();
        }

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
                        preloaded_skill_details={undefined}
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
