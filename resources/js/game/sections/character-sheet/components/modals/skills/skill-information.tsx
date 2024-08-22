import React, { Fragment } from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";
import clsx from "clsx";
import { upperFirst } from "lodash";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import SkillHelpModal from "./skill-help-modal";
import SkillBonusBreakDown from "./skill-bonus-break-down";

export default class SkillInformation extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            skill_data: {},
            show_help: false,
            bonus_type: null,
        };
    }

    componentDidMount() {
        new Ajax()
            .setRoute(
                "character/skill/" +
                    this.props.skill.character_id +
                    "/" +
                    this.props.skill.id,
            )
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        skill_data: response.data,
                    });
                },
                (error: AxiosError) => {},
            );
    }

    manageHelpDialogue(bonusType: string | null) {
        this.setState({
            show_help: !this.state.show_help,
            bonus_type: bonusType,
        });
    }

    getFilteredFields() {
        const validFields = [
            "unit_time_reduction",
            "building_time_reduction",
            "unit_movement_time_reduction",
            "base_damage_mod",
            "base_healing_mod",
            "base_ac_mod",
            "fight_timeout_mod",
            "move_timeout_mod",
            "class_bonus",
        ];

        return validFields.filter((field: string) => {
            return this.state.skill_data[field] > 0.0;
        });
    }

    iSkillDetailsEmpty() {
        return !(this.getFilteredFields().length > 0);
    }

    renderDetails() {
        return this.getFilteredFields().map((attributeName: string) => {
            return (
                <Fragment>
                    <dt>{upperFirst(attributeName.replaceAll("_", " "))}</dt>
                    <dd>
                        {(this.state.skill_data[attributeName] * 100).toFixed(
                            2,
                        )}
                        %
                    </dd>
                </Fragment>
            );
        });
    }

    renderSkillDetails() {
        return <dl>{this.renderDetails()}</dl>;
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.state.skill_data.name}
            >
                {this.state.loading ? (
                    <div className="p-4 m-4">
                        <ComponentLoading />
                    </div>
                ) : (
                    <Fragment>
                        {this.state.skill_data.is_locked ? (
                            <DangerAlert additional_css={"mb-4 mt-4"}>
                                This skill is locked. You will need to complete
                                a quest to unlock it.
                            </DangerAlert>
                        ) : null}

                        <p className="mb-4">
                            {this.state.skill_data.description}
                        </p>

                        <div
                            className={clsx({
                                "grid gap-2 md:grid-cols-2 md:gap-4":
                                    !this.iSkillDetailsEmpty(),
                            })}
                        >
                            <div>
                                <dl>
                                    <dt>Current Level</dt>
                                    <dd>{this.state.skill_data.level}</dd>
                                    <dt>Max Level</dt>
                                    <dd>{this.state.skill_data.max_level}</dd>
                                    {this.props.is_trainable ? (
                                        <>
                                            <dt>XP Towards</dt>
                                            <dd>
                                                {this.state.skill_data
                                                    .xp_towards !== null
                                                    ? (
                                                          this.state.skill_data
                                                              .xp_towards * 100
                                                      ).toFixed(2)
                                                    : 0.0}
                                                %
                                            </dd>
                                        </>
                                    ) : null}

                                    <dt>
                                        Skill Bonus{" "}
                                        <button
                                            type={"button"}
                                            onClick={() =>
                                                this.manageHelpDialogue("skill")
                                            }
                                            className="text-blue-500 dark:text-blue-300"
                                        >
                                            <i
                                                className={"fas fa-info-circle"}
                                            ></i>{" "}
                                            Help
                                        </button>
                                    </dt>
                                    <dd>
                                        {(
                                            this.state.skill_data.skill_bonus *
                                            100
                                        ).toFixed(2)}
                                        %
                                    </dd>
                                    <dt>
                                        Skill XP Bonus{" "}
                                        <button
                                            type={"button"}
                                            onClick={() =>
                                                this.manageHelpDialogue("xp")
                                            }
                                            className="text-blue-500 dark:text-blue-300"
                                        >
                                            <i
                                                className={"fas fa-info-circle"}
                                            ></i>{" "}
                                            Help
                                        </button>
                                    </dt>
                                    <dd>
                                        {(
                                            this.state.skill_data
                                                .skill_xp_bonus * 100
                                        ).toFixed(2)}{" "}
                                        %
                                    </dd>
                                    <dt>XP</dt>
                                    <dd>
                                        {formatNumber(this.state.skill_data.xp)}{" "}
                                        /{" "}
                                        {formatNumber(
                                            this.state.skill_data.xp_max,
                                        )}
                                    </dd>
                                </dl>
                            </div>
                            {!this.iSkillDetailsEmpty() ? (
                                <div>{this.renderSkillDetails()}</div>
                            ) : null}
                        </div>

                        {this.state.show_help ? (
                            <SkillBonusBreakDown
                                manage_modal={this.manageHelpDialogue.bind(
                                    this,
                                )}
                                title={
                                    this.state.bonus_type === "xp"
                                        ? "Skill Xp Bonus Breakdown"
                                        : "Skill Bonus Breakdown"
                                }
                                bonus_type={this.state.bonus_type}
                                items={
                                    this.state.bonus_type === "xp"
                                        ? this.state.skill_data
                                              .skill_xp_bonus_break_down
                                        : this.state.skill_data
                                              .skill_bonus_break_down
                                }
                            />
                        ) : null}
                    </Fragment>
                )}
            </Dialogue>
        );
    }
}
