import React, { Fragment } from "react";
import CraftingSkillsProps from "./types/tables/crafting-skills-props";
import SkillType from "./deffinitions/skill-type";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import SkillInformation from "./modals/skill-information";


export default class CraftingSkills extends React.Component<
    CraftingSkillsProps,
    any
> {
    constructor(props: CraftingSkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            skill: null,
        };
    }

    manageSkillDetails(row?: any) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: typeof row !== "undefined" ? row : null,
        });
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
                            className="underline"
                        >
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
                        {row.xp}/{row.xp_max}
                    </span>
                ),
            },
        ];
    }

    renderCraftingSkills(): JSX.Element {
        const skills = this.props.crafting_skills.map(
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
                                        <i className="ra  ra-flat-hammer"></i>{" "}
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
                        </div>
                        {index < this.props.crafting_skills.length - 1 && (
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
                <div className="mb-4">
                    <InfoAlert>
                        This section will not update in real time.
                    </InfoAlert>
                </div>

                <div className={"max-w-full"}>
                    {this.renderCraftingSkills()}
                </div>

                {this.state.show_skill_details && this.state.skill !== null ? (
                    <SkillInformation
                        is_trainable={false}
                        skill={this.state.skill}
                        manage_modal={this.manageSkillDetails.bind(this)}
                        is_open={this.state.show_skill_details}
                    />
                ) : null}
            </Fragment>
        );
    }
}
